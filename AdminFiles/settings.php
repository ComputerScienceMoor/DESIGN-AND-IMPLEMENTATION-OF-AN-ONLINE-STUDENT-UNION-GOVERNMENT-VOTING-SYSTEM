<?php
// admin/manage_settings.php
require_once 'auth_admin.php';
require_once __DIR__ . '/../inc/db.php';

$message = "";

if (isset($_POST['save_times'])) {
    // 1. Sanitize input strings
    $start_raw = trim($_POST['start_time']);
    $end_raw = trim($_POST['end_time']);

    // 2. Normalization: Convert the HTML 'T' separator to a standard SQL space separator
    // Example: "2026-07-06T14:30" becomes "2026-07-06 14:30:00"
    $start_time = str_replace('T', ' ', $start_raw);
    if (strlen($start_time) == 16) { $start_time .= ':00'; }

    $end_time = str_replace('T', ' ', $end_raw);
    if (strlen($end_time) == 16) { $end_time .= ':00'; }

    try {
        // FIXED: Explicitly added the database prefix `student_voting_system.` to target the correct table
        $stmt_start = $db->prepare("REPLACE INTO student_voting_system.settings (setting_name, setting_value) VALUES (?, ?)");
        $stmt_start->execute(['voting_start_time', $start_time]);

        $stmt_end = $db->prepare("REPLACE INTO student_voting_system.settings (setting_name, setting_value) VALUES (?, ?)");
        $stmt_end->execute(['voting_end_time', $end_time]);

        $message = "<p style='color:green; font-weight:bold;'>Voting times updated successfully!</p>";

    } catch (PDOException $e) {
        $message = "<p style='color:red; font-weight:bold;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Fetch current times for display (FIXED: Added schema prefix here too)
$current_settings = [];
$settings_query = $db->query("SELECT setting_name, setting_value FROM student_voting_system.settings WHERE setting_name IN ('voting_start_time', 'voting_end_time')");
while ($row = $settings_query->fetch(PDO::FETCH_ASSOC)) {
    $current_settings[$row['setting_name']] = $row['setting_value'];
}

$start_value = $current_settings['voting_start_time'] ?? '';
$end_value = $current_settings['voting_end_time'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Voting Period</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; padding: 20px; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #2c3e50; margin-top: 0; }
        label { font-weight: bold; color: #34495e; display: block; margin-top: 15px; margin-bottom: 5px; }
        input[type="datetime-local"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 15px; }
        button { background: #04bc51; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; }
        button:hover { background: #03a846; }
        .back { display: inline-block; margin-bottom: 20px; color: #3498db; text-decoration: none; font-weight: bold; }
        .back:hover { text-decoration: underline; }
        .server-time { background: #e8f4fd; color: #2b6cb0; padding: 10px; border-radius: 4px; font-size: 14px; margin-bottom: 20px; border-left: 4px solid #3182ce; }
    </style>
</head>
<body>
<div class="container">
    <a href="index.php" class="back">← Back to Dashboard</a>
    <h2>🗳️ Set Voting Period</h2>
    
    <?php echo $message; ?>

    <!-- Helpful reference to verify XAMPP timezone alignment -->
    <div class="server-time">
        <strong>Current Server System Time:</strong> <?php echo date('Y-m-d h:i A'); ?>
    </div>

    <form method="POST">
        <label for="start_time">Start Time</label>
        <input type="datetime-local" id="start_time" name="start_time" value="<?php echo htmlspecialchars(str_replace(' ', 'T', substr($start_value, 0, 16))); ?>" required>

        <label for="end_time">End Time</label>
        <input type="datetime-local" id="end_time" name="end_time" value="<?php echo htmlspecialchars(str_replace(' ', 'T', substr($end_value, 0, 16))); ?>" required>

        <button type="submit" name="save_times">Save Voting Period</button>
    </form>
    <p style="margin-top: 20px; font-size: 13px; color: #7f8c8d;">*Note: Ensure your XAMPP <code>php.ini</code> configuration file defines the correct local timezone matching your system clock.</p>
</div>
</body>
</html>