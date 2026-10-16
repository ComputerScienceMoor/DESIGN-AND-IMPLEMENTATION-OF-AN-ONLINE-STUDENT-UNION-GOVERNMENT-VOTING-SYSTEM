<?php
function clean($data) {
    return htmlspecialchars(trim($data));
}

function hash_matric($matric) {
    return hash('sha256', strtoupper(trim($matric)));
}
?>