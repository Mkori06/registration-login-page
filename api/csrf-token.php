<?php
/**
 * REST API: Get CSRF Token & Session Information
 * GET /api/csrf-token.php
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

json_response(200, [
    'status'     => 'success',
    'code'       => 200,
    'csrf_token' => csrf_token(),
    'session_id' => session_id(),
    'logged_in'  => is_logged_in(),
    'user'       => current_user()
]);
