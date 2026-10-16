<?php
session_start();
require_once __DIR__ . '/../inc/db.php';

// 1. SECURITY: Ensure candidate has paid before accessing the form
if (!isset($_SESSION['payment_verified'], $_SESSION['candidate_position_id'])) {
    header("Location: choose_position.php");
    exit;
}

// 2. FETCH POSITIONS: For the dropdown menu (FIXED: Added database prefix)
$positions = $db->query("SELECT position_id, position_name FROM student_voting_system.positions ORDER BY position_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply as Candidate</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 20px; }
        .container { width: 100%; max-width: 420px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,.1); }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #34495e; font-size: 14px; }
        input, select, textarea, button { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        textarea { resize: vertical; min-height: 100px; }
        
        button { background: #0fcd4eff; color: #fff; border: none; cursor: pointer; font-weight: bold; transition: background 0.3s ease; }
        button:hover { background: #0a9341ff; }
        
        .helper-text { font-size: 12px; color: #7f8c8d; margin-top: -10px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Apply as Candidate</h2>

    <form method="POST" action="submit.php" enctype="multipart/form-data">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="Enter your full name" required>

        <label>Email Address</label>
        <input type="email" name="email" placeholder="e.g. candidate@example.com" required>

        <label>Phone Number</label>
        <input type="text" name="phone" placeholder="e.g. 08012345678" required>
        
        <label>Position Applied For:</label>
        <select class="form-control" disabled>
            <?php foreach ($positions as $p): ?>
                <option value="<?= $p['position_id'] ?>" <?= ($p['position_id'] == $_SESSION['candidate_position_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['position_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small style="color: #e67e22;">(Position locked based on your payment)</small>

        <input type="hidden" name="position_id" value="<?= $_SESSION['candidate_position_id'] ?>">

        <label>Your Manifesto</label>
        <textarea name="manifesto" placeholder="What are your plans and visions?" required></textarea>

        <label>Profile Photo</label>
        <input type="file" name="photo" accept="image/*" required>
        <p class="helper-text">Please upload a clear passport-sized photograph (JPG/PNG).</p>

        <button type="submit">Submit Application</button>
    </form>
</div>

</body>
</html>