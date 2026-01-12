-- Migration: Add phone_number and profile_picture_path columns to nira_users table
-- Run this SQL to add phone number and profile picture support

USE nira_system;

-- Add phone_number column (nullable, stores user phone number)
ALTER TABLE nira_users 
ADD COLUMN phone_number VARCHAR(20) NULL DEFAULT NULL AFTER username;

-- Add profile_picture_path column (nullable, stores path to user profile picture)
ALTER TABLE nira_users 
ADD COLUMN profile_picture_path VARCHAR(500) NULL DEFAULT NULL AFTER phone_number;

-- Add index for faster queries on phone number
CREATE INDEX idx_phone_number ON nira_users(phone_number);

-- Add index for faster queries on profile picture path
CREATE INDEX idx_profile_picture_path ON nira_users(profile_picture_path);

-- Note: After running this migration, all existing users will have NULL for both fields
