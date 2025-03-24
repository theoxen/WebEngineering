<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Either include your homepage content
// include_once('./pages/homepage.php');

// OR redirect to homepage.php
header('Location: pages/homepage.php');
// exit;
?>