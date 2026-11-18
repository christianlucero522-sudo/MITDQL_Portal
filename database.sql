-- Database creation script for MITDQL
CREATE DATABASE IF NOT EXISTS `MITDQL` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `MITDQL`;

-- Table structure for studinfo
CREATE TABLE IF NOT EXISTS `studinfo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(150) NOT NULL,
  `IdNumber` VARCHAR(50) NOT NULL UNIQUE,
  `Course` VARCHAR(100) NOT NULL,
  `Year` VARCHAR(20) NOT NULL,
  `Subject` VARCHAR(150) NOT NULL,
  `Image` VARCHAR(255) DEFAULT 'default_avatar.png',
  `Grade` VARCHAR(10) NOT NULL,
  `Password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for admins
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default admin (Username: admin, Password: admin123)
-- Password hash generated using password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`username`, `password`)
VALUES ('admin', '$2y$10$vQ3pXvV2z9.j9wzrqK2qXe1qVv9q6rV5G0jA3L8k8j7H6yQ0dK8Ce')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Seed sample student record (IdNumber: 2026-0001, Password: password123)
-- Password hash generated using password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO `studinfo` (`Name`, `IdNumber`, `Course`, `Year`, `Subject`, `Image`, `Grade`, `Password`)
VALUES (
  'Juan Dela Cruz',
  '2026-0001',
  'BS Information Technology',
  '3rd Year',
  'Advanced Database Systems',
  'default_avatar.png',
  '1.25',
  '$2y$10$1Y8g4G8L.fVnI2.ZtQWvfe8n1zD4lV.r5H5c6pY7B7Y0m4N9uF3aK'
)
ON DUPLICATE KEY UPDATE `IdNumber`=`IdNumber`;
