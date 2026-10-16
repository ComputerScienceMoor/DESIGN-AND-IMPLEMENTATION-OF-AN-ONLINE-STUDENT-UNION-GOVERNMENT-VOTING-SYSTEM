<?php
session_start();
require_once __DIR__ . '/../inc/db.php'; 

// Basic admin session security check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$error = "";

/* ==========================================
   ADD NEW STUDENT PROFILE
   ========================================== */
if (isset($_POST['add_student'])) {
    $matric = trim($_POST['matric_no']);
    $email  = trim($_POST['email']);
    $name   = trim($_POST['full_name']);
    $dept   = trim($_POST['department']);

    if (empty($matric) || empty($email) || empty($name)) {
        $error = "Matric Number, Email, and Full Name are required.";
    } else {
        try {
            $db->beginTransaction();

            // 1. Insert into the authoritative central school registry
            $stmt = $db->prepare("INSERT INTO sug_portal.students (matric_no, email, name, department) VALUES (?, ?, ?, ?)");
            $stmt->execute([$matric, $email, $name, $dept]);

            // 2. Create the baseline voter registration tracking tracking stub locally
            $stub = $db->prepare("INSERT IGNORE INTO student_voting_system.students (matric_no) VALUES (?)");
            $stub->execute([$matric]);

            $db->commit();
            $message = "Student profile successfully registered across portals.";
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Registration failed. The Matric Number or Email may already exist.";
        }
    }
}

/* ==========================================
   UPDATE EXISTING STUDENT PROFILE
   ========================================== */
if (isset($_POST['update_student'])) {
    $original_matric = $_POST['original_matric'];
    $matric = trim($_POST['matric_no']);
    $email  = trim($_POST['email']);
    $name   = trim($_POST['full_name']);
    $dept   = trim($_POST['department']);

    if (empty($matric) || empty($email) || empty($name)) {
        $error = "Fields cannot be updated to empty values.";
    } else {
        try {
            $db->beginTransaction();

            // Update primary registry identities
            $stmt = $db->prepare("UPDATE sug_portal.students SET matric_no = ?, email = ?, name = ?, department = ? WHERE matric_no = ?");
            $stmt->execute([$matric, $email, $name, $dept, $original_matric]);

            // If the matric number changed, keep the local election tracking table aligned
            if ($original_matric !== $matric) {
                $stub_update = $db->prepare("UPDATE student_voting_system.students SET matric_no = ? WHERE matric_no = ?");
                $stub_update->execute([$matric, $original_matric]);
            }

            $db->commit();
            $message = "Student metadata changes saved successfully.";
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Failed to update record details: " . $e->getMessage();
        }
    }
}

/* ==========================================
   REMOVE STUDENT RECORD
   ========================================== */
if (isset($_GET['delete'])) {
    $target_matric = $_GET['delete'];
    try {
        $db->beginTransaction();

        // Evict from centralized portal records
        $stmt = $db->prepare("DELETE FROM sug_portal.students WHERE matric_no = ?");
        $stmt->execute([$target_matric]);

        // Evict local tracking stub details safely
        $stub_del = $db->prepare("DELETE FROM student_voting_system.students WHERE matric_no = ?");
        $stub_del->execute([$target_matric]);

        $db->commit();
        $message = "Student record completely purged.";
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Purge execution failure: " . $e->getMessage();
    }
}

/* ==========================================
   SEARCH, FILTERS & LIVE JOIN QUERIES
   ========================================== */
$search = trim($_GET['search'] ?? '');
$deptFilter = trim($_GET['department'] ?? '');

// 🔗 High-Performance Dynamic Cross-Database Join query strategy
$sql = "
    SELECT s.*, 
           (SELECT COUNT(*) FROM student_voting_system.votes v WHERE v.matric_no = s.matric_no) AS has_voted
    FROM sug_portal.students s
    WHERE 1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (s.matric_no LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($deptFilter !== '') {
    $sql .= " AND s.department = ?";
    $params[] = $deptFilter;
}

$sql .= " ORDER BY s.department ASC, s.full_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available departments dynamically for filter headers
$departments = $db->query("
    SELECT DISTINCT department 
    FROM sug_portal.students 
    WHERE department IS NOT NULL AND department != '' 
    ORDER BY department
")->fetchAll(PDO::FETCH_COLUMN);

// Handle active inline row edits
$editing_student = null;
if (isset($_GET['edit'])) {
    $edit_stmt = $db->prepare("SELECT * FROM sug_portal.students WHERE matric_no = ? LIMIT 1");
    $edit_stmt->execute([$_GET['edit']]);
    $editing_student = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students | Election Board Control</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1, h2 { color: #2c3e50; }
        .alert { padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        @media(max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        input, select, button { width: 100%; padding: 10px; margin: 8px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #27ae60; color: white; font-weight: bold; border: none; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #219150; }
        .btn-secondary { background: #7f8c8d; }
        .btn-secondary:hover { background: #6c7a7d; }
        .filter-bar { display: flex; gap: 10px; align-items: center; margin-bottom: 15px; }
        .filter-bar input, .filter-bar select { margin: 0; }
        .filter-bar button { width: auto; margin: 0; padding: 10px 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #2c3e50; color: white; }
        tr:hover { background: #f9f9f9; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-success { background: #e8f5e9; color: #2e7d32; }
        .badge-pending { background: #fff3e0; color: #ef6c00; }
        .action-links a { margin-right: 10px; text-decoration: none; font-weight: bold; }
        .action-edit { color: #2980b9; }
        .action-delete { color: #c0392b; }
    </style>
</head>
<body>

<div class="container">
    <h1>Voter Registry Control Panel</h1>
    <p><a href="index.php">← Back to Dashboard Center</a></p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="grid">
        <!-- FORM WORKSPACE: ADD OR EDIT CONTEXT -->
        <div class="card">
            <?php if ($editing_student): ?>
                <h2>Modify Student Record</h2>
                <form method="POST" action="manage_students.php">
                    <input type="hidden" name="original_matric" value="<?= htmlspecialchars($editing_student['matric_no']) ?>">
                    
                    <label>Matric Number</label>
                    <input type="text" name="matric_no" value="<?= htmlspecialchars($editing_student['matric_no']) ?>" required>
                    
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($editing_student['name']) ?>" required>
                    
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($editing_student['email']) ?>" required>
                    
                    <label>Academic Department</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($editing_student['department']) ?>">
                    
                    <button type="submit" name="update_student">Save Changes</button>
                    <a href="manage_students.php"><button type="button" class="btn-secondary">Cancel Edit</button></a>
                </form>
            <?php else: ?>
                <h2>Register New Student</h2>
                <form method="POST" action="manage_students.php">
                    <label>Matric Number</label>
                    <input type="text" name="matric_no" placeholder="e.g. HND/24/COM/002" required>
                    
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="Surname Firstname" required>
                    
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="student@school.edu.ng" required>
                    
                    <label>Academic Department</label>
                    <input type="text" name="department" placeholder="e.g. Computer Science">
                    
                    <button type="submit" name="add_student">Enroll Student Profile</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- REGISTRY ROSTER MANAGEMENT OVERVIEW -->
        <div class="card">
            <h2>Active Roster Data Registry</h2>
            
            <form method="GET" class="filter-bar">
                <input type="text" name="search" placeholder="Search by name or matric..." value="<?= htmlspecialchars($search) ?>">
                <select name="department">
                    <option value="">-- All Departments --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= $deptFilter === $dept ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filter</button>
                <?php if ($search || $deptFilter): ?>
                    <a href="manage_students.php"><button type="button" class="btn-secondary">Clear</button></a>
                <?php endif; ?>
            </form>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Matric Number</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Ballot Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#999;">No student profiles found matching criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['matric_no']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['department'] ?? 'General') ?></td>
                                    <td>
                                        <?php if ($row['has_voted'] > 0): ?>
                                            <span classs="badge badge-success">Voted</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending">Not Voted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-links">
                                        <a href="manage_students.php?edit=<?= urlencode($row['matric_no']) ?>" class="action-edit">Edit</a>
                                        <a href="manage_students.php?delete=<?= urlencode($row['matric_no']) ?>" 
                                           class="action-delete" 
                                           onclick="return confirm('Are you completely sure you want to remove this record profile? This actions purges local system stubs.');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>/