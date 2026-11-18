<?php
$basePath = './';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
} elseif (isStudent()) {
    header('Location: student/dashboard.php');
    exit;
}

$error = '';
$activeTab = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getPDO();
    $loginType = $_POST['login_type'] ?? '';

    if ($loginType === 'student') {
        $activeTab = 'student';
        $idNumber = trim($_POST['id_number'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($idNumber) || empty($password)) {
            $error = "Please enter both ID Number and Password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM studinfo WHERE IdNumber = ?");
            $stmt->execute([$idNumber]);
            $student = $stmt->fetch();

            if ($student) {
                // Verify hash or plain text fallback (and migrate to hash)
                $isValid = password_verify($password, $student['Password']) || ($student['Password'] === $password);
                if ($isValid) {
                    // Update to secure hash if it wasn't hashed yet
                    if ($student['Password'] === $password) {
                        $newHash = password_hash($password, PASSWORD_BCRYPT);
                        $upStmt = $pdo->prepare("UPDATE studinfo SET Password = ? WHERE id = ?");
                        $upStmt->execute([$newHash, $student['id']]);
                    }

                    $_SESSION['user_type'] = 'student';
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_idnumber'] = $student['IdNumber'];
                    $_SESSION['student_name'] = $student['Name'];

                    setFlash('success', "Welcome back, " . htmlspecialchars($student['Name']) . "!");
                    header('Location: student/dashboard.php');
                    exit;
                } else {
                    $error = "Invalid ID Number or Password.";
                }
            } else {
                $error = "No student found with ID Number: " . htmlspecialchars($idNumber);
            }
        }

    } elseif ($loginType === 'admin') {
        $activeTab = 'admin';
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = "Please enter both Admin Username and Password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_type'] = 'admin';
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                setFlash('success', "Logged in successfully as Administrator.");
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $error = "Invalid Admin credentials.";
            }
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-7 col-lg-5">
        <div class="text-center mb-4">
            <div class="display-6 fw-bold text-primary mb-1">MITDQL</div>
            <p class="text-muted">Student Information System & Portal</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4 pb-0">
                <ul class="nav nav-pills nav-fill" id="loginTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $activeTab === 'student' ? 'active' : '' ?>" id="student-tab" data-bs-toggle="pill" data-bs-target="#student-login" type="button" role="tab">
                            <i class="bi bi-person-fill me-1"></i> Student Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $activeTab === 'admin' ? 'active' : '' ?>" id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin-login" type="button" role="tab">
                            <i class="bi bi-shield-lock-fill me-1"></i> Admin Login
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="loginTabContent">
                    <!-- Student Login Pane -->
                    <div class="tab-pane fade <?= $activeTab === 'student' ? 'show active' : '' ?>" id="student-login" role="tabpanel">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="login_type" value="student">
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">ID Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                                    <input type="text" name="id_number" class="form-control" placeholder="e.g. 2026-0001" required value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>">
                                </div>
                                <small class="text-muted">Enter student ID: <code>2026-0001</code></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                                </div>
                                <small class="text-muted">Enter password: <code>password123</code></small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mt-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Access Student Portal
                            </button>
                        </form>
                    </div>

                    <!-- Admin Login Pane -->
                    <div class="tab-pane fade <?= $activeTab === 'admin' ? 'show active' : '' ?>" id="admin-login" role="tabpanel">
                        <form method="POST" action="index.php">
                            <input type="hidden" name="login_type" value="admin">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Admin Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-badge-fill"></i></span>
                                    <input type="text" name="username" class="form-control" placeholder="e.g. admin" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                </div>
                                <small class="text-muted">Default username: <code>admin</code></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Admin Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Enter admin password" required>
                                </div>
                                <small class="text-muted">Default password: <code>admin123</code></small>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold mt-2">
                                <i class="bi bi-shield-check me-1"></i> Log in as Admin
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom mt-3 bg-light border-0">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> System Information:</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li>Database: <strong>MITDQL</strong> | Table: <strong>studinfo</strong></li>
                    <li>Fields: <code>Name</code>, <code>IdNumber</code>, <code>Course</code>, <code>Year</code>, <code>Subject</code>, <code>Image</code>, <code>Grade</code></li>
                    <li>Admin can manage all student records; Students can view their record, upload their photo, and edit their Name only.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
