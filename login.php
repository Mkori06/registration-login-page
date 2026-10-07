<?php
/**
 * Login Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Redirect authenticated users away
require_guest();

// If accessed directly via GET, redirect to login tab
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=login');
    exit;
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid security token (CSRF). Please try signing in again.');
    header('Location: index.php?tab=login');
    exit;
}

// 2. Retrieve & Sanitize Inputs
$login_id = trim($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);

if (empty($login_id) || empty($password)) {
    set_flash('error', 'Please enter your username/email and password.');
    header('Location: index.php?tab=login');
    exit;
}

try {
    // 3. Query user by username OR email
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

    // 4. Verify password
    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation attack
        session_regenerate_id(true);

        // Update last login timestamp
        $update_login = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $update_login->execute(['id' => $user['id']]);

        // Set session variables
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['bio']       = $user['bio'];

        // If remember me is checked, set a secure cookie if needed
        if ($remember) {
            $remember_payload = base64_encode($user['id'] . ':' . hash('sha256', $user['password']));
            setcookie('aura_remember', $remember_payload, [
                'expires'  => time() + (86400 * 30), // 30 days
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
        header('Location: dashboard.php');
        exit;
    } else {
        // Generic failure message to prevent username enumeration
        set_flash('error', 'Invalid login credentials. Please check your username/email and password.');
        header('Location: index.php?tab=login');
        exit;
    }

} catch (PDOException $e) {
    error_log('Login Error: ' . $e->getMessage());
    set_flash('error', 'A system error occurred while attempting to sign in. Please try again.');
    header('Location: index.php?tab=login');
    exit;
}
