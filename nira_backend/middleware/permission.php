<?php
/**
 * Permission Middleware Helper for NIRA System
 * Used to check specific permissions in protected endpoints
 * 
 * Usage in endpoints:
 * require_once __DIR__ . '/../../middleware/permission.php';
 * requirePermission('CREATE_CITIZEN');
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../services/rbac.service.php';
require_once __DIR__ . '/auth.php';

/**
 * Require a specific permission for the current user
 * Returns 403 if user doesn't have the permission
 * @param string $permissionCode Permission code (e.g., 'CREATE_CITIZEN')
 * @return void Exits with 403 if permission denied
 */
function requirePermission($permissionCode) {
    // First validate session
    $user = AuthMiddleware::validateSession();
    
    // Check permission
    $rbacService = new RBACService();
    if (!$rbacService->hasPermission($user['userId'], $permissionCode)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient permissions. Required: ' . $permissionCode
        ]);
        exit;
    }
}

/**
 * Require any of the specified permissions
 * @param array $permissionCodes Array of permission codes
 * @return void Exits with 403 if none of the permissions are granted
 */
function requireAnyPermission($permissionCodes) {
    // First validate session
    $user = AuthMiddleware::validateSession();
    
    // Check if user has any of the required permissions
    $rbacService = new RBACService();
    $userPermissions = $rbacService->getUserPermissions($user['userId']);
    
    $hasPermission = false;
    foreach ($permissionCodes as $code) {
        if (in_array($code, $userPermissions)) {
            $hasPermission = true;
            break;
        }
    }
    
    if (!$hasPermission) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient permissions. Required: ' . implode(' OR ', $permissionCodes)
        ]);
        exit;
    }
}

/**
 * Require all of the specified permissions
 * @param array $permissionCodes Array of permission codes
 * @return void Exits with 403 if not all permissions are granted
 */
function requireAllPermissions($permissionCodes) {
    // First validate session
    $user = AuthMiddleware::validateSession();
    
    // Check if user has all required permissions
    $rbacService = new RBACService();
    $userPermissions = $rbacService->getUserPermissions($user['userId']);
    
    foreach ($permissionCodes as $code) {
        if (!in_array($code, $userPermissions)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient permissions. Required: ' . implode(' AND ', $permissionCodes)
            ]);
            exit;
        }
    }
}

