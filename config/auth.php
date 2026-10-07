<?php
/**
 * Authentication and Security Helper Functions
 */

declare(strict_types=1);

// Configure secure session settings before starting session
if (session_status() === PHP_SESSION_NONE) {
    // Support Session Token in Headers for API / Postman testing
    $header_token = $_SERVER['HTTP_X_SESSION_ID'] ?? null;
    if (!$header_token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+([a-zA-Z0-9,-]{16,64})/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $header_token = $matches[1];
        }
    }
    if ($header_token && preg_match('/^[a-zA-Z0-9,-]{16,64}$/', $header_token)) {
        session_id($header_token);
    }

    // Only set cookie parameters if session is not active
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Generate or retrieve the CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden HTML CSRF input field
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify submitted CSRF token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape HTML output for XSS prevention
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash message
 * @param string $type 'success' | 'error' | 'warning' | 'info'
 * @param string $message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and clear the session flash message
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if a user is currently authenticated
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Guard: Requires the user to be logged in
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to access your dashboard.');
        header('Location: index.php?tab=login');
        exit;
    }
}

/**
 * Guard: Redirects authenticated users away from login/register pages
 */
function require_guest(): void {
    if (is_logged_in()) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Get current authenticated user details from session
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'username'  => $_SESSION['username'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'user',
        'bio'       => $_SESSION['bio'] ?? '',
    ];
}

/**
 * Sanitize simple string input
 */
function sanitize_text(string $data): string {
    return trim(strip_tags($data));
}
