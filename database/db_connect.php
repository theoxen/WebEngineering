<?php
// Database configuration FOR DEPLOYMENT AND DEVELOPMENT
$dbHost = "127.0.0.1";
$dbUsername = "cei326omada1user";
$dbPassword = "UPJ!AqeNu3i1!kE";
$dbName = "cei326omada1user";

// Create database connection
$mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($mysqli->connect_error) {
	die("Failed connecting to the database: " . $mysqli->connect_error);
}




?>