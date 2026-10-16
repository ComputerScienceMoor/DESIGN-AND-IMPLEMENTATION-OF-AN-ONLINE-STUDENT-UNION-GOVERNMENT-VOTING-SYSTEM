<?php
require_once 'auth_admin.php'; // Ensures only admins can access this
require_once __DIR__ . '/../inc/db.php';

// --- TEMPORARY DEBUGGING SETUP ---
// Remove these three lines once you confirm the script is working correctly.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Set PDO to throw exceptions on errors
if (isset($db)) {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
// ----------------------------------


// SQL to fetch all students who have NOT cast a vote.
// This uses a LEFT JOIN and filters for rows where the 'votes' data is NULL.

// IMPORTANT NOTE: The JOIN condition (s.id = v.student_id) assumes:
// 1. 'students' table uses 'id' as its primary key.
// 2. 'votes' table uses 'student_id' as the foreign key referencing 'students.id'.
// Adjust the JOIN key if your schema is different (e.g., ON s.matric_no = v.matric_no).
$sql = "
    SELECT 
        s.matric_no AS Matric_No, 
        s.full_name AS Student_Name 
    FROM 
        students s
    LEFT JOIN 
        votes v ON s.id = v.id 
    WHERE 
        v.id IS NULL
    ORDER BY 
        s.matric_no ASC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Catch and display detailed database errors
    die("Database Error: Could not fetch non-voted students. Check the SQL JOIN condition. Error: " . htmlspecialchars($e->getMessage()));
}

if (empty($results)) {
    die("All students have voted or there are no students registered.");
}

// --- CSV EXPORT LOGIC ---

$filename = "non_voted_students_" . date('Ymd_His') . ".csv";

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// 1. Write column headers using the keys from the first result row
fputcsv($output, array_keys($results[0]));

// 2. Write data rows
foreach ($results as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>