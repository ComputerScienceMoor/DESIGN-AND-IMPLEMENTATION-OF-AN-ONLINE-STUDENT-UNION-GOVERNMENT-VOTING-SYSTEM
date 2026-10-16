<?php
session_start();
require_once __DIR__ . '/../inc/db.php';

// 1. Protection
if (!isset($_SESSION['candidate_logged_in'], $_SESSION['candidate_id'])) {
    header("Location: candidate_login.php");
    exit();
}

$candidate_id = $_SESSION['candidate_id'];
$success = "";
$error = "";

// 2. Fetch Current Data
$stmt = $db->prepare("SELECT email, photo FROM candidates WHERE id = ?");
$stmt->execute([$candidate_id]);
$candidate = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Handle Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = trim($_POST['email']);
    $new_pass  = trim($_POST['new_password']); // Added trim
    $confirm_pass = trim($_POST['confirm_password']);
    $upload_ok = true;
    $photo_name = $candidate['photo']; 

    // --- A. Handle Photo Upload ---
    if (!empty($_FILES['new_photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['new_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            $error = "Only JPG and PNG photos are allowed.";
            $upload_ok = false;
        } else {
            $photo_name = time() . "_candidate_" . $candidate_id . "." . $ext;
            $target = "../uploads/candidates/" . $photo_name;

            if (move_uploaded_file($_FILES['new_photo']['tmp_name'], $target)) {
                if ($candidate['photo'] && file_exists("../uploads/candidates/" . $candidate['photo'])) {
                    unlink("../uploads/candidates/" . $candidate['photo']);
                }
            } else {
                $error = "Failed to upload new photo.";
                $upload_ok = false;
            }
        }
    }

    // --- B. Handle Text/Password Updates ---
    if ($upload_ok) {
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            // Check if password is being changed
            if (!empty($new_pass)) {
                if ($new_pass !== $confirm_pass) {
                    $error = "New passwords do not match.";
                } elseif (strlen($new_pass) < 4) { // Lowered for your testing
                    $error = "Password must be at least 4 characters.";
                } else {
                    // ✅ UPDATED: Saving as PLAIN TEXT to match your login page
                    $stmt = $db->prepare("UPDATE candidates SET email = ?, password = ?, photo = ? WHERE id = ?");
                    $stmt->execute([$new_email, $new_pass, $photo_name, $candidate_id]);
                    $success = "Profile and Password updated successfully!";
                }
            } else {
                // Update email and photo only (keep old password)
                $stmt = $db->prepare("UPDATE candidates SET email = ?, photo = ? WHERE id = ?");
                $stmt->execute([$new_email, $photo_name, $candidate_id]);
                $success = "Profile updated successfully!";
            }
            
            // Refresh local data for the form
            $candidate['email'] = $new_email;
            $candidate['photo'] = $photo_name;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - FCAHPT Votes</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f8; margin: 0; padding: 40px; }
        .settings-box { max-width: 500px; margin: 0 auto; background: white; padding: 35px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 25px; }
        .photo-container { text-align: center; margin-bottom: 25px; }
        .current-photo { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #27ae60; margin-bottom: 10px; }
        label { display: block; margin-top: 15px; font-weight: 600; color: #34495e; font-size: 14px; }
        input { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        .btn-save { background: #27ae60; color: white; border: none; padding: 14px; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 30px; font-size: 16px; font-weight: bold; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #7f8c8d; }
    </style>
</head>
<body>

<div class="settings-box">
    <h2>Account Settings</h2>

    <?php if ($success): ?>
        <div class="alert success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="photo-container">
            <img src="../uploads/candidates/<?= htmlspecialchars($candidate['photo'] ?: 'default.png') ?>?t=<?= time() ?>" class="current-photo">
            <label for="file-upload" style="color: #27ae60; cursor: pointer; display: block;">Change Profile Photo</label>
            <input id="file-upload" type="file" name="new_photo" accept="image/*" style="display:none;">
        </div>

        <label>Email Address</label>
        <input type="email" name="email" value="<?= htmlspecialchars($candidate['email']) ?>" required>

        <label>New Password</label>
        <input type="password" name="new_password" placeholder="Leave blank to keep current">

        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat new password">

        <button type="submit" class="btn-save">Save Changes</button>
    </form>

    <a href="candidate_dashboard.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>