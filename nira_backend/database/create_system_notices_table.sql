-- Migration: Create system_notices table for manual announcements
-- Run this SQL to add system notices functionality

USE nira_system;

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

