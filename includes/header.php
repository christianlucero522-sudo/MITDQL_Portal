<?php
if (!isset($basePath)) {
    $basePath = './';
}
require_once __DIR__ . '/auth.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>MITDQL Student Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $basePath ?>assets/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $basePath ?>index.php">
                <i class="bi bi-mortarboard-fill text-primary"></i>
                <span>MITDQL Portal</span>
                <span class="brand-badge">PDO</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>admin/add_student.php">
                                <i class="bi bi-person-plus me-1"></i> Add Student
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-outline-danger btn-sm" href="<?= $basePath ?>logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i> Logout (Admin)
                            </a>
                        </li>
                    <?php elseif (isStudent()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>student/dashboard.php">
                                <i class="bi bi-person-circle me-1"></i> My Account
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-outline-danger btn-sm" href="<?= $basePath ?>logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i> Logout (<?= htmlspecialchars($_SESSION['student_name'] ?? 'Student') ?>)
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $basePath ?>index.php">Home / Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content py-4">
        <div class="container">
            <?php if ($flash): ?>
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
