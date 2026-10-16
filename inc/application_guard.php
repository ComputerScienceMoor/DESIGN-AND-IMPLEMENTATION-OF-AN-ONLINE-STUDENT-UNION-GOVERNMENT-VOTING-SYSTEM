<?php
// inc/application_guard.php
require_once __DIR__ . '/db.php';

$stmt = $db->query("SELECT * FROM application_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$now = new DateTime();

$applications_open = false;

if ($settings) {

    if ((int)$settings['applications_open'] === 1) {

        if ($settings['form_open'] && $settings['form_close']) {
            $openFrom = new DateTime($settings['form_open']);
            $closeAt  = new DateTime($settings['form_close']);

            if ($now >= $openFrom && $now <= $closeAt) {
                $applications_open = true;
            }

        } else {
            // Manual open without date restriction
            $applications_open = true;
        }
    }
}

if (!$applications_open) {
    die("<h2 style='text-align:center;color:red;margin-top:100px;'>
        🚫 Candidate applications are currently CLOSED.<br>
        Please check back later.
    </h2>");
}
