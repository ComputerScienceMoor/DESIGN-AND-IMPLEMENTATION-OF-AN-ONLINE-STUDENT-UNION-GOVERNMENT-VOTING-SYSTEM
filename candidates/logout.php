<?php
// 1. Start the session to gain access to it
session_start();

// 2. Clear all session variables
$_SESSION = array();

// 3. Destroy the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// 4. Completely destroy the session on the server
session_destroy();

// 5. Redirect back to the login page with a logged-out message
header("Location: candidate_login.php?logout=success");
exit();
?>