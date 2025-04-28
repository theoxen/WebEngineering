<?php
// api-key-validation.php - Include this in any API endpoint that requires authentication

/**
 * Validates an API key from the request headers
 * 
 * @return array|false Returns user info if valid, false if invalid
 */
function validateApiKey() {
    global $conn;
    
    // Check if API key is provided in the headers
    $headers = getallheaders();
    $apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';
    
    if (empty($apiKey)) {
        return false;
    }
    
    // Check if the key exists and is valid (not expired, not revoked)
    $stmt = $conn->prepare("
        SELECT users.id, users.email, users.account_type 
        FROM api_keys 
        JOIN users ON api_keys.user_id = users.id
        WHERE api_keys.api_key = ? 
        AND api_keys.revoked = 0 
        AND api_keys.expired = 0
        AND api_keys.expires_at > NOW()
    ");
    
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

// Example usage in an API endpoint:
/*
require_once 'api-key-validation.php';

// Check if the API key is valid
$user = validateApiKey();
if (!$user) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired API key'
    ]);
    exit();
}

// Continue with the API logic using $user data
*/