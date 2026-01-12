-- Migration: Create system_activities table for automatic system events
-- Run this SQL to add system activities logging functionality

USE nira_system;

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

