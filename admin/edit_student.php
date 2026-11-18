<?php
$basePath = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pdo = getPDO();
$error = '';
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($studentId <= 0) {
    header("Location: dashboard.php?error=" . urlencode("Invalid student ID."));
    exit;
}

// Fetch existing student
$stmt = $pdo->prepare("SELECT * FROM studinfo WHERE id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: dashboard.php?error=" . urlencode("Student record not found."));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $grade = trim($_POST['grade'] ?? '');
        $newPassword = trim($_POST['password'] ?? '');

        if (empty($name) || empty($idNumber) || empty($course) || empty($year) || empty($subject) || empty($grade)) {
            $error = "All fields (Name, ID Number, Course, Year, Subject, Grade) are required.";
        } else {
            // Check if IdNumber is taken by another student
            $checkStmt = $pdo->prepare("SELECT id FROM studinfo WHERE IdNumber = ? AND id != ?");
            $checkStmt->execute([$idNumber, $studentId]);
            if ($checkStmt->fetch()) {
                $error = "Another student with ID Number '" . htmlspecialchars($idNumber) . "' already exists.";
            } else {
                $imageFilename = $student['Image'];

                // Handle image upload if provided
                if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['student_image'];
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $error = "File upload failed with error code: " . $file['error'];
                    } elseif ($file['size'] > 3 * 1024 * 1024) {
                        $error = "Image must be 3MB or smaller.";
                    } else {
                        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);

                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                        if (!in_array($mimeType, $allowedMimes) || !in_array($ext, $allowedExts)) {
                            $error = "Invalid image type. Allowed: JPG, PNG, WEBP, GIF.";
                        } else {
                            $uploadDir = __DIR__ . '/../uploads/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            $imageFilename = 'stud_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $idNumber) . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($file['tmp_name'], $uploadDir . $imageFilename)) {
                                // Delete old file if custom
                                if ($student['Image'] && $student['Image'] !== 'default_avatar.svg' && $student['Image'] !== 'default_avatar.png') {
                                    $oldPath = $uploadDir . $student['Image'];
                                    if (file_exists($oldPath)) {
                                        @unlink($oldPath);
                                    }
                                }
                            } else {
                                $error = "Could not save uploaded photo.";
                            }
                        }
                    }
                }

                if (empty($error)) {
                    if (!empty($newPassword)) {
                        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                        $updateStmt = $pdo->prepare("
                            UPDATE studinfo 
                            SET `Name` = ?, `IdNumber` = ?, `Course` = ?, `Year` = ?, `Subject` = ?, `Image` = ?, `Grade` = ?, `Password` = ?
                            WHERE `id` = ?
                        ");
                        $updateStmt->execute([$name, $idNumber, $course, $year, $subject, $imageFilename, $grade, $hashed, $studentId]);
                    } else {
                        $updateStmt = $pdo->prepare("
                            UPDATE studinfo 
                            SET `Name` = ?, `IdNumber` = ?, `Course` = ?, `Year` = ?, `Subject` = ?, `Image` = ?, `Grade` = ?
                            WHERE `id` = ?
                        ");
                        $updateStmt->execute([$name, $idNumber, $course, $year, $subject, $imageFilename, $grade, $studentId]);
                    }

                    setFlash('success', "Student '" . htmlspecialchars($name) . "' updated successfully!");
                    header("Location: dashboard.php");
                    exit;
                }
            }
        }
    }
}

$imageFile = !empty($student['Image']) ? $student['Image'] : 'default_avatar.svg';
$imagePath = $basePath . 'uploads/' . htmlspecialchars($imageFile);
if (!file_exists(__DIR__ . '/../uploads/' . $imageFile)) {
    $imagePath = $basePath . 'uploads/default_avatar.svg';
}

$pageTitle = 'Edit Student - ' . htmlspecialchars($student['Name']);
$csrfToken = generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Edit Student Information</h3>
                <p class="text-muted mb-0">Modify student academic profile in <code>studinfo</code>.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom">
            <div class="card-header card-header-custom">
                <span class="text-primary fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Student: <?= htmlspecialchars($student['Name']) ?></span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="edit_student.php?id=<?= $studentId ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3">
                        <img src="<?= $imagePath ?>" alt="Current Avatar" class="student-avatar-sm" style="width: 64px; height: 64px;">
                        <div>
                            <div class="fw-semibold">Current Photo</div>
                            <small class="text-muted">Upload a new file below if you want to replace it.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($student['Name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ID Number <span class="text-danger">*</span></label>
                            <input type="text" name="id_number" class="form-control" value="<?= htmlspecialchars($student['IdNumber']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Course / Degree <span class="text-danger">*</span></label>
                            <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($student['Course']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Year Level <span class="text-danger">*</span></label>
                            <select name="year" class="form-select" required>
                                <option value="1st Year" <?= $student['Year'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= $student['Year'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= $student['Year'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= $student['Year'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($student['Subject']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Grade <span class="text-danger">*</span></label>
                            <input type="text" name="grade" class="form-control" value="<?= htmlspecialchars($student['Grade']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Replace Photo (Image)</label>
                        <input type="file" name="student_image" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif">
                        <div class="form-text">Leave empty to keep existing photo. Formats: JPG, PNG, WEBP, GIF.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Reset Student Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep existing password">
                        <div class="form-text">Only enter a value if you wish to reset the student's login password.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Update Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
