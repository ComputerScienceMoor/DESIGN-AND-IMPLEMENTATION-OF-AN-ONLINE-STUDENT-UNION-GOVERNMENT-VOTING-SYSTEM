<?php
require_once 'auth_admin.php'; // Ensures only admins can access this
require_once __DIR__ . '/../inc/db.php';

// --- TEMPORARY DEBUGGING SETUP (KEEP THIS IN UNTIL THE ERROR IS RESOLVED) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set PDO to throw exceptions on errors
if (isset($db)) {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
// -----------------------------------------------------------------------------

// SQL to fetch all students who have cast a vote.
// FIXED: Explicit schema targeting added & JOIN fixed to link matric strings together
$sql = "
    SELECT 
        s.matric_no AS Matric_No, 
        s.full_name AS Student_Name,
        c.name AS Candidate_Voted_For,
        v.created_at AS Vote_Time,
        v.voter_ip AS Voter_IP
    FROM 
        student_voting_system.votes v
    JOIN 
        student_voting_system.students s ON s.matric_no = v.matric_no    /* FIXED JOIN RELATION */
    JOIN 
        student_voting_system.candidates c ON c.id = v.candidate_id
    ORDER BY 
        s.matric_no ASC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Catch and display detailed database errors instead of crashing silently
    die("Database Error: Could not fetch voted students. Check your SQL JOIN. Error: " . htmlspecialchars($e->getMessage()));
}

if (empty($results)) {
    die("No voted students to export.");
}

// --- CSV EXPORT LOGIC ---

$filename = "voted_students_" . date('Ymd_His') . ".csv";

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// 1. Write column headers
// Check if results array has any elements before trying to access index 0
if (!empty($results)) {
    fputcsv($output, array_keys($results[0]));
}

// 2. Write data rows
foreach ($results as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>