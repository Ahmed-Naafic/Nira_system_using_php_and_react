<?php
/**
 * Get Current User Information
 * 
 * Returns the authenticated user's information including:
 * - User details (id, username, role)
 * - Permissions
 * - Accessible menus
 * 
 * Requires active session.
 */

session_start();

// CORS headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
    exit;
}

require_once __DIR__ . '/../../services/rbac.service.php';

try {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'Unknown';
    
    $rbac = new RBACService();
    
    // Get user role
    $role = $rbac->getUserRole($userId);
    
    // Check if user has a role assigned
    if (!$role) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'User does not have a role assigned. Please contact administrator.',
            'user_id' => (int)$userId,
            'username' => $username
        ]);
        exit;
    }
    
    // Get user permissions
    $permissions = $rbac->getUserPermissions($userId);
    
    // Get user menus
    $menus = $rbac->getUserMenus($userId);
    
    // Build response
    $response = [
        'success' => true,
        'user' => [
            'id' => (int)$userId,
            'username' => $username,
            'role' => [
                'id' => (int)$role['id'],
                'name' => $role['name'],
                'description' => $role['description']
            ]
        ],
        'permissions' => $permissions,
        'menus' => $menus
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching user information',
        'error' => $e->getMessage()
    ]);
}

