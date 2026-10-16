<?php
require_once 'auth_admin.php';
require_once __DIR__ . '/../inc/db.php';

// --- 1. FETCH ALL VOTES COUNT ---
// Get the total number of votes cast across ALL positions to calculate percentages
try {
    $total_votes_overall = $db->query("SELECT COUNT(id) FROM votes")->fetchColumn();
} catch (PDOException $e) {
    // Handle error if the votes table is missing or inaccessible
    die("Error fetching total votes: " . htmlspecialchars($e->getMessage()));
}


// --- 2. FETCH RESULTS GROUPED BY POSITION ---
// This complex query performs three steps:
// 1. LEFT JOIN: Links candidates to votes.
// 2. COUNT(v.id): Counts the votes for each candidate.
// 3. GROUP BY: Groups the results by position and candidate.
$sql = "
    SELECT 
        c.position, 
        c.name AS candidate_name, 
        c.photo,
        COUNT(v.id) AS vote_count
    FROM 
        candidates c
    LEFT JOIN 
        votes v ON v.candidate_id = c.id
    GROUP BY 
        c.position, c.name, c.photo
    ORDER BY 
        c.position ASC, vote_count DESC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $raw_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching results: " . htmlspecialchars($e->getMessage()));
}

// --- 3. PROCESS RESULTS AND GROUP BY POSITION ---
$results_by_position = [];
$total_votes_per_position = [];

foreach ($raw_results as $row) {
    $position = $row['position'];
    $vote_count = (int)$row['vote_count'];

    // Group results by position
    $results_by_position[$position][] = $row;
    
    // Calculate total votes for the current position (summing up candidates' votes)
    $total_votes_per_position[$position] = ($total_votes_per_position[$position] ?? 0) + $vote_count;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Election Results by Position</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 40px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 10px; }
        h3 { color: #04bc51; border-bottom: 3px solid #04bc51; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; font-size: 24px; }
        .btn-back { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background: #7f8c8d; color: white; text-decoration: none; border-radius: 5px; }
        .total-info { text-align: center; margin-bottom: 30px; padding: 15px; background: #ecf0f1; border-radius: 5px; font-weight: bold; }
        .candidate-card {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: box-shadow 0.2s;
        }
        .candidate-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .candidate-card img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: 15px; }
        .candidate-info { flex-grow: 1; }
        .candidate-info strong { font-size: 1.1em; color: #27ae60; }
        .vote-count { font-size: 1.2em; font-weight: bold; color: #e74c3c; margin-right: 20px; text-align: right; width: 100px; }
        .percentage-bar {
            background: #eee;
            border-radius: 5px;
            height: 10px;
            width: 100%;
            margin-top: 5px;
        }
        .percentage-fill {
            height: 100%;
            background: #2ecc71;
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        .winner {
            border: 2px solid gold;
            background-color: #ffffe0;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="btn-back">← Back to Dashboard</a>
    <h2>🗳️ Official Election Results</h2>

    <div class="total-info">
        Total Votes Cast Across All Positions: <?php echo $total_votes_overall; ?>
    </div>
    
    <?php if (empty($results_by_position)): ?>
        <p style="text-align: center;">No candidates or votes recorded yet.</p>
    <?php else: ?>
        <?php foreach ($results_by_position as $position => $candidates): ?>
            <?php 
                $position_total_votes = $total_votes_per_position[$position];
                // Sort candidates within the loop to find the winner for highlighting
                usort($candidates, function($a, $b) {
                    return $b['vote_count'] <=> $a['vote_count'];
                });
                $winner_count = $candidates[0]['vote_count'];
            ?>
            
            <h3><?php echo htmlspecialchars($position); ?> (Total Votes: <?php echo $position_total_votes; ?>)</h3>
            
            <?php foreach ($candidates as $candidate): ?>
                <?php 
                    $vote_count = (int)$candidate['vote_count'];
                    // Calculate percentage based on total votes for THIS position
                    $percentage = ($position_total_votes > 0) ? ($vote_count / $position_total_votes) * 100 : 0;
                    $is_winner = $vote_count > 0 && $vote_count === $winner_count;
                ?>
                <div class="candidate-card <?php echo $is_winner ? 'winner' : ''; ?>">
                    <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" alt="Photo">
                    
                    <div class="candidate-info">
                        <strong><?php echo htmlspecialchars($candidate['candidate_name']); ?></strong>
                        <div class="percentage-bar">
                            <div class="percentage-fill" style="width: <?php echo round($percentage); ?>%;"></div>
                        </div>
                    </div>
                    
                    <div class="vote-count">
                        <?php echo $vote_count; ?> Votes<br>
                        (<?php echo number_format($percentage, 1); ?>%)
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

</body>
</html>