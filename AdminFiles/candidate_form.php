<?php
session_start();
require_once '../inc/db.php';

// Block access if payment not done
if (!isset($_SESSION['application_id'], $_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit;
}

$app_id = $_SESSION['application_id'];
$student_id = $_SESSION['student_id'];

$error = "";
$success = "";

// Fetch position name
$stmt = $db->prepare("
    SELECT p.position_name 
    FROM candidate_applications c
    JOIN positions p ON c.position_id = p.id
    WHERE c.id = ?
");
$stmt->execute([$app_id]);
$position = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$position) {
    die("Invalid application.");
}

// Handle form submission
if (isset($_POST['submit_form'])) {

    $name = trim($_POST['full_name']);
    $dept = trim($_POST['department']);
    $cgpa = trim($_POST['cgpa']);

    if (empty($name) || empty($dept) || empty($cgpa)) {
        $error = "All fields are required.";
    } elseif ($cgpa < 0 || $cgpa > 5) {
        $error = "Invalid CGPA.";
    } else {

        // Upload photo
        $photo_name = null;

        if (!empty($_FILES['photo']['name'])) {

            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_name = 'cand_' . time() . '.' . $ext;
            $path = "../uploads/candidates/" . $photo_name;

            move_uploaded_file($_FILES['photo']['tmp_name'], $path);
        }

        // Save details
        $stmt = $db->prepare("
            UPDATE candidate_applications
            SET full_name = ?, department = ?, cgpa = ?, photo = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $dept, $cgpa, $photo_name, $app_id]);

        // Clear application session
        unset($_SESSION['application_id'], $_SESSION['candidate_position_id']);

        header("Location: application_success.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Candidate Application Form</title>
    <style>
        body { font-family: Arial; background: #f4f6f8; }
        .box {
            width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
        }
        button {
            background: #27ae60;
            border: none;
            color: #fff;
            font-size: 16px;
        }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>

<div class="box">
    <h2>Candidate Application Form</h2>
    <p><strong>Position:</strong> <?= htmlspecialchars($position['position_name']) ?></p>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="text" name="department" placeholder="Department" required>
        <input type="number" step="0.01" name="cgpa" placeholder="CGPA (e.g 4.20)" required>
        <input type="file" name="photo" accept="image/*" required>
        <button type="submit" name="submit_form">Submit Application</button>
    </form>
</div>

</body>
</html>
