-- Migration: Add deleted_at column to nira_users table for soft delete functionality
-- Run this SQL to add soft delete support (matches citizens table pattern)

USE nira_system;

-- Add deleted_at column (nullable, NULL means not deleted)
ALTER TABLE nira_users 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- Add index for faster queries on deleted_at
CREATE INDEX idx_deleted_at ON nira_users(deleted_at);

-- Note: After running this migration, all existing users will have deleted_at = NULL (not deleted)

