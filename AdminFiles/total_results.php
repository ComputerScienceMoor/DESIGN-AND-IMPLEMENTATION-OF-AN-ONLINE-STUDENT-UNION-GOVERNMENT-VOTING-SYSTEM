<?php
// admin/view_results.php

session_start();
require_once __DIR__ . '/../inc/db.php';

// SECURITY: Only admin can access
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// -----------------------------------------------------------
// 1. PATH DEFINITIONS 
// -----------------------------------------------------------
// HTML Source Path: Since this file is in 'admin/', we need '../uploads/candidates/'
$html_image_base_path = '../uploads/candidates/'; 
// PHP Server Path (for file_exists check)
$server_image_base_path = __DIR__ . '/../uploads/candidates/';


// -----------------------------------------------------------
// Query 2: Total votes for each candidate (Primary Results)
// -----------------------------------------------------------
$totalQuery = "
SELECT 
    c.id AS candidate_id,
    c.name AS candidate_name,
    c.photo,
    c.position,           
    COUNT(v.id) AS total_votes
FROM candidates c
LEFT JOIN votes v ON v.candidate_id = c.id
GROUP BY c.id, c.name, c.photo, c.position
ORDER BY total_votes DESC
";

$results_total = $db->query($totalQuery)->fetchAll(PDO::FETCH_ASSOC);

// You can add back the departmental query if needed, but for 'Total Results', this one is key.
?>

<!DOCTYPE html>
<html>
<head>
    <title>Total Election Results</title>
    <style>
        /* ... (Your style block is unchanged) ... */
        body { font-family: Arial; background: #f2f2f2; padding: 20px; }
        h2 { text-align: center; }
        .section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .back-link {
            display: block; width: 250px; margin: 0 auto 30px; padding: 10px 15px; text-align: center;
            background: #7f8c8d; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #27ae60; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        img.photo { width: 70px; height: 70px; border-radius: 8px; object-fit: cover; }
        .text-center { text-align: center; } 
        .no-photo { color: #7f8c8d; font-size: 12px; display: block; line-height: 70px; height: 70px; width: 70px; margin: 0 auto; border: 1px solid #eee; border-radius: 8px; background-color: #fafafa; }
        .section h3 { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .export-btn {
            padding: 8px 15px; background: #04bc51; color: white; text-decoration: none;
            border-radius: 6px; font-size: 14px; font-weight: 600; transition: background 0.3s;
        }
        
        .export-btn:hover { background: #059c45; }
    </style>
</head>
<body>

<h2>Total Election Results</h2>

<a href="index.php" class="back-link">← Back to Dashboard</a>
<a href="election_results_live.php" class="back-link">← Back</a>

<div class="section">
    <h3>
        Total Votes Per Candidate
        <a href="export_results.php" class="export-btn">⬇ Download Full Results (CSV)</a>
    </h3>
    <table>
        <tr>
            <th class="text-center">Photo</th>
            <th>Candidate</th>
            <th>Position</th>          
            <th class="text-center">Total Votes</th>
        </tr>

        <?php foreach ($results_total as $row) { 
            $img_filename = $row['photo'];
            $img_src_html = $html_image_base_path . htmlspecialchars($img_filename);
        ?>
            <tr>
                <td class="text-center">
                    <?php 
                    // Check if the file physically exists on the server
                    if ($img_filename && file_exists($server_image_base_path . $img_filename)): ?>
                        <img src="<?php echo $img_src_html; ?>" class="photo" alt="<?php echo htmlspecialchars($row['candidate_name']); ?> Photo">
                    <?php else: ?>
                        <span class="no-photo">No Photo</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['candidate_name']); ?></td>
                <td><?php echo htmlspecialchars($row['position']); ?></td> 
                <td class="text-center"><?php echo htmlspecialchars($row['total_votes']); ?></td>
            </tr>
        <?php } ?>
    </table>
</div>

</body>
</html>