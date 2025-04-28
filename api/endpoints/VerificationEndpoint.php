<?php
/**
 * API Key Verification Endpoint
 */
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/ApiKeyValidation.php';

// Set content type to JSON
header('Content-Type: application/json');

// This will exit with error if API key is invalid
$user = requireApiKey();

// If we reach here, the API key is valid
echo json_encode([
    'status' => 'success',
    'message' => 'API key is valid',
    'data' => [
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
    ]
]);
?>