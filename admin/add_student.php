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
$subjects = [''];
$grades = [''];
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
        $password = trim($_POST['password'] ?? '');

        $rawSubjects = $_POST['subjects'] ?? [];
        $rawGrades = $_POST['grades'] ?? [];

        // Clean subject & grade arrays
        $cleanedSubjects = [];
        $cleanedGrades = [];
        for ($i = 0; $i < count($rawSubjects); $i++) {
            $s = trim($rawSubjects[$i] ?? '');
            $g = trim($rawGrades[$i] ?? '');
            if ($s !== '') {
                $cleanedSubjects[] = $s;
                $cleanedGrades[] = $g !== '' ? $g : 'N/A';
            }
        }

        // Validation
        if (empty($name) || empty($idNumber) || empty($course) || empty($year) || empty($cleanedSubjects)) {
            $error = "Please fill in all required fields and provide at least one Subject and Grade.";
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
                            $imgData = file_get_contents($file['tmp_name']);
                            $imageFilename = 'data:' . $mimeType . ';base64,' . base64_encode($imgData);
                        }
                    }
                }

                if (empty($error)) {
                    $subjectJson = json_encode($cleanedSubjects, JSON_UNESCAPED_UNICODE);
                    $gradeJson = json_encode($cleanedGrades, JSON_UNESCAPED_UNICODE);

                    $insertStmt = $pdo->prepare("
                        INSERT INTO studinfo (`Name`, `IdNumber`, `Course`, `Year`, `Subject`, `Image`, `Grade`, `Password`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $name,
                        $idNumber,
                        $course,
                        $year,
                        $subjectJson,
                        $imageFilename,
                        $gradeJson,
                        $hashedPassword
                    ]);

                    setFlash('success', "Student '" . htmlspecialchars($name) . "' has been added successfully with " . count($cleanedSubjects) . " subject(s)!");
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
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Add New Student</h3>
                <p class="text-muted mb-0">Register student details with multiple enrolled subjects and grades.</p>
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
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Course / Degree <span class="text-danger">*</span></label>
                            <input type="text" name="course" class="form-control" placeholder="e.g. BS Information Technology" value="<?= htmlspecialchars($course) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Year Level <span class="text-danger">*</span></label>
                            <select name="year" class="form-select" required>
                                <option value="" disabled selected>Select Year Level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dynamic Multiple Subjects and Grades -->
                    <div class="card bg-light border-0 mb-3 p-3 rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0 text-dark">
                                <i class="bi bi-journal-bookmark-fill text-primary me-1"></i> Enrolled Subjects & Grades
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="addSubjectBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add Another Subject
                            </button>
                        </div>
                        <div class="small text-muted mb-3">You can add multiple subjects with their corresponding grades for this student.</div>

                        <div id="subjectContainer">
                            <div class="row g-2 mb-2 subject-row align-items-center">
                                <div class="col-md-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-book"></i></span>
                                        <input type="text" name="subjects[]" class="form-control" placeholder="Subject Name (e.g. Advanced Database Systems)" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-award"></i></span>
                                        <input type="text" name="grades[]" class="form-control" placeholder="Grade (e.g. 1.25)" required>
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary remove-row-btn" disabled>
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('subjectContainer');
    const addBtn = document.getElementById('addSubjectBtn');

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.subject-row');
        rows.forEach((row) => {
            const btn = row.querySelector('.remove-row-btn');
            if (rows.length === 1) {
                btn.disabled = true;
                btn.classList.add('btn-outline-secondary');
                btn.classList.remove('btn-outline-danger');
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-outline-danger');
            }
        });
    }

    addBtn.addEventListener('click', function() {
        const newRow = document.createElement('div');
        newRow.className = 'row g-2 mb-2 subject-row align-items-center';
        newRow.innerHTML = `
            <div class="col-md-7">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-book"></i></span>
                    <input type="text" name="subjects[]" class="form-control" placeholder="Subject Name" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-award"></i></span>
                    <input type="text" name="grades[]" class="form-control" placeholder="Grade" required>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        updateRemoveButtons();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row-btn')) {
            const rows = container.querySelectorAll('.subject-row');
            if (rows.length > 1) {
                e.target.closest('.subject-row').remove();
                updateRemoveButtons();
            }
        }
    });

    updateRemoveButtons();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
