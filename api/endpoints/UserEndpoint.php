<?php
require_once __DIR__ . '/../../utils/DatabaseHelper.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/api_helpers.php';

function handleUserRequest($method, $pathSegments) {
    // Get user ID if provided in URL
    $userId = isset($pathSegments[0]) && is_numeric($pathSegments[0]) ? (int)$pathSegments[0] : null;
    $action = isset($pathSegments[0]) && !is_numeric($pathSegments[0]) ? $pathSegments[0] : '';
    
    // Get database connection
    $db = DatabaseHelper::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($userId) {
                // Get specific user by ID
                requireAuth();
                
                $user = $db->fetchOne(
                    "SELECT userId, username, email, role FROM users WHERE userId = ?", 
                    "i", 
                    [$userId]
                );
                
                if ($user) {
                    echo json_encode($user);
                } else {
                    sendError(404, "User not found");
                }
            } else if ($action === 'me') {
                // Get current user details
                requireAuth();
                $currentUserId = $_SESSION['userId'];
                $user = $db->fetchOne(
                    "SELECT userId, username, email, role FROM users WHERE userId = ?", 
                    "i", 
                    [$currentUserId]
                );
                echo json_encode($user);
            } else {
                // List users (with pagination)
                requireAuth();
                
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                $offset = ($page - 1) * $limit;
                
                $users = $db->fetchAll(
                    "SELECT userId, username, email, role FROM users LIMIT ? OFFSET ?", 
                    "ii", 
                    [$limit, $offset]
                );
                
                // Get total count for pagination
                $totalCount = $db->fetchOne("SELECT COUNT(*) AS total FROM users")['total'];
                
                echo json_encode([
                    'users' => $users,
                    'pagination' => [
                        'total' => $totalCount,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => ceil($totalCount / $limit)
                    ]
                ]);
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
}

function handleAdminUserRequest($method, $pathSegments) {
    requireAdmin();

    $db = DatabaseHelper::getInstance();
    
    switch ($method) {
        case 'GET':
            if (isset($pathSegments[0]) && $pathSegments[0] === 'all') {
                // Get all users
                $users = $db->fetchAll("SELECT userId, username, email, role FROM users");
                echo json_encode(['users' => $users, 'total' => count($users)]);
            } else {
                sendError(400, "Invalid action");
            }
            break;

            case 'POST':
                // Create new user
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Validate required fields
                if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                    sendError(400, "Missing required fields");
                }
                
                // Check if user already exists
                $existingUser = $db->fetchOne(
                    "SELECT userId FROM users WHERE username = ? OR email = ?", 
                    "ss", 
                    [$data['username'], $data['email']]
                );
                
                if ($existingUser) {
                    sendError(409, "Username or email already exists");
                }
                
                // Hash password
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                
                // Default role is 'user' unless specified
                $role = isset($data['role']) ? $data['role'] : 'user';
                
                // Insert user
                $userId = $db->insert(
                    "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)",
                    "ssss",
                    [$data['username'], $data['email'], $hashedPassword, $role]
                );
                
                if ($userId) {
                    http_response_code(201); // Created
                    echo json_encode([
                        'userId' => $userId, 
                        'message' => 'User created successfully',
                        'username' => $data['username'],
                        'email' => $data['email'],
                        'role' => $role
                    ]);
                } else {
                    sendError(500, "Failed to create user");
                }
        break;

        case 'PUT':
            if (isset($pathSegments[0]) && $pathSegments[0] === 'role') {
                // Update a user's role
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['userId']) || !isset($data['role'])) {
                    sendError(400, "Missing required fields");
                }
                
                // Validate role
                $validRoles = ['user', 'admin'];
                if (!in_array($data['role'], $validRoles)) {
                    sendError(400, "Invalid role. Valid roles are: " . implode(', ', $validRoles));
                }
                
                // Update the user's role
                $result = $db->executeQuery(
                    "UPDATE users SET role = ? WHERE userId = ?",
                    "si",
                    [$data['role'], $data['userId']]
                );
                
                if ($result['success']) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'User role updated successfully'
                    ]);
                } else {
                    sendError(500, "Failed to update user role: " . $result['error']);
                }
            } else {
                sendError(400, "Invalid action");
            }
            break;
            
        case 'DELETE':
            // Delete a user
            $userId = $pathSegments[0] ?? null;
            
            if (!$userId || !is_numeric($userId)) {
                sendError(400, "Invalid user ID");
            }
            
            // Check if the user exists
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE userId = ?",
                "i",
                [$userId]
            );
            
            if (!$user) {
                sendError(404, "User not found");
            }
            
            // Delete the user
            $result = $db->executeQuery(
                "DELETE FROM users WHERE userId = ?",
                "i",
                [$userId]
            );
            
            if ($result['success']) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User deleted successfully'
                ]);
            } else {
                sendError(500, "Failed to delete user: " . $result['error']);
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
}
?>