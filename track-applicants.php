<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'database/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['userId']) && !isset($_SESSION['user_id'])) {
    // Redirect to login page if user isn't logged in
    header("Location: pages/login.php");
    exit;
}

// Get the user ID from session
$userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : $_SESSION['user_id'];

// Initialize tracked applicants array in session if not exists
if (!isset($_SESSION['tracked_applicants'])) {
    $_SESSION['tracked_applicants'] = [];
}

// // Database connection
// $servername = "localhost";
// $username = "root"; 
// $password = ""; 
// $dbname = "cei326omada1";

// $conn = new mysqli($servername, $username, $password, $dbname);

// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// Function to fetch applicant data from database
function getApplicantData($mysqli, $applicantID) {
    // Check if categories table exists
    $sql = "SHOW TABLES LIKE 'categories'";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        // Categories table exists
        $sql = "SELECT r.*, c.fields 
                FROM rankinglist r 
                JOIN categories c ON r.categoryID = c.categoryID 
                WHERE r.id = ?";
    } else {
        // No categories table, just query rankinglist
        $sql = "SELECT r.*, 'Unknown' as fields 
                FROM rankinglist r 
                WHERE r.id = ?";
    }
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $applicantID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Function to add tracking to database - using the actual trackings table structure
function addTrackingToDatabase($mysqli, $userId, $applicantData) {
    // Check if tracking already exists - now checking by name and birthday only
    $sql = "SELECT trackingID FROM trackings 
            WHERE userID = ? AND candidateFullName = ? AND candidateBirthdayDate = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iss", $userId, $applicantData['fullName'], $applicantData['birthdayDate']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Insert new tracking record - without appNum
        $sql = "INSERT INTO trackings (userID, candidateFullName, candidateBirthdayDate, candidateTitleDate, isOwnCandidate) 
                VALUES (?, ?, ?, ?, 0)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isss", $userId, $applicantData['fullName'], $applicantData['birthdayDate'], $applicantData['titleDate']);
        return $stmt->execute();
    }
    
    return false;
}

// Function to remove tracking from database
function removeTrackingFromDatabase($mysqli, $userId, $applicantData) {
    $sql = "DELETE FROM trackings 
            WHERE userID = ? AND candidateFullName = ? AND candidateBirthdayDate = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iss", $userId, $applicantData['fullName'], $applicantData['birthdayDate']);
    $stmt->execute();
    return $stmt->affected_rows > 0;
}

// Check if form was submitted to track multiple applicants
if ((isset($_POST['track_applicants']) && is_array($_POST['track_applicants'])) || 
    (isset($_POST['track_selected']) && isset($_POST['track_applicants']) && is_array($_POST['track_applicants']))) {
    
    // Get selected applicant IDs
    $applicantIDs = $_POST['track_applicants'];
    
    // For each selected applicant
    foreach ($applicantIDs as $applicantID) {
        // Check if this applicant is already being tracked in session
        $alreadyTracked = false;
        foreach ($_SESSION['tracked_applicants'] as $tracked) {
            if ($tracked['id'] == $applicantID) {
                $alreadyTracked = true;
                break;
            }
        }
        
        if (!$alreadyTracked) {
            // Fetch applicant data from database
            $applicantData = getApplicantData($mysqli, $applicantID);
            
            if ($applicantData) {
                // Add to session
                $_SESSION['tracked_applicants'][] = $applicantData;
                
                // Add to database
                addTrackingToDatabase($mysqli, $userId, $applicantData);
            }
        }
    }
    
    // Redirect back to homepage
    header("Location: pages/homepage.php?tracked=success");
    exit;
}

// Check if a single applicant is being tracked (AJAX)
if (isset($_POST['track_single']) && isset($_POST['applicantID'])) {
    $applicantID = $_POST['applicantID'];
    
    // Check if already tracked in session
    $alreadyTracked = false;
    foreach ($_SESSION['tracked_applicants'] as $tracked) {
        if ($tracked['id'] == $applicantID) {
            $alreadyTracked = true;
            break;
        }
    }
    
    if (!$alreadyTracked) {
        // Fetch applicant data
        $applicantData = getApplicantData($mysqli, $applicantID);
        
        if ($applicantData) {
            // Add to session
            $_SESSION['tracked_applicants'][] = $applicantData;
            
            // Add to database
            addTrackingToDatabase($mysqli, $userId, $applicantData);
        }
    }
    
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

// Handle untracking an applicant
if (isset($_POST['untrack']) && isset($_POST['applicantID'])) {
    $applicantID = $_POST['applicantID'];
    
    // Find the applicant in the tracked list
    $applicantData = null;
    foreach ($_SESSION['tracked_applicants'] as $key => $tracked) {
        if ($tracked['id'] == $applicantID) {
            $applicantData = $tracked;
            unset($_SESSION['tracked_applicants'][$key]);
            break;
        }
    }
    
    // Reindex array
    $_SESSION['tracked_applicants'] = array_values($_SESSION['tracked_applicants']);
    
    // Remove from database if applicant was found
    if ($applicantData) {
        removeTrackingFromDatabase($mysqli, $userId, $applicantData);
    }
    
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

// If no action was taken, redirect to homepage
$mysqli->close();
header("Location: pages/homepage.php");
exit;