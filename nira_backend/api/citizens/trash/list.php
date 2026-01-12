<?php
/**
 * List Trash (Deleted Citizens) Endpoint
 * GET /api/citizens/trash/list.php?limit=50&offset=0
 * Requires: VIEW_CITIZEN permission
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../services/citizen.service.php';
require_once __DIR__ . '/../../../middleware/permission.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Require VIEW_CITIZEN permission
    requirePermission('VIEW_CITIZEN');
    
    // Get pagination parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // List deleted citizens using service
    $citizenService = new CitizenService();
    $citizens = $citizenService->listTrash($limit, $offset);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $citizens,
        'count' => count($citizens)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to list trash: ' . $e->getMessage()
    ]);
}

