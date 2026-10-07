<?php
/**
 * REST API: User Logout
 * POST /api/logout.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

// Unset session variables
$_SESSION = [];

// Clear session cookie
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

// Clear remember me cookie if present
if (isset($_COOKIE['aura_remember'])) {
    setcookie('aura_remember', '', time() - 3600, '/');
}

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

json_response(200, [
    'status'  => 'success',
    'code'    => 200,
    'message' => 'Logged out successfully.'
]);
