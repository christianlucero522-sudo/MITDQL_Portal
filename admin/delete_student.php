<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pdo = getPDO();
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrfToken = $_GET['csrf_token'] ?? '';

if (!verifyCsrfToken($csrfToken)) {
    header("Location: dashboard.php?error=" . urlencode("Invalid security token for deletion."));
    exit;
}

if ($studentId > 0) {
    // Get existing image to clean up
    $stmt = $pdo->prepare("SELECT Name, Image FROM studinfo WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if ($student) {
        if ($student['Image'] && $student['Image'] !== 'default_avatar.svg' && $student['Image'] !== 'default_avatar.png') {
            $imagePath = __DIR__ . '/../uploads/' . $student['Image'];
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        $delStmt = $pdo->prepare("DELETE FROM studinfo WHERE id = ?");
        $delStmt->execute([$studentId]);

        setFlash('success', "Student '" . htmlspecialchars($student['Name']) . "' has been deleted successfully.");
    } else {
        setFlash('warning', "Student record not found.");
    }
}

header("Location: dashboard.php");
exit;
