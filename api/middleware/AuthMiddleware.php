<?php
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check API key authentication first
    $headers = getallheaders();
    $apiKey = null;
    
    // Check for X-API-Key header (case insensitive)
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'x-api-key') {
            $apiKey = $value;
            break;
        }
    }
    
    if ($apiKey) {
        // Validate API key
        $apiUser = validateApiKey();
        if ($apiUser) {
            // Set session data for API user with admin privileges
            $_SESSION['userId'] = $apiUser['user_id'];
            $_SESSION['role'] = 'admin'; // Grant admin role to all API key users
            return true;
        }
    }
    
    // Check session-based authentication
    if (isset($_SESSION['userId'])) {
        return true;
    }
    
    // Check token-based authentication for API
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
    
    // Check if authentication is via API key
    $headers = getallheaders();
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'x-api-key') {
            // API key users automatically have admin privileges
            return true;
        }
    }
    
    // For session-based authentication, check the role
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
    // Get the API key from the request headers
    $headers = getallheaders();
    $apiKey = null;
    
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'x-api-key') {
            $apiKey = $value;
            break;
        }
    }
    
    if (!$apiKey) {
        return false;
    }
    
    // Get database connection
    require_once __DIR__ . '/../../utils/DatabaseHelper.php';
    $db = DatabaseHelper::getInstance();
    
    // Check if the API key exists and is valid
    $apiKeyData = $db->fetchOne(
        "SELECT k.id, k.userId, k.name, k.created_at, k.expires_at, k.is_active, 
                k.allow_get, k.allow_post, k.allow_put, k.allow_delete,
                u.userId as user_id, u.username, u.email, u.role
         FROM api_keys k
         JOIN users u ON k.userId = u.userId
         WHERE k.api_key = ?",
        "s",
        [$apiKey]
    );
    
    if (!$apiKeyData) {
        return false;
    }
    
    // Check if the key is active
    if (!$apiKeyData['is_active']) {
        return false;
    }
    
    // Check if the key has expired
    $now = new DateTime();
    $expiryDate = new DateTime($apiKeyData['expires_at']);
    
    if ($now > $expiryDate) {
        // Key has expired, mark it as inactive
        $db->executeQuery(
            "UPDATE api_keys SET is_active = 0 WHERE id = ?",
            "i",
            [$apiKeyData['id']]
        );
        return false;
    }
    
    // Update last used timestamp
    $db->executeQuery(
        "UPDATE api_keys SET last_used = NOW() WHERE id = ?",
        "i",
        [$apiKeyData['id']]
    );
    
    // Add permissions to API key data
    $apiKeyData['permissions'] = [
        'get' => (bool)$apiKeyData['allow_get'],
        'post' => (bool)$apiKeyData['allow_post'],
        'put' => (bool)$apiKeyData['allow_put'],
        'delete' => (bool)$apiKeyData['allow_delete']
    ];
    
    return $apiKeyData;
}
?>