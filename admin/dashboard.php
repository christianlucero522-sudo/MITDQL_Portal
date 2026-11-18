<?php
$basePath = '../';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pdo = getPDO();
$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM studinfo 
        WHERE Name LIKE ? OR IdNumber LIKE ? OR Course LIKE ? OR Subject LIKE ?
        ORDER BY id DESC
    ");
    $term = "%$search%";
    $stmt->execute([$term, $term, $term, $term]);
    $students = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM studinfo ORDER BY id DESC");
    $students = $stmt->fetchAll();
}

// Quick stats
$totalStudents = count($students);

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold mb-1">Student Management</h3>
        <p class="text-muted mb-0">Database: <strong>MITDQL</strong> &bull; Table: <strong>studinfo</strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="add_student.php" class="btn btn-primary fw-semibold">
            <i class="bi bi-person-plus-fill me-1"></i> Add New Student
        </a>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" action="dashboard.php" class="row g-2 align-items-center">
            <div class="col-md-9 col-lg-10">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by Student Name, ID Number, Course, or Subject..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-semibold">Filter</button>
                <?php if (!empty($search)): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Student Records Table -->
<div class="card card-custom">
    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
        <span class="fw-bold text-secondary">
            <i class="bi bi-table me-2"></i> All Enrolled Students (<?= $totalStudents ?>)
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($students)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people text-muted display-4"></i>
                <p class="mt-3 text-muted mb-2">No student records found <?= !empty($search) ? 'matching "' . htmlspecialchars($search) . '"' : '' ?>.</p>
                <a href="add_student.php" class="btn btn-primary btn-sm">Add First Student</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Photo</th>
                            <th>ID Number</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Subject</th>
                            <th>Grade</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stud): 
                            $img = !empty($stud['Image']) ? $stud['Image'] : 'default_avatar.svg';
                            $imgSrc = $basePath . 'uploads/' . htmlspecialchars($img);
                            if (!file_exists(__DIR__ . '/../uploads/' . $img)) {
                                $imgSrc = $basePath . 'uploads/default_avatar.svg';
                            }
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <img src="<?= $imgSrc ?>" alt="Avatar" class="student-avatar-sm">
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace">
                                        <?= htmlspecialchars($stud['IdNumber']) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($stud['Name']) ?>
                                </td>
                                <td><?= htmlspecialchars($stud['Course']) ?></td>
                                <td><?= htmlspecialchars($stud['Year']) ?></td>
                                <td><?= htmlspecialchars($stud['Subject']) ?></td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <?= htmlspecialchars($stud['Grade']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_student.php?id=<?= $stud['id'] ?>" class="btn btn-outline-primary" title="Edit Student">
                                            <i class="bi bi-pencil-fill"></i> Edit
                                        </a>
                                        <a href="delete_student.php?id=<?= $stud['id'] ?>&csrf_token=<?= generateCsrfToken() ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Delete Student"
                                           onclick="return confirm('Are you sure you want to delete student <?= htmlspecialchars(addslashes($stud['Name'])) ?> (ID: <?= htmlspecialchars(addslashes($stud['IdNumber'])) ?>)? This action cannot be undone.');">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
