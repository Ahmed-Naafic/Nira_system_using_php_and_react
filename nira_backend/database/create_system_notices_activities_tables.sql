-- Migration: Create system_notices and system_activities tables
-- Run this SQL to add system notices and activities functionality

USE nira_system;

-- Create system_notices table
CREATE TABLE IF NOT EXISTS system_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('INFO', 'WARNING', 'ALERT') NOT NULL DEFAULT 'INFO',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES nira_users(id) ON DELETE RESTRICT,
    INDEX idx_created_by (created_by),
    INDEX idx_type (type),
    INDEX idx_expires_at (expires_at),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create system_activities table
CREATE TABLE IF NOT EXISTS system_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(50) NULL DEFAULT NULL,
    description TEXT NOT NULL,
    performed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by) REFERENCES nira_users(id) ON DELETE RESTRICT,
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_performed_by (performed_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add permissions
INSERT IGNORE INTO permissions (id, code, description) VALUES
(8, 'VIEW_NOTICES', 'View system notices'),
(9, 'MANAGE_NOTICES', 'Create and delete system notices'),
(10, 'VIEW_ACTIVITIES', 'View system activities');

-- Grant permissions to ADMIN role (role_id = 1)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(1, 8),  -- ADMIN -> VIEW_NOTICES
(1, 9),  -- ADMIN -> MANAGE_NOTICES
(1, 10); -- ADMIN -> VIEW_ACTIVITIES

-- Grant permissions to OFFICER role (role_id = 2)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(2, 8),  -- OFFICER -> VIEW_NOTICES
(2, 10); -- OFFICER -> VIEW_ACTIVITIES

