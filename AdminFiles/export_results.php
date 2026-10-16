<?php
// admin/export_results.php

session_start();
require_once __DIR__ . '/../inc/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// 1. Set headers to force download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=total_election_results_' . date('Ymd_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// 2. Define CSV column headers
fputcsv($output, array('Position', 'Candidate Name', 'Total Votes'));

// 3. Construct SQL Query to get aggregated results
$sql = "
    SELECT 
        c.position, 
        c.name AS candidate_name, 
        COUNT(v.candidate_id) AS total_votes
    FROM 
        candidates c
    LEFT JOIN 
        votes v ON c.id = v.candidate_id
    GROUP BY 
        c.id, c.position, c.name
    ORDER BY 
        c.position ASC, total_votes DESC
";

// 4. Execute query and write results to CSV
$stmt = $db->query($sql);

if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Write Position, Candidate Name, Total Votes
        fputcsv($output, array(
            $row['position'],
            $row['candidate_name'],
            $row['total_votes']
        ));
    }
}

// 5. Close the output stream
fclose($output);
exit();

?>