<?php
/**
 * Logout Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

// Unset all session variables
$_SESSION = [];

// Delete session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Clear remember me cookie if set
if (isset($_COOKIE['aura_remember'])) {
    setcookie('aura_remember', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Start fresh session to pass the logout notification
session_start();
session_regenerate_id(true);
set_flash('info', 'You have been successfully logged out. See you again soon!');

header('Location: index.php?tab=login');
exit;
