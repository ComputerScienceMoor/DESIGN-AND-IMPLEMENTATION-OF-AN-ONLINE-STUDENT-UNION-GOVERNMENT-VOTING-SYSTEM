<?php
session_start();
require_once __DIR__ . '/../inc/db.php'; 

// --- Authentication Check ---
if (!isset($_SESSION['candidate_logged_in']) || !isset($_SESSION['candidate_id'])) {
    header("Location: candidate_login.php");
    exit();
}

$candidate_id = $_SESSION['candidate_id'];
$candidate_name = $_SESSION['candidate_name'];

// --- 1. Fetch Candidate Details & Position Name ---
// Using a JOIN to get position_name since candidates table stores position_id
$stmt_info = $db->prepare("
    SELECT c.name, c.photo, c.manifesto, p.position_name, c.position_id 
    FROM candidates c
    JOIN positions p ON c.position_id = p.position_id 
    WHERE c.id = ?
");
$stmt_info->execute([$candidate_id]);
$candidate_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

if (!$candidate_info) {
    session_destroy();
    header("Location: candidate_login.php?error=data_not_found");
    exit();
}

// --- 2. Calculate Total Votes for This Candidate ---
$stmt_votes = $db->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id = ?");
$stmt_votes->execute([$candidate_id]);
$total_candidate_votes = $stmt_votes->fetchColumn();

// --- 3. Calculate Total Votes for This Position ---
$stmt_position_votes = $db->prepare("
    SELECT COUNT(*) 
    FROM votes v
    JOIN candidates c ON c.id = v.candidate_id
    WHERE c.position_id = ?
");
$stmt_position_votes->execute([$candidate_info['position_id']]);
$total_position_votes = $stmt_position_votes->fetchColumn();

// --- 4. Calculate Percentage and Rank ---
$percentage = ($total_position_votes > 0) ? ($total_candidate_votes / $total_position_votes) * 100 : 0;

// Rank Logic: Count how many other candidates in this position have more votes
$stmt_rank = $db->prepare("
    SELECT COUNT(*) + 1 
    FROM (
        SELECT candidate_id, COUNT(*) as vote_count 
        FROM votes 
        GROUP BY candidate_id
    ) AS results
    JOIN candidates c ON c.id = results.candidate_id
    WHERE c.position_id = ? AND results.vote_count > ?
");
$stmt_rank->execute([$candidate_info['position_id'], $total_candidate_votes]);
$candidate_rank = $stmt_rank->fetchColumn();

// --- 5. Prepare Display Values ---
if ($total_candidate_votes > 0) {
    if ($candidate_rank == 1) $rank_display = "🥇 1st Place";
    elseif ($candidate_rank == 2) $rank_display = "🥈 2nd Place";
    elseif ($candidate_rank == 3) $rank_display = "🥉 3rd Place";
    else $rank_display = $candidate_rank . "th Place";
} else {
    $rank_display = "No votes yet";
}

$manifesto_preview = $candidate_info['manifesto'] 
    ? (strlen($candidate_info['manifesto']) > 150 ? substr(strip_tags($candidate_info['manifesto']), 0, 150) . "..." : $candidate_info['manifesto'])
    : 'No manifesto uploaded yet.';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?= htmlspecialchars($candidate_name) ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header-section { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .candidate-profile { display: flex; align-items: center; }
        .candidate-profile img { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; margin-right: 20px; border: 4px solid #2ecc71; }
        .candidate-profile h3 { margin: 0; color: #2c3e50; }
        .candidate-profile p { margin: 5px 0 0; color: #7f8c8d; font-weight: bold; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { padding: 20px; border-radius: 10px; text-align: center; color: white; }
        .votes { background: #3498db; }
        .rank { background: #f1c40f; }
        .percent { background: #2ecc71; }
        .total { background: #95a5a6; }
        .stat-card h4 { margin: 0 0 10px; font-size: 0.9em; text-transform: uppercase; }
        .stat-card .value { font-size: 1.8em; font-weight: bold; }

        .manifesto-box { background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 5px solid #2c3e50; }
        .btn-group { margin-top: 20px; display: flex; gap: 10px; }
        .btn { padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; color: white; }
        .btn-edit { background: #2c3e50; }
        .btn-logout { background: #e74c3c; }
        .btn-setting { background: #17b942ff; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-section">
        <div class="candidate-profile">
            <img src="../uploads/candidates/<?= htmlspecialchars($candidate_info['photo'] ?: 'default.png') ?>" alt="Profile">
            <div>
                <h3><?= htmlspecialchars($candidate_name) ?></h3>
                <p>Position: <?= htmlspecialchars($candidate_info['position_name']) ?></p>
            </div>
        </div>
        <a href="candidate_settings.php" class="btn btn-setting">Settings</a>

        <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card votes">
            <h4>Your Votes</h4>
            <div class="value"><?= $total_candidate_votes ?></div>
        </div>
        <div class="stat-card rank">
            <h4>Current Rank</h4>
            <div class="value"><?= $rank_display ?></div>
        </div>
        <div class="stat-card percent">
            <h4>Vote Share</h4>
            <div class="value"><?= number_format($percentage, 1) ?>%</div>
        </div>
        <div class="stat-card total">
            <h4>Total Pool</h4>
            <div class="value"><?= $total_position_votes ?></div>
        </div>
    </div>

    <div class="manifesto-box">
        <h4>Manifesto Preview</h4>
        <p><?= nl2br(htmlspecialchars($manifesto_preview)) ?></p>
        <div class="btn-group">
            <a href="candidate_manifesto_edit.php" class="btn btn-edit">Edit Manifesto</a>
        </div>
    </div>
</div>

</body>
</html>