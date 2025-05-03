<?php
// Disable error display and set content type
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

// Start a session
session_start();

try {
    // Try to include database connection
    require_once __DIR__ . '/../database/db_connect.php';
    
    // Test database connection
    $dbTest = isset($mysqli) && !$mysqli->connect_error 
        ? "Connected successfully" 
        : "Connection error: " . ($mysqli->connect_error ?? "mysqli not initialized");
    
    // Try to create a test API key
    $testSuccess = false;
    $errorInfo = "Not attempted";
    
    if (isset($mysqli) && !$mysqli->connect_error) {
        // Get user ID from session
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            // Generate a test API key (not actually inserting to database)
            $testApiKey = bin2hex(random_bytes(16));
            $testSuccess = true;
        } else {
            $errorInfo = "User ID not found in session";
        }
    }
    
    // Return test results
    echo json_encode([
        'success' => true,
        'test_results' => [
            'php_version' => PHP_VERSION,
            'database_test' => $dbTest,
            'session_data' => $_SESSION,
            'api_key_test' => [
                'success' => $testSuccess,
                'info' => $errorInfo,
                'test_key' => $testSuccess ? $testApiKey : null
            ]
        ]
    ]);
    
} catch (Exception $e) {
    // Return any exceptions as JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>