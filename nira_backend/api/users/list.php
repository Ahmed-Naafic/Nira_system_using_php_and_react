<?php
/**
 * List Users Endpoint
 * GET /api/users/list.php
 * Requires: MANAGE_USERS permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/user.service.php';
require_once __DIR__ . '/../../middleware/permission.php';

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
    // Require MANAGE_USERS permission
    requirePermission('MANAGE_USERS');
    
    // List users using service
    $userService = new UserService();
    $users = $userService->listUsers();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $users,
        'count' => count($users)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to list users: ' . $e->getMessage()
    ]);
}

