<?php
session_start();
require_once __DIR__ . '/../inc/db.php';

// 1. Admin Protection
if (!isset($_SESSION['admin_logged_in'])) {
    exit("Unauthorized access.");
}

// 2. Get the parameters from the URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if (!$id || !$action) {
    header("Location: admin_candidates.php?message=Invalid Request");
    exit;
}

try {
    if ($action === 'approve') {
        // Change status to 'approved' so they show up in manage_candidates.php and the ballot
        $stmt = $db->prepare("UPDATE candidates SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Candidate approved successfully!";
        
    } elseif ($action === 'reject') {
        // If rejected, we delete their record and their photo to save server space
        $photoQuery = $db->prepare("SELECT photo FROM candidates WHERE id = ?");
        $photoQuery->execute([$id]);
        $photo = $photoQuery->fetchColumn();

        if ($photo && file_exists(__DIR__ . '/../uploads/candidates/' . $photo)) {
            unlink(__DIR__ . '/../uploads/candidates/' . $photo);
        }

        $stmt = $db->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Application rejected and removed.";
    }

    // Redirect back to the review list with a success message
    header("Location: admin_candidates.php?message=" . urlencode($msg));
    exit;

} catch (PDOException $e) {
    // Redirect back with an error message if something goes wrong
    header("Location: manage_applications.php?message=" . urlencode("Error: " . $e->getMessage()));
    exit;
}