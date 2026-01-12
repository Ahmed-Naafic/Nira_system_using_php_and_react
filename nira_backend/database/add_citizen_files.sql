-- Migration: Add image_path and document_path columns to citizens table
-- Run this SQL to add file upload support

USE nira_system;

-- Add image_path column (nullable, stores path to citizen photo/image)
ALTER TABLE citizens 
ADD COLUMN image_path VARCHAR(500) NULL DEFAULT NULL AFTER nationality;

-- Add document_path column (nullable, stores path to citizen document)
ALTER TABLE citizens 
ADD COLUMN document_path VARCHAR(500) NULL DEFAULT NULL AFTER image_path;

-- Add indexes for faster queries
CREATE INDEX idx_image_path ON citizens(image_path);
CREATE INDEX idx_document_path ON citizens(document_path);

-- Note: After running this migration, all existing citizens will have NULL for both paths
