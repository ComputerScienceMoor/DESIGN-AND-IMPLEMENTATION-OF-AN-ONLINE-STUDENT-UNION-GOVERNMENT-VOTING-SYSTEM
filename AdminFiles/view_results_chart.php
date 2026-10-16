<?php
session_start();
require_once '../inc/db.php';

// Admin protection
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$id || !in_array($action, ['approve', 'reject'])) {
    header("Location: manage_candidates.php");
    exit;
}

$db->beginTransaction();

try {

    // Fetch application
    $stmt = $db->prepare("
        SELECT c.*, p.position_name 
        FROM candidate_applications c
        JOIN positions p ON c.position_id = p.id
        WHERE c.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        throw new Exception("Application not found");
    }

    // Update application status
    $update = $db->prepare("
        UPDATE candidate_applications 
        SET status = ?
        WHERE id = ?
    ");
    $update->execute([$action === 'approve' ? 'approved' : 'rejected', $id]);

    // If approved → create candidate
    if ($action === 'approve') {

        // Check if already exists
        $check = $db->prepare("
            SELECT id FROM candidates WHERE application_id = ?
        ");
        $check->execute([$id]);

        if (!$check->fetch()) {

            $insert = $db->prepare("
                INSERT INTO candidates 
                (application_id, name, department, position, photo, manifesto, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            $insert->execute([
                $app['id'],
                $app['full_name'],
                $app['department'],
                $app['position_name'],
                $app['photo'],
                $app['manifesto']
            ]);
        }
    }

    $db->commit();

} catch (Exception $e) {
    $db->rollBack();
    die("Error: " . $e->getMessage());
}

header("Location: manage_candidates.php");
exit;
