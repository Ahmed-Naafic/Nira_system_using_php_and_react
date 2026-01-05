<?php
/**
 * Permission Middleware
 * 
 * Use this middleware to protect endpoints that require specific permissions.
 * 
 * Usage:
 * require_once __DIR__ . '/../middlewares/permission.php';
 * requirePermission('VIEW_DASHBOARD');
 */

session_start();

require_once __DIR__ . '/../services/rbac.service.php';

/**
 * Require a specific permission
 * 
 * @param string $permissionCode The permission code to check
 * @throws Exception If user doesn't have the permission
 */
function requirePermission($permissionCode) {
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please login.'
        ]);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $rbac = new RBACService();
    
    // Check if user has the required permission
    if (!$rbac->hasPermission($userId, $permissionCode)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Access denied. You do not have permission to perform this action.',
            'required_permission' => $permissionCode
        ]);
        exit;
    }
}

/**
 * Check if user has a permission (returns boolean, doesn't exit)
 * 
 * @param string $permissionCode The permission code to check
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($permissionCode) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    $rbac = new RBACService();
    
    return $rbac->hasPermission($userId, $permissionCode);
}

