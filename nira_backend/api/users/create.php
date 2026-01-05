<?php
/**
 * Create User Endpoint
 * POST /api/users/create.php
 * Requires: MANAGE_USERS permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/user.service.php';
require_once __DIR__ . '/../../middleware/permission.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    // Create user using service
    $userService = new UserService();
    $user = $userService->createUser($input);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'data' => $user
    ]);
    
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code == 409) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else if ($code >= 400 && $code < 500) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create user: ' . $e->getMessage()
        ]);
    }
}

