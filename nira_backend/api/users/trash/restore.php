<?php
/**
 * Restore User from Trash Endpoint
 * POST /api/users/trash/restore.php
 * Requires: MANAGE_USERS permission
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../services/user.service.php';
require_once __DIR__ . '/../../../middleware/permission.php';

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
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    $userService = new UserService();
    
    // Check if restoring all or single
    if (isset($input['restoreAll']) && $input['restoreAll'] === true) {
        // Restore all
        $count = $userService->restoreAllUsers();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Successfully restored {$count} user(s)",
            'count' => $count
        ]);
    } else {
        // Restore single user
        if (!isset($input['id']) || empty($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
            exit;
        }
        
        $userId = (int)$input['id'];
        $success = $userService->restoreUser($userId);
        
        if (!$success) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found in trash'
            ]);
            exit;
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'User restored successfully'
        ]);
    }
    
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code === 404) {
        http_response_code(404);
    } else if ($code === 400 || $code === 0) {
        http_response_code(400);
    } else {
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

