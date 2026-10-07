-- ========================================================
-- Database Schema for Registration & Login System
-- Database: `auth_system_db`
-- ========================================================

CREATE DATABASE IF NOT EXISTS `auth_system_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `auth_system_db`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

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
