<?php
/**
 * API Helpers & Response Formatter
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Allow CORS if needed for API testing
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send structured JSON response
 */
function json_response(int $status_code, array $payload): void {
    http_response_code($status_code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Parse incoming request payload (JSON or Form URL-encoded)
 */
function get_request_payload(): array {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($content_type, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

/**
 * Guard: Enforce authenticated session for protected API endpoints
 */
function require_api_auth(): array {
    if (!is_logged_in()) {
        json_response(401, [
            'status'  => 'error',
            'code'    => 401,
            'message' => 'Unauthorized. You must be logged in to access this resource.'
        ]);
    }
    return current_user();
}
