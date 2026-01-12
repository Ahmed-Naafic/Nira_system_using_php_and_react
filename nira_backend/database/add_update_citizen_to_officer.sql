-- Migration: Add UPDATE_CITIZEN permission to OFFICER role
-- This allows officers to edit/update citizen records as per requirements

USE nira_system;

-- Add UPDATE_CITIZEN permission (permission_id = 4) to OFFICER role (role_id = 2)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, 4);

-- Verify the change
SELECT r.name as role_name, p.code as permission_code
FROM roles r
INNER JOIN role_permissions rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE r.name = 'OFFICER'
ORDER BY p.code;

