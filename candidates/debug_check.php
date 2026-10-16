<?php
require_once __DIR__ . '/../inc/db.php';

// 1. Let's create a test user manually
$email = "test@test.com";
$pass = "12345";

// Clear any previous test
$db->prepare("DELETE FROM candidates WHERE email = ?")->execute([$email]);

// Insert plain text
$db->prepare("INSERT INTO candidates (name, email, password, status) VALUES ('Tester', ?, ?, 'approved')")
   ->execute([$email, $pass]);

// 2. Now try to fetch it back
$stmt = $db->prepare("SELECT password FROM candidates WHERE email = ?");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Database Debugger</h3>";
echo "Password we tried to save: <b>$pass</b><br>";
echo "Password actually in DB: <b>" . ($row['password'] ?? 'NOT FOUND') . "</b><br><br>";

if ($row['password'] === $pass) {
    echo "<span style='color:green;'>✅ SUCCESS: Database is saving plain text correctly!</span>";
} else {
    echo "<span style='color:red;'>❌ FAIL: The database is still changing the password. check column length!</span>";
}
?>