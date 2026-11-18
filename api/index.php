<?php
/**
 * Vercel Serverless Entrypoint / Router for PHP
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Normalize root or empty path to index.php
if ($uri === '/' || empty($uri)) {
    require __DIR__ . '/../index.php';
    exit;
}

$targetFile = realpath(__DIR__ . '/..' . $uri);
$rootDir = realpath(__DIR__ . '/..');

// Prevent directory traversal attacks
if ($targetFile && strpos($targetFile, $rootDir) === 0) {
    if (is_dir($targetFile)) {
        $indexInDir = $targetFile . DIRECTORY_SEPARATOR . 'index.php';
        $dashboardInDir = $targetFile . DIRECTORY_SEPARATOR . 'dashboard.php';
        if (file_exists($indexInDir)) {
            require $indexInDir;
            exit;
        } elseif (file_exists($dashboardInDir)) {
            require $dashboardInDir;
            exit;
        }
    } elseif (file_exists($targetFile)) {
        if (pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
            require $targetFile;
            exit;
        }
    }
}

// Fallback to main index.php
require __DIR__ . '/../index.php';
