<?php
require_once __DIR__ . '/../../utils/DatabaseHelper.php';
require_once __DIR__ . '/../utils/api_helpers.php';

function handleLogin($method) {
    if ($method !== 'POST') {
        sendError(405, "Method not allowed");
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email']) || !isset($data['password'])) {
        sendError(400, "Missing email or password");
    }
    
    try {
        $db = DatabaseHelper::getInstance();
        
        // Debug the query
        $tableName = 'users'; // The table name you're querying
        $columns = $db->fetchAll("SHOW COLUMNS FROM $tableName");
        error_log("Table columns for $tableName: " . json_encode($columns));
        
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE email = ?", 
            "s", 
            [$data['email']]
        );
        
        if (!$user) {
            sendError(401, "Invalid credentials - user not found");
        }
        
        // Now we have all fields, we can see what columns actually exist
        error_log("User data: " . json_encode($user));
        
        if (!password_verify($data['password'], $user['password'])) {
            sendError(401, "Invalid credentials - password incorrect");
        }
        
        // Use the correct column names based on what's in your database
        $userId = $user['userId'] ?? $user['id'] ?? $user['ID'] ?? null;
        $userEmail = $user['email'] ?? $user['EMAIL'] ?? null;
        $userRole = $user['role'] ?? $user['user_role'] ?? 'user';
        
        if (!$userId) {
            sendError(500, "User ID column not found in database");
        }
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session variables
        $_SESSION['userId'] = $userId;
        $_SESSION['email'] = $userEmail;
        $_SESSION['role'] = $userRole;
        
        
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => [
                'id' => $userId,
                'email' => $userEmail,
                'role' => $userRole
            ],
            
        ]);
        
    } catch (Exception $e) {
        sendError(500, "Server error: " . $e->getMessage());
    }
}

function handleRegister($method) {
    if ($method !== 'POST') {
        sendError(405, "Method not allowed");
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        sendError(400, "Missing required fields");
    }
    
    $db = DatabaseHelper::getInstance();
    
    // Check if username or email already exists
    $existing = $db->fetchOne(
        "SELECT * FROM users WHERE username = ? OR email = ?", 
        "ss", 
        [$data['username'], $data['email']]
    );
    
    if ($existing) {
        sendError(409, "Username or email already in use");
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    
    // Set default values for optional fields
    $phoneNumber = $data['phoneNumber'] ?? null;
    $dateOfBirth = $data['dateOfBirth'] ?? null;
    
    // Format date of birth as string if it exists
    $formattedDate = null;
    if ($dateOfBirth) {
        // Parse the date and format it as a string the database can use
        $dateObj = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
        if ($dateObj) {
            $formattedDate = $dateObj->format('Y-m-d');
        }else{
            sendError(400, "Invalid date format. Please use YYYY-MM-DD format (e.g. 2000-01-20)");
        }
    }
    
    // Insert new user
    $userId = $db->insert(
        "INSERT INTO users (username, email, phoneNumber, dateOfBirth, password, role, email_verified, verification_token) VALUES (?, ?, ?, ?, ?, 'user', 0, ?)",
        "ssssss",
        [$data['username'], $data['email'], $phoneNumber, $formattedDate, $hashedPassword, $verificationToken]
    );
    
    if ($userId) {
        http_response_code(201); // Created
        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful',
            'user' => [
                'id' => $userId,
                'username' => $data['username'],
                'email' => $data['email']
            ]
        ]);
    } else {
        // Add more detailed error information for debugging
        sendError(500, "Registration failed");
    }
}

function handleLogout($method) {
    if ($method !== 'POST' && $method !== 'GET') {
        sendError(405, "Method not allowed");
    }
    
    // Clear session data
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Store the logged out status
    $wasLoggedIn = isset($_SESSION['userId']);
    $username = $_SESSION['username'] ?? 'Unknown';
    
    // Destroy session
    session_unset();
    session_destroy();
    
    if ($wasLoggedIn) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    } else {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Already logged out'
        ]);
    }
}



// Only define if not already defined elsewhere
if (!function_exists('sendError')) {
    function sendError($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}
?>