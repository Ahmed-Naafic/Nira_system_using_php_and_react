<?php
/**
 * RBAC Service for NIRA System
 * Provides role-based access control functionality
 */

require_once __DIR__ . '/../config/database.php';

class RBACService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get all permissions for a user based on their role
     * @param int $userId
     * @return array Array of permission codes
     */
    public function getUserPermissions($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT p.code
                FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                INNER JOIN nira_users u ON rp.role_id = u.role_id
                WHERE u.id = :userId AND u.status = 'ACTIVE'
            ");
            
            $stmt->execute(['userId' => $userId]);
            $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return $permissions ?: [];
        } catch (Exception $e) {
            error_log("RBAC Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all menus accessible to a user based on their permissions
     * Returns structured menu tree for frontend
     * @param int $userId
     * @return array Structured menu array
     */
    public function getUserMenus($userId) {
        try {
            // Get user's permissions
            $permissions = $this->getUserPermissions($userId);
            
            if (empty($permissions)) {
                return [];
            }
            
            // Get menus that user has permission to access
            $placeholders = str_repeat('?,', count($permissions) - 1) . '?';
            $stmt = $this->conn->prepare("
                SELECT DISTINCT m.id, m.label, m.route, m.icon, m.order_index, m.parent_id
                FROM menus m
                INNER JOIN menu_permissions mp ON m.id = mp.menu_id
                INNER JOIN permissions p ON mp.permission_id = p.id
                WHERE p.code IN ($placeholders)
                ORDER BY m.order_index ASC, m.id ASC
            ");
            
            $stmt->execute($permissions);
            $menus = $stmt->fetchAll();
            
            // Build menu tree structure
            $menuTree = $this->buildMenuTree($menus);
            
            return $menuTree;
        } catch (Exception $e) {
            error_log("RBAC Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build hierarchical menu tree from flat menu array
     * @param array $menus Flat menu array
     * @return array Hierarchical menu tree
     */
    private function buildMenuTree($menus) {
        $menuMap = [];
        $rootMenus = [];
        
        // First pass: create menu map
        foreach ($menus as $menu) {
            $menuItem = [
                'id' => (int)$menu['id'],
                'label' => $menu['label'],
                'route' => $menu['route'],
                'icon' => $menu['icon'],
                'order_index' => (int)$menu['order_index'],
                'children' => []
            ];
            
            $menuMap[$menu['id']] = $menuItem;
        }
        
        // Second pass: build tree structure
        foreach ($menus as $menu) {
            $parentId = $menu['parent_id'];
            
            if ($parentId === null || !isset($menuMap[$parentId])) {
                // Root menu
                $rootMenus[] = $menuMap[$menu['id']];
            } else {
                // Child menu
                $menuMap[$parentId]['children'][] = $menuMap[$menu['id']];
            }
        }
        
        // Sort root menus by order_index
        usort($rootMenus, function($a, $b) {
            return $a['order_index'] <=> $b['order_index'];
        });
        
        // Sort children in each menu
        foreach ($rootMenus as &$menu) {
            if (!empty($menu['children'])) {
                usort($menu['children'], function($a, $b) {
                    return $a['order_index'] <=> $b['order_index'];
                });
            }
        }
        
        return $rootMenus;
    }
    
    /**
     * Get user role information
     * @param int $userId
     * @return array|null Role information
     */
    public function getUserRole($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT r.id, r.name, r.description
                FROM roles r
                INNER JOIN nira_users u ON r.id = u.role_id
                WHERE u.id = :userId AND u.status = 'ACTIVE'
            ");
            
            $stmt->execute(['userId' => $userId]);
            $role = $stmt->fetch();
            
            return $role ?: null;
        } catch (Exception $e) {
            error_log("RBAC Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user has a specific permission
     * @param int $userId
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission($userId, $permissionCode) {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permissionCode, $permissions);
    }
}

