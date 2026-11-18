<?php
$basePath = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireStudent();

$pdo = getPDO();
$studentId = $_SESSION['student_id'];
$error = '';
$success = '';

// Handle Profile Update (Change Name and/or Upload Photo only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $newName = trim($_POST['name'] ?? '');

        if (empty($newName)) {
            $error = "Student Name cannot be empty.";
        } else {
            // Fetch current student record
            $stmt = $pdo->prepare("SELECT * FROM studinfo WHERE id = ?");
            $stmt->execute([$studentId]);
            $currentStudent = $stmt->fetch();

            if (!$currentStudent) {
                $error = "Student account not found.";
            } else {
                $newImageFilename = $currentStudent['Image'];

                // Check if new photo was uploaded
                if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['student_image'];
                    
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $error = "File upload failed with error code: " . $file['error'];
                    } else {
                        // Max 3MB
                        if ($file['size'] > 3 * 1024 * 1024) {
                            $error = "Image size must be 3MB or less.";
                        } else {
                            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeType = finfo_file($finfo, $file['tmp_name']);
                            finfo_close($finfo);

                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                            if (!in_array($mimeType, $allowedMimes) || !in_array($ext, $allowedExts)) {
                                $error = "Invalid file type. Only JPG, PNG, WEBP, and GIF images are permitted.";
                            } else {
                                $uploadDir = __DIR__ . '/../uploads/';
                                if (!is_dir($uploadDir)) {
                                    mkdir($uploadDir, 0755, true);
                                }

                                $newImageFilename = 'stud_' . $studentId . '_' . time() . '.' . $ext;
                                $targetFilePath = $uploadDir . $newImageFilename;

                                if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                                    // Remove old image if not default avatar
                                    $oldImage = $currentStudent['Image'];
                                    if ($oldImage && $oldImage !== 'default_avatar.svg' && $oldImage !== 'default_avatar.png') {
                                        $oldPath = $uploadDir . $oldImage;
                                        if (file_exists($oldPath)) {
                                            @unlink($oldPath);
                                        }
                                    }
                                } else {
                                    $error = "Failed to save uploaded photo to server.";
                                }
                            }
                        }
                    }
                }

                if (empty($error)) {
                    // Update only Name and Image as required
                    $updateStmt = $pdo->prepare("UPDATE studinfo SET `Name` = ?, `Image` = ? WHERE `id` = ?");
                    $updateStmt->execute([$newName, $newImageFilename, $studentId]);

                    $_SESSION['student_name'] = $newName;
                    $success = "Your profile has been updated successfully!";
                }
            }
        }
    }
}

// Fetch latest student record for display
$stmt = $pdo->prepare("SELECT * FROM studinfo WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: ../logout.php");
    exit;
}

// Determine image path
$imageFile = !empty($student['Image']) ? $student['Image'] : 'default_avatar.svg';
$imagePath = $basePath . 'uploads/' . htmlspecialchars($imageFile);
if (!file_exists(__DIR__ . '/../uploads/' . $imageFile)) {
    $imagePath = $basePath . 'uploads/default_avatar.svg';
}

$pageTitle = 'Student Portal';
$csrfToken = generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Student Account</h3>
                <p class="text-muted mb-0">View your academic details, upload your photo, and update your name.</p>
            </div>
            <span class="badge bg-primary px-3 py-2 fs-6">
                <i class="bi bi-person-badge me-1"></i> ID: <?= htmlspecialchars($student['IdNumber']) ?>
            </span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom mb-4">
            <div class="card-header card-header-custom d-flex align-items-center justify-content-between">
                <span><i class="bi bi-person-lines-fill me-2 text-primary"></i>Profile Information</span>
                <span class="badge bg-light text-dark border">
                    <i class="bi bi-lock me-1"></i> Academic details locked by Administrator
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="dashboard.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="row align-items-center mb-4">
                        <div class="col-sm-auto text-center mb-3 mb-sm-0">
                            <img src="<?= $imagePath ?>" alt="Student Photo" class="student-avatar">
                        </div>
                        <div class="col">
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($student['Name']) ?></h5>
                            <div class="text-muted mb-3"><?= htmlspecialchars($student['Course']) ?> &bull; <?= htmlspecialchars($student['Year']) ?></div>
                            
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-camera me-1"></i> Upload / Change Photo:
                            </label>
                            <input type="file" name="student_image" class="form-control form-control-sm" accept="image/png, image/jpeg, image/webp, image/gif">
                            <div class="form-text">Accepted formats: JPG, PNG, WEBP, GIF (Max 3MB).</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3 text-secondary">
                        <i class="bi bi-pencil-square me-1"></i> Editable Field:
                    </h6>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($student['Name']) ?>" required>
                        </div>
                        <div class="form-text text-muted">Per institution policy, students are permitted to update their Name only.</div>
                    </div>

                    <h6 class="fw-bold mb-3 text-secondary">
                        <i class="bi bi-lock-fill me-1"></i> Academic Records (Read-Only):
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold">ID Number</label>
                            <input type="text" class="form-control field-locked" value="<?= htmlspecialchars($student['IdNumber']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted fw-semibold">Course / Degree</label>
                            <input type="text" class="form-control field-locked" value="<?= htmlspecialchars($student['Course']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted fw-semibold">Year Level</label>
                            <input type="text" class="form-control field-locked" value="<?= htmlspecialchars($student['Year']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted fw-semibold">Enrolled Subject</label>
                            <input type="text" class="form-control field-locked" value="<?= htmlspecialchars($student['Subject']) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted fw-semibold">Current Grade</label>
                            <div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle badge-grade">
                                    <i class="bi bi-award-fill me-1"></i> <?= htmlspecialchars($student['Grade']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Save Changes (Name & Photo)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
