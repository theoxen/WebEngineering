<?php
/**
 * API Keys Handler
 * This file implements API key management functionality
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
                created_at, expires_at, last_used, is_active 
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
        
    case 'create_key':
        if ($method !== 'POST') {
            sendApiError(405, "Method not allowed");
        }
        
        // Get JSON data from request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || empty(trim($data['name']))) {
            sendApiError(400, "Key name is required");
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
        
        // Store in database
        $stmt = $mysqli->prepare("
            INSERT INTO api_keys (userId, api_key, name, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $userId, $apiKey, $data['name'], $expiresAt);
        
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
                    'expires_at' => $expiresAt
                ]
            ], 201);
        } else {
            sendApiError(500, "Failed to create API key: " . $mysqli->error);
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
        
        // First verify the key belongs to this user
        $stmt = $mysqli->prepare("
            SELECT id FROM api_keys 
            WHERE id = ? AND userId = ? AND is_active = TRUE
        ");
        $stmt->bind_param("ii", $keyId, $userId);
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
        
    default:
        sendApiError(400, "Invalid action");
}
?>