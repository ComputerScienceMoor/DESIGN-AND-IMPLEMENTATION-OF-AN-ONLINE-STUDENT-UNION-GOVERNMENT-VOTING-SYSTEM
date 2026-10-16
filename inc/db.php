<?php
// inc/db.php

$host = "localhost";
$user = "root";
$pass = "";

// 1. Your local Election Management Portal Database
$election_dbname = "student_voting_system";

// 2. The External Student Portal Database containing the authoritative registry
$school_dbname = "sug_portal"; 

try {
    // Connection Handle for the local Voting System
    $db = new PDO("mysql:host=$host;dbname=$election_dbname;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection Handle for the external SUG Student Portal
    $school_db = new PDO("mysql:host=$host;dbname=$school_dbname;charset=utf8mb4", $user, $pass);
    $school_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Cleanly halt execution and print configuration connection runtime faults
    die("Database Connection Failed: " . $e->getMessage());
}
?>