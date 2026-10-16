<?php
require_once 'auth_admin.php';
require_once __DIR__ . '/../inc/db.php';

// The fixed query uses v.matric_no to join with s.matric_no

$votes = $db->query("SELECT v.id, v.created_at, s.full_name, s.matric_no, c.name AS candidate, v.voter_ip, v.user_agent
    FROM votes v
    -- FIX: Change join key from s.id to s.matric_no to match v.matric_no
    JOIN students s ON s.matric_no = v.matric_no
    JOIN candidates c ON c.id = v.candidate_id
    ORDER BY v.id DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>View Votes - Audit Log</title>
    <style>
        /* ... (CSS remains the same) ... */
        
* { box-sizing: border-box; margin: 0; padding: 0; }



        body {

            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

            background: #f4f6f8;

            color: #333;

            padding: 40px;

        }



        h2 {

            text-align: center;

            font-size: 36px;

            margin-bottom: 20px;

            color: #2c3e50;

        }



        /* Container for the action buttons */

        .actions {

            display: flex;

            justify-content: flex-start;

            gap: 15px;

            margin-bottom: 30px;

            flex-wrap: wrap;

        }



        .btn-back, .btn-export {

            background: #7f8c8d;

            color: #fff;

            padding: 12px 20px;

            border-radius: 8px;

            text-decoration: none;

            display: inline-block;

            transition: all 0.3s ease;

            font-weight: bold;

        }

        .btn-back:hover { background: #95a5a6; }

        

        .btn-export-voted { 

            background: #27ae60; /* Green for Voted */

        }

        .btn-export-voted:hover { 

            background: #2ecc71; 

        }



        .btn-export-not-voted { 

            background: #e74c3c; /* Red for Not Voted */

        }

        .btn-export-not-voted:hover { 

            background: #c0392b; 

        }



        table {

            width: 100%;

            border-collapse: collapse;

            background: #fff;

            border-radius: 12px;

            overflow: hidden;

            box-shadow: 0 5px 15px rgba(0,0,0,0.05);

        }



        th, td {

            padding: 15px 20px;

            text-align: left;

        }

        

        .audit-cell {

            padding: 10px 15px; 

            font-size: 0.9em;

        }



        th {

            background-color: #27ae60;

            color: #fff;

            font-weight: 600;

        }



        tr:nth-child(even) {

            background-color: #f9f9f9;

        }



        tr:hover {

            background-color: #ecf0f1;

        }



        .user-agent-cell {

            max-width: 250px; 

            overflow: hidden;

            text-overflow: ellipsis; 

            white-space: nowrap; 

            cursor: help; 

        }

        .user-agent-cell:hover {

            white-space: normal;

            overflow: visible;

            max-width: none;

        }
        @media (max-width: 768px) {

            table, th, td {

                font-size: 14px;

            }

        }
    </style>
</head>
<body>

<h2>All Votes (With Audit Log)</h2>

<div class="actions">
    <a href="index.php" class="btn-back">← Back to Dashboard</a>
    
    <a href="export_voted.php" class="btn-export btn-export-voted">✅ Export Voted Students</a>
    <a href="export_not_voted.php" class="btn-export btn-export-not-voted">❌ Export Non-Voted Students</a>
</div>


<table>
    <tr>
        <th>Matric No.</th>
        <th>Voter Name</th> 
        <th>Candidate</th>
        <th>Time</th>
        <th>Voter IP</th>
        <th>Device/User Agent</th>
    </tr>
    <?php if (count($votes) > 0): ?>
        <?php foreach ($votes as $v): ?>
            <tr>
                <td><?php echo htmlspecialchars($v['matric_no']); ?></td> 
                <td><?php echo htmlspecialchars($v['full_name']); ?></td> 
                <td><?php echo htmlspecialchars($v['candidate']); ?></td>
                <td><?php echo htmlspecialchars($v['created_at']); ?></td>
                
                <td class="audit-cell"><?php echo htmlspecialchars($v['voter_ip']); ?></td>
                <td class="audit-cell user-agent-cell" title="<?php echo htmlspecialchars($v['user_agent']); ?>">
                    <?php echo htmlspecialchars($v['user_agent']); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No votes recorded yet.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>