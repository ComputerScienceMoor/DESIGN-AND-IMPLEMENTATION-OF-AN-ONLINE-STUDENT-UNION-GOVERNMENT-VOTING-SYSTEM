<?php
require_once __DIR__ . '/../inc/db.php'; 
$manifesto_text = "Manifesto not found.";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $candidate_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT name, position, manifesto FROM candidates WHERE id = ?");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($candidate) {
        $title = htmlspecialchars($candidate['name']) . ' - ' . htmlspecialchars($candidate['position']) . ' Manifesto';
        $manifesto_text = nl2br(htmlspecialchars($candidate['manifesto'] ?? 'No manifesto provided by this candidate.'));
    } else {
        $title = "Manifesto Not Found";
    }
} else {
    $title = "Error";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f8; }
        .manifesto-box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #3498db; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="manifesto-box">
        <div class="back">
            <a href="vote.php">Back</a>
            
        </div>
        <h1><?php echo $title; ?></h1>
        <p><?php echo $manifesto_text; ?></p>
    </div>
</body>
</html>