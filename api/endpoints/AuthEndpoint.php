<?php
// api/endpoints/AuthEndpoint.php

require_once __DIR__ . '/../../database/db_connect.php';

function loginHandler($method) {
    if ($method !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }
    
    // Retrieve and decode JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if (!$username || !$password) {
        http_response_code(400);
        echo json_encode(["error" => "Missing login details"]);
        return;
    }
    
    // Verify the credentials (simplified example)
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid username or password"]);
        return;
    }
    
    // On success: return success message (consider adding a token here)
    http_response_code(200);
    echo json_encode(["message" => "Login successful", "user_id" => $user['id']]);
}

function registerHandler($method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $email    = $input['email'] ?? '';
    
    if (!$username || !$password || !$email) {
        http_response_code(400);
        echo json_encode(["error" => "Missing registration information"]);
        return;
    }
    
    $db = getDbConnection();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
    $result = $stmt->execute([$username, $hashedPassword, $email]);
    
    if ($result) {
        http_response_code(201);
        echo json_encode(["message" => "Registration successful"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Registration failed"]);
    }
}
?>
