<?php
/**
 * Delete User Endpoint (Soft Delete)
 * POST /api/users/delete.php
 * Requires: MANAGE_USERS permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/user.service.php';
require_once __DIR__ . '/../../services/activity.service.php';
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
    // Require MANAGE_USERS permission (validates session)
    requirePermission('MANAGE_USERS');
    
    // Get current user ID from session (already validated by requirePermission)
    $currentUserId = $_SESSION['user_id'];
    
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
    
    // Validate id
    if (!isset($input['id']) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required'
        ]);
        exit;
    }
    
    $userId = (int)$input['id'];
    
    // Get user info before deleting (for activity log)
    $userService = new UserService();
    $user = $userService->getUserById($userId);
    $userName = $user ? $user['username'] : "User ID: {$userId}";
    
    // Soft delete user using service
    $success = $userService->deleteUser($userId, $currentUserId);
    
    if (!$success) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found or already deleted'
        ]);
        exit;
    }
    
    // Log activity
    if ($currentUserId) {
        $activityService = new ActivityService();
        $activityService->logActivity(
            'DELETE_USER',
            'user',
            (string)$userId,
            "Deleted user: {$userName}",
            $currentUserId
        );
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'User moved to trash successfully'
    ]);
    
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code === 404) {
        http_response_code(404);
    } else if ($code === 403) {
        http_response_code(403);
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

