<?php
// Ensure this file is only accessible by the administrator
session_start();
require_once __DIR__ . '/../inc/db.php';

// Security check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// -----------------------------------------------------------
// Election Reset Logic
// -----------------------------------------------------------

try {
    // Start transaction for safety
    $db->beginTransaction();

    // 1. Delete all records from the votes table
    $db->exec("DELETE FROM votes");
    
    // 2. Reset voted status for all students (optional but good practice if you had a 'voted' column)
    // NOTE: If your students table has a 'has_voted' column, uncomment and adapt the line below.
    // $db->exec("UPDATE students SET has_voted = 0");

    // Commit the transaction
    $db->commit();

    $message = "Election data successfully reset! All votes have been deleted.";
    $type = 'success';

} catch (PDOException $e) {
    // Rollback if anything failed
    $db->rollBack();
    $message = "Error resetting election data: " . $e->getMessage();
    $type = 'error';
}

// Redirect back to the dashboard with a status message
$_SESSION['reset_status'] = [
    'message' => $message,
    'type' => $type
];

header("Location: index.php");
exit();
?>
