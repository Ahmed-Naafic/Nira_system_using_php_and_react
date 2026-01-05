<?php
/**
 * NIRA Database Setup - RBAC Extension
 * 
 * This script extends the existing database setup to include
 * Role-Based Access Control (RBAC) tables and seed data.
 * 
 * Run this script once to set up RBAC tables.
 * Safe to run multiple times (uses IF NOT EXISTS).
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "Starting RBAC database setup...\n\n";
    
    // ============================================
    // CREATE TABLES
    // ============================================
    
    // Roles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created roles table\n";
    
    // Permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100) NOT NULL UNIQUE,
            description VARCHAR(255),
            INDEX idx_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created permissions table\n";
    
    // Menus table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menus (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(100) NOT NULL,
            route VARCHAR(255),
            icon VARCHAR(100),
            order_index INT DEFAULT 0,
            parent_id INT NULL,
            FOREIGN KEY (parent_id) REFERENCES menus(id) ON DELETE CASCADE,
            INDEX idx_parent (parent_id),
            INDEX idx_order (order_index)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created menus table\n";
    
    // Role-Permissions junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created role_permissions table\n";
    
    // Menu-Permissions junction table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_permissions (
            menu_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (menu_id, permission_id),
            FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created menu_permissions table\n";
    
    // Update nira_users table to add role_id
    $pdo->exec("
        ALTER TABLE nira_users 
        ADD COLUMN IF NOT EXISTS role_id INT NULL,
        ADD FOREIGN KEY IF NOT EXISTS fk_user_role (role_id) REFERENCES roles(id) ON DELETE SET NULL,
        ADD INDEX IF NOT EXISTS idx_role (role_id)
    ");
    echo "✓ Updated nira_users table with role_id\n";
    
    // ============================================
    // SEED DATA
    // ============================================
    
    // Clear existing data (optional - comment out if you want to preserve)
    // $pdo->exec("DELETE FROM menu_permissions");
    // $pdo->exec("DELETE FROM role_permissions");
    // $pdo->exec("DELETE FROM menus");
    // $pdo->exec("DELETE FROM permissions");
    // $pdo->exec("DELETE FROM roles");
    
    // Insert Roles
    $roles = [
        ['ADMIN', 'System Administrator with full access'],
        ['OFFICER', 'Officer with citizen management permissions'],
        ['VIEWER', 'Read-only access to dashboard and reports']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO roles (name, description) VALUES (?, ?)");
    foreach ($roles as $role) {
        $stmt->execute($role);
    }
    echo "✓ Seeded roles\n";
    
    // Insert Permissions
    $permissions = [
        ['VIEW_DASHBOARD', 'View dashboard'],
        ['CREATE_CITIZEN', 'Create new citizens'],
        ['VIEW_CITIZEN', 'View citizen details'],
        ['UPDATE_CITIZEN', 'Update citizen information'],
        ['VIEW_REPORTS', 'View reports'],
        ['MANAGE_USERS', 'Manage system users'],
        ['MANAGE_ROLES', 'Manage roles and permissions']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (code, description) VALUES (?, ?)");
    foreach ($permissions as $perm) {
        $stmt->execute($perm);
    }
    echo "✓ Seeded permissions\n";
    
    // Get role IDs for mapping
    $roleIds = [];
    $stmt = $pdo->query("SELECT id, name FROM roles");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $roleIds[$row['name']] = $row['id'];
    }
    
    // Get permission IDs for mapping
    $permIds = [];
    $stmt = $pdo->query("SELECT id, code FROM permissions");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $permIds[$row['code']] = $row['id'];
    }
    
    // Map Roles to Permissions
    $rolePermissions = [
        'ADMIN' => [
            'VIEW_DASHBOARD', 'CREATE_CITIZEN', 'VIEW_CITIZEN', 
            'UPDATE_CITIZEN', 'VIEW_REPORTS', 'MANAGE_USERS', 'MANAGE_ROLES'
        ],
        'OFFICER' => [
            'VIEW_DASHBOARD', 'CREATE_CITIZEN', 'VIEW_CITIZEN', 'UPDATE_CITIZEN'
        ],
        'VIEWER' => [
            'VIEW_DASHBOARD', 'VIEW_CITIZEN', 'VIEW_REPORTS'
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
    foreach ($rolePermissions as $roleName => $permCodes) {
        $roleId = $roleIds[$roleName];
        foreach ($permCodes as $permCode) {
            $permId = $permIds[$permCode];
            $stmt->execute([$roleId, $permId]);
        }
    }
    echo "✓ Mapped roles to permissions\n";
    
    // Insert Menus
    $menus = [
        ['Dashboard', '/dashboard', 'home', 1, null],
        ['Citizens', '/citizens', 'users', 2, null],
        ['Add Citizen', '/citizens/create', 'user-plus', 3, null], // Will be updated with parent_id
        ['Reports', '/reports', 'bar-chart', 4, null],
        ['Users', '/users', 'user-cog', 5, null],
        ['Roles', '/roles', 'shield', 6, null]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO menus (label, route, icon, order_index, parent_id) VALUES (?, ?, ?, ?, ?)");
    $menuIds = [];
    
    foreach ($menus as $index => $menu) {
        $stmt->execute($menu);
        $menuIds[$menu[0]] = $pdo->lastInsertId();
    }
    
    // Update "Add Citizen" to be child of "Citizens"
    if (isset($menuIds['Citizens']) && isset($menuIds['Add Citizen'])) {
        $pdo->prepare("UPDATE menus SET parent_id = ? WHERE id = ?")
            ->execute([$menuIds['Citizens'], $menuIds['Add Citizen']]);
    }
    
    echo "✓ Seeded menus\n";
    
    // Map Menus to Permissions
    $menuPermissions = [
        'Dashboard' => ['VIEW_DASHBOARD'],
        'Citizens' => ['VIEW_CITIZEN'],
        'Add Citizen' => ['CREATE_CITIZEN'],
        'Reports' => ['VIEW_REPORTS'],
        'Users' => ['MANAGE_USERS'],
        'Roles' => ['MANAGE_ROLES']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO menu_permissions (menu_id, permission_id) VALUES (?, ?)");
    foreach ($menuPermissions as $menuLabel => $permCodes) {
        if (isset($menuIds[$menuLabel])) {
            $menuId = $menuIds[$menuLabel];
            foreach ($permCodes as $permCode) {
                $permId = $permIds[$permCode];
                $stmt->execute([$menuId, $permId]);
            }
        }
    }
    echo "✓ Mapped menus to permissions\n";
    
    echo "\n✅ RBAC database setup completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Assign roles to users: UPDATE nira_users SET role_id = <role_id> WHERE username = '<username>';\n";
    echo "2. Default role IDs: ADMIN=1, OFFICER=2, VIEWER=3\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

