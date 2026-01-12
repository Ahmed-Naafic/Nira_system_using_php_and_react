-- Migration: Add permissions for System Notices and Activities
-- Run this SQL to add the new permissions and assign them to roles

USE nira_system;

-- Insert new permissions
INSERT IGNORE INTO permissions (id, code, description) VALUES
(8, 'VIEW_NOTICES', 'View system notices'),
(9, 'MANAGE_NOTICES', 'Create and delete system notices'),
(10, 'VIEW_ACTIVITIES', 'View system activities');

-- Grant permissions to ADMIN role (role_id = 1)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(1, 8),  -- ADMIN -> VIEW_NOTICES
(1, 9),  -- ADMIN -> MANAGE_NOTICES
(1, 10); -- ADMIN -> VIEW_ACTIVITIES

-- Grant permissions to OFFICER role (role_id = 2)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(2, 8),  -- OFFICER -> VIEW_NOTICES
(2, 10); -- OFFICER -> VIEW_ACTIVITIES

-- Verify the changes
SELECT r.name as role_name, p.code as permission_code
FROM roles r
INNER JOIN role_permissions rp ON r.id = rp.role_id
INNER JOIN permissions p ON rp.permission_id = p.id
WHERE p.code IN ('VIEW_NOTICES', 'MANAGE_NOTICES', 'VIEW_ACTIVITIES')
ORDER BY r.name, p.code;

