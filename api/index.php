<?php
/**
 * API Entry Point
 * This file serves as the primary entry point for the API, routing requests to appropriate handlers
 */

// Enable CORS for API access
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once __DIR__ . '/../database/db_connect.php';

// Include utility functions
require_once __DIR__ . '/endpoints/ApiKeyValidation.php';

// Utility function to send error response
function sendError($code, $message) {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'error' => $message]);
    exit;
}

// Utility function to send success response
function sendResponse($data) {
    echo json_encode(array_merge(['status' => 'success'], $data));
    exit;
}

// Get the requested endpoint from query parameters
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Log API request for monitoring (optional)
$requestLog = [
    'time' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'method' => $_SERVER['REQUEST_METHOD'],
    'endpoint' => $endpoint,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
];

// Append to log file (you can implement more sophisticated logging)
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
file_put_contents($logDir . '/api_requests.log', json_encode($requestLog) . "\n", FILE_APPEND);

// Check API key validation for endpoints that require it
$apiUser = null;

// Get the action from query parameters (if it exists)
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Skip API key validation for auth endpoint's login action only
if ($endpoint !== 'auth' || ($endpoint === 'auth' && $action === 'register')) {
    // Try to validate API key
    $apiUser = validateApiKey($mysqli);
    
    // If endpoint is not 'verify' and not the login action, then require valid API key
    if (!$apiUser && $endpoint !== 'verify' && !($endpoint === 'auth' && $action === 'login')) {
        sendError(401, "Invalid or missing API key");
    }
}

// Check permission for the current method based on endpoint
if ($apiUser) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Skip permission check for auth and verify endpoints
    if (!in_array($endpoint, ['auth', 'verify'])) {
        $hasPermission = false;
        
        if ($method === 'GET' && isset($apiUser['permissions']['get']) && $apiUser['permissions']['get']) {
            $hasPermission = true;
        } else if ($method === 'POST' && isset($apiUser['permissions']['post']) && $apiUser['permissions']['post']) {
            $hasPermission = true;
        } else if ($method === 'PUT' && isset($apiUser['permissions']['put']) && $apiUser['permissions']['put']) {
            $hasPermission = true;
        } else if ($method === 'DELETE' && isset($apiUser['permissions']['delete']) && $apiUser['permissions']['delete']) {
            $hasPermission = true;
        }
        
        if (!$hasPermission) {
            sendError(403, "API key does not have permission for " . $method . " method.");
        }
    }
}

// Route request to appropriate handler based on endpoint
switch ($endpoint) {
    case 'auth':
        // Handle authentication (login)
        handleAuth($mysqli,$apiUser);
        break;
        
    case 'verify':
        // Verify API key, simply returns user info if key is valid
        handleVerify($apiUser);
        break;
        
    case 'data':
        // Handle data operations with permission check
        handleData($mysqli, $apiUser);
        break;
        
    default:
        // Handle invalid endpoint
        sendError(404, "Unknown endpoint: $endpoint");
}

// Close database connection
$mysqli->close();

/**
 * Handle authentication requests
 */
function handleAuth($mysqli, $apiUser) {
    // Get the action from query parameters
    $action = isset($_GET['action']) ? $_GET['action'] : 'login';
    
    // Only allow POST method for authentication
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError(405, "Method not allowed for authentication");
    }
    
    // Get JSON data from request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Route to the appropriate handler based on action
    switch($action) {
        case 'login':
            // Check required fields for login
            if (!isset($data['email']) || !isset($data['password'])) {
                sendError(400, "Email and password are required");
            }
            
            // Continue with existing login logic
            $email = trim($data['email']);
            $password = $data['password'];
            
            // Validate email and password
            $stmt = $mysqli->prepare("SELECT userId, username, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                sendError(401, "Invalid credentials");
            }
            
            $user = $result->fetch_assoc();
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                sendError(401, "Invalid credentials");
            }
        
            
            // Send successful response with API key and user info
            sendResponse([
                'message' => 'Authentication successful',
                'user' => [
                    'userId' => $user['userId'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
            break;
            
            case 'register':
                if (!$apiUser || !$apiUser['permissions']['post']) {
                    sendError(403, "This API key does not have POST permission for registration");
                }
                
                // Check required fields for registration
                if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                    sendError(400, "Username, email, and password are required");
                }
                
                $username = trim($data['username']);
                $email = trim($data['email']);
                $password = $data['password'];
                
                // Check if username or email already exists
                $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row['count'] > 0) {
                    sendError(409, "Username or email already in use");
                }
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Generate verification token (optional)
                $verificationToken = bin2hex(random_bytes(32));
                
                // Insert new user
                $stmt = $mysqli->prepare("
                    INSERT INTO users (username, email, password, role, email_verified, verification_token, dateCreated)
                    VALUES (?, ?, ?, 'user', 0, ?, NOW())
                ");
                $stmt->bind_param("ssss", $username, $email, $hashedPassword, $verificationToken);
                
                if (!$stmt->execute()) {
                    sendError(500, "Failed to register user");
                }
                
                $userId = $mysqli->insert_id;
                
                // Send successful response
                sendResponse([
                    'message' => 'Registration successful',
                    'user' => [
                        'userId' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'role' => 'user'
                    ]
                ]);
                break;
            
        case 'logout':
            // Clear session data if using sessions
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_unset();
            session_destroy();
            
            sendResponse(['message' => 'Logout successful']);
            break;
            
        default:
            sendError(400, "Unknown action: $action");
    }
}

/**
 * Handle key verification requests
 */
function handleVerify($apiUser) {
    // Only allow GET method for verification
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendError(405, "Method not allowed for key verification");
    }
    
    if (!$apiUser) {
        sendError(401, "Invalid API key");
    }
    
    // Send user information
    sendResponse([
        'message' => 'API key is valid',
        'data' => [
            'user_id' => $apiUser['user_id'],
            'username' => $apiUser['username'],
            'email' => $apiUser['email'],
            'permissions' => $apiUser['permissions']
        ]
    ]);
}

/**
 * Handle data endpoint requests
 */
function handleData($mysqli, $apiUser) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Implementation would depend on your specific data model
    // This is a simple example that returns dummy data
    switch ($method) {
        case 'GET':
            // Ensure the API key has GET permission
            if (!$apiUser['permissions']['get']) {
                sendError(403, "This API key does not have GET permission");
            }
            
            // Check if specific ID is requested
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            
            if ($id) {
                // Get specific item by ID
                $stmt = $mysqli->prepare("SELECT * FROM users WHERE userId = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    sendError(404, "Item not found");
                }
                
                $item = $result->fetch_assoc();
                sendResponse(['data' => $item]);
            } else {
                // Get all items
                $stmt = $mysqli->prepare("SELECT * FROM users");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $items = [];
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
                
                sendResponse(['data' => $items]);
            }
            break;
            
            case 'POST':
                // Ensure the API key has POST permission
                if (!$apiUser['permissions']['post']) {
                    sendError(403, "This API key does not have POST permission");
                }
                
                // Get data from request body
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Validate required fields
                if (!isset($data['username']) || !isset($data['email'])) {
                    sendError(400, "Username and email are required fields");
                }
                
                // Insert data into database
                $stmt = $mysqli->prepare("
                    INSERT INTO users (username, email, password, dateCreated)
                    VALUES (?, ?, ?, NOW())
                ");
                
                $username = trim($data['username']);
                $email = $data['email'];
                $createdBy = $apiUser['user_id'];
                
                $stmt->bind_param("sdi", $username, $email, $createdBy);
                
                if (!$stmt->execute()) {
                    sendError(500, "Failed to create data: " . $mysqli->error);
                }
                
                $newId = $mysqli->insert_id;
                
                // Return the created data with its new ID
                sendResponse([
                    'message' => 'Data created successfully',
                    'data' => array_merge($data, ['id' => $newId])
                ]);
        break;
            
        case 'PUT':
            // Ensure the API key has PUT permission
            if (!$apiUser['permissions']['put']) {
                sendError(403, "This API key does not have PUT permission");
            }
            
            // Get data from request body
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check for required ID
            if (!isset($data['id'])) {
                sendError(400, "User ID is required for update");
            }
            
            // Check if user exists
            $stmt = $mysqli->prepare("SELECT userId FROM users WHERE userId = ?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                sendError(404, "User not found");
            }
            
            // Build update query based on provided fields
            $updateFields = [];
            $types = "";
            $params = [];
            
            if (isset($data['username'])) {
                $updateFields[] = "username = ?";
                $types .= "s";
                $params[] = trim($data['username']);
            }
            
            if (isset($data['email'])) {
                $updateFields[] = "email = ?";
                $types .= "s";
                $params[] = trim($data['email']);
            }
            
            if (isset($data['phoneNumber'])) {
                $updateFields[] = "phoneNumber = ?";
                $types .= "s";
                $params[] = trim($data['phoneNumber']);
            }
            
            if (isset($data['dateOfBirth'])) {
                $updateFields[] = "dateOfBirth = ?";
                $types .= "s";
                $params[] = trim($data['dateOfBirth']);
            }
            
            if (isset($data['role'])) {
                $updateFields[] = "role = ?";
                $types .= "s";
                $params[] = trim($data['role']);
            }
            
            if (isset($data['password'])) {
                // Hash the new password
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $updateFields[] = "password = ?";
                $types .= "s";
                $params[] = $hashedPassword;
            }
            
            if (isset($data['email_verified'])) {
                $updateFields[] = "email_verified = ?";
                $types .= "i";
                $params[] = (int)$data['email_verified'];
            }
            
            if (empty($updateFields)) {
                sendError(400, "No fields to update");
            }
            
            // Create update query
            $query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE userId = ?";
            $types .= "i";
            $params[] = $data['id'];
            
            // Execute update
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                sendError(500, "Failed to update user: " . $mysqli->error);
            }
            
            // Get updated user
            $stmt = $mysqli->prepare("SELECT userId, username, email, phoneNumber, dateOfBirth, role, email_verified FROM users WHERE userId = ?");
            $stmt->bind_param("i", $data['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $updatedUser = $result->fetch_assoc();
            
            sendResponse([
                'message' => 'User updated successfully',
                'data' => $updatedUser
            ]);
        break;
            
        case 'DELETE':
            // Ensure the API key has DELETE permission
            if (!$apiUser['permissions']['delete']) {
                sendError(403, "This API key does not have DELETE permission");
            }
            
            // Get ID from query parameter
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            
            if (!$id) {
                sendError(400, "ID is required for deletion");
            }
            
            // Check if item exists
            $stmt = $mysqli->prepare("SELECT userId FROM users WHERE userId = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                sendError(404, "Item not found");
            }
            
            // Delete the item
            $stmt = $mysqli->prepare("DELETE FROM users WHERE userId = ?");
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                sendError(500, "Failed to delete data: " . $mysqli->error);
            }
            
            sendResponse([
                'message' => 'Data deleted successfully',
                'id' => $id
            ]);
        break;
            
        default:
            sendError(405, "Method not allowed");
    }
}