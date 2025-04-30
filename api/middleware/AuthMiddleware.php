<?php
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check session-based authentication
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check token-based authentication for API
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        // Validate token (implement JWT validation or your token system)
        if (validateToken($token)) {
            return true;
        }
    }
    
    // No valid authentication
    if (isApiRequest()) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    } else {
        // Redirect web pages to login
        header("Location: /WebEngineering/pages/login.php");
        exit;
    }
}

function isApiRequest() {
    return strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
}

function validateToken($token) {
    // Implement your token validation logic here
    // This is just a placeholder
    return false;
}

function requireAdmin() {
    requireAuth();
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        if (isApiRequest()) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden: Admin access required"]);
            exit;
        } else {
            // Redirect web pages
            header("Location: /WebEngineering/pages/homepage.php");
            exit;
        }
    }
    
    return true;
}

/**
 * Validates the API key from the request headers
 * 
 * @return array|false Returns the API key data if valid, false otherwise
 */
function validateApiKey() {
    include '../database/db_connect.php';
    
    // Get the API key from the request headers
    $headers = getallheaders();
    $apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : null;
    
    if (!$apiKey) {
        return false;
    }
    
    // Get database connection
    $db = DatabaseHelper::getInstance();
    
    // Check if the API key exists and is valid
    $apiKeyData = $db->fetchOne(
        "SELECT ak.*, ak.userId as user_id 
         FROM api_keys ak
         WHERE ak.api_key = ? 
         AND ak.is_active = 1 
         AND ak.expires_at > NOW()",
        "s",
        [$apiKey]
    );
    
    if (!$apiKeyData) {
        return false;
    }
    
    // Add a hard-coded 'read' permission
    $apiKeyData['permissions'] = 'read';
    
    return $apiKeyData;
}



?>