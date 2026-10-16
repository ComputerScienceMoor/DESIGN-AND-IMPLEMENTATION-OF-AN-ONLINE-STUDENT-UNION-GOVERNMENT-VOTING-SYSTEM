<?php
session_start();

// SECURITY: Only logged-in admin can access
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION['admin_username'];
?>
<?php
// ... existing PHP code in index.php ...

// Check for and display reset status message
$status_message = '';
if (isset($_SESSION['reset_status'])) {
    $status = $_SESSION['reset_status'];
    $color = ($status['type'] == 'success') ? 'green' : 'red';
    $status_message = "<p style='color: white; background: {$color}; padding: 10px; border-radius: 5px; text-align: center; font-weight: bold;'>{$status['message']}</p>";
    // Clear the session variable immediately after display
    unset($_SESSION['reset_status']);
}

// ... rest of PHP and HTML ...
?>
<?php
// require_once 'auth_admin.php'; 
require_once __DIR__ . '/../inc/db.php'; // Ensure database connection is included

// --- 1. PHP Logic: Fetch and Determine Election Status ---

try {
    // Fetch settings from the database
    $stmt = $db->query("SELECT setting_name, setting_value FROM settings 
                        WHERE setting_name IN ('voting_start_time', 'voting_end_time')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $start_time_str = $settings['voting_start_time'] ?? null;
    $end_time_str = $settings['voting_end_time'] ?? null;

    $start_timestamp = $start_time_str ? strtotime($start_time_str) : 0;
    $end_timestamp = $end_time_str ? strtotime($end_time_str) : 0;

} catch (PDOException $e) {
    // Handle database error if settings table is missing
    $start_timestamp = 0;
    $end_timestamp = 0;
    // Note: In a live environment, you would log this, not die.
}


$current_time = time();

// Default values
$countdown_message = "Election Status Unknown";
$countdown_target_ms = 0;
$box_style = 'background: #7f8c8d;'; // Gray

if ($current_time < $start_timestamp) {
    // Case 1: Election hasn't started yet
    $countdown_target_ms = $start_timestamp * 1000;
    $countdown_message = "🕒 Voting Starts In:";
    $box_style = 'background: #f39c12;'; // Orange for pending
    $status_id = 'pending';
} elseif ($current_time >= $start_timestamp && $current_time < $end_timestamp) {
    // Case 2: Voting is active
    $countdown_target_ms = $end_timestamp * 1000;
    $countdown_message = "🔥 Voting Ends In:";
    $box_style = 'background: #e74c3c;'; // Red for active/urgency
    $status_id = 'active';
} else {
    // Case 3: Voting is over
    $countdown_message = "✅ Voting Period Ended!";
    $box_style = 'background: #27ae60;'; // Green for closed
    $status_id = 'closed';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin:0; padding:0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            color: #333;
        }

        .header {
            background: #27ae60;
            color: white;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px; /* space between logos and text */
            flex-wrap: wrap;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header img {
            height: 80px;
        }

        .header-text {
            text-align: center;
        }

        .header-text h1 {
            font-size: 36px;
            margin: 0;
        }

        .header-text p {
            font-size: 18px;
            margin-top: 5px;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }

        .card {
            background: #fff;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .card h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 25px;
            color: #2c3e50;
        }

        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .links a {
            flex: 1 1 200px;
            text-align: center;
            background: #04bc51;
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .links a:hover {
            background: #219150;
        }

        .links a.logout {
            background: #c0392b;
        }

        .links a.logout:hover {
            background: #e74c3c;
        }

        .countdown-box {
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            <?php echo $box_style; ?> /* Apply dynamic style */
        }
        .countdown-box h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        .timer {
            font-size: 2.5em;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .links {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <img src="img/two.png" alt="Logo 2">
    <img src="img/one.png" alt="Logo 1">

    <div class="header-text">
        <h1>Admin Dashboard</h1>
        <p>Welcome, <b><?php echo htmlspecialchars($admin_name); ?></b></p>
    </div>
</div>

<div class="container">
    <div class="card">
        <div class="countdown-box" id="election-status-box">
    <h3><?php echo $countdown_message; ?></h3>
    <div id="countdown-timer" class="timer">
        <?php echo ($status_id === 'closed') ? 'Election is Over.' : 'Calculating...'; ?>
    </div>
</div>
        <h2>Admin Controls</h2>
        <div class="links">
            <a href="manage_students.php">Manage Students</a>
            <a href="manage_candidates.php">Manage Candidates</a>
            <a href="view_votes.php">View All Votes</a>
            <a href="departmental_results.php">Departmental Results</a>
            <a href="election_results_live.php">Live Results</a>
            <a href="results_by_position.php">results_by_position</a>
            <a href="manage_applications.php">Approve/Reject candidates</a>
            
           
    
            <a href="settings.php">Election Settings</a>
            <!-- <a href="reset_credentials.php">Reset Admin Username/Password</a> -->
            <a href="reset_election.php" 
       onclick="return confirm('WARNING: Are you absolutely sure you want to reset the election? This will PERMANENTLY delete ALL cast votes and cannot be undone.')" 
       style="  background: #c0392b; color: white; border-radius: 8px; text-decoration: none; font-weight: bold;">
       ⚠️ Reset Election (Delete All Votes)
    </a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>
</div>
<script>
// --- 2. JavaScript Logic: The Dynamic Countdown Timer ---

// Get the deadline/start time from PHP (passed in milliseconds)
var countDownDate = <?php echo $countdown_target_ms; ?>;
var statusId = '<?php echo $status_id; ?>';
var x = document.getElementById("countdown-timer");

// Only run the timer if the election is not closed
if (statusId !== 'closed' && countDownDate > 0 && x) {
    var countdown = setInterval(function() {

        var now = new Date().getTime();
        var distance = countDownDate - now;

        // Time calculations
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Display the result
        x.innerHTML = days + "d " + hours + "h "
        + minutes + "m " + seconds + "s ";

        // If the count down is finished
        if (distance < 0) {
            clearInterval(countdown);
            // Reload the page to refresh the PHP logic and check the new status (Active or Closed)
            window.location.reload(); 
        }
    }, 1000);
}
</script>
</body>
</html>
