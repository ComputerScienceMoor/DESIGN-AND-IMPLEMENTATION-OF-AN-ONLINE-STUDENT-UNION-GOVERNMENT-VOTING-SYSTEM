<?php
session_start();
require_once '../inc/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ensure the names match your FORM inputs
    $open = isset($_POST['applications_open']) ? 1 : 0;
    $open_from = $_POST['open_from'] ?: null;
    $close_at = $_POST['close_at'] ?: null;

    // 2. Check if a row exists first
    $check = $db->query("SELECT id FROM application_settings LIMIT 1")->fetch();

    if ($check) {
        // UPDATE existing row - Using 'isactive' as established previously
        $stmt = $db->prepare("
            UPDATE application_settings 
            SET isactive = ?, form_open = ?, form_close = ? 
            WHERE id = ?
        ");
        $stmt->execute([$open, $open_from, $close_at, $check['id']]);
    } else {
        // INSERT if table is empty
        $stmt = $db->prepare("
            INSERT INTO application_settings (isactive, form_open, form_close) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$open, $open_from, $close_at]);
    }

    $msg = "Settings updated successfully.";
}

// Fetch fresh settings
$settings = $db->query("SELECT * FROM application_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Application Control</title>
<style>
body { font-family: Arial; background:#f4f6f8; }
.box { max-width:500px; margin:80px auto; background:#fff; padding:30px; border-radius:10px; }
label { font-weight:bold; display:block; margin-top:15px; }
button { margin-top:20px; padding:12px; width:100%; background:#27ae60; color:#fff; border:none; }
</style>
</head>
<body>

<div class="box">
<h2>Candidate Application Control</h2>

<?php if (!empty($msg)) echo "<p style='color:green'>$msg</p>"; ?>

<form method="POST">
    <label>
        <input type="checkbox" name="applications_open" <?= $settings['applications_open'] ? 'checked' : '' ?>>
        Applications Open
    </label>

    <label>Open From</label>
    <input type="datetime-local" name="open_from"
           value="<?= $settings['form_open'] ? date('Y-m-d\TH:i', strtotime($settings['form_open'])) : '' ?>">

    <label>Close At</label>
    <input type="datetime-local" name="close_at"
           value="<?= $settings['form_close'] ? date('Y-m-d\TH:i', strtotime($settings['form_close'])) : '' ?>">

    <button>Save Settings</button>
</form>
</div>

</body>
</html>
