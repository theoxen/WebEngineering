<?php
// Database configuration
$dbHost = "127.0.0.1";
$dbUsername = "root";
$dbPassword = "";
$dbName = "dioristeoidb";

// Create database connection
$mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($mysqli->connect_error) {
	die("Failed connecting to the database: " . $mysqli->connect_error);
}
?>