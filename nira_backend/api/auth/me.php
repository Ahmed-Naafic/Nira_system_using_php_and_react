<?php
/**
 * Get Current User Information Endpoint
 * GET /api/auth/me.php
 * Returns user info, permissions, and menus for the authenticated user
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/rbac.service.php';
require_once __DIR__ . '/../../middleware/auth.php';

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
    // Validate session - this will return 401 if not authenticated
    $user = AuthMiddleware::validateSession();
    
    // Get user details from database
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.username,
            u.phone_number,
            u.profile_picture_path,
            u.role_id,
            u.role,
            u.status,
            r.name as role_name,
            r.description as role_description
        FROM nira_users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.id = :userId AND u.status = 'ACTIVE'
    ");
    
    $stmt->execute(['userId' => $user['userId']]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User not found or inactive'
        ]);
        exit;
    }
    
    // Get RBAC data
    $rbacService = new RBACService();
    $permissions = $rbacService->getUserPermissions($user['userId']);
    $menus = $rbacService->getUserMenus($user['userId']);
    $role = $rbacService->getUserRole($user['userId']);
    
    // Get profile picture URL if available
    $profilePictureUrl = null;
    if (!empty($userData['profile_picture_path'])) {
        require_once __DIR__ . '/../../utils/file_upload.php';
        $profilePictureUrl = FileUpload::getFileUrl($userData['profile_picture_path']);
    }
    
    // Calculate session expiration time
    $sessionTimeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 300; // 5 minutes
    $currentTime = time();
    $lastActivity = $_SESSION['last_activity'] ?? $currentTime;
    $expiresAt = $lastActivity + $sessionTimeout;
    
    // Build response
    http_response_code(200);
    $responseUser = [
        'id' => (int)$userData['id'],
        'username' => $userData['username'],
        'role' => [
            'id' => (int)$userData['role_id'],
            'name' => $userData['role_name'] ?? $userData['role'],
            'description' => $userData['role_description'] ?? null
        ],
        'status' => $userData['status']
    ];
    
    // Include phone number if available
    if (!empty($userData['phone_number'])) {
        $responseUser['phoneNumber'] = $userData['phone_number'];
    }
    
    // Include profile picture URL if available
    if ($profilePictureUrl) {
        $responseUser['profilePictureUrl'] = $profilePictureUrl;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $responseUser,
        'permissions' => $permissions,
        'menus' => $menus,
        'sessionExpiresAt' => $expiresAt, // Unix timestamp when session expires
        'sessionTimeout' => $sessionTimeout // Session timeout in seconds
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve user information: ' . $e->getMessage()
    ]);
}

