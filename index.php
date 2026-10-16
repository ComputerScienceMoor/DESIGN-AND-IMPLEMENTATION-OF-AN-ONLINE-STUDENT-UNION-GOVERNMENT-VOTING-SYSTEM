<?php

session_start();

// Set local timezone to match admin settings panel precisely
date_default_timezone_set('Africa/Lagos');

require_once __DIR__ . '/inc/db.php'; 

// --- Check Voting Status (Logic from previous step) ---
$is_voting_open = false;
$voting_status_message = '';
$start_time = 'TBD';

try {
 
    $stmt_settings = $db->query("SELECT setting_name, setting_value FROM settings WHERE setting_name IN ('voting_start_time', 'voting_end_time')");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);

    $start_time_ts = strtotime($settings['voting_start_time'] ?? '9999-01-01');
    $end_time_ts = strtotime($settings['voting_end_time'] ?? '9999-01-01');
    $current_time = time();
    $start_time = date('F j, Y, h:i A', $start_time_ts);
    $end_time = date('F j, Y, h:i A', $end_time_ts);


    if ($current_time < $start_time_ts) {
        // ✅ FIXED: Changed Markdown ** to HTML <strong>
        $voting_status_message = "The <strong>Official Election</strong> will begin on <strong>{$start_time}</strong>. We are preparing the final ballots!";
        $status_class = 'status-pending';
    } elseif ($current_time > $end_time_ts) {
        // ✅ FIXED: Changed Markdown ** to HTML <strong>
        $voting_status_message = "The polls have officially <strong>CLOSED</strong>. Thank you for voting! Results will be announced shortly.";
        $status_class = 'status-closed';
    } else {
        $is_voting_open = true;
        // ✅ FIXED: Changed Markdown ** to HTML <strong>
        $voting_status_message = "The polls are <strong>OPEN NOW</strong>! Cast your vote securely before <strong>{$end_time}</strong>.";
        $status_class = 'status-open';
    }

} catch (PDOException $e) {
    $voting_status_message = "⚠️ Election status is currently unavailable due to a system error. Please try refreshing.";
    $status_class = 'status-closed';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure E-Voting Portal | Campus Elections</title>
    <style>
        :root {
            --primary-yellow: #27ae60; 
            --dark-text: #333;
            --light-text: #666;
            --button-blue: #27ae60;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-bg: #ecf0f1;
            --error-color: #e74c3c;
        }

      
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f4f6f8 0%, var(--light-bg) 100%);
            color: var(--dark-text);
        
            font-size: 16px; 
        }


        .header-wave {
            position: relative;
            height: 300px; /* Base height */
            background-color: var(--primary-yellow);
            border-bottom-left-radius: 50% 20%;
            border-bottom-right-radius: 50% 20%;
            z-index: 1;
        }


        .navbar {
            position: absolute;
            top: 20px;
            right: 10%;
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        .navbar a {
            color: var(--dark-text);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95em;
            padding: 5px 0;
            transition: color 0.3s;
        }

        .main-content-wrapper {
            position: relative;
            z-index: 2;
            margin-top: -150px; 
            padding: 0 10%;
        }

        .main-content {
            display: flex;
            align-items: center;
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

      
        .text-section {
            flex: 1.2;
            padding-right: 50px;
            text-align: left;
        }

        .text-section h1 {
         
            font-size: clamp(2em, 5vw, 3.5em); 
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 20px;
            line-height: 1.1;
        }
        
        .text-section p {
            color: var(--light-text);
            font-size: 1em;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
     
        .read-more-btn {
            background-color: var(--primary-yellow);
            color: white; /* Changed to white for better readability on green background */
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: background-color 0.3s;
            border: 1px solid var(--primary-yellow);
          
            font-size: 1em; 
        }

        .read-more-btn.disabled {
            background: #dcdcdc;
            color: #999;
            border-color: #dcdcdc;
            cursor: not-allowed;
        }
        

        .image-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
          
        }
        
        .illustration-placeholder {
            width: 100%;
            max-width: 500px;
          
            aspect-ratio: 1.25 / 1; 
            background: var(--light-bg); 
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-style: italic;
            color: var(--light-text);
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }

    
        .status-alert { 
            padding: 20px; 
            margin-bottom: 30px; 
            border-radius: 10px; 
            font-weight: bold; 
            font-size: 1.2em; text-align: center; 
        }
        .status-open { 
            background: #e6ffed;
             color: #155724;
             border: 2px solid var(--secondary-color);
             }
        .status-closed, .status-pending { 
            background: #fff0f0; 
            color: var(--error-color); 
            border: 2px solid var(--error-color); 
        }

        .info-grid {
             display: flex;
             justify-content: space-around; 
            gap: 20px; 
            margin: 30px 0; 
            padding: 0 5%; 
        }
        .info-card { 
            
            flex: 1; 
            
            padding: 20px; 
            border-radius: 10px; 
            background: var(--light-bg);
             border-left: 5px solid var(--secondary-color);
             text-align: left; 
            }
        .info-card h4 { 
            color: var(--dark-color); 
            margin-top: 0; 
            display: flex; 
            align-items: center; 
            font-size: 1.2em; 
        }
        .info-card h4 span { 
            margin-right: 10px; 
            font-size: 1.5em;
         }
        .info-card p { 
            font-size: 0.95em; 
            color: #7f8c8d; 
        }


        .bottom-links { 
            text-align: center; 
            padding: 40px 0; 
        }
        .bottom-links a {
             color: var(--button-blue); 
            text-decoration: none; 
            font-weight: 600; margin: 0 15px; 
        }

        @media (max-width: 1200px) {
            .main-content-wrapper {
                padding: 0 5%;
            }
        }

     
        @media (max-width: 992px) {
            .main-content {
                flex-direction: column;
                padding: 30px;
            }
            .text-section {
                padding-right: 0;
                padding-bottom: 30px;
                text-align: center;
            }
            .text-section h1 {
                font-size: clamp(2em, 6vw, 2.5em); 
            }
            .main-content-wrapper {
                margin-top: -100px;
            }
            .image-section {
                width: 100%;
                max-width: 450px;
            }
            .illustration-placeholder {
                height: auto;
                aspect-ratio: 1.25 / 1;
            }
            .navbar {
                right: 5%;
      
            }
            .info-grid {
                flex-direction: column;
                gap: 15px;
            }
            .image-section {
                width: 100%;
                max-width: 450px;
            }
            .illustration-placeholder {
                height: auto;
                aspect-ratio: 1.25 / 1;
            }
            .illustration-placeholder img{
                height: 75%;
            }
            h2{
            font-size: 15px;
        }
        
        }
        
     
        @media (max-width: 576px) {
            body {
                font-size: 14px; 
            }
            .navbar {
                position: static;
                justify-content: center;
                padding: 0 10px;
                order: 2;
             
            }
            .header-wave {
                height: 150px; 
            }
            .main-content-wrapper {
                margin-top: -30px; 
                padding: 0 3%;
            }
            .main-content {
                padding: 15px;
            }
            .text-section h1 {
                font-size: clamp(1.8em, 8vw, 2em);
                margin-bottom: 10px;
            }
            .text-section p {
                font-size: 0.9em;
                margin-bottom: 20px;
            }
            .read-more-btn {
                width: 100%;
                box-sizing: border-box;
            }
            .illustration-placeholder {
                max-width: 100%;
                aspect-ratio: 1.5 / 1; 
            }
            .bottom-links {
                padding: 20px 10px;
            }
            .bottom-links a {
                display: block;
                margin: 10px 0;
            }
            .image-section {
                width: 100%;
                max-width: 450px;
            }
            .illustration-placeholder {
                height: auto;
                aspect-ratio: 1.25 / 1;
            }
            
            .illustration-placeholder img{
                height: 75%;
            }
           .navbar h2{
            font-size: 20px;
        }
        
        .navbar img{
            padding-top: 3%;
        }
        
        }
        h2{
            font-size: 25px;
            color: #fff;
        }
        .illustration-placeholder img{
                height: 95%;
            }
        
    </style>
</head>
<body>

<div class="header-wave">
    <div class="navbar">
        <img src="uploads/candidates/one.png" class="sug" width="80" height="80">
        <h2>STUDENT UNION GOVERNMENT VOTING SITE</h2>
    </div>
</div>

<div class="main-content-wrapper">
    <div class="main-content">
        
        <div class="text-section">
            <h1>ONLINE VOTING</h1>
            <p>
                Secure your franchise with our certified digital platform. Our system ensures maximum security, real-time results, and full compliance with election regulations. 
                Vote easily, vote from anywhere, and ensure your voice is counted in the student elections.
            </p>
            
            <a href="./students/student_login.php" 
               class="read-more-btn <?php echo !$is_voting_open ? 'disabled' : ''; ?>"
               <?php echo !$is_voting_open ? 'onclick="return false;"' : ''; ?>
            >
                START VOTING NOW
            </a>
        </div>
        
        <div class="image-section">
            <div class="illustration-placeholder">
                <img src="uploads/candidates/download__1_-removebg-preview.png">
            </div>
        </div>
    </div>

    <!-- ✅ FIXED: Removed nl2br since we now render standard HTML tags out of strings safely -->
    <div class="status-alert <?php echo $status_class; ?>" style="margin-top: 30px;">
        <?php echo $voting_status_message; ?>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <h4><span role="img" aria-label="Lock">🔒</span> Security & Privacy</h4>
            <p>Your vote is recorded anonymously and encrypted immediately. We use your Matric Number only for validation to ensure **one student, one vote**.</p>
        </div>
        <div class="info-card">
            <h4><span role="img" aria-label="Clock">⏱️</span> Accessibility</h4>
            <p>Vote from any device, anywhere, during the official polling window. Say goodbye to long queues and paper ballots.</p>
        </div>
        <div class="info-card">
            <h4><span role="img" aria-label="Trophy">🏆</span> Transparency</h4>
            <p>Votes are tallied instantly and accurately. Promoting a fair electoral process.</p>
        </div>
    </div>
</div>

<div class="bottom-links">
    <a href="./AdminFiles/admin_login.php">Administrator Login</a>
    <a href="./candidates/choose_position.php">Candidate Application</a>
    <a href="./students/student_login.php">Student Login</a>
</div>

</body>
</html>