<?php
session_start();
require_once __DIR__ . '/../inc/db.php';

// 1. Protection: Only logged-in admins can see this
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// --- 2. FETCH PENDING APPLICATIONS ---
// We filter for 'pending' so the admin only sees new people who need approval
try {
    $stmt = $db->query("
        SELECT * FROM candidates 
        WHERE status = 'pending' 
        ORDER BY created_at DESC
    ");
    $pending_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback if 'status' column is missing (avoids Fatal Error)
    $pending_apps = [];
    $message = "<p class='msg error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

if (isset($_GET['message'])) {
    $message = "<p class='msg success'>" . htmlspecialchars($_GET['message']) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Applications | Admin</title>
    <style>
        :root {
            --primary-green: #27ae60;
            --dark-green: #1e8449;
            --light-green: #eafaf1;
            --bg: #f4f7f6;
            --text: #2c3e50;
            --danger: #e74c3c;
        }

        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-top: 5px solid var(--primary-green); }
        
        h2 { color: var(--dark-green); text-align: center; margin-bottom: 10px; font-size: 28px; }
        p.subtitle { text-align: center; color: #7f8c8d; margin-bottom: 30px; }
        
        .msg { padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: bold; }
        .success { background: var(--light-green); color: var(--dark-green); border: 1px solid var(--primary-green); }
        .error { background: #fdedec; color: #a94442; border: 1px solid #ebccd1; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; border-radius: 10px; overflow: hidden; }
        th { background: var(--primary-green); color: white; padding: 15px; text-align: left; }
        td { padding: 15px; border-bottom: 1px solid #eee; background: white; vertical-align: middle; }
        tr:hover td { background: var(--light-green); }

        .img-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 2px solid #ddd; }

        /* Action Buttons */
        .btn { padding: 8px 16px; border-radius: 5px; text-decoration: none; color: white; font-weight: bold; border: none; cursor: pointer; display: inline-block; font-size: 13px; transition: 0.2s; }
        .btn-approve { background: var(--primary-green); margin-right: 5px; }
        .btn-approve:hover { background: var(--dark-green); transform: translateY(-1px); }
        .btn-reject { background: var(--danger); }
        .btn-reject:hover { background: #c0392b; transform: translateY(-1px); }
        
        .nav-link { color: var(--primary-green); text-decoration: none; font-weight: bold; }
        .status-badge { background: #f39c12; color: white; padding: 4px 8px; border-radius: 10px; font-size: 11px; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <a href="index.php" class="nav-link">← Dashboard</a>
        <a href="manage_candidates.php" class="nav-link">View Approved Candidates →</a>
    </div>

    <h2>Pending Applications</h2>
    <p class="subtitle">Review new candidate submissions before they go live.</p>
    
    <?= $message ?>

    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Candidate Details</th>
                <th>Position</th>
                <th>Applied Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pending_apps)): ?>
                <?php foreach ($pending_apps as $app): ?>
                <tr>
                    <td>
                        <img src="../uploads/candidates/<?= htmlspecialchars($app['photo'] ?: 'default.png') ?>" class="img-thumb">
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($app['name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($app['email'] ?? 'No email') ?></small>
                    </td>
                    <td>
                        <span style="color: var(--dark-green); font-weight: 500;">
                            <?= htmlspecialchars($app['position'] ?? 'N/A') ?>
                        </span>
                    </td>
                    <td>
                        <small><?= date('M d, Y', strtotime($app['created_at'])) ?></small>
                    </td>
                    <td>
                        <a href="process_application.php?id=<?= $app['id'] ?>&action=approve" 
                           class="btn btn-approve" 
                           onclick="return confirm('Approve this candidate?')">✅ Approve</a>
                        
                        <a href="process_application.php?id=<?= $app['id'] ?>&action=reject" 
                           class="btn btn-reject" 
                           onclick="return confirm('Reject this candidate? This will remove their application.')">❌ Reject</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding: 50px; color: #95a5a6;">
                        <h3>No pending applications found.</h3>
                        <p>All clear! New applications will appear here when submitted.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>