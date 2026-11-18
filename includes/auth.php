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
        'type' => $type,
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

/**
 * Helper to parse single or multiple subjects and grades
 */
function parseSubjectsAndGrades(?string $subjectField, ?string $gradeField): array {
    $decodedSub = !empty($subjectField) ? json_decode($subjectField, true) : null;
    $decodedGrd = !empty($gradeField) ? json_decode($gradeField, true) : null;

    $subjects = [];
    $grades = [];

    if (is_array($decodedSub)) {
        $subjects = $decodedSub;
        $grades = is_array($decodedGrd) ? $decodedGrd : [];
    } elseif (!empty($subjectField)) {
        if (strpos($subjectField, ',') !== false) {
            $subjects = array_map('trim', explode(',', $subjectField));
            $grades = array_map('trim', explode(',', $gradeField ?? ''));
        } else {
            $subjects = [$subjectField];
            $grades = [$gradeField ?? 'N/A'];
        }
    }

    $list = [];
    $count = max(count($subjects), count($grades));
    for ($i = 0; $i < $count; $i++) {
        $s = $subjects[$i] ?? '';
        $g = $grades[$i] ?? '';
        if ($s !== '') {
            $list[] = [
                'subject' => $s,
                'grade' => $g !== '' ? $g : 'N/A'
            ];
        }
    }

    return !empty($list) ? $list : [['subject' => 'No Subject Enrolled', 'grade' => 'N/A']];
}
