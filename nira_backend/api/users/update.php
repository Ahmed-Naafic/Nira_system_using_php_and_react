<?php
/**
 * Update User Endpoint
 * PUT /api/users/update.php
 * Requires: MANAGE_USERS permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/user.service.php';
require_once __DIR__ . '/../../services/activity.service.php';
require_once __DIR__ . '/../../middleware/permission.php';

// Allow both PUT and POST requests (POST for multipart/form-data)
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
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
    
    // Check if request is multipart/form-data (file upload) or JSON
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && 
                   strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    
    if ($isMultipart) {
        // Handle multipart/form-data (with file uploads)
        $userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
            exit;
        }
        
        $input = [
            'username' => $_POST['username'] ?? null,
            'role_id' => isset($_POST['role_id']) ? (int)$_POST['role_id'] : null,
            'phoneNumber' => $_POST['phoneNumber'] ?? null,
            'status' => $_POST['status'] ?? null
        ];
        
        // Remove null values
        $input = array_filter($input, function($value) {
            return $value !== null;
        });
    } else {
        // Handle JSON input (backward compatibility)
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
        
        $userId = (int)$input['id'];
        unset($input['id']); // Remove id from update data
    }
    
    // Get current user ID
    $performedBy = $_SESSION['user_id'] ?? null;
    
    // Update user using service
    $userService = new UserService();
    $user = $userService->updateUser($userId, $input);
    
    // Handle profile picture upload if present
    require_once __DIR__ . '/../../utils/file_upload.php';
    
    $profilePicturePath = null;
    
    // Upload new profile picture if provided
    if ($isMultipart && isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        try {
            // Delete old profile picture if exists
            if (!empty($user['profilePicturePath'])) {
                FileUpload::deleteFile($user['profilePicturePath']);
            }
            $profilePicturePath = FileUpload::uploadProfilePicture($_FILES['profilePicture'], $userId);
            // Update user with profile picture path
            $user = $userService->updateUserProfilePicture($userId, $profilePicturePath);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Profile picture upload failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Log activity
    if ($performedBy) {
        $activityService = new ActivityService();
        $activityService->logActivity(
            'UPDATE_USER',
            'user',
            (string)$user['id'],
            "Updated user: {$user['username']} ({$user['role']['name']})",
            $performedBy
        );
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully',
        'data' => $user
    ]);
    
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code == 404) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else if ($code == 409) {
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
            'message' => 'Failed to update user: ' . $e->getMessage()
        ]);
    }
}

