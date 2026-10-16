<?php
session_start();
// Use the relative path to your db.php file
require_once __DIR__ . '/../inc/db.php';

// SECURITY: Only admin can access this file
if (!isset($_SESSION['admin_logged_in'])) {
    // Redirect back to login if not authenticated
    header("Location: admin_login.php");
    exit();
}

// -----------------------------------------------------------
// 1. Set CSV Headers for Download
// -----------------------------------------------------------
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=departmental_votes_' . date('Ymd_His') . '.csv');

// Open the output stream
$output = fopen('php://output', 'w');

// -----------------------------------------------------------
// 2. Define CSV Column Headers
// -----------------------------------------------------------
$header_row = [
    'Candidate Name',
    'Position',
    'Department',
    'Votes'
];
fputcsv($output, $header_row);


// -----------------------------------------------------------
// 3. Execute the Departmental Votes Query
// -----------------------------------------------------------
$query = "
SELECT 
    c.name AS candidate_name,
    c.position, 
    s.department,
    COUNT(v.id) AS vote_count
FROM votes v
JOIN students s ON v.matric_no = s.matric_no
JOIN candidates c ON v.candidate_id = c.id
GROUP BY c.id, s.department
ORDER BY candidate_name, department
";

// Use $db->query for PDO and then iterate over the results
$stmt = $db->query($query);

// -----------------------------------------------------------
// 4. Output Data Rows
// -----------------------------------------------------------
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data_row = [
        $row['candidate_name'],
        $row['position'],
        $row['department'],
        $row['vote_count']
    ];
    fputcsv($output, $data_row);
}

// -----------------------------------------------------------
// 5. Close the stream and exit
// -----------------------------------------------------------
fclose($output);
exit();
?>