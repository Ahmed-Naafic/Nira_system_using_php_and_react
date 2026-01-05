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
    
    // Build response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$userData['id'],
            'username' => $userData['username'],
            'role' => [
                'id' => (int)$userData['role_id'],
                'name' => $userData['role_name'] ?? $userData['role'],
                'description' => $userData['role_description'] ?? null
            ],
            'status' => $userData['status']
        ],
        'permissions' => $permissions,
        'menus' => $menus
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve user information: ' . $e->getMessage()
    ]);
}

