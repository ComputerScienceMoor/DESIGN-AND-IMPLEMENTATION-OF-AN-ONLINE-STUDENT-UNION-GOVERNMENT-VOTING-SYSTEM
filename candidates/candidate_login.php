<?php
session_start();
require_once __DIR__ . '/../inc/db.php';
// Include your custom mailer function configuration file
require_once __DIR__ . '/mailer.php'; 

$error = "";
$success_msg = "";

// --- 1. HANDLE LOGIN SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_action'])) {
    // Force a clean session for this attempt
    session_unset(); 

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Fetch the candidate profile
        $stmt = $db->prepare("SELECT * FROM candidates WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$candidate) {
            $error = "❌ No account found with that email.";
        } else {
            // ✅ CRITICAL SECURITY LAYER: Block unapproved candidate entries
            if ($candidate['status'] !== 'approved') {
                $error = "⏳ Your application is currently awaiting administrative approval. Please check back later.";
            } else {
                $db_password = trim($candidate['password']);
                
                // Compare plain text password configurations safely
                if ($password === $db_password) {
                    $_SESSION['candidate_logged_in'] = true;
                    $_SESSION['candidate_id'] = $candidate['id'];
                    $_SESSION['candidate_name'] = $candidate['name'];

                    header("Location: candidate_dashboard.php");
                    exit();
                } else {
                    $error = "❌ Incorrect password. (Hint: check for capital letters)";
                }
            }
        }
    } else {
        $error = "⚠️ Please fill in all fields.";
    }
}

// --- 2. HANDLE FORGOT PASSWORD REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_action'])) {
    $forgot_email = trim($_POST['email']);

    if (!empty($forgot_email)) {
        $stmt = $db->prepare("SELECT * FROM candidates WHERE email = ? LIMIT 1");
        $stmt->execute([$forgot_email]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($candidate) {
            try {
                // Reuse your helper script function to securely dispatch their plain text password configuration
                sendCandidateCredentials($candidate['email'], $candidate['name'], $candidate['password']);
                $success_msg = "🔑 Password reminder sent! Please check your email inbox.";
            } catch (Exception $e) {
                $error = "❌ Mailer failed to send reminder. System exception logged.";
            }
        } else {
            // Soft response configuration for generic application privacy mapping
            $error = "❌ If that email matches our system records, a reminder has been generated.";
        }
    } else {
        $error = "⚠️ Please enter your email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Portal Hub</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); width: 350px; transition: all 0.3s ease; }
        h2 { text-align: center; margin-top: 0; margin-bottom: 25px; color: #2c3e50; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; margin-top: 10px; transition: background 0.2s; }
        button:hover { background: #219150; }
        
        .alert { padding: 12px; border-radius: 6px; text-align: center; margin-bottom: 20px; font-size: 14px; font-weight: bold; line-height: 1.4; }
        .error { background: #fdedec; color: #e74c3c; border: 1px solid #fadbd8; }
        .success { background: #eafaf1; color: #27ae60; border: 1px solid #d4efdf; }
        
        .toggle-link { display: block; text-align: center; margin-top: 15px; font-size: 13px; color: #3498db; text-decoration: none; font-weight: 600; cursor: pointer; }
        .toggle-link:hover { text-decoration: underline; color: #2980b9; }
        
        .hidden-form { display: none; }
        .back-links { display: block; text-align: center; margin-top: 25px; font-size: 13px; color: #7f8c8d; text-decoration: none; border-top: 1px solid #eee; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="box">
        
        <!-- --- ALERTS PANELS CONTAINER --- -->
        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert success"><?= $success_msg ?></div>
        <?php endif; ?>

        <!-- =============================================
             FORM 1: CANDIDATE PORTAL LOGIN INTERFACE
             ============================================= -->
        <div id="loginFormContainer">
            <h2>Candidate Login</h2>
            <form method="POST">
                <input type="hidden" name="login_action" value="1">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Log In Securely</button>
            </form>
            <a class="toggle-link" onclick="toggleForms(true)">Forgot Access Password?</a>
        </div>

        <!-- =============================================
             FORM 2: PASSWORD RECOVERY INTERFACE
             ============================================= -->
        <div id="forgotFormContainer" class="hidden-form">
            <h2>Recover Account</h2>
            <p style="font-size: 13px; color: #666; text-align: center; line-height: 1.5; margin-bottom: 20px;">
                Enter your registered email below and the system will re-transmit your account password straight to your inbox.
            </p>
            <form method="POST">
                <input type="hidden" name="forgot_action" value="1">
                <input type="email" name="email" placeholder="Enter your email address" required>
                <button type="submit" style="background: #3498db;">Send Password Reminder</button>
            </form>
            <a class="toggle-link" onclick="toggleForms(false)" style="color: #7f8c8d;">← Back to Login</a>
        </div>

        <a href="../index.php" class="back-links">← Back to Election Landing Page</a>
    </div>

    <!-- --- DYNAMIC VIEWPORT ANIMATION HANDLER --- -->
    <script>
        function toggleForms(showForgot) {
            const loginForm = document.getElementById('loginFormContainer');
            const forgotForm = document.getElementById('forgotFormContainer');
            
            if (showForgot) {
                loginForm.style.display = 'none';
                forgotForm.style.display = 'block';
            } else {
                loginForm.style.display = 'block';
                forgotForm.style.display = 'none';
            }
        }

        // Auto-show forgot form view layout if form actions return validation notices
        <?php if (isset($_POST['forgot_action']) && !empty($error)): ?>
            toggleForms(true);
        <?php endif; ?>
    </script>
</body>
</html>