<?php
// api/endpoints/AuthEndpoint.php

require_once __DIR__ . '/../../database/db_connect.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

function loginHandler($method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Username and password are required"]);
        return;
    }

    $username = $data['username'];
    $password = $data['password'];

    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid credentials"]);
        return;
    }

    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid credentials"]);
        return;
    }

    $token = AuthMiddleware::generateToken($user['id'], $user['username']);
    
    echo json_encode([
        "token" => $token,
        "user" => [
            "id" => $user['id'],
            "username" => $user['username']
        ]
    ]);
}

function registerHandler($method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(["error" => "Username, password, and email are required"]);
        return;
    }

    $username = $data['username'];
    $password = $data['password'];
    $email = $data['email'];

    // Validate input
    if (strlen($username) < 3 || strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(["error" => "Username must be at least 3 characters and password at least 6 characters"]);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid email format"]);
        return;
    }

    global $mysqli;
    
    // Check if username already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Username already exists"]);
        return;
    }

    // Check if email already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Email already exists"]);
        return;
    }

    // Hash password and create user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $mysqli->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $email);
    
    if ($stmt->execute()) {
        $userId = $mysqli->insert_id;
        $token = AuthMiddleware::generateToken($userId, $username);
        
        echo json_encode([
            "message" => "User registered successfully",
            "token" => $token,
            "user" => [
                "id" => $userId,
                "username" => $username,
                "email" => $email
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to register user"]);
    }
}
?>
