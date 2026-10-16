<?php
require_once 'auth_admin.php';
require_once __DIR__ . '/../inc/db.php';

if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);

    // Photo upload
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $newName = 'cand_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../uploads/candidates/' . $newName);
        $photo = $newName;
    }

    $stmt = $db->prepare("INSERT INTO candidates (name, position, photo) VALUES (?, ?, ?)");
    $stmt->execute([$name, $position, $photo]);
}
?><!DOCTYPE html><html>
<head>
<title>Add Candidate With Photo</title>
</head>
<body>
<h2>Add Candidate With Photo</h2>
<a href="index.php">Back</a><form method="post" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Name" required /><br>
    <input type="text" name="position" placeholder="Position" required /><br>
    <input type="file" name="photo" accept="image/*" required /><br><br>
    <button name="add">Add Candidate</button>
</form>
</body>
</html>