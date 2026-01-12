-- Migration: Remove VIEW_REPORTS permission from VIEWER role
-- VIEWER role should only have VIEW_DASHBOARD and VIEW_CITIZEN permissions

USE nira_system;

-- Remove VIEW_REPORTS permission (permission_id = 5) from VIEWER role (role_id = 3)
DELETE FROM role_permissions 
WHERE role_id = 3 AND permission_id = 5;

-- Verify the change
SELECT r.name as role_name, p.code as permission_code
FROM roles r
INNER JOIN role_permissions rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE r.name = 'VIEWER'
ORDER BY p.code;

