<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once('../database/db_connect.php');

$message = '';
$messageClass = '';

// Check if token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Validate token and update user account
    $stmt = $mysqli->prepare("SELECT userId, username FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Token is valid, update user status
        $userData = $result->fetch_assoc();
        $userId = $userData['userId'];
        
        // Update user to verified status and clear token
        $updateStmt = $mysqli->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE userId = ?");
        $updateStmt->bind_param("i", $userId);
        
        if ($updateStmt->execute()) {
            $message = "Your email has been verified successfully! You can now log in.";
            $messageClass = "success";
        } else {
            $message = "Error verifying email: " . $mysqli->error;
            $messageClass = "danger";
        }
        $updateStmt->close();
    } else {
        $message = "Invalid or expired verification token.";
        $messageClass = "warning";
    }
    $stmt->close();
} else {
    $message = "No verification token provided.";
    $messageClass = "warning";
}

// Store message in session for display on login page
$_SESSION['message'] = $message;
$_SESSION['toastClass'] = $messageClass;

header("Location: login.php");
exit();
?>