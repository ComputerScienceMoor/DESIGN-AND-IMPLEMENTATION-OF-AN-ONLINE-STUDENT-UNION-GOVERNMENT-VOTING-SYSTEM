<?php
require_once 'auth_admin.php';
require_once __DIR__ . '/../inc/db.php';

// ADD NEW CANDIDATE
$message = "";

if (isset($_POST['add_candidate'])) {
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);
    $photo = "";

    // PHOTO UPLOAD
    if (!empty($_FILES['photo']['name'])) {
        $targetDir = __DIR__ . '/../uploads/candidates/';
        $fileName = time() . '_' . basename($_FILES['photo']['name']);
        $targetFile = $targetDir . $fileName;

        // Create folder if missing
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
            $photo = $fileName;
        } else {
            $message = "<p style='color:red;'>Failed to upload image.</p>";
        }
    }

    // Insert to DB
    $stmt = $db->prepare("INSERT INTO candidates (name, position, photo) VALUES (?, ?, ?)");
    $stmt->execute([$name, $position, $photo]);
    $message = "<p style='color:green;'>Candidate added successfully!</p>";
}


// DELETE CANDIDATE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Remove image file
    $photo = $db->query("SELECT photo FROM candidates WHERE id = $id")->fetchColumn();
    if ($photo && file_exists(__DIR__ . '/../uploads/candidates/' . $photo)) {
        unlink(__DIR__ . '/../uploads/candidates/' . $photo);
    }

    // Delete DB row
    $db->query("DELETE FROM candidates WHERE id = $id");

    header("Location: manage_candidates.php");
    exit();
}

// FETCH CANDIDATES
$candidates = $db->query("SELECT * FROM candidates ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Candidates</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f4;
        margin: 0;
    }
    .container {
        width: 90%;
        max-width: 1000px;
        margin: 40px auto;
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
    }
    form {
        margin-bottom: 30px;
    }
    input, select {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 16px;
    }
    button {
        background: #04bc51;
        padding: 12px 25px;
        border: none;
        color: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
    }
    button:hover {
        background: #059c45;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
    }
    table th, table td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        text-align: center;
    }
    table img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
    }
    .delete-btn {
        background: red;
        color: white;
        padding: 7px 15px;
        border-radius: 5px;
        text-decoration: none;
    }
    .delete-btn:hover {
        background: darkred;
    }
    .back {
        display: inline-block;
        margin-bottom: 20px;
        color: #333;
        text-decoration: none;
        font-weight: bold;
    }
</style>
</head>
<body>

<div class="container">

    <a href="index.php" class="back">⬅ Back to Dashboard</a>

    <h2>Manage Candidates</h2>

    <?php echo $message; ?>

    <!-- ADD NEW CANDIDATE -->
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Candidate Name" required>
        <input type="text" name="position" placeholder="Position (e.g., President)" required>
        <input type="file" name="photo" accept="image/*" required>
        <button type="submit" name="add_candidate">Add Candidate</button>
    </form>

    <!-- CANDIDATE LIST -->
    <table>
        <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Position</th>
            <th>Action</th>
        </tr>

        <?php foreach ($candidates as $c): ?>
            <tr>
                <td>
                    <img src="../uploads/candidates/<?php echo $c['photo']; ?>" alt="Photo">
                </td>
                <td><?php echo htmlspecialchars($c['name']); ?></td>
                <td><?php echo htmlspecialchars($c['position']); ?></td>
                <td>
                    <a class="delete-btn" href="?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete this candidate?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>

</div>

</body>
</html>
