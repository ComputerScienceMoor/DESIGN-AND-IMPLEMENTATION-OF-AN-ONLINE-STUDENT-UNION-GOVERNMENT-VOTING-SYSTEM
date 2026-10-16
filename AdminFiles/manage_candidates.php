<?php
session_start();
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../candidates/mailer.php'; 

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// --- 1. HANDLE APPROVAL LOGIC ---
if (isset($_POST['approve_candidate'])) {
    $id = (int)$_POST['candidate_id'];

    try {
        // FIXED: Added cross-database table path
        $stmt = $db->prepare("SELECT * FROM student_voting_system.candidates WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($candidate) {
            try {
                sendCandidateCredentials($candidate['email'], $candidate['name'], $candidate['password']);
            } catch (Exception $mailException) {
                die("<h3>❌ PHPMailer Failure Detected</h3>" .
                    "<strong>Error Message:</strong> " . htmlspecialchars($mailException->getMessage()) . "<br>");
            }

            // FIXED: Added cross-database table path
            $update = $db->prepare("UPDATE student_voting_system.candidates SET status = 'approved' WHERE id = ?");
            $update->execute([$id]);

            header("Location: manage_candidates.php?message=Candidate approved and login email sent!");
            exit();
        } else {
            $message = "<p class='msg error'>Error: Candidate context not found or already verified.</p>";
        }
    } catch (Exception $e) {
        $message = "<p class='msg error'>System Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// --- 2. HANDLE EDIT UPDATE ---
if (isset($_POST['update_candidate'])) {
    $id = (int)$_POST['candidate_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $position = trim($_POST['position']); 
    $manifesto = trim($_POST['manifesto']);
    $new_password = $_POST['new_password'];

    // FIXED: Added cross-database table path
    $sql = "UPDATE student_voting_system.candidates SET name = ?, email = ?, position = ?, manifesto = ?";
    $params = [$name, $email, $position, $manifesto];

    if (!empty($new_password)) {
        $sql .= ", password = ?";
        $params[] = $new_password; 
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        header("Location: manage_candidates.php?message=Updated successfully");
        exit();
    } catch (PDOException $e) {
        $message = "<p class='msg error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// --- 3. DELETE LOGIC ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // FIXED: Added cross-database table path
    $db->prepare("DELETE FROM student_voting_system.candidates WHERE id = ?")->execute([$id]);
    header("Location: manage_candidates.php?message=Candidate deleted");
    exit;
}

// --- 4. FETCH DATA (Segregated by Status) ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// A. Fetch All Pending Requests (FIXED: Added schema prefix)
$pending_sql = "SELECT * FROM student_voting_system.candidates WHERE status = 'pending' ORDER BY id DESC";
$pending_candidates = $db->query($pending_sql)->fetchAll(PDO::FETCH_ASSOC);

// B. Fetch Approved Candidates (FIXED: Added schema prefix)
$approved_sql = "SELECT * FROM student_voting_system.candidates WHERE status = 'approved'";
$params = [];

if (!empty($search)) {
    $approved_sql .= " AND (name LIKE ? OR position LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$approved_sql .= " ORDER BY name ASC";

$stmt_approved = $db->prepare($approved_sql);
$stmt_approved->execute($params);
$approved_candidates = $stmt_approved->fetchAll(PDO::FETCH_ASSOC);

// C. Editing Entity Selection (FIXED: Added schema prefix)
$editing_candidate = null;
if (isset($_GET['edit'])) {
    $editStmt = $db->prepare("SELECT * FROM student_voting_system.candidates WHERE id = ?");
    $editStmt->execute([(int)$_GET['edit']]);
    $editing_candidate = $editStmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_GET['message'])) $message = "<p class='msg success'>".htmlspecialchars($_GET['message'])."</p>";
?>

<!-- HTML display code remains exactly the same as your layout template -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate Management Dashboard</title>
    <style>
        :root { --primary-green: #27ae60; --dark-green: #1e8449; --light-green: #eafaf1; --bg: #f4f7f6; --text: #2c3e50; --warning-orange: #e67e22; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-top: 5px solid var(--primary-green); }
        h2 { color: var(--dark-green); text-align: center; margin-bottom: 30px; font-size: 28px; }
        h3 { border-bottom: 2px solid #eee; padding-bottom: 8px; color: var(--text); margin-top: 40px; }
        .msg { padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: bold; }
        .success { background: var(--light-green); color: var(--dark-green); border: 1px solid var(--primary-green); }
        .error { background: #fdedec; color: #a94442; border: 1px solid #ebccd1; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; border-radius: 10px; overflow: hidden; margin-bottom: 30px; }
        th { background: var(--text); color: white; padding: 15px; text-align: left; }
        .pending-header th { background: var(--warning-orange); }
        td { padding: 15px; border-bottom: 1px solid #eee; background: white; }
        tr:hover td { background: var(--light-green); }
        .img-thumb { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-green); }
        .btn { padding: 8px 15px; border-radius: 5px; text-decoration: none; color: white; font-weight: bold; border: none; cursor: pointer; display: inline-block; font-size: 13px; text-align: center; }
        .btn-edit { background: var(--primary-green); }
        .btn-delete { background: #e74c3c; }
        .btn-approve { background: var(--warning-orange); }
        .edit-box { background: var(--light-green); padding: 25px; border-radius: 12px; margin-bottom: 40px; border: 1px dashed var(--primary-green); }
        input, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .no-records { padding: 20px; text-align: center; font-style: italic; color: #7f8c8d; background: #fafafa; border-radius: 8px; border: 1px solid #eee; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" style="color: var(--primary-green); text-decoration: none;">← Back to Dashboard</a>
    <h2>Candidate Screening Center</h2>
    
    <?= $message ?>

    <?php if ($editing_candidate): ?>
    <div class="edit-box">
        <h3>✍️ Edit Approved Candidate: <?= htmlspecialchars($editing_candidate['name']) ?></h3>
        <form method="POST">
            <input type="hidden" name="candidate_id" value="<?= $editing_candidate['id'] ?>">
            <label>Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($editing_candidate['name']) ?>" required>
            
            <label>Position</label>
            <input type="text" name="position" value="<?= htmlspecialchars($editing_candidate['position'] ?? '') ?>" required>

            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($editing_candidate['email'] ?? '') ?>" required>

            <label>Manifesto</label>
            <textarea name="manifesto" rows="4"><?= htmlspecialchars($editing_candidate['manifesto'] ?? '') ?></textarea>

            <label>Update Password (leave empty to keep current password)</label>
            <input type="text" name="new_password" placeholder="Enter new plain text password">

            <button type="submit" name="update_candidate" class="btn btn-edit" style="width:100%; padding:15px; font-size:15px;">Update Candidate Profile</button>
            <a href="manage_candidates.php" style="display:block; text-align:center; margin-top:10px; color: #666; text-decoration:none;">Cancel Revision</a>
        </form>
    </div>
    <?php endif; ?>

    <h3>📋 Applications Awaiting Review (<?= count($pending_candidates) ?>)</h3>
    <?php if (empty($pending_candidates)): ?>
        <div class="no-records">There are currently no pending applications requiring administrative screening.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr class="pending-header">
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Requested Position</th>
                    <th>Email Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_candidates as $p): ?>
                <tr>
                    <td><img src="../uploads/candidates/<?= htmlspecialchars($p['photo'] ?: 'default.png') ?>" class="img-thumb" style="border-color: var(--warning-orange);"></td>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong><br><small style="color:#7f8c8d;">📞 <?= htmlspecialchars($p['phone'] ?? '') ?></small></td>
                    <td><span style="background: #fff3cd; color: #d35400; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;"><?= htmlspecialchars($p['position'] ?? 'N/A') ?></span></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Approve candidate?');">
                            <input type="hidden" name="candidate_id" value="<?= $p['id'] ?>">
                            <button type="submit" name="approve_candidate" class="btn btn-approve">Approve & Send Mail</button>
                        </form>
                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-delete" onclick="return confirm('Reject application record?')">Reject</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3>🟢 Officially Balloted & Approved Candidates</h3>
    
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Filter active ballot directory by name or position..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn" style="background: var(--text);">Search Filter</button>
        <?php if(!empty($search)): ?>
            <a href="manage_candidates.php" class="btn" style="background:#7f8c8d; line-height:22px;">Reset</a>
        <?php endif; ?>
    </form>

    <?php if (empty($approved_candidates)): ?>
        <div class="no-records">No approved candidates matched your selection criteria.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Email Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($approved_candidates as $c): ?>
                <tr>
                    <td><img src="../uploads/candidates/<?= htmlspecialchars($c['photo'] ?: 'default.png') ?>" class="img-thumb"></td>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><span style="background: var(--light-green); color: var(--dark-green); padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;"><?= htmlspecialchars($c['position'] ?? 'N/A') ?></span></td>
                    <td><?= htmlspecialchars($c['email'] ?? 'No Email Profile Available') ?></td>
                    <td>
                        <a href="?edit=<?= $c['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-delete" onclick="return confirm('Revoke verification?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>