<?php
/**
 * Database Configuration & Connection (PDO)
 * Automatically initializes database and tables if they don't exist.
 */

declare(strict_types=1);

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'auth_system_db';
$db_charset = 'utf8mb4';

try {
    // 1. Connect to MySQL server first (without database) to ensure DB exists
    $dsn_server = "mysql:host={$db_host};charset={$db_charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $server_pdo = new PDO($dsn_server, $db_user, $db_pass, $options);

    // Auto-create database if it doesn't exist
    $server_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // 2. Connect to the specific database
    $dsn_db = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $pdo = new PDO($dsn_db, $db_user, $db_pass, $options);

    // 3. Auto-create 'users' table if it doesn't exist
    $create_users_table = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `full_name` VARCHAR(100) NOT NULL,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `bio` VARCHAR(255) DEFAULT 'Welcome to my profile!',
            `role` VARCHAR(20) DEFAULT 'user',
            `last_login` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_username` (`username`),
            INDEX `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($create_users_table);

} catch (PDOException $e) {
    // Display a user-friendly error message if database connection fails
    die("
        <div style='font-family: system-ui, -apple-system, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border-radius: 12px; background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
            <h2 style='margin-top: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 8px;'>
                <span>⚠️</span> Database Connection Error
            </h2>
            <p style='line-height: 1.5; color: #4c0519;'>Could not connect to MySQL. Please make sure MySQL is running in your XAMPP Control Panel.</p>
            <p style='background: #ffe4e6; padding: 10px 14px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; word-break: break-all; margin: 15px 0 0;'>
                <strong>Error Details:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "
            </p>
        </div>
    ");
}
