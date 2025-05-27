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
            $message = "Το email σας έχει επαληθευτεί με επιτυχία! Μπορείτε τώρα να συνδεθείτε.";
            $messageClass = "success";
        } else {
            $message = "Σφάλμα επαλήθευσης email: " . $mysqli->error;
            $messageClass = "danger";
        }
        $updateStmt->close();
    } else {
        $message = "Μη έγκυρο ή ληγμένο token επαλήθευσης.";
        $messageClass = "warning";
    }
    $stmt->close();
} else {
    $message = "Δεν παρέχεται token επαλήθευσης.";
    $messageClass = "warning";
}

// Store message in session for display on login page
$_SESSION['message'] = $message;
$_SESSION['toastClass'] = $messageClass;

header("Location: login.php");
exit();
?>