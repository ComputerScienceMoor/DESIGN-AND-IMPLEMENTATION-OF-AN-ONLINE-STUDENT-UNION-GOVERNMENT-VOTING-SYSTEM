<?php
session_start();
require_once '../inc/db.php';
require_once '../inc/application_guard.php';

$positions = $db->query("
    SELECT id, position_name, form_price 
    FROM positions 
    WHERE status = 'active'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Apply as Candidate</title>
    <style>
        body { font-family: Arial; background: #f4f6f8; }
        .box { max-width: 700px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; }
        th { background: #2ecc71; color: white; }
        a.btn { background: #3498db; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

<div class="box">
    <h2>Apply as Candidate</h2>

    <table>
        <tr>
            <th>Position</th>
            <th>Form Price</th>
            <th>Action</th>
        </tr>

        <?php foreach ($positions as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['position_name']) ?></td>
            <td>₦<?= number_format($p['form_price']) ?></td>
            <td>
                <a class="btn" href="paystack_initialize.php?position_id=<?= $p['id'] ?>">
                    Apply
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
