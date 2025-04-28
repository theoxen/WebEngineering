<?php
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check session-based authentication
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check token-based authentication for API
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        // Validate token (implement JWT validation or your token system)
        if (validateToken($token)) {
            return true;
        }
    }
    
    // No valid authentication
    if (isApiRequest()) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    } else {
        // Redirect web pages to login
        header("Location: /WebEngineering/pages/login.php");
        exit;
    }
}

function isApiRequest() {
    return strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
}

function validateToken($token) {
    // Implement your token validation logic here
    // This is just a placeholder
    return false;
}

function requireAdmin() {
    requireAuth();
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        if (isApiRequest()) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden: Admin access required"]);
            exit;
        } else {
            // Redirect web pages
            header("Location: /WebEngineering/pages/homepage.php");
            exit;
        }
    }
    
    return true;
}
?>