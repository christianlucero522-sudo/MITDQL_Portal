<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdmin(): bool {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isStudent(): bool {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

function requireAdmin(string $redirectPath = '../index.php'): void {
    if (!isAdmin()) {
        header("Location: $redirectPath?error=" . urlencode("Access denied. Please log in as Admin."));
        exit;
    }
}

function requireStudent(string $redirectPath = '../index.php'): void {
    if (!isStudent()) {
        header("Location: $redirectPath?error=" . urlencode("Access denied. Please log in as a Student."));
        exit;
    }
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
