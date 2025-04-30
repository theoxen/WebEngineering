<?php
// Include utilities and helpers first
require_once __DIR__ . '/utils/api_helpers.php';

// Then include other dependencies
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/endpoints/AuthEndpoint.php';
require_once __DIR__ . '/endpoints/UserEndpoint.php';
require_once __DIR__ . '/endpoints/ApiKeysEndpoint.php';
require_once __DIR__ . '/endpoints/DataEndpoint.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

// Set JSON content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get endpoint and action from query parameters (for Postman testing)
$endpoint = $_GET['endpoint'] ?? '';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// Check for API key authentication
$apiUser = null;
if ($endpoint !== 'auth' || ($endpoint === 'auth' && $action !== 'login' && $action !== 'register')) {
    // Try API key authentication for non-auth endpoints or non-login/register actions
    $headers = getallheaders();
    $apiKey = null;
    
    // Look for X-API-Key in headers (case insensitive)
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'x-api-key') {
            $apiKey = $value;
            break;
        }
    }
    
    // If API key found, validate it
    if ($apiKey) {
        $db = DatabaseHelper::getInstance();
        
        // Get the API key with user info
        $keyData = $db->fetchOne(
            "SELECT k.id, k.userId, k.expires_at, k.is_active, 
                   u.userId as user_id, u.username, u.email, u.role
            FROM api_keys k
            JOIN users u ON k.userId = u.userId
            WHERE k.api_key = ?",
            "s",
            [$apiKey]
        );
        
        if ($keyData) {
            // Check if key is active
            if ($keyData['is_active']) {
                // Check if key has expired
                $now = new DateTime();
                $expiryDate = new DateTime($keyData['expires_at']);
                
                if ($now > $expiryDate) {
                    // Key has expired, mark it as inactive
                    $db->executeQuery(
                        "UPDATE api_keys SET is_active = 0 WHERE id = ?",
                        "i",
                        [$keyData['id']]
                    );
                    
                    // If it's the verify endpoint, send expiration error
                    if ($endpoint === 'verify') {
                        sendError(401, "API key has expired");
                    }
                } else {
                    // Key is valid and not expired
                    $apiUser = [
                        'userId' => $keyData['user_id'],
                        'username' => $keyData['username'],
                        'email' => $keyData['email'],
                        'role' => $keyData['role']
                    ];
                    
                    // Update last used timestamp
                    $db->executeQuery(
                        "UPDATE api_keys SET last_used = NOW() WHERE id = ?",
                        "i",
                        [$keyData['id']]
                    );
                }
            } else if ($endpoint === 'verify') {
                // Key exists but is inactive
                sendError(401, "API key is inactive or has been revoked");
            }
        } else if ($endpoint === 'verify') {
            // Key doesn't exist
            sendError(401, "Invalid API key");
        }
    }
}

// Check authentication status
$isAuthenticated = false;

// If API key authentication was successful
if ($apiUser) {
    $isAuthenticated = true;
    // Use the API user's ID for operations
    $_SESSION['userId'] = $apiUser['userId'];
    $_SESSION['role'] = $apiUser['role'];

    // If authentication is via API key, restrict to read-only operations for non-admin users
    if ($apiUser && $method !== 'GET' && $apiUser['role'] !== 'admin') {
        // Allow POST for verify endpoint (special case)
        if (!($endpoint === 'verify' && $method === 'POST')) {
            sendError(403, "API keys can only be used for read operations unless you have admin privileges");
        }
    }
} else if (isset($_SESSION['userId'])) {
    // Fallback to session authentication
    $isAuthenticated = true;
}

// Define routes that don't require authentication
$publicRoutes = ['auth', 'verify'];

// Check authentication for protected routes
if (!$isAuthenticated && !in_array($endpoint, $publicRoutes)) {
    sendError(401, "Authentication required");
}

// Handle different endpoints
try {
    switch ($endpoint) {
        case 'auth':
            switch ($action) {
                case 'login':
                    handleLogin($method);
                    break;
                    
                case 'register':
                    handleRegister($method);
                    break;
                    
                case 'logout':
                    handleLogout($method);
                    break;
                    
                case 'reset-password':
                    handleResetPassword($method);
                    break;
                    
                default:
                    sendError(404, "Auth action not found");
            }
            break;
            
        case 'users':
            // If ID is provided, pass it as first element in pathSegments array
            $segments = [];
            if ($id !== null) {
                $segments[] = $id;
            } elseif ($action) {
                $segments[] = $action;
            }
            handleUserRequest($method, $segments);
            break;
            
        case 'api-keys':
            $segments = [];
            if ($id !== null) {
                $segments[] = $id;
            } elseif ($action) {
                $segments[] = $action;
            }
            handleApiKeysRequest($method, $segments);
            break;
            
        case 'data':
            $segments = [];
            if ($id !== null) {
                $segments[] = $id;
            } elseif ($action) {
                $segments[] = $action;
            }
            handleDataRequest($method, $segments);
            break;
            
        case 'verify':
            if ($method === 'GET') {
                // Check if we have an API key user
                if ($apiUser) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'API key is valid',
                        'data' => [
                            'user_id' => $apiUser['userId'],
                            'username' => $apiUser['username'],
                            'email' => $apiUser['email'],
                            'role' => $apiUser['role']
                        ]
                    ]);
                } else {
                    sendError(401, "Invalid or missing API key");
                }
            } else {
                sendError(405, "Method not allowed");
            }
            break;
            
        case '':
            // API root - return API information
            echo json_encode([
                'name' => 'WebEngineering API',
                'version' => '1.0',
                'endpoints' => [
                    '/api/index.php?endpoint=auth&action=login',
                    '/api/index.php?endpoint=auth&action=register',
                    '/api/index.php?endpoint=auth&action=logout',
                    '/api/index.php?endpoint=auth&action=reset-password',
                    '/api/index.php?endpoint=users',
                    '/api/index.php?endpoint=users&id=1',
                    '/api/index.php?endpoint=data',
                    '/api/index.php?endpoint=data&id=1',
                    '/api/index.php?endpoint=api-keys',
                    '/api/index.php?endpoint=verify'
                ],
                'documentation' => '/pages/documentation.php'
            ]);
            break;
            
        default:
            sendError(404, "Endpoint not found");
    }
} catch (Exception $e) {
    sendError(500, "Server error: " . $e->getMessage());
}

// Function to create a basic success response
function basicSuccess($message) {
    echo json_encode([
        'status' => 'success',
        'message' => $message
    ]);
    exit;
}
?>

