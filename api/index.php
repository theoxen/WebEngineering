<?php
header('Content-Type: application/json');

// Get the requested URL (e.g., /api/login or /api/users/10)
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($scriptName, '', $requestUri);
$path = trim($path, '/');

// Remove the "api/" prefix if present
if (strpos($path, 'api/') === 0) {
    $path = substr($path, 4);
}

$segments = explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

// Simple routing based on the first segment in the URL
switch ($segments[0]) {
    case 'login':
        require_once __DIR__ . '/endpoints/AuthEndpoint.php';
        loginHandler($method);
        break;
        
    case 'register':
        require_once __DIR__ . '/endpoints/AuthEndpoint.php';
        registerHandler($method);
        break;
        
    case 'users':
        require_once __DIR__ . '/endpoints/UserEndpoint.php';
        userHandler($method, $segments);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found"]);
        break;
}
?>
