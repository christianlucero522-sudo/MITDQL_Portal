<?php
/**
 * MITDQL One-Click Installer & Database Check
 */
require_once __DIR__ . '/config/db.php';

$message = '';
$status = 'info';

try {
    $pdo = getPDO();
    $message = "Database 'MITDQL' and table 'studinfo' are ready to use!";
    $status = 'success';
} catch (Exception $e) {
    $message = "Installation error: " . $e->getMessage();
    $status = 'danger';
}

$pageTitle = 'Setup & Diagnostics';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card card-custom mt-4">
            <div class="card-header card-header-custom text-primary fw-bold">
                <i class="bi bi-gear-fill me-2"></i>Database Setup & Diagnostics
            </div>
            <div class="card-body p-4 text-center">
                <div class="alert alert-<?= $status ?> mb-4">
                    <i class="bi bi-<?= $status === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill' ?> me-2 fs-5"></i>
                    <?= htmlspecialchars($message) ?>
                </div>

                <div class="text-start bg-light p-3 rounded mb-4">
                    <h6 class="fw-bold mb-2">Default Credentials:</h6>
                    <ul class="mb-0">
                        <li><strong>Admin:</strong> Username: <code>admin</code> | Password: <code>admin123</code></li>
                        <li><strong>Sample Student:</strong> ID Number: <code>2026-0001</code> | Password: <code>password123</code></li>
                    </ul>
                </div>

                <a href="index.php" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-arrow-right-circle me-1"></i> Proceed to Login Portal
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
