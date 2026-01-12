<?php
/**
 * Create User Endpoint
 * POST /api/users/create.php
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
    // Require MANAGE_USERS permission
    requirePermission('MANAGE_USERS');
    
    // Check if request is multipart/form-data (file upload) or JSON
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && 
                   strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    
    if ($isMultipart) {
        // Handle multipart/form-data (with file uploads)
        $input = [
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'role_id' => $_POST['role_id'] ?? '',
            'phoneNumber' => $_POST['phoneNumber'] ?? null,
            'status' => $_POST['status'] ?? 'ACTIVE'
        ];
        
        // Validate required fields
        if (empty($input['username']) || empty($input['password']) || empty($input['role_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username, password, and role are required'
            ]);
            exit;
        }
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
    }
    
    // Get current user ID
    $performedBy = $_SESSION['user_id'] ?? null;
    
    // Create user using service (will generate user ID)
    $userService = new UserService();
    $user = $userService->createUser($input);
    
    // Handle profile picture upload if present (after user is created so we have user ID)
    require_once __DIR__ . '/../../utils/file_upload.php';
    
    $profilePicturePath = null;
    
    // Upload profile picture if provided
    if ($isMultipart && isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        try {
            $profilePicturePath = FileUpload::uploadProfilePicture($_FILES['profilePicture'], $user['id']);
            // Update user with profile picture path
            $user = $userService->updateUserProfilePicture($user['id'], $profilePicturePath);
        } catch (Exception $e) {
            // If profile picture upload fails, delete the user and return error
            // Or you could choose to continue without profile picture
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
            'CREATE_USER',
            'user',
            (string)$user['id'],
            "Created user: {$user['username']} ({$user['role']['name']})",
            $performedBy
        );
    }
    
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

