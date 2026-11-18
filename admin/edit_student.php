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
        $newPassword = trim($_POST['password'] ?? '');

        $rawSubjects = $_POST['subjects'] ?? [];
        $rawGrades = $_POST['grades'] ?? [];

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

        if (empty($name) || empty($idNumber) || empty($course) || empty($year) || empty($cleanedSubjects)) {
            $error = "All fields and at least one Subject and Grade are required.";
        } else {
            // Check if IdNumber is taken by another student
            $checkStmt = $pdo->prepare("SELECT id FROM studinfo WHERE IdNumber = ? AND id != ?");
            $checkStmt->execute([$idNumber, $studentId]);
            if ($checkStmt->fetch()) {
                $error = "Another student with ID Number '" . htmlspecialchars($idNumber) . "' already exists.";
            } else {
                $imageFilename = !empty($student['Image']) ? $student['Image'] : DEFAULT_AVATAR;

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

                        if (!in_array($mimeType, $allowedMimes)) {
                            $error = "Invalid image type. Allowed: JPG, PNG, WEBP, GIF.";
                        } else {
                            $imgData = file_get_contents($file['tmp_name']);
                            $imageFilename = 'data:' . $mimeType . ';base64,' . base64_encode($imgData);
                        }
                    }
                }

                if (empty($error)) {
                    $subjectJson = json_encode($cleanedSubjects, JSON_UNESCAPED_UNICODE);
                    $gradeJson = json_encode($cleanedGrades, JSON_UNESCAPED_UNICODE);

                    if (!empty($newPassword)) {
                        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                        $updateStmt = $pdo->prepare("
                            UPDATE studinfo 
                            SET `Name` = ?, `IdNumber` = ?, `Course` = ?, `Year` = ?, `Subject` = ?, `Image` = ?, `Grade` = ?, `Password` = ?
                            WHERE `id` = ?
                        ");
                        $updateStmt->execute([$name, $idNumber, $course, $year, $subjectJson, $imageFilename, $gradeJson, $hashed, $studentId]);
                    } else {
                        $updateStmt = $pdo->prepare("
                            UPDATE studinfo 
                            SET `Name` = ?, `IdNumber` = ?, `Course` = ?, `Year` = ?, `Subject` = ?, `Image` = ?, `Grade` = ?
                            WHERE `id` = ?
                        ");
                        $updateStmt->execute([$name, $idNumber, $course, $year, $subjectJson, $imageFilename, $gradeJson, $studentId]);
                    }

                    setFlash('success', "Student '" . htmlspecialchars($name) . "' updated successfully with " . count($cleanedSubjects) . " subject(s)!");
                    header("Location: dashboard.php");
                    exit;
                }
            }
        }
    }
}

// Parse existing subjects and grades
$existingSubjectList = parseSubjectsAndGrades($student['Subject'], $student['Grade']);

// Display avatar preview
$imageDisplay = !empty($student['Image']) ? $student['Image'] : DEFAULT_AVATAR;
if (strpos($imageDisplay, 'data:') !== 0 && strpos($imageDisplay, 'http') !== 0) {
    if (file_exists(__DIR__ . '/../uploads/' . $imageDisplay)) {
        $imageDisplay = $basePath . 'uploads/' . htmlspecialchars($imageDisplay);
    } else {
        $imageDisplay = DEFAULT_AVATAR;
    }
}

$pageTitle = 'Edit Student - ' . htmlspecialchars($student['Name']);
$csrfToken = generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Edit Student Information</h3>
                <p class="text-muted mb-0">Modify student academic profile, multiple enrolled subjects, and grades.</p>
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
                        <img src="<?= $imageDisplay ?>" alt="Current Avatar" class="student-avatar-sm" style="width: 64px; height: 64px;">
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

                    <!-- Dynamic Multiple Subjects and Grades -->
                    <div class="card bg-light border-0 mb-3 p-3 rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0 text-dark">
                                <i class="bi bi-journal-bookmark-fill text-primary me-1"></i> Enrolled Subjects & Grades (<?= count($existingSubjectList) ?>)
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="addSubjectBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add Another Subject
                            </button>
                        </div>
                        <div class="small text-muted mb-3">Modify, add, or remove subjects and grades for this student.</div>

                        <div id="subjectContainer">
                            <?php foreach ($existingSubjectList as $idx => $pair): ?>
                                <div class="row g-2 mb-2 subject-row align-items-center">
                                    <div class="col-md-7">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-book"></i></span>
                                            <input type="text" name="subjects[]" class="form-control" value="<?= htmlspecialchars($pair['subject']) ?>" placeholder="Subject Name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-award"></i></span>
                                            <input type="text" name="grades[]" class="form-control" value="<?= htmlspecialchars($pair['grade']) ?>" placeholder="Grade" required>
                                        </div>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Replace Photo (Image)</label>
                        <input type="file" name="student_image" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif">
                        <div class="form-text">Leave empty to keep existing photo. Formats: JPG, PNG, WEBP, GIF (Max 3MB).</div>
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
