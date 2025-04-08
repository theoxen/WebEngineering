<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once('../database/db_connect.php');

// Get the user ID from session
$userId = $_SESSION['user_id'];

// Begin transaction to ensure all deletions succeed or fail together
$mysqli->begin_transaction();

try {
    // Delete the user account (notification settings will cascade delete)
    $stmt = $mysqli->prepare("DELETE FROM users WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $result = $stmt->execute();
    $stmt->close();
    
    // If successful, commit transaction
    $mysqli->commit();
    
    // Clear all session data and destroy session
    $_SESSION = array();
    
    // If a session cookie is used, destroy it
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Start a new session to set flash message
    session_start();
    $_SESSION['message'] = "Your account has been successfully deleted";
    $_SESSION['toastClass'] = "success";
    
    // Redirect to homepage
    header("Location: homepage.php");
    exit();
    
} catch (Exception $e) {
    // If there was an error, roll back the transaction
    $mysqli->rollback();
    
    // Set error message
    session_start();
    $_SESSION['flash_message'] = "Error deleting account: " . $e->getMessage();
    $_SESSION['flash_class'] = "danger";
    
    // Redirect back to settings page
    header("Location: user-settings.php");
    exit();
}
?>