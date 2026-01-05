<?php
/**
 * Reset User Password Endpoint
 * POST /api/users/reset-password.php
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
    
    if (!isset($input['id']) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }
    
    if (!isset($input['password']) || empty(trim($input['password']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'New password is required'
        ]);
        exit;
    }
    
    $userId = (int)$input['id'];
    $newPassword = trim($input['password']);
    
    // Reset password using service
    $userService = new UserService();
    $userService->resetUserPassword($userId, $newPassword);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully'
    ]);
    
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code == 404) {
        http_response_code(404);
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
            'message' => 'Failed to reset password: ' . $e->getMessage()
        ]);
    }
}

