<?php
require_once 'auth_admin.php';
require_once __DIR__ . '/../inc/db.php';

$results = $db->query("SELECT candidates.name, candidates.photo, candidates.position,
    COUNT(votes.id) AS votes
    FROM candidates
    LEFT JOIN votes ON votes.candidate_id = candidates.id
    GROUP BY candidates.id
    ORDER BY votes DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Live Election Results</title>
    <meta http-equiv="refresh" content="5"> <!-- Auto refresh every 5 seconds -->
    <style>
        * { box-sizing: border-box; margin:0; padding:0; }

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

        .btn-back {
            background: #7f8c8d;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        .btn-back:hover { background: #95a5a6; }

        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            width: 300px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }

        .card:hover { transform: scale(1.02); }

        .card img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
        }

        .card-details strong {
            font-size: 18px;
            color: #27ae60;
        }

        .card-details small {
            display: block;
            color: #34495e;
            margin-bottom: 5px;
        }

        .card-details h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .btnfront {
            background: #04bc51;
            color: #fff;
            text-align: center;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .cards-container {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>

<h2>Live Election Results</h2>
<a href="index.php" class="btn-back">← Back to Dashboard</a>

<a href="view_results_chart.php" class="btnfront">View Graphical Results (Charts)</a>

            <a href="total_results.php" class="btnfront">Final Results (CSV)</a>

<div class="cards-container">
    <?php if(count($results) > 0): ?>
        <?php foreach ($results as $r): ?>
            <div class="card">
                <?php if($r['photo'] && file_exists(__DIR__ . '/../uploads/candidates/' . $r['photo'])): ?>
                    <img src="../uploads/candidates/<?php echo htmlspecialchars($r['photo']); ?>" alt="Candidate Photo">
                <?php else: ?>
                    <img src="../uploads/candidates/default.png" alt="No Photo"> <!-- Default placeholder -->
                <?php endif; ?>
                <div class="card-details">
                    <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                    <small><?php echo htmlspecialchars($r['position']); ?></small>
                    <h3><?php echo $r['votes']; ?> votes</h3>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center; color:#7f8c8d;">No candidates found.</p>
    <?php endif; ?>
</div>

</body>
</html>
