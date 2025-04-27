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
                // Only allow users to see their own data unless admin
                if ($_SESSION['user_id'] != $userId && $_SESSION['role'] != 'admin') {
                    sendError(403, "Forbidden");
                }
                
                $user = $db->fetchOne(
                    "SELECT id, username, email, created_at, role FROM users WHERE id = ?", 
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
                $currentUserId = $_SESSION['user_id'];
                $user = $db->fetchOne(
                    "SELECT id, username, email, created_at, role FROM users WHERE id = ?", 
                    "i", 
                    [$currentUserId]
                );
                echo json_encode($user);
            } else {
                // List users (with pagination)
                requireAdmin(); // Only admins can list all users
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                $offset = ($page - 1) * $limit;
                
                $users = $db->fetchAll(
                    "SELECT id, username, email, created_at, role FROM users LIMIT ? OFFSET ?", 
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
            
        case 'POST':
            // Create new user
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                sendError(400, "Missing required fields");
            }
            
            // Check if user already exists
            $existingUser = $db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?", 
                "ss", 
                [$data['username'], $data['email']]
            );
            
            if ($existingUser) {
                sendError(409, "Username or email already exists");
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Default role is 'user' unless specified by admin
            $role = 'user';
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($data['role'])) {
                $role = $data['role'];
            }
            
            // Insert user
            $userId = $db->insert(
                "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)",
                "ssss",
                [$data['username'], $data['email'], $hashedPassword, $role]
            );
            
            if ($userId) {
                http_response_code(201); // Created
                echo json_encode([
                    'id' => $userId, 
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
            if (!$userId) {
                sendError(400, "User ID is required");
            }
            
            requireAuth();
            // Only allow users to update their own profile unless admin
            if ($_SESSION['user_id'] != $userId && $_SESSION['role'] != 'admin') {
                sendError(403, "Forbidden");
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Build update query dynamically based on provided fields
            $updateFields = [];
            $types = "";
            $values = [];
            
            if (isset($data['username'])) {
                $updateFields[] = "username = ?";
                $types .= "s";
                $values[] = $data['username'];
            }
            
            if (isset($data['email'])) {
                $updateFields[] = "email = ?";
                $types .= "s";
                $values[] = $data['email'];
            }
            
            // Only admin can update roles
            if (isset($data['role']) && $_SESSION['role'] === 'admin') {
                $updateFields[] = "role = ?";
                $types .= "s";
                $values[] = $data['role'];
            }
            
            if (!empty($updateFields)) {
                $values[] = $userId;
                $types .= "i";
                
                $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $result = $db->executeQuery($sql, $types, $values);
                
                if ($result['success']) {
                    echo json_encode(['message' => 'User updated successfully']);
                } else {
                    sendError(500, "Failed to update user");
                }
            } else {
                sendError(400, "No fields to update");
            }
            break;
            
        case 'DELETE':
            if (!$userId) {
                sendError(400, "User ID is required");
            }
            
            requireAuth();
            // Only allow admin to delete users or users to delete themselves
            if ($_SESSION['user_id'] != $userId && $_SESSION['role'] != 'admin') {
                sendError(403, "Forbidden");
            }
            
            $result = $db->executeQuery("DELETE FROM users WHERE id = ?", "i", [$userId]);
            
            if ($result['success']) {
                // If user deleted themselves, destroy session
                if ($_SESSION['user_id'] == $userId) {
                    session_destroy();
                }
                echo json_encode(['message' => 'User deleted successfully']);
            } else {
                sendError(500, "Failed to delete user");
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
}

