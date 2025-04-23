<?php
// api/endpoints/UserEndpoint.php

require_once __DIR__ . '/../../database/db_connect.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

function userHandler($method, $segments) {
    // Verify authentication for all user endpoints
    $auth = AuthMiddleware::requireAuth();
    
    switch ($method) {
        case 'GET':
            if (count($segments) === 1) {
                // Get all users (admin only)
                getAllUsers();
            } elseif (count($segments) === 2) {
                // Get specific user
                $userId = $segments[1];
                if ($auth->userId == $userId) {
                    getUserProfile($userId);
                } else {
                    http_response_code(403);
                    echo json_encode(["error" => "Access denied"]);
                }
            }
            break;
            
        case 'PUT':
            if (count($segments) === 2) {
                $userId = $segments[1];
                if ($auth->userId == $userId) {
                    updateUserProfile($userId);
                } else {
                    http_response_code(403);
                    echo json_encode(["error" => "Access denied"]);
                }
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
            break;
    }
}

function getUserProfile($userId) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["error" => "User not found"]);
        return;
    }
    
    $user = $result->fetch_assoc();
    echo json_encode($user);
}

function updateUserProfile($userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email'])) {
        http_response_code(400);
        echo json_encode(["error" => "Email is required"]);
        return;
    }
    
    $email = $data['email'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid email format"]);
        return;
    }
    
    global $mysqli;
    
    // Check if email is already taken by another user
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Email already in use"]);
        return;
    }
    
    // Update user profile
    $stmt = $mysqli->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->bind_param("si", $email, $userId);
    
    if ($stmt->execute()) {
        echo json_encode([
            "message" => "Profile updated successfully",
            "user" => [
                "id" => $userId,
                "email" => $email
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update profile"]);
    }
}

function getAllUsers() {
    global $mysqli;
    
    $result = $mysqli->query("SELECT id, username, email, created_at FROM users");
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode($users);
}
?>
