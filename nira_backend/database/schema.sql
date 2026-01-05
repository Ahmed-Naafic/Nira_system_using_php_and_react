-- NIRA System Database Schema
-- National Identity Registration Authority

CREATE DATABASE IF NOT EXISTS nira_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nira_system;

-- NIRA Users Table (Officers and Admins)
CREATE TABLE IF NOT EXISTS nira_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'OFFICER') NOT NULL DEFAULT 'OFFICER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
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
    status ENUM('ACTIVE', 'DECEASED') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_national_id (national_id),
    INDEX idx_status (status),
    INDEX idx_full_name (first_name, middle_name, last_name)
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

-- Insert default admin user (password: admin123)
-- NOTE: Run database/setup.php after schema creation to properly hash passwords
-- The setup script will create users with correct password hashes
-- Default credentials: username=admin/officer1, password=admin123
-- IMPORTANT: Change passwords in production!

