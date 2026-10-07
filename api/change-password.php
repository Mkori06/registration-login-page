<?php
/**
 * REST API: Change Password
 * POST /api/change-password.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// Authentication guard
$auth_user = require_api_auth();
$user_id = (int)$auth_user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    json_response(405, [
        'status'  => 'error',
        'code'    => 405,
        'message' => 'Method Not Allowed. Use POST or PUT.'
    ]);
}

$input = get_request_payload();

$current_password = (string)($input['current_password'] ?? '');
$new_password     = (string)($input['new_password'] ?? '');
$confirm_password = (string)($input['confirm_password'] ?? '');

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    json_response(422, [
        'status'  => 'error',
        'code'    => 422,
        'message' => 'Please provide current_password, new_password, and confirm_password.'
    ]);
}

if (strlen($new_password) < 8) {
    json_response(422, [
        'status'  => 'error',
        'code'    => 422,
        'message' => 'The new password must be at least 8 characters long.'
    ]);
}

if ($new_password !== $confirm_password) {
    json_response(422, [
        'status'  => 'error',
        'code'    => 422,
        'message' => 'New password and confirmation do not match.'
    ]);
}

try {
    // Verify current password
    $pwd_stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $pwd_stmt->execute(['id' => $user_id]);
    $current_hash = $pwd_stmt->fetchColumn();

    if (!$current_hash || !password_verify($current_password, $current_hash)) {
        json_response(400, [
            'status'  => 'error',
            'code'    => 400,
            'message' => 'Incorrect current password. Please try again.'
        ]);
    }

    // Hash and update
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    $update_stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
    $update_stmt->execute([
        'password' => $new_hash,
        'id'       => $user_id
    ]);

    json_response(200, [
        'status'  => 'success',
        'code'    => 200,
        'message' => 'Password updated successfully.'
    ]);

} catch (PDOException $e) {
    error_log('API Password Change Error: ' . $e->getMessage());
    json_response(500, [
        'status'  => 'error',
        'code'    => 500,
        'message' => 'Failed to update password.'
    ]);
}
