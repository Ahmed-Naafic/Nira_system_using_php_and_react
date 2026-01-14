-- =====================================================
-- NIRA System - Complete Database Installation Script
-- =====================================================
-- This script includes all database files in the correct order
-- Run this script to set up the complete database schema
-- 
-- Usage: mysql -u root -p < install_all.sql
-- =====================================================

-- Set database
USE nira_system;

-- =====================================================
-- 1. MAIN SCHEMA
-- =====================================================
-- Source: schema.sql
-- Creates main tables: nira_users, citizens, roles, permissions, etc.

-- NIRA Users Table (Officers and Admins)
CREATE TABLE IF NOT EXISTS nira_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT DEFAULT NULL,
    role ENUM('ADMIN', 'OFFICER', 'VIEWER') DEFAULT NULL,
    status ENUM('ACTIVE', 'DISABLED') NOT NULL DEFAULT 'ACTIVE',
    phone_number VARCHAR(20) NULL,
    profile_picture_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_username (username),
    INDEX idx_role_id (role_id),
    INDEX idx_status (status),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions Table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions Table
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menus Table
CREATE TABLE IF NOT EXISTS menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    route VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    order_index INT DEFAULT 0,
    permission_code VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES menus(id) ON DELETE CASCADE,
    INDEX idx_parent_id (parent_id),
    INDEX idx_permission_code (permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Menus Table
CREATE TABLE IF NOT EXISTS role_menus (
    role_id INT NOT NULL,
    menu_id INT NOT NULL,
    PRIMARY KEY (role_id, menu_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Citizens Table
CREATE TABLE IF NOT EXISTS citizens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    gender ENUM('MALE', 'FEMALE') NOT NULL,
    date_of_birth DATE NOT NULL,
    place_of_birth VARCHAR(200) NOT NULL,
    nationality VARCHAR(100) DEFAULT 'Somali',
    image_path VARCHAR(500) NULL,
    document_path VARCHAR(500) NULL,
    status ENUM('ACTIVE', 'DECEASED') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_national_id (national_id),
    INDEX idx_status (status),
    INDEX idx_full_name (first_name, middle_name, last_name),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status Change Log Table (for audit trail)
CREATE TABLE IF NOT EXISTS status_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    national_id VARCHAR(20) NOT NULL,
    old_status ENUM('ACTIVE', 'DECEASED') NOT NULL,
    new_status ENUM('ACTIVE', 'DECEASED') NOT NULL,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (changed_by) REFERENCES nira_users(id),
    INDEX idx_national_id (national_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Notices Table
CREATE TABLE IF NOT EXISTS system_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('LOW', 'MEDIUM', 'HIGH', 'URGENT') DEFAULT 'MEDIUM',
    created_by INT NOT NULL,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES nira_users(id),
    INDEX idx_priority (priority),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Activities Table
CREATE TABLE IF NOT EXISTS system_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES nira_users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. MIGRATIONS - Add deleted_at to citizens
-- =====================================================
-- Source: add_deleted_at_to_citizens.sql
ALTER TABLE citizens 
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
ADD INDEX IF NOT EXISTS idx_deleted_at (deleted_at);

-- =====================================================
-- 3. MIGRATIONS - Add deleted_at to users
-- =====================================================
-- Source: add_deleted_at_to_users.sql
ALTER TABLE nira_users 
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
ADD INDEX IF NOT EXISTS idx_deleted_at (deleted_at);

-- =====================================================
-- 4. MIGRATIONS - Add file upload fields to citizens
-- =====================================================
-- Source: add_citizen_files.sql
ALTER TABLE citizens 
ADD COLUMN IF NOT EXISTS image_path VARCHAR(500) NULL AFTER nationality,
ADD COLUMN IF NOT EXISTS document_path VARCHAR(500) NULL AFTER image_path;

-- =====================================================
-- 5. MIGRATIONS - Add phone and profile picture to users
-- =====================================================
-- Source: add_user_phone_profile.sql
ALTER TABLE nira_users 
ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) NULL AFTER status,
ADD COLUMN IF NOT EXISTS profile_picture_path VARCHAR(500) NULL AFTER phone_number,
ADD INDEX IF NOT EXISTS idx_phone_number (phone_number);

-- =====================================================
-- 6. MIGRATIONS - Create system notices and activities tables
-- =====================================================
-- Source: create_system_notices_table.sql
-- (Already created above in main schema)

-- Source: create_system_activities_table.sql
-- (Already created above in main schema)

-- Source: create_system_notices_activities_tables.sql
-- (Already created above in main schema)

-- =====================================================
-- 7. INSERT DEFAULT DATA
-- =====================================================

-- Insert default roles
INSERT IGNORE INTO roles (id, name, description) VALUES
(1, 'ADMIN', 'System Administrator with full access'),
(2, 'OFFICER', 'Registration Officer with citizen management access'),
(3, 'VIEWER', 'View-only access to citizen records');

-- Insert default permissions
INSERT IGNORE INTO permissions (id, code, name, description) VALUES
(1, 'MANAGE_USERS', 'Manage Users', 'Create, update, and delete system users'),
(2, 'VIEW_CITIZEN', 'View Citizens', 'View citizen records'),
(3, 'CREATE_CITIZEN', 'Create Citizens', 'Register new citizens'),
(4, 'UPDATE_CITIZEN', 'Update Citizens', 'Update citizen information'),
(5, 'DELETE_CITIZEN', 'Delete Citizens', 'Delete citizen records'),
(6, 'VIEW_DASHBOARD', 'View Dashboard', 'Access dashboard statistics'),
(7, 'VIEW_REPORTS', 'View Reports', 'Access system reports'),
(8, 'MANAGE_NOTICES', 'Manage Notices', 'Create and manage system notices'),
(9, 'VIEW_ACTIVITIES', 'View Activities', 'View system activity logs');

-- Assign permissions to ADMIN role
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9);

-- Assign permissions to OFFICER role
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(2, 2), (2, 3), (2, 4), (2, 6), (2, 7);

-- Assign permissions to VIEWER role
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(3, 2), (3, 6);

-- Insert default menus
INSERT IGNORE INTO menus (id, name, route, icon, parent_id, order_index, permission_code) VALUES
(1, 'Dashboard', '/dashboard', 'home', NULL, 1, 'VIEW_DASHBOARD'),
(2, 'Citizens', NULL, 'users', NULL, 2, 'VIEW_CITIZEN'),
(3, 'Register Citizen', '/citizens/create', 'user-plus', 2, 1, 'CREATE_CITIZEN'),
(4, 'List Citizens', '/citizens', NULL, 2, 2, 'VIEW_CITIZEN'),
(5, 'Users', '/users', 'user-cog', NULL, 3, 'MANAGE_USERS'),
(6, 'Reports', NULL, 'bar-chart', NULL, 4, 'VIEW_REPORTS'),
(7, 'Citizen Reports', '/reports/citizens', NULL, 6, 1, 'VIEW_REPORTS'),
(8, 'Registration Reports', '/reports/registrations', NULL, 6, 2, 'VIEW_REPORTS'),
(9, 'User Reports', '/reports/users', NULL, 6, 3, 'VIEW_REPORTS'),
(10, 'Summary Reports', '/reports/summary', NULL, 6, 4, 'VIEW_REPORTS'),
(11, 'System', NULL, 'shield', NULL, 5, NULL),
(12, 'Notices', '/notices', NULL, 11, 1, 'MANAGE_NOTICES'),
(13, 'Activities', '/activities', NULL, 11, 2, 'VIEW_ACTIVITIES');

-- Assign menus to ADMIN role
INSERT IGNORE INTO role_menus (role_id, menu_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11), (1, 12), (1, 13);

-- Assign menus to OFFICER role
INSERT IGNORE INTO role_menus (role_id, menu_id) VALUES
(2, 1), (2, 2), (2, 3), (2, 4), (2, 6), (2, 7), (2, 8), (2, 9), (2, 10);

-- Assign menus to VIEWER role
INSERT IGNORE INTO role_menus (role_id, menu_id) VALUES
(3, 1), (3, 2), (3, 4);

-- Insert default admin user (password: admin123)
-- Password hash is generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT IGNORE INTO nira_users (id, username, password, role_id, role, status) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'ADMIN', 'ACTIVE');

-- Insert default officer user (password: admin123)
INSERT IGNORE INTO nira_users (id, username, password, role_id, role, status) VALUES
(2, 'officer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'OFFICER', 'ACTIVE');

-- =====================================================
-- 8. MIGRATIONS - Add notices and activities permissions
-- =====================================================
-- Source: add_notices_activities_permissions.sql
-- (Already inserted above in default permissions)

-- =====================================================
-- 9. MIGRATIONS - Add UPDATE_CITIZEN to Officer role
-- =====================================================
-- Source: add_update_citizen_to_officer.sql
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 2, id FROM permissions WHERE code = 'UPDATE_CITIZEN';

-- =====================================================
-- 10. MIGRATIONS - Add VIEW_REPORTS to Officer role
-- =====================================================
-- Source: add_view_reports_to_officer.sql
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 2, id FROM permissions WHERE code = 'VIEW_REPORTS';

-- =====================================================
-- 11. MIGRATIONS - Remove VIEW_REPORTS from Viewer role
-- =====================================================
-- Source: remove_view_reports_from_viewer.sql
DELETE FROM role_permissions 
WHERE role_id = 3 
AND permission_id = (SELECT id FROM permissions WHERE code = 'VIEW_REPORTS');

-- =====================================================
-- Installation Complete
-- =====================================================
SELECT 'NIRA System database installation completed successfully!' AS message;
SELECT 'Default users created:' AS info;
SELECT '  - admin / admin123 (ADMIN role)' AS admin_user;
SELECT '  - officer1 / admin123 (OFFICER role)' AS officer_user;
SELECT 'Please change default passwords in production!' AS warning;
