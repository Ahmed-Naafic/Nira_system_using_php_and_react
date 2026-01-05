<?php
/**
 * Database Setup Script with RBAC System
 * Run this script to initialize the database with proper password hashes and RBAC tables
 * Usage: php database/setup.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Starting database setup...\n\n";
    
    // ============================================
    // STEP 1: Create RBAC Tables
    // ============================================
    
    // 1. Create roles table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) UNIQUE NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created roles table\n";
    
    // 2. Add role_id column to nira_users if it doesn't exist
    $checkColumn = $conn->query("SHOW COLUMNS FROM nira_users LIKE 'role_id'");
    if ($checkColumn->rowCount() == 0) {
        $conn->exec("
            ALTER TABLE nira_users 
            ADD COLUMN role_id INT DEFAULT NULL,
            ADD COLUMN status ENUM('ACTIVE', 'DISABLED') DEFAULT 'ACTIVE' AFTER password,
            ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
            ADD INDEX idx_role_id (role_id)
        ");
        echo "✓ Added role_id and status columns to nira_users table\n";
    } else {
        echo "✓ role_id column already exists in nira_users table\n";
    }
    
    // 3. Create permissions table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(100) UNIQUE NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created permissions table\n";
    
    // 4. Create menus table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS menus (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(100) NOT NULL,
            route VARCHAR(255) NOT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            order_index INT DEFAULT 0,
            parent_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES menus(id) ON DELETE CASCADE,
            INDEX idx_parent_id (parent_id),
            INDEX idx_order (order_index)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created menus table\n";
    
    // 5. Create role_permissions table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
            INDEX idx_role_id (role_id),
            INDEX idx_permission_id (permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created role_permissions table\n";
    
    // 6. Create menu_permissions table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS menu_permissions (
            menu_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (menu_id, permission_id),
            FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
            INDEX idx_menu_id (menu_id),
            INDEX idx_permission_id (permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created menu_permissions table\n";
    
    // ============================================
    // STEP 2: Seed Initial Data
    // ============================================
    
    // Insert roles
    $conn->exec("
        INSERT IGNORE INTO roles (id, name, description) VALUES
        (1, 'ADMIN', 'Full system access'),
        (2, 'OFFICER', 'Can create and view citizens'),
        (3, 'VIEWER', 'Read-only access')
    ");
    echo "✓ Seeded roles (ADMIN, OFFICER, VIEWER)\n";
    
    // Insert permissions
    $conn->exec("
        INSERT IGNORE INTO permissions (id, code, description) VALUES
        (1, 'VIEW_DASHBOARD', 'View dashboard'),
        (2, 'CREATE_CITIZEN', 'Create new citizen records'),
        (3, 'VIEW_CITIZEN', 'View citizen records'),
        (4, 'UPDATE_CITIZEN', 'Update citizen records'),
        (5, 'VIEW_REPORTS', 'View reports'),
        (6, 'MANAGE_USERS', 'Manage system users'),
        (7, 'MANAGE_ROLES', 'Manage roles and permissions')
    ");
    echo "✓ Seeded permissions\n";
    
    // Insert menus
    $conn->exec("
        INSERT IGNORE INTO menus (id, label, route, icon, order_index, parent_id) VALUES
        (1, 'Dashboard', '/dashboard', 'home', 1, NULL),
        (2, 'Citizens', '/citizens', 'users', 2, NULL),
        (3, 'Add Citizen', '/citizens/create', 'user-plus', 3, 2),
        (4, 'Reports', '/reports', 'bar-chart', 4, NULL),
        (5, 'User Management', '/users', 'settings', 5, NULL)
    ");
    echo "✓ Seeded menus\n";
    
    // Map permissions to menus
    $conn->exec("
        INSERT IGNORE INTO menu_permissions (menu_id, permission_id) VALUES
        (1, 1),  -- Dashboard -> VIEW_DASHBOARD
        (2, 3),  -- Citizens -> VIEW_CITIZEN
        (3, 2),  -- Add Citizen -> CREATE_CITIZEN
        (4, 5),  -- Reports -> VIEW_REPORTS
        (5, 6)   -- User Management -> MANAGE_USERS
    ");
    echo "✓ Mapped permissions to menus\n";
    
    // Map roles to permissions
    // ADMIN gets all permissions
    $conn->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
        (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7)
    ");
    
    // OFFICER gets dashboard and citizen permissions
    $conn->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
        (2, 1), (2, 2), (2, 3)
    ");
    
    // VIEWER gets view-only permissions
    $conn->exec("
        INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
        (3, 1), (3, 3), (3, 5)
    ");
    echo "✓ Mapped permissions to roles\n";
    
    // ============================================
    // STEP 3: Update existing users with role_id
    // ============================================
    
    // Update admin user to use role_id
    $conn->exec("
        UPDATE nira_users 
        SET role_id = 1 
        WHERE username = 'admin' AND role_id IS NULL
    ");
    
    // Update officer1 user to use role_id
    $conn->exec("
        UPDATE nira_users 
        SET role_id = 2 
        WHERE username = 'officer1' AND role_id IS NULL
    ");
    
    // Create viewer user if it doesn't exist
    $checkViewer = $conn->query("SELECT id FROM nira_users WHERE username = 'viewer1'");
    if ($checkViewer->rowCount() == 0) {
        $viewerPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO nira_users (username, password, role, role_id, status) 
            VALUES ('viewer1', ?, 'OFFICER', 3, 'ACTIVE')
        ");
        $stmt->execute([$viewerPassword]);
        echo "✓ Created viewer1 user\n";
    }
    
    // ============================================
    // STEP 4: Update existing users with proper password hashes
    // ============================================
    
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $officerPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Update passwords for existing users
    $stmt = $conn->prepare("UPDATE nira_users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$adminPassword]);
    
    $stmt = $conn->prepare("UPDATE nira_users SET password = ? WHERE username = 'officer1'");
    $stmt->execute([$officerPassword]);
    
    echo "\n✓ Updated user passwords\n";
    
    // ============================================
    // Summary
    // ============================================
    
    echo "\n========================================\n";
    echo "Database setup completed successfully!\n";
    echo "========================================\n\n";
    echo "Default credentials:\n";
    echo "  Admin: username=admin, password=admin123 (role_id=1, ADMIN)\n";
    echo "  Officer: username=officer1, password=admin123 (role_id=2, OFFICER)\n";
    echo "  Viewer: username=viewer1, password=admin123 (role_id=3, VIEWER)\n";
    echo "\nRBAC System:\n";
    echo "  - 3 Roles: ADMIN, OFFICER, VIEWER\n";
    echo "  - 7 Permissions: VIEW_DASHBOARD, CREATE_CITIZEN, VIEW_CITIZEN, UPDATE_CITIZEN, VIEW_REPORTS, MANAGE_USERS, MANAGE_ROLES\n";
    echo "  - 5 Menus: Dashboard, Citizens, Add Citizen, Reports, User Management\n";
    echo "\nIMPORTANT: Change these passwords in production!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
