<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize tracked applicants array in session if not exists
if (!isset($_SESSION['tracked_applicants'])) {
    $_SESSION['tracked_applicants'] = [];
}

// Database connection
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "cei326omada1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to fetch applicant data from database
function getApplicantData($conn, $applicantID) {
    // Check if categories table exists
    $sql = "SHOW TABLES LIKE 'categories'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        // Categories table exists
        $sql = "SELECT r.*, c.categoryName 
                FROM rankinglist r 
                JOIN categories c ON r.categoryID = c.categoryID 
                WHERE r.id = ?";
    } else {
        // No categories table, just query rankinglist
        $sql = "SELECT r.*, 'Unknown' as categoryName 
                FROM rankinglist r 
                WHERE r.id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicantID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Check if form was submitted to track multiple applicants
// Look for both 'track_applicants' and 'track_selected' parameters
if ((isset($_POST['track_applicants']) && is_array($_POST['track_applicants'])) || 
    (isset($_POST['track_selected']) && isset($_POST['track_applicants']) && is_array($_POST['track_applicants']))) {
    
    // Get selected applicant IDs
    $applicantIDs = $_POST['track_applicants'];
    
    // For each selected applicant
    foreach ($applicantIDs as $applicantID) {
        // Check if this applicant is already being tracked
        $alreadyTracked = false;
        foreach ($_SESSION['tracked_applicants'] as $tracked) {
            if ($tracked['id'] == $applicantID) {
                $alreadyTracked = true;
                break;
            }
        }
        
        if (!$alreadyTracked) {
            // Fetch applicant data from database
            $applicantData = getApplicantData($conn, $applicantID);
            
            if ($applicantData) {
                $_SESSION['tracked_applicants'][] = $applicantData;
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
    
    // Check if already tracked
    $alreadyTracked = false;
    foreach ($_SESSION['tracked_applicants'] as $tracked) {
        if ($tracked['id'] == $applicantID) {
            $alreadyTracked = true;
            break;
        }
    }
    
    if (!$alreadyTracked) {
        // Fetch applicant data
        $applicantData = getApplicantData($conn, $applicantID);
        
        if ($applicantData) {
            $_SESSION['tracked_applicants'][] = $applicantData;
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
    
    // Remove from tracked list
    foreach ($_SESSION['tracked_applicants'] as $key => $tracked) {
        if ($tracked['id'] == $applicantID) {
            unset($_SESSION['tracked_applicants'][$key]);
            break;
        }
    }
    
    // Reindex array
    $_SESSION['tracked_applicants'] = array_values($_SESSION['tracked_applicants']);
    
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

// If no action was taken, redirect to homepage
$conn->close();
header("Location: pages/homepage.php");
exit;