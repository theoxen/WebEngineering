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
    $userId = $_SESSION['userId'];
    
    switch ($method) {
        case 'GET':
            if ($keyId) {
                // Get specific API key (masked for security)
                $key = $db->fetchOne(
                    "SELECT id, name, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 8)) AS masked_key, expires_at, last_used, is_active 
                    FROM api_keys WHERE id = ? AND userId = ? AND is_active = 1",
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
                    "SELECT id, name, CONCAT(LEFT(api_key, 8), '...', RIGHT(api_key, 8)) AS masked_key, expires_at, last_used, is_active 
                    FROM api_keys WHERE userId = ? AND is_active = 1 ",
                    "i", 
                    [$userId]
                );
                
                echo json_encode(['keys' => $keys]);
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
}

function handleAdminApiKeysRequest($method, $pathSegments) {
    // Check if the user is an admin
    requireAdmin();
    
    $db = DatabaseHelper::getInstance();
    
    switch ($method) {
        case 'GET':
            if (isset($pathSegments[0]) && $pathSegments[0] === 'all') {
                // Get all API keys from all users
                $keys = $db->fetchAll(
                    "SELECT k.*, u.username 
                     FROM api_keys k
                     JOIN users u ON k.userId = u.userId
                     ORDER BY k.created_at DESC"
                );
                
                echo json_encode(['keys' => $keys]);
            } else {
                sendError(400, "Invalid action");
            }
            break;
            
        case 'POST':
            if (isset($pathSegments[0]) && $pathSegments[0] === 'for-user') {
                // Create an API key for a specific user
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['userId']) || !isset($data['name']) || !isset($data['expires_in_days'])) {
                    sendError(400, "Missing required fields");
                }
                
                // Generate a new API key
                $apiKey = bin2hex(random_bytes(32));
                
                // Calculate expiry date
                $expiryDate = date('Y-m-d H:i:s', strtotime("+{$data['expires_in_days']} days"));
                
                // Insert the new API key
                $result = $db->executeQuery(
                    "INSERT INTO api_keys (userId, api_key, name, expires_at) VALUES (?, ?, ?, ?)",
                    "isss",
                    [$data['userId'], $apiKey, $data['name'], $expiryDate]
                );
                
                if ($result['success']) {
                    $keyId = $result['insert_id'];
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'API key created successfully',
                        'key' => [
                            'id' => $keyId,
                            'name' => $data['name'],
                            'api_key' => $apiKey,
                            'created_at' => date('Y-m-d H:i:s'),
                            'expires_at' => $expiryDate
                        ]
                    ]);
                } else {
                    sendError(500, "Failed to create API key: " . $result['error']);
                }
            } else {
                sendError(400, "Invalid action");
            }
            break;
            
        case 'DELETE':
            if (isset($pathSegments[0]) && $pathSegments[0] === 'for-user') {
                // Get the user ID from query parameter
                $userId = $_GET['userId'] ?? null;
                
                if (!$userId) {
                    sendError(400, "User ID is required");
                }
                
                // Revoke all API keys for the specified user
                $result = $db->executeQuery(
                    "UPDATE api_keys SET is_active = 0 WHERE userId = ?",
                    "i",
                    [$userId]
                );
                
                if ($result['success']) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'All API keys for user revoked successfully',
                        'count' => $result['affected_rows']
                    ]);
                } else {
                    sendError(500, "Failed to revoke API keys: " . $result['error']);
                }
            } else {
                sendError(400, "Invalid action");
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