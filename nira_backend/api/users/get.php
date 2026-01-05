<?php
/**
 * Get User by ID Endpoint
 * GET /api/users/get.php?id=XX
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
    
    // Get user ID from query parameter
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }
    
    // Get user using service
    $userService = new UserService();
    $user = $userService->getUserById($userId);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve user: ' . $e->getMessage()
    ]);
}

