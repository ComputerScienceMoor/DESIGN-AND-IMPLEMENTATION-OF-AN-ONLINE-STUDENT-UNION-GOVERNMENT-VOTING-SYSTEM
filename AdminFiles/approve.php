<?php
require_once '../inc/db.php';

$id = $_GET['id'];
$status = $_GET['s'];

$stmt = $db->prepare("UPDATE candidate_applications SET status=? WHERE id=?");
$stmt->execute([$status, $id]);

header("Location: candidates.php");
exit;
