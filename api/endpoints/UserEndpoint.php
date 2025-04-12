<?php
// api/endpoints/UserEndpoint.php

require_once __DIR__ . '/../../database/db_connect.php';

function userHandler($method, $segments) {
    $db = getDbConnection();
    
    // If a user ID is provided, it will be in $segments[1]
    $userId = isset($segments[1]) ? intval($segments[1]) : null;
    
    switch ($method) {
        case 'GET':
            if ($userId === null) {
                // Endpoint to list all users or some general user information
                echo json_encode(["message" => "List all users not implemented yet."]);
            } else {
                // Retrieve specific user info (this is a simplified example)
                $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo json_encode($user);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "User not found"]);
                }
            }
            break;
        
        case 'PUT':
            if (!$userId) {
                http_response_code(400);
                echo json_encode(["error" => "User ID is required for updating a user"]);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            // Update user details (this is just a stub)
            echo json_encode(["message" => "User $userId updated", "data" => $input]);
            break;
        
        case 'DELETE':
            if (!$userId) {
                http_response_code(400);
                echo json_encode(["error" => "User ID is required for deletion"]);
                return;
            }
            // Delete user logic (simplified)
            echo json_encode(["message" => "User $userId deleted"]);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }
}
?>
