<?php
require_once __DIR__ . '/../../utils/DatabaseHelper.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/api_helpers.php';
function handleApiKeysRequest($method, $pathSegments) {
    // Get key ID if provided in URL
    $keyId = isset($pathSegments[0]) && is_numeric($pathSegments[0]) ? (int)$pathSegments[0] : null;
    
    // Ensure user is authenticated
    requireAuth();
    
    // Get database connection
    $db = DatabaseHelper::getInstance();
    $userId = $_SESSION['user_id'];
    
    switch ($method) {
        case 'GET':
            if ($keyId) {
                // Get specific API key (masked for security)
                $key = $db->fetchOne(
                    "SELECT id, name, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 8)) AS masked_key, 
                    created_at, expires_at, last_used, is_active 
                    FROM api_keys WHERE id = ? AND user_id = ? AND is_active = 1",
                    "ii", 
                    [$keyId, $userId]
                );
                
                if ($key) {
                    echo json_encode($key);
                } else {
                    sendError(404, "API key not found");
                }
            } else {
                // List all active API keys for the user
                $keys = $db->fetchAll(
                    "SELECT id, name, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 8)) AS masked_key, 
                    created_at, expires_at, last_used, is_active 
                    FROM api_keys WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC",
                    "i", 
                    [$userId]
                );
                
                echo json_encode(['keys' => $keys]);
            }
            break;
            
        case 'POST':
            // Create a new API key
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name'])) {
                sendError(400, "Name is required for the API key");
            }
            
            // Set expiration days (default to 30 if not specified)
            $expiresInDays = isset($data['expires_in_days']) ? (int)$data['expires_in_days'] : 30;
            
            // Validate expiration (1-90 days)
            if ($expiresInDays < 1 || $expiresInDays > 90) {
                sendError(400, "Expiration must be between 1 and 90 days");
            }
            
            // Generate a secure random API key
            $apiKey = bin2hex(random_bytes(32));
            
            // Calculate expiration date
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days"));
            
            // Insert the key
            $keyId = $db->insert(
                "INSERT INTO api_keys (user_id, api_key, name, expires_at, is_active) VALUES (?, ?, ?, ?, 1)",
                "isss",
                [$userId, $apiKey, $data['name'], $expiresAt]
            );
            
            if ($keyId) {
                // Return the full key only when first created
                echo json_encode([
                    'id' => $keyId,
                    'name' => $data['name'],
                    'api_key' => $apiKey, // Full key shown only once
                    'expires_at' => $expiresAt,
                    'message' => 'API key created successfully'
                ]);
            } else {
                sendError(500, "Failed to create API key");
            }
            break;
            
        case 'DELETE':
            if (!$keyId) {
                sendError(400, "API key ID is required");
            }
            
            // Revoke the API key (soft delete)
            $result = $db->executeQuery(
                "UPDATE api_keys SET is_active = 0 WHERE id = ? AND user_id = ?",
                "ii",
                [$keyId, $userId]
            );
            
            if ($result['success'] && $result['affected_rows'] > 0) {
                echo json_encode(['message' => 'API key revoked successfully']);
            } else {
                sendError(404, "API key not found or already revoked");
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
}

// Helper function to send error responses if not defined elsewhere
if (!function_exists('sendError')) {
    function sendError($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}
?>