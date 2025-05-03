<?php
/**
 * API Keys Handler
 * This file implements API key management functionality with permissions
 */

// Include database connection
require_once __DIR__ . '/../database/db_connect.php';

date_default_timezone_set('Europe/Nicosia');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error response helper function
function sendApiError($code, $message) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'error' => $message]);
    exit;
}

// Success response helper function
function sendApiResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['status' => 'success'], $data));
    exit;
}

// Check authentication
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendApiError(401, "Authentication required");
    }
    return $_SESSION['user_id'];
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Clean up expired keys
function cleanupExpiredKeys($mysqli) {
    $stmt = $mysqli->prepare("
        UPDATE api_keys 
        SET is_active = FALSE 
        WHERE expires_at < NOW() AND is_active = TRUE
    ");
    $stmt->execute();
}

// Handle API key requests based on HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get the action from query parameter or default to 'list'
$action = isset($_GET['action']) ? $_GET['action'] : 'list_keys';

// Get user ID from session (will exit if not authenticated)
$userId = checkAuth();

// Run cleanup function to mark expired keys as inactive
cleanupExpiredKeys($mysqli);

// Handle different actions
switch ($action) {
    case 'list_keys':
        if ($method !== 'GET') {
            sendApiError(405, "Method not allowed");
        }
        
        // Get all active API keys for the current user
        $stmt = $mysqli->prepare("
            SELECT id, name, 
                CONCAT(LEFT(api_key, 4), '************************', RIGHT(api_key, 4)) AS masked_key,
                created_at, expires_at, last_used, is_active,
                allow_get, allow_post, allow_put, allow_delete
            FROM api_keys 
            WHERE userId = ? AND is_active = TRUE
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $keys = $result->fetch_all(MYSQLI_ASSOC);
        
        // Add expiration status and days remaining
        foreach ($keys as &$key) {
            $expiryDate = new DateTime($key['expires_at']);
            $now = new DateTime();
            $interval = $now->diff($expiryDate);
            
            // Calculate days, hours, minutes, seconds
            $key['days_remaining'] = $expiryDate > $now ? $interval->days : 0;
            $key['hours_remaining'] = $expiryDate > $now ? $interval->h : 0;
            $key['minutes_remaining'] = $expiryDate > $now ? $interval->i : 0;
            $key['seconds_remaining'] = $expiryDate > $now ? $interval->s : 0;
            $key['is_expired'] = $expiryDate < $now;
        }
        
        sendApiResponse(['keys' => $keys]);
        break;
        
    case 'admin_list_all_keys':
        // Only admins can list all keys
        if (!isAdmin()) {
            sendApiError(403, "Admin privileges required");
        }
        
        if ($method !== 'GET') {
            sendApiError(405, "Method not allowed");
        }
        
        // Get all API keys with user details
        $stmt = $mysqli->prepare("
            SELECT k.id, k.name, k.userId,
                CONCAT(LEFT(k.api_key, 4), '************************', RIGHT(k.api_key, 4)) AS masked_key,
                k.created_at, k.expires_at, k.last_used, k.is_active,
                k.allow_get, k.allow_post, k.allow_put, k.allow_delete,
                u.username, u.email
            FROM api_keys k
            JOIN users u ON k.userId = u.userId
            ORDER BY k.created_at DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $keys = $result->fetch_all(MYSQLI_ASSOC);
        
        // Add expiration status
        foreach ($keys as &$key) {
            $expiryDate = new DateTime($key['expires_at']);
            $now = new DateTime();
            $interval = $now->diff($expiryDate);
            
            $key['days_remaining'] = $expiryDate > $now ? $interval->days : 0;
            $key['hours_remaining'] = $expiryDate > $now ? $interval->h : 0;
            $key['minutes_remaining'] = $expiryDate > $now ? $interval->i : 0;
            $key['seconds_remaining'] = $expiryDate > $now ? $interval->s : 0;
            $key['is_expired'] = $expiryDate < $now;
        }
        
        sendApiResponse(['keys' => $keys]);
        break;
        
    case 'search_users':
        // Only admins can search users
        if (!isAdmin()) {
            sendApiError(403, "Admin privileges required");
        }
        
        if ($method !== 'GET') {
            sendApiError(405, "Method not allowed");
        }
        
        $search = isset($_GET['q']) ? $_GET['q'] : '';
        
        if (empty($search)) {
            // Get first 20 users
            $stmt = $mysqli->prepare("
                SELECT userId, username, email, role
                FROM users
                ORDER BY username
                LIMIT 20
            ");
            $stmt->execute();
        } else {
            // Search by username or email
            $searchParam = "%$search%";
            $stmt = $mysqli->prepare("
                SELECT userId, username, email, role
                FROM users
                WHERE username LIKE ? OR email LIKE ?
                ORDER BY username
                LIMIT 20
            ");
            $stmt->bind_param("ss", $searchParam, $searchParam);
            $stmt->execute();
        }
        
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        sendApiResponse(['users' => $users]);
        break;
        
    case 'create_key':
        if ($method !== 'POST') {
            sendApiError(405, "Method not allowed");
        }
        
        // Get JSON data from request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || empty(trim($data['name']))) {
            sendApiError(400, "Key name is required");
        }
        
        // For admin users, allow creating a key for another user
        $targetUserId = $userId;
        
        if (isAdmin() && isset($data['target_user_id'])) {
            $targetUserId = (int)$data['target_user_id'];
            
            // Verify the user exists
            $userCheck = $mysqli->prepare("SELECT userId FROM users WHERE userId = ?");
            $userCheck->bind_param("i", $targetUserId);
            $userCheck->execute();
            
            if ($userCheck->get_result()->num_rows === 0) {
                sendApiError(404, "User not found");
            }
        }
        
        // Validate expiration (default 30 days, max 90 days)
        $expiresInDays = isset($data['expires_in_days']) ? (int)$data['expires_in_days'] : 30;
        
        // Special handling for 1-minute expiration (for testing)
        if ($expiresInDays == 1) {
            // When user selects "1", make it expire in 1 minute for testing
            $expiresAt = date('Y-m-d H:i:s', strtotime("+1 minute"));
        } else {
            // Normal handling for other durations
            if ($expiresInDays < 7 || $expiresInDays > 90) {
                sendApiError(400, "Expiration must be between 7 and 90 days");
            }
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days"));
        }
        
        // Generate a secure API key
        $apiKey = bin2hex(random_bytes(32));
        
        // Set permissions (admin only)
        $allowGet = isset($data['allow_get']) ? (int)$data['allow_get'] : 1;
        $allowPost = isset($data['allow_post']) ? (int)$data['allow_post'] : 0;
        $allowPut = isset($data['allow_put']) ? (int)$data['allow_put'] : 0;
        $allowDelete = isset($data['allow_delete']) ? (int)$data['allow_delete'] : 0;
        
        // Regular users can only create keys with read permissions
        if (!isAdmin()) {
            $allowGet = 1;
            $allowPost = 0;
            $allowPut = 0;
            $allowDelete = 0;
        }
        
        // Store in database
        $stmt = $mysqli->prepare("
            INSERT INTO api_keys (userId, api_key, name, expires_at, allow_get, allow_post, allow_put, allow_delete)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssiiii", 
            $targetUserId, 
            $apiKey, 
            $data['name'], 
            $expiresAt, 
            $allowGet, 
            $allowPost, 
            $allowPut, 
            $allowDelete
        );
        
        if ($stmt->execute()) {
            $keyId = $mysqli->insert_id;
            
            // Return the created key (full key returned only at creation time)
            sendApiResponse([
                'message' => 'API key created successfully',
                'key' => [
                    'id' => $keyId,
                    'name' => $data['name'],
                    'api_key' => $apiKey,
                    'created_at' => date('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt,
                    'allow_get' => $allowGet,
                    'allow_post' => $allowPost,
                    'allow_put' => $allowPut,
                    'allow_delete' => $allowDelete
                ]
            ], 201);
        } else {
            sendApiError(500, "Failed to create API key: " . $mysqli->error);
        }
        
        break;
        
    case 'update_key_permissions':
        // Only admins can update permissions
        if (!isAdmin()) {
            sendApiError(403, "Admin privileges required");
        }
        
        if ($method !== 'PUT') {
            sendApiError(405, "Method not allowed");
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['key_id']) || !is_numeric($data['key_id'])) {
            sendApiError(400, "Valid key ID is required");
        }
        
        $keyId = (int)$data['key_id'];
        
        // Verify the key exists
        $keyCheck = $mysqli->prepare("SELECT id FROM api_keys WHERE id = ? AND is_active = 1");
        $keyCheck->bind_param("i", $keyId);
        $keyCheck->execute();
        
        if ($keyCheck->get_result()->num_rows === 0) {
            sendApiError(404, "API key not found or inactive");
        }
        
        // Update permissions
        $allowGet = isset($data['allow_get']) ? (int)$data['allow_get'] : 1;
        $allowPost = isset($data['allow_post']) ? (int)$data['allow_post'] : 0;
        $allowPut = isset($data['allow_put']) ? (int)$data['allow_put'] : 0;
        $allowDelete = isset($data['allow_delete']) ? (int)$data['allow_delete'] : 0;
        
        $stmt = $mysqli->prepare("
            UPDATE api_keys
            SET allow_get = ?, allow_post = ?, allow_put = ?, allow_delete = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iiiii", $allowGet, $allowPost, $allowPut, $allowDelete, $keyId);
        
        if ($stmt->execute()) {
            sendApiResponse([
                'message' => 'API key permissions updated successfully'
            ]);
        } else {
            sendApiError(500, "Failed to update API key permissions: " . $mysqli->error);
        }
        
        break;
        
    case 'revoke_key':
        if ($method !== 'DELETE') {
            sendApiError(405, "Method not allowed");
        }
        
        // Check if key_id is provided
        if (!isset($_GET['key_id']) || !is_numeric($_GET['key_id'])) {
            sendApiError(400, "Invalid key ID");
        }
        
        $keyId = (int)$_GET['key_id'];
        
        if (isAdmin()) {
            // Admin can revoke any key
            $stmt = $mysqli->prepare("
                SELECT id FROM api_keys 
                WHERE id = ? AND is_active = TRUE
            ");
            $stmt->bind_param("i", $keyId);
        } else {
            // Regular user can only revoke their own keys
            $stmt = $mysqli->prepare("
                SELECT id FROM api_keys 
                WHERE id = ? AND userId = ? AND is_active = TRUE
            ");
            $stmt->bind_param("ii", $keyId, $userId);
        }
        
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            sendApiError(404, "API key not found or already revoked");
        }
        
        // Revoke the key (soft delete)
        $stmt = $mysqli->prepare("
            UPDATE api_keys 
            SET is_active = FALSE 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $keyId);
        
        if ($stmt->execute()) {
            sendApiResponse([
                'message' => 'API key revoked successfully'
            ]);
        } else {
            sendApiError(500, "Failed to revoke API key: " . $mysqli->error);
        }
        break;
        
        case 'get_full_key':
            if ($method !== 'GET') {
                sendApiError(405, "Method not allowed");
            }
            
            // Get key ID from query param
            if (!isset($_GET['key_id']) || !is_numeric($_GET['key_id'])) {
                sendApiError(400, "Invalid key ID");
            }
            
            $keyId = (int)$_GET['key_id'];
            
            if (isAdmin()) {
                // Admin can get any key
                $stmt = $mysqli->prepare("
                    SELECT api_key FROM api_keys 
                    WHERE id = ? AND is_active = TRUE
                ");
                $stmt->bind_param("i", $keyId);
            } else {
                // Regular user can only get their own keys
                $stmt = $mysqli->prepare("
                    SELECT api_key FROM api_keys 
                    WHERE id = ? AND userId = ? AND is_active = TRUE
                ");
                $stmt->bind_param("ii", $keyId, $userId);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                sendApiError(404, "API key not found or not authorized");
            }
            
            $keyData = $result->fetch_assoc();
            
            // Return the full key
            sendApiResponse([
                'api_key' => $keyData['api_key']
            ]);
            break;
        
    default:
        sendApiError(400, "Invalid action");
}
?>