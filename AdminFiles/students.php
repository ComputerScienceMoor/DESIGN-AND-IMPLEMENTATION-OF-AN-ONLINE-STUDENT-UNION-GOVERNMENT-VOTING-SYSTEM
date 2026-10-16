<?php
include "config.php"; // DB connection

// ADD NEW STUDENT
if (isset($_POST['add_student'])) {

    $name = $_POST['name'];
    $matric = $_POST['matric'];
    $department = $_POST['department'];
    $level = $_POST['level'];

    $stmt = $conn->prepare("INSERT INTO students (name, matric, department, level) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $matric, $department, $level);

    if ($stmt->execute()) {
        echo "<script>alert('Student Added Successfully'); window.location='manage_students.php';</script>";
    } else {
        echo "<script>alert('Error Adding Student');</script>";
    }
}

// DELETE STUDENT
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $conn->query("DELETE FROM students WHERE id='$id'");
    echo "<script>alert('Student Removed'); window.location='manage_students.php';</script>";
}

// FETCH ALL STUDENTS
$students = $conn->query("SELECT * FROM students ORDER BY id DESC");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Students</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
        th { background: #333; color: #fff; }
        form { margin-bottom: 40px; }
        input, select { padding: 8px; width: 100%; margin-bottom: 10px; }
        button { padding: 10px 20px; background: #333; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #444; }
    </style>
</head>
<body>

<h2>Add New Student</h2>

<form method="POST">
    <input type="text" name="name" placeholder="Student Full Name" required>

    <input type="text" name="matric" placeholder="Matric Number" required>

    <input type="text" name="department" placeholder="Department" required>

    <input type="text" name="level" placeholder="Level (e.g. ND1, ND2, HND1)" required>

    <button type="submit" name="add_student">Add Student</button>
</form>

<h2>Students List</h2>

<table>
    <tr>
        <th>Name</th>
        <th>Matric</th>
        <th>Department</th>
        <th>Level</th>
        <th>Action</th>
    </tr>

    <?php while ($row = $students->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['name']; ?></td>
        <td><?php echo $row['matric']; ?></td>
        <td><?php echo $row['department']; ?></td>
        <td><?php echo $row['level']; ?></td>

        <td>
            <a href="edit_student.php?id=<?php echo $row['id']; ?>" style="color:green;">Edit</a> |
            <a href="manage_students.php?delete=<?php echo $row['id']; ?>"
               onclick="return confirm('Remove this student?');"
               style="color:red;">Delete</a>
        </td>
    </tr>
    <?php } ?>
</table>

</body>
</html>
