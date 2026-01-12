-- Migration: Add VIEW_REPORTS permission to OFFICER role
-- This allows officers to view reports as per requirements

USE nira_system;

-- Add VIEW_REPORTS permission (permission_id = 5) to OFFICER role (role_id = 2)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 5);

-- Verify the change
SELECT r.name as role_name, p.code as permission_code
FROM roles r
INNER JOIN role_permissions rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE r.name = 'OFFICER'
ORDER BY p.code;

