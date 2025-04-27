<?php
/**
 * API Key Validation
 * Validates API keys and marks expired ones as inactive
 */

require_once __DIR__ . '/../database/db_connect.php';

/**
 * Get the API key from headers or query string
 */
function getApiKey() {
    $headers = getallheaders();
    
    // Look for X-API-Key in headers (case insensitive)
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'x-api-key') {
            return $value;
        }
    }
    
    // Fall back to query string
    return $_GET['api_key'] ?? null;
}

/**
 * Check if API key is valid and not expired
 * Returns user data if valid, false otherwise
 */
function validateApiKey($mysqli) {
    $apiKey = getApiKey();
    
    if (!$apiKey) {
        return false;
    }
    
    // First check if key exists and get associated data
    $stmt = $mysqli->prepare("
        SELECT k.id, k.userId, k.expires_at, k.is_active, 
               u.id as user_id, u.username, u.email, u.role
        FROM api_keys k
        JOIN users u ON k.userId = u.id
        WHERE k.api_key = ?
    ");
    
    if (!$stmt) {
        error_log("SQL Error in validateApiKey: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $keyData = $result->fetch_assoc();
    
    // Check if key is marked as inactive
    if (!$keyData['is_active']) {
        return false;
    }
    
    // Check if key has expired
    $now = new DateTime();
    $expiryDate = new DateTime($keyData['expires_at']);
    
    if ($now > $expiryDate) {
        // Key has expired, mark it as inactive
        $updateStmt = $mysqli->prepare("
            UPDATE api_keys
            SET is_active = FALSE
            WHERE id = ?
        ");
        $updateStmt->bind_param("i", $keyData['id']);
        $updateStmt->execute();
        
        return false;
    }
    
    // Key is valid, update last used timestamp
    $updateStmt = $mysqli->prepare("
        UPDATE api_keys
        SET last_used = NOW()
        WHERE id = ?
    ");
    $updateStmt->bind_param("i", $keyData['id']);
    $updateStmt->execute();
    
    // Return user data for the API consumer
    return [
        'user_id' => $keyData['user_id'],
        'username' => $keyData['username'],
        'email' => $keyData['email'],
        'role' => $keyData['role']
    ];
}

/**
 * Require a valid API key for access
 * Dies with error message if key is invalid
 */
function requireApiKey() {
    global $mysqli;
    
    $user = validateApiKey($mysqli);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'error' => 'Invalid or expired API key'
        ]);
        exit;
    }
    
    return $user;
}
?>