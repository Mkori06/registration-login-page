<?php
/**
 * REST API: User Registration
 * POST /api/register.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, [
        'status'  => 'error',
        'code'    => 405,
        'message' => 'Method Not Allowed. Use POST for registration.'
    ]);
}

$input = get_request_payload();

$full_name        = trim((string)($input['full_name'] ?? ''));
$username         = trim((string)($input['username'] ?? ''));
$email            = trim((string)($input['email'] ?? ''));
$password         = (string)($input['password'] ?? '');
$confirm_password = (string)($input['confirm_password'] ?? '');

// Validation
$errors = [];

if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 100) {
    $errors['full_name'] = 'Full Name must be between 2 and 100 characters.';
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    $errors['username'] = 'Username must be 3-30 characters long and contain only letters, numbers, and underscores.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    $errors['email'] = 'Please provide a valid email address (max 100 chars).';
}

if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters long.';
}

if ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match.';
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
    // Check for duplicate username or email
    $check_stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE username = :username OR email = :email LIMIT 1');
    $check_stmt->execute([
        'username' => $username,
        'email'    => $email,
    ]);
    $existing = $check_stmt->fetch();

    if ($existing) {
        $field = strcasecmp($existing['username'], $username) === 0 ? 'username' : 'email';
        json_response(409, [
            'status'  => 'error',
            'code'    => 409,
            'message' => "The {$field} is already taken.",
            'field'   => $field
        ]);
    }

    // Securely hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $insert_stmt = $pdo->prepare('
        INSERT INTO users (full_name, username, email, password)
        VALUES (:full_name, :username, :email, :password)
    ');

    $insert_stmt->execute([
        'full_name' => $full_name,
        'username'  => $username,
        'email'     => $email,
        'password'  => $hashed_password,
    ]);

    $new_id = (int)$pdo->lastInsertId();

    json_response(201, [
        'status'  => 'success',
        'code'    => 201,
        'message' => 'Account registered successfully.',
        'data'    => [
            'id'        => $new_id,
            'full_name' => $full_name,
            'username'  => $username,
            'email'     => $email,
            'role'      => 'user',
        ]
    ]);

} catch (PDOException $e) {
    error_log('API Register Error: ' . $e->getMessage());
    json_response(500, [
        'status'  => 'error',
        'code'    => 500,
        'message' => 'Database error occurred during registration.'
    ]);
}
