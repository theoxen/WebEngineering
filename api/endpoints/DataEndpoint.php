<?php
require_once __DIR__ . '/../../utils/DatabaseHelper.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

function handleDataRequest($method, $pathSegments) {
    // Get data ID if provided in URL
    $dataId = isset($pathSegments[0]) && is_numeric($pathSegments[0]) ? (int)$pathSegments[0] : null;
    $action = isset($pathSegments[0]) && !is_numeric($pathSegments[0]) ? $pathSegments[0] : '';
    
    // Get database connection
    $db = DatabaseHelper::getInstance();
    
    // Ensure user is authenticated
    requireAuth();
    $userId = $_SESSION['user_id'];
    
    switch ($method) {
        case 'GET':
            if ($dataId) {
                // Get specific data entry
                $data = $db->fetchOne(
                    "SELECT * FROM data_entries WHERE id = ? AND (user_id = ? OR is_public = 1)",
                    "ii", 
                    [$dataId, $userId]
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
                $allowedSortFields = ['id', 'title', 'created_at', 'updated_at'];
                if (!in_array($sort, $allowedSortFields)) {
                    $sort = 'created_at';
                }
                
                // Build query for user's data or public data
                $sql = "SELECT * FROM data_entries 
                       WHERE user_id = ? OR is_public = 1 
                       ORDER BY $sort $order 
                       LIMIT ? OFFSET ?";
                
                $data = $db->fetchAll($sql, "iii", [$userId, $limit, $offset]);
                
                // Get total count for pagination
                $totalCount = $db->fetchOne(
                    "SELECT COUNT(*) AS total FROM data_entries WHERE user_id = ? OR is_public = 1",
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
            $isPublic = isset($data['is_public']) ? (int)$data['is_public'] : 0;
            $tags = isset($data['tags']) ? json_encode($data['tags']) : '[]';
            
            // Insert data entry
            $dataId = $db->insert(
                "INSERT INTO data_entries (user_id, title, description, status, is_public, tags) 
                VALUES (?, ?, ?, ?, ?, ?)",
                "issssi",
                [$userId, $data['title'], $description, $status, $isPublic, $tags]
            );
            
            if ($dataId) {
                // Get the created entry
                $createdData = $db->fetchOne(
                    "SELECT * FROM data_entries WHERE id = ?", 
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
                "SELECT user_id FROM data_entries WHERE id = ?", 
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
            
            if (isset($data['title'])) {
                $updateFields[] = "title = ?";
                $types .= "s";
                $values[] = $data['title'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $types .= "s";
                $values[] = $data['description'];
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $types .= "s";
                $values[] = $data['status'];
            }
            
            if (isset($data['is_public'])) {
                $updateFields[] = "is_public = ?";
                $types .= "i";
                $values[] = (int)$data['is_public'];
            }
            
            if (isset($data['tags'])) {
                $updateFields[] = "tags = ?";
                $types .= "s";
                $values[] = json_encode($data['tags']);
            }
            
            // Always update the updated_at timestamp
            $updateFields[] = "updated_at = NOW()";
            
            if (!empty($updateFields)) {
                $values[] = $dataId;
                $types .= "i";
                
                $sql = "UPDATE data_entries SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $result = $db->executeQuery($sql, $types, $values);
                
                if ($result['success']) {
                    // Get the updated entry
                    $updatedData = $db->fetchOne(
                        "SELECT * FROM data_entries WHERE id = ?", 
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
                "SELECT user_id FROM data_entries WHERE id = ?", 
                "i", 
                [$dataId]
            );
            
            if (!$existingData) {
                sendError(404, "Data not found");
            }
            
            if ($existingData['user_id'] != $userId && $_SESSION['role'] != 'admin') {
                sendError(403, "You don't have permission to delete this data");
            }
            
            $result = $db->executeQuery("DELETE FROM data_entries WHERE id = ?", "i", [$dataId]);
            
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