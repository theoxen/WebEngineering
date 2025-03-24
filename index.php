<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: pages/homepage.php');
// exit;
?>