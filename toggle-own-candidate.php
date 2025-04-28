<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'database/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userId']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Get the user ID from session
$userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : $_SESSION['user_id'];

// Check if we have the necessary parameters
if (!isset($_POST['trackingID']) || !isset($_POST['isOwn'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$trackingID = (int) $_POST['trackingID'];
$isOwn = $_POST['isOwn'] === 'true' ? 1 : 0;

// // Database connection
// $servername = "localhost";
// $username = "root"; 
// $password = ""; 
// $dbname = "cei326omada1";

// $conn = new mysqli($servername, $username, $password, $dbname);

// if ($conn->connect_error) {
//     echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
//     exit;
// }

// If marking as own candidate, first unmark all other candidates
if ($isOwn) {
    // Reset all candidates to not be own
    $resetSql = "UPDATE trackings SET isOwnCandidate = 0 WHERE userID = ?";
    $resetStmt = $mysqli->prepare($resetSql);
    $resetStmt->bind_param("i", $userId);
    $resetStmt->execute();
}

// Update the isOwnCandidate status in database for the selected candidate
$sql = "UPDATE trackings SET isOwnCandidate = ? WHERE trackingID = ? AND userID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("iii", $isOwn, $trackingID, $userId);
$result = $stmt->execute();

if ($result) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update database']);
}

$mysqli->close();
exit;