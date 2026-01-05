<?php
/**
 * RBAC Service
 * 
 * Provides functions to retrieve user roles, permissions, and menus
 * based on the role-based access control system.
 */

require_once __DIR__ . '/../config/database.php';

class RBACService {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Get user's role
     * 
     * @param int $userId
     * @return array|null Role data or null if not found
     */
    public function getUserRole($userId) {
        $stmt = $this->pdo->prepare("
            SELECT r.id, r.name, r.description
            FROM roles r
            INNER JOIN nira_users u ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get user's permissions
     * 
     * @param int $userId
     * @return array Array of permission codes
     */
    public function getUserPermissions($userId) {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.code
            FROM permissions p
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            INNER JOIN nira_users u ON u.role_id = rp.role_id
            WHERE u.id = ?
            ORDER BY p.code
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get user's accessible menus
     * Builds hierarchical menu structure based on user permissions
     * 
     * @param int $userId
     * @return array Hierarchical menu structure
     */
    public function getUserMenus($userId) {
        // Get all menus that user has permission to access
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT m.id, m.label, m.route, m.icon, m.order_index, m.parent_id
            FROM menus m
            INNER JOIN menu_permissions mp ON mp.menu_id = m.id
            INNER JOIN permissions p ON p.id = mp.permission_id
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            INNER JOIN nira_users u ON u.role_id = rp.role_id
            WHERE u.id = ?
            ORDER BY m.order_index ASC, m.id ASC
        ");
        $stmt->execute([$userId]);
        $allMenus = $stmt->fetchAll();
        
        // Build hierarchical structure
        $menuMap = [];
        $rootMenus = [];
        
        // First pass: create menu map
        foreach ($allMenus as $menu) {
            $menuMap[$menu['id']] = [
                'id' => $menu['id'],
                'label' => $menu['label'],
                'route' => $menu['route'],
                'icon' => $menu['icon'],
                'order_index' => $menu['order_index'],
                'children' => []
            ];
        }
        
        // Second pass: build hierarchy
        foreach ($allMenus as $menu) {
            if ($menu['parent_id'] === null) {
                // Root menu
                $rootMenus[] = $menuMap[$menu['id']];
            } else {
                // Child menu
                if (isset($menuMap[$menu['parent_id']])) {
                    $menuMap[$menu['parent_id']]['children'][] = $menuMap[$menu['id']];
                }
            }
        }
        
        // Sort root menus by order_index
        usort($rootMenus, function($a, $b) {
            return $a['order_index'] <=> $b['order_index'];
        });
        
        // Sort children by order_index
        foreach ($rootMenus as &$menu) {
            usort($menu['children'], function($a, $b) {
                return $a['order_index'] <=> $b['order_index'];
            });
        }
        
        return $rootMenus;
    }
    
    /**
     * Check if user has a specific permission
     * 
     * @param int $userId
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission($userId, $permissionCode) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM role_permissions rp
            INNER JOIN permissions p ON p.id = rp.permission_id
            INNER JOIN nira_users u ON u.role_id = rp.role_id
            WHERE u.id = ? AND p.code = ?
        ");
        $stmt->execute([$userId, $permissionCode]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}

