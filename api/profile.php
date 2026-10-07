<?php
/**
 * REST API: User Profile Management
 * GET  /api/profile.php - Get authenticated profile
 * POST /api/profile.php - Update profile details
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// Authentication guard
$auth_user = require_api_auth();
$user_id = (int)$auth_user['id'];

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Retrieve Profile
if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare('
            SELECT id, full_name, username, email, role, bio, last_login, created_at, updated_at 
            FROM users 
            WHERE id = :id 
            LIMIT 1
        ');
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            json_response(404, [
                'status'  => 'error',
                'code'    => 404,
                'message' => 'User profile not found.'
            ]);
        }

        json_response(200, [
            'status' => 'success',
            'code'   => 200,
            'user'   => $user
        ]);

    } catch (PDOException $e) {
        error_log('API Profile Fetch Error: ' . $e->getMessage());
        json_response(500, [
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to retrieve profile.'
        ]);
    }
}

// Handle POST or PUT: Update Profile
if ($method === 'POST' || $method === 'PUT') {
    $input = get_request_payload();

    $full_name = trim((string)($input['full_name'] ?? ''));
    $email     = trim((string)($input['email'] ?? ''));
    $bio       = trim((string)($input['bio'] ?? ''));

    $errors = [];

    if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 100) {
        $errors['full_name'] = 'Full Name must be between 2 and 100 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $errors['email'] = 'Please provide a valid email address.';
    }

    if (!empty($errors)) {
        json_response(422, [
            'status'  => 'error',
            'code'    => 422,
            'message' => 'Validation failed.',
            'errors'  => $errors
        ]);
    }

    try {
        // Check if email belongs to someone else
        $check_stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $check_stmt->execute(['email' => $email, 'id' => $user_id]);

        if ($check_stmt->fetch()) {
            json_response(409, [
                'status'  => 'error',
                'code'    => 409,
                'message' => 'This email address is already in use by another account.'
            ]);
        }

        // Update record
        $update_stmt = $pdo->prepare('
            UPDATE users 
            SET full_name = :full_name, email = :email, bio = :bio 
            WHERE id = :id
        ');
        $update_stmt->execute([
            'full_name' => $full_name,
            'email'     => $email,
            'bio'       => $bio,
            'id'        => $user_id
        ]);

        // Sync session cache
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email']     = $email;
        $_SESSION['bio']       = $bio;

        json_response(200, [
            'status'  => 'success',
            'code'    => 200,
            'message' => 'Profile updated successfully.',
            'user'    => [
                'id'        => $user_id,
                'full_name' => $full_name,
                'username'  => $auth_user['username'],
                'email'     => $email,
                'bio'       => $bio
            ]
        ]);

    } catch (PDOException $e) {
        error_log('API Profile Update Error: ' . $e->getMessage());
        json_response(500, [
            'status'  => 'error',
            'code'    => 500,
            'message' => 'Failed to update profile.'
        ]);
    }
}

json_response(405, [
    'status'  => 'error',
    'code'    => 405,
    'message' => 'Method Not Allowed. Use GET or POST/PUT.'
]);
