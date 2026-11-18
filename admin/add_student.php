<?php
$basePath = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pdo = getPDO();
$error = '';
$success = '';

// Pre-fill values
$name = '';
$idNumber = '';
$course = '';
$year = '';
$subject = '';
$grade = '';
$password = '';

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
        $password = trim($_POST['password'] ?? '');

        // Validation
        if (empty($name) || empty($idNumber) || empty($course) || empty($year) || empty($subject) || empty($grade)) {
            $error = "Please fill in all required fields (Name, ID Number, Course, Year, Subject, Grade).";
        } else {
            // Check for duplicate IdNumber
            $checkStmt = $pdo->prepare("SELECT id FROM studinfo WHERE IdNumber = ?");
            $checkStmt->execute([$idNumber]);
            if ($checkStmt->fetch()) {
                $error = "A student with ID Number '" . htmlspecialchars($idNumber) . "' already exists.";
            } else {
                $plainPassword = !empty($password) ? $password : $idNumber;
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
                $imageFilename = DEFAULT_AVATAR;

                // Check for image upload
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

                        if (!in_array($mimeType, $allowedMimes)) {
                            $error = "Invalid file type. Allowed formats: JPG, PNG, WEBP, GIF.";
                        } else {
                            // Store as Data URI (persists in MySQL and compatible with Vercel's read-only serverless filesystem)
                            $imgData = file_get_contents($file['tmp_name']);
                            $imageFilename = 'data:' . $mimeType . ';base64,' . base64_encode($imgData);
                        }
                    }
                }

                if (empty($error)) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO studinfo (`Name`, `IdNumber`, `Course`, `Year`, `Subject`, `Image`, `Grade`, `Password`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $name,
                        $idNumber,
                        $course,
                        $year,
                        $subject,
                        $imageFilename,
                        $grade,
                        $hashedPassword
                    ]);

                    setFlash('success', "Student '" . htmlspecialchars($name) . "' has been added successfully! Initial password: " . htmlspecialchars($plainPassword));
                    header("Location: dashboard.php");
                    exit;
                }
            }
        }
    }
}

$pageTitle = 'Add Student';
$csrfToken = generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Add New Student</h3>
                <p class="text-muted mb-0">Input student information to insert into <code>studinfo</code> table.</p>
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
                <span class="text-primary fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Student Registration Form</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="add_student.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Maria Santos" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ID Number <span class="text-danger">*</span></label>
                            <input type="text" name="id_number" class="form-control" placeholder="e.g. 2026-0002" value="<?= htmlspecialchars($idNumber) ?>" required>
                            <div class="form-text">Must be unique for each student.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Course / Degree <span class="text-danger">*</span></label>
                            <input type="text" name="course" class="form-control" placeholder="e.g. BS Computer Science" value="<?= htmlspecialchars($course) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Year Level <span class="text-danger">*</span></label>
                            <select name="year" class="form-select" required>
                                <option value="" disabled <?= empty($year) ? 'selected' : '' ?>>Select Year Level</option>
                                <option value="1st Year" <?= $year === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= $year === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= $year === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= $year === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" placeholder="e.g. Database Management 101" value="<?= htmlspecialchars($subject) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Grade <span class="text-danger">*</span></label>
                            <input type="text" name="grade" class="form-control" placeholder="e.g. 1.50 or 92" value="<?= htmlspecialchars($grade) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Student Photo (Image)</label>
                        <input type="file" name="student_image" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif">
                        <div class="form-text">Optional. Formats: JPG, PNG, WEBP, GIF (Max 3MB).</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Student Account Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to default to student's ID Number">
                        <div class="form-text">The student will use this password together with their ID Number to log into their account.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Save Student Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
