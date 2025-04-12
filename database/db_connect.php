<?php
// Database configuration FOR DEPLOYMENT AND DEVELOPMENT
$dbHost = "127.0.0.1";
$dbUsername = "cei326omada1user";
$dbPassword = "UPJ!AqeNu3i1!kE";
$dbName = "cei326omada1";

// Create database connection
$mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($mysqli->connect_error) {
	die("Failed connecting to the database: " . $mysqli->connect_error);
}


$mysqli = new mysqli("localhost", "your_username", "your_password", "your_database");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Add this new function to provide PDO connection for API endpoints
function getDbConnection() {	
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=your_database;charset=utf8mb4',
            'your_username', 
            'your_password',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Log the error and return a generic message
        error_log("Database connection error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed"]);
        exit;
    }
}

// Return the mysqli connection for backward compatibility
return $mysqli;

?>