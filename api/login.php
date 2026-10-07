<?php
/**
 * REST API: User Login / Authentication
 * POST /api/login.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, [
        'status'  => 'error',
        'code'    => 405,
        'message' => 'Method Not Allowed. Use POST for login.'
    ]);
}

$input = get_request_payload();

$login_id = trim((string)($input['login_id'] ?? ''));
$password = (string)($input['password'] ?? '');
$remember = !empty($input['remember']);

if (empty($login_id) || empty($password)) {
    json_response(422, [
        'status'  => 'error',
        'code'    => 422,
        'message' => 'Please provide both login_id (username or email) and password.'
    ]);
}

try {
    $stmt = $pdo->prepare('
        SELECT id, full_name, username, email, password, role, bio 
        FROM users 
        WHERE username = :u_id OR email = :e_id 
        LIMIT 1
    ');
    $stmt->execute([
        'u_id' => $login_id,
        'e_id' => $login_id,
    ]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation
        session_regenerate_id(true);

        // Update last login
        $update_login = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $update_login->execute(['id' => $user['id']]);

        // Save session state
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['email']     = $email = $user['email'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['bio']       = $user['bio'];

        if ($remember) {
            $remember_payload = base64_encode($user['id'] . ':' . hash('sha256', $user['password']));
            setcookie('aura_remember', $remember_payload, [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        json_response(200, [
            'status'     => 'success',
            'code'       => 200,
            'message'    => 'Login successful.',
            'session_id' => session_id(),
            'user'       => [
                'id'        => (int)$user['id'],
                'full_name' => $user['full_name'],
                'username'  => $user['username'],
                'email'     => $user['email'],
                'role'      => $user['role'],
                'bio'       => $user['bio']
            ]
        ]);
    } else {
        json_response(401, [
            'status'  => 'error',
            'code'    => 401,
            'message' => 'Invalid login credentials. Please check your username/email and password.'
        ]);
    }

} catch (PDOException $e) {
    error_log('API Login Error: ' . $e->getMessage());
    json_response(500, [
        'status'  => 'error',
        'code'    => 500,
        'message' => 'Database error occurred during login.'
    ]);
}
