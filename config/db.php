<?php
/**
 * Database Configuration & PDO Connection
 * Database: MITDQL
 * Supports both local XAMPP and Cloud Environment Variables (for Vercel / Railway / Cloud hosts)
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'MITDQL');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

function getPDO(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            // First connect to ensure database exists if local
            if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
                $initDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $initPdo = new PDO($initDsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $initPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }

            // Connect to MITDQL database
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Auto-ensure tables exist
            autoMigrateTables($pdo);

        } catch (PDOException $e) {
            die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}

function autoMigrateTables(PDO $pdo): void {
    // Create studinfo table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `studinfo` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `Name` VARCHAR(150) NOT NULL,
            `IdNumber` VARCHAR(50) NOT NULL UNIQUE,
            `Course` VARCHAR(100) NOT NULL,
            `Year` VARCHAR(20) NOT NULL,
            `Subject` VARCHAR(150) NOT NULL,
            `Image` VARCHAR(255) DEFAULT 'default_avatar.svg',
            `Grade` VARCHAR(10) NOT NULL,
            `Password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create admins table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed default admin if empty
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ($checkAdmin == 0) {
        $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password`) VALUES (?, ?)");
        $stmt->execute(['admin', $adminPass]);
    }
}
