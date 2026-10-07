<?php
/**
 * Registration Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Redirect authenticated users away
require_guest();

// If accessed directly via GET, redirect to registration tab
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=register');
    exit;
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid security token (CSRF). Please try submitting the form again.');
    header('Location: index.php?tab=register');
    exit;
}

// 2. Retrieve & Sanitize Inputs
$full_name        = trim($_POST['full_name'] ?? '');
$username         = trim($_POST['username'] ?? '');
$email            = trim($_POST['email'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Save old input for UX repopulation (excluding passwords)
$_SESSION['old_input'] = [
    'full_name' => $full_name,
    'username'  => $username,
    'email'     => $email,
];

// 3. Validation
$errors = [];

if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 100) {
    $errors[] = 'Full Name must be between 2 and 100 characters.';
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    $errors[] = 'Username must be 3-30 characters long and contain only letters, numbers, and underscores.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    $errors[] = 'Please provide a valid email address.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

if ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    set_flash('error', implode('<br>', $errors));
    header('Location: index.php?tab=register');
    exit;
}

try {
    // 4. Check for existing username or email
    $check_stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE username = :username OR email = :email LIMIT 1');
    $check_stmt->execute([
        'username' => $username,
        'email'    => $email,
    ]);
    $existing = $check_stmt->fetch();

    if ($existing) {
        if (strcasecmp($existing['username'], $username) === 0) {
            set_flash('error', 'This username is already taken. Please choose another.');
        } else {
            set_flash('error', 'An account with this email address already exists. Please sign in instead.');
        }
        header('Location: index.php?tab=register');
        exit;
    }

    // 5. Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // 6. Insert new user into database
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

    // Clear saved old input on success
    unset($_SESSION['old_input']);

    set_flash('success', 'Your account has been created successfully! Please sign in with your credentials.');
    header('Location: index.php?tab=login');
    exit;

} catch (PDOException $e) {
    error_log('Registration Error: ' . $e->getMessage());
    set_flash('error', 'An unexpected error occurred while processing your registration. Please try again.');
    header('Location: index.php?tab=register');
    exit;
}
