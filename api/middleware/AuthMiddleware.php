<?php
// api/middleware/AuthMiddleware.php

require_once __DIR__ . '/../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class AuthMiddleware {
    private static $secretKey = "your-secret-key-here"; // Change this to a secure secret key
    private static $algorithm = 'HS256';

    public static function requireAuth() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["error" => "No authorization token provided"]);
            exit;
        }

        $authHeader = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, self::$algorithm));
            return $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            http_response_code(401);
            echo json_encode(["error" => "Token has expired"]);
            exit;
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid token"]);
            exit;
        }
    }

    public static function generateToken($userId, $username) {
        $issuedAt = time();
        $expire = $issuedAt + 60 * 60 * 24; // 24 hours

        $payload = [
            "iat" => $issuedAt,
            "exp" => $expire,
            "userId" => $userId,
            "username" => $username
        ];

        return JWT::encode($payload, self::$secretKey, self::$algorithm);
    }
}
?>
