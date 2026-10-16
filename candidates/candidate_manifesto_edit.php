<?php
session_start();
require_once __DIR__ . '/../inc/db.php'; 

// 1. Authentication check
if (!isset($_SESSION['candidate_logged_in']) || !isset($_SESSION['candidate_id'])) {
    header("Location: candidate_login.php");
    exit();
}

$candidate_id = $_SESSION['candidate_id'];
$success_msg = '';
$error_msg = '';

// --- Handle Manifesto Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_manifesto'])) {
    // Sanitizing input
    $new_manifesto = trim($_POST['manifesto']); 
    
    if (empty($new_manifesto)) {
        $error_msg = "Manifesto cannot be empty.";
    } elseif (strlen($new_manifesto) > 5000) {
        $error_msg = "Manifesto is too long (max 5000 characters).";
    } else {
        try {
            $stmt = $db->prepare("UPDATE candidates SET manifesto = ? WHERE id = ?");
            if ($stmt->execute([$new_manifesto, $candidate_id])) {
                $success_msg = "✅ Your manifesto has been updated successfully!";
            } else {
                $error_msg = "Failed to update manifesto. Please try again.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// --- Fetch Current Manifesto ---
$stmt = $db->prepare("SELECT manifesto, name FROM candidates WHERE id = ?");
$stmt->execute([$candidate_id]);
$candidate_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$candidate_data) {
    die("Candidate data not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Manifesto - <?= htmlspecialchars($candidate_data['name']) ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f8; margin: 0; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 35px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f0f2f5; padding-bottom: 15px; }
        h2 { color: #2c3e50; margin: 0; font-size: 22px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 15px; text-align: center; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        label { display: block; margin-bottom: 10px; font-weight: 600; color: #34495e; }
        textarea { width: 100%; min-height: 350px; padding: 15px; border-radius: 8px; border: 1px solid #ddd; font-size: 16px; font-family: inherit; line-height: 1.6; box-sizing: border-box; transition: border-color 0.3s; }
        textarea:focus { border-color: #3498db; outline: none; }
        
        .btn-save { padding: 14px 25px; background: #27ae60; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background 0.3s; width: 100%; margin-top: 15px; }
        .btn-save:hover { background: #219150; }
        
        .back-link { text-decoration: none; color: #3498db; font-weight: 500; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
        
        .char-count { text-align: right; color: #95a5a6; font-size: 12px; margin-top: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <h2>✍️ Edit Manifesto</h2>
        <a href="candidate_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert success"><?= $success_msg ?></div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert error"><?= $error_msg ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="manifesto">Your Campaign Vision & Promises</label>
        <textarea id="manifesto" name="manifesto" placeholder="Type your manifesto here..."><?= htmlspecialchars($candidate_data['manifesto'] ?? ''); ?></textarea>
        <div class="char-count">Recommended: 500 - 2000 characters.</div>
        
        <button type="submit" name="update_manifesto" class="btn-save">Save Manifesto</button>
    </form>
</div>

</body>
</html>