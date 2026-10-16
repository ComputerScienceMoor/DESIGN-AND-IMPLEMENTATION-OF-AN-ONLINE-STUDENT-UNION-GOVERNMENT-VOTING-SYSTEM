<?php
session_start();
require_once __DIR__ . '/../inc/db.php';

// Fetch all available positions and their prices
$positions = $db->query("SELECT * FROM positions ORDER BY position_name ASC")
                ->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply as Candidate - Choose Position</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f4f6f8; 
            margin: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
        }
        
        .box { 
            width: 100%;
            max-width: 420px; 
            background: #fff; 
            padding: 35px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.08); 
            border-top: 5px solid #27ae60;
        }

        h2 { 
            text-align: center; 
            color: #2c3e50; 
            margin-bottom: 10px; 
            font-size: 24px;
        }

        p.subtitle {
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* Added styling for the email input to match the select box */
        input[type="email"], select {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            font-size: 16px;
            margin-bottom: 20px;
            color: #2c3e50;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus, select:focus {
            border-color: #27ae60;
            outline: none;
        }

        button { 
            padding: 15px; 
            width: 100%; 
            background: #27ae60; 
            color: #fff; 
            border: none; 
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover { 
            background: #219150; 
        }

        .label-text {
            display:block; 
            margin-bottom:8px; 
            font-weight:600; 
            color:#34495e; 
            font-size:14px;
        }

        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #bdc3c7;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Choose Position</h2>
    <p class="subtitle">Enter your email and select an office to proceed to payment.</p>

    <form method="POST" action="pay.php">
        <label class="label-text">Email Address:</label>
        <input type="email" name="email" placeholder="Enter your active email" required>

        <label class="label-text">Available Offices:</label>
        <select name="position_id" required>
            <option value="">-- Select Position --</option>
            <?php foreach ($positions as $p): ?>
                <option value="<?= $p['position_id'] ?>"> 
                    <?= htmlspecialchars($p['position_name']) ?> — ₦<?= number_format($p['form_price'], 2) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit">Proceed to Payment</button>
    </form>

    <div class="footer-note">
        <a href="../index.php" style="color: #3498db; text-decoration: none;">← Back to Home</a>
    </div>
</div>

</body>
</html>