<?php
require_once __DIR__ . '/../../utils/DatabaseHelper.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

function handleDataRequest($method, $pathSegments) {
    // Get data ID if provided in URL
    $dataId = isset($pathSegments[0]) && is_numeric($pathSegments[0]) ? (int)$pathSegments[0] : null;
    $action = isset($pathSegments[0]) && !is_numeric($pathSegments[0]) ? $pathSegments[0] : '';
    
    // Get database connection
    $db = DatabaseHelper::getInstance();

    // API key authentication 
    $apiKeyData = validateApiKey();
    if (!$apiKeyData) {
        sendError(401, "Unauthorized");
        exit;
    }

   // Check if the API key has the correct permission for this method
    if ($method === 'GET' && !$apiKeyData['permissions']['get']) {
        sendError(403, "Method not allowed. This API key does not have GET permission.");
        exit;
    } else if ($method === 'POST' && !$apiKeyData['permissions']['post']) {
        sendError(403, "Method not allowed. This API key does not have POST permission.");
        exit;
    } else if ($method === 'PUT' && !$apiKeyData['permissions']['put']) {
        sendError(403, "Method not allowed. This API key does not have PUT permission.");
        exit;
    } else if ($method === 'DELETE' && !$apiKeyData['permissions']['delete']) {
        sendError(403, "Method not allowed. This API key does not have DELETE permission.");
        exit;
    }

    $userId = $apiKeyData['user_id']; // Get the user ID from the API key data

    switch ($method) {
        case 'GET':
            if ($dataId) {
                // Get specific data entry
                $data = $db->fetchOne(
                    "SELECT * FROM users WHERE userId = ?",
                    "i", 
                    [$dataId]
                );
                
                if ($data) {
                    echo json_encode($data);
                } else {
                    sendError(404, "Data not found");
                }
            } else {
                // List data entries with pagination
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                
                // Validate limit (max 100)
                if ($limit > 100) {
                    $limit = 100;
                }
                
                $offset = ($page - 1) * $limit;
                
                // Get sort parameters
                $sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
                $order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
                
                // Validate sort field (prevent SQL injection)
                $allowedSortFields = ['userId', 'email', 'username', 'dateCreated'];
                if (!in_array($sort, $allowedSortFields)) {
                    $sort = 'dateCreated';
                }

                // Build query for user's data or public data
                $sql = "SELECT * FROM users 
                    WHERE userId = ?
                    ORDER BY $sort $order 
                    LIMIT ? OFFSET ?";

                $data = $db->fetchAll($sql, "iii", [$userId, $limit, $offset]);

                // Get total count for pagination
                $totalCount = $db->fetchOne(
                    "SELECT COUNT(*) AS total FROM users WHERE userId = ?",
                    "i", 
                    [$userId]
                )['total'];
                
                echo json_encode([
                    'data' => $data,
                    'pagination' => [
                        'total' => $totalCount,
                        'count' => count($data),
                        'per_page' => $limit,
                        'current_page' => $page,
                        'total_pages' => ceil($totalCount / $limit),
                        'links' => [
                            'next' => $page < ceil($totalCount / $limit) ? 
                                "/api/data?page=" . ($page + 1) . "&limit=$limit" : null,
                            'prev' => $page > 1 ? 
                                "/api/data?page=" . ($page - 1) . "&limit=$limit" : null
                        ]
                    ]
                ]);
            }
            break;
            
        case 'POST':
            // Create new data entry
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['title'])) {
                sendError(400, "Title is required");
            }
            
            // Set default values
            $description = $data['description'] ?? '';
            $status = $data['status'] ?? 'active';
            $tags = isset($data['tags']) ? json_encode($data['tags']) : '[]';
            
            // Insert data entry
            $dataId = $db->insert(
                "INSERT INTO users (userId, email, role, username) 
                VALUES (?, ?, ?, ?)",
                "isss",
                [$userId, $data['email'], $data['role'] ?? 'user', $data['username']]
            );
            
            if ($dataId) {
                // Get the created entry
                $createdData = $db->fetchOne(
                    "SELECT * FROM users WHERE userId = ?", 
                    "i", 
                    [$dataId]
                );
                
                http_response_code(201); // Created
                echo json_encode([
                    'message' => 'Data entry created successfully',
                    'data' => $createdData
                ]);
            } else {
                sendError(500, "Failed to create data entry");
            }
            break;
            
        case 'PUT':
            if (!$dataId) {
                sendError(400, "Data ID is required");
            }
            
            // Check if user owns this data entry
            $existingData = $db->fetchOne(
                "SELECT user_id FROM users WHERE userId = ?", 
                "i", 
                [$dataId]
            );
            
            if (!$existingData) {
                sendError(404, "Data not found");
            }
            
            if ($existingData['user_id'] != $userId) {
                sendError(403, "You don't have permission to update this data");
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Build update query dynamically based on provided fields
            $updateFields = [];
            $types = "";
            $values = [];
            
            if (isset($data['email'])) {
                $updateFields[] = "email = ?";
                $types .= "s";
                $values[] = $data['email'];
            }
            
            if (isset($data['username'])) {
                $updateFields[] = "username = ?";
                $types .= "s";
                $values[] = $data['username'];
            }
            
            if (isset($data['role'])) {
                $updateFields[] = "role = ?";
                $types .= "s";
                $values[] = $data['role'];
            }
            
            if (!empty($updateFields)) {
                $values[] = $dataId;
                $types .= "i";
                
                $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE userId = ?";
                $result = $db->executeQuery($sql, $types, $values);
                
                if ($result['success']) {
                    // Get the updated entry
                    $updatedData = $db->fetchOne(
                        "SELECT * FROM users WHERE userId = ?", 
                        "i", 
                        [$dataId]
                    );
                    
                    echo json_encode([
                        'message' => 'Data entry updated successfully',
                        'data' => $updatedData
                    ]);
                } else {
                    sendError(500, "Failed to update data entry");
                }
            } else {
                sendError(400, "No fields to update");
            }
            break;
            
        case 'DELETE':
            if (!$dataId) {
                sendError(400, "Data ID is required");
            }
            
            // Check if user owns this data entry
            $existingData = $db->fetchOne(
                "SELECT userId FROM users WHERE userId = ?", 
                "i", 
                [$dataId]
            );
            
            if (!$existingData) {
                sendError(404, "Data not found");
            }
            
            if ($existingData['userId'] != $userId && $_SESSION['role'] != 'admin') {
                sendError(403, "You don't have permission to delete this data");
            }
            
            $result = $db->executeQuery("DELETE FROM users WHERE userId = ?", "i", [$dataId]);
            
            if ($result['success']) {
                echo json_encode(['message' => 'Data entry deleted successfully']);
            } else {
                sendError(500, "Failed to delete data entry");
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
}
?>