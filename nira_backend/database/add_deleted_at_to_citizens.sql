-- Migration: Add deleted_at column to citizens table for soft delete functionality
-- Run this SQL to add soft delete support

USE nira_system;

-- Add deleted_at column (nullable, NULL means not deleted)
ALTER TABLE citizens 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at;

-- Add index for faster queries on deleted_at
CREATE INDEX idx_deleted_at ON citizens(deleted_at);

-- Note: After running this migration, all existing citizens will have deleted_at = NULL (not deleted)

