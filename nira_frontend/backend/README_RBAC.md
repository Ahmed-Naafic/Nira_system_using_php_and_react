# RBAC System Documentation

## Overview

This Role-Based Access Control (RBAC) system provides a complete, scalable solution for managing user permissions and menu access in the NIRA system.

## Database Setup

### Step 1: Run Setup Script

```bash
php database/setup.php
```

This will:
- Create all RBAC tables (roles, permissions, menus, etc.)
- Seed initial data
- Add `role_id` column to `nira_users` table

### Step 2: Assign Roles to Users

```sql
-- Assign ADMIN role to a user
UPDATE nira_users SET role_id = 1 WHERE username = 'admin';

-- Assign OFFICER role to a user
UPDATE nira_users SET role_id = 2 WHERE username = 'officer';

-- Assign VIEWER role to a user
UPDATE nira_users SET role_id = 3 WHERE username = 'viewer';
```

## Backend Usage

### Using Permission Middleware

Protect any API endpoint with a required permission:

```php
<?php
// api/citizens/create.php
session_start();
require_once __DIR__ . '/../../middlewares/permission.php';

// Require CREATE_CITIZEN permission
requirePermission('CREATE_CITIZEN');

// Your endpoint logic here...
// If user doesn't have permission, script exits with 403
```

### Checking Permissions (Non-blocking)

```php
<?php
require_once __DIR__ . '/../../middlewares/permission.php';

if (hasPermission('VIEW_REPORTS')) {
    // Show reports
} else {
    // Show message: "Access denied"
}
```

### Using RBAC Service Directly

```php
<?php
require_once __DIR__ . '/../services/rbac.service.php';

$rbac = new RBACService();

// Get user role
$role = $rbac->getUserRole($userId);

// Get user permissions
$permissions = $rbac->getUserPermissions($userId);

// Get user menus
$menus = $rbac->getUserMenus($userId);

// Check permission
if ($rbac->hasPermission($userId, 'VIEW_DASHBOARD')) {
    // User has permission
}
```

## Frontend Usage

### Accessing Auth Context

```jsx
import { useAuth } from '../context/AuthContext';

function MyComponent() {
  const { user, permissions, menus, hasPermission } = useAuth();
  
  if (hasPermission('CREATE_CITIZEN')) {
    return <button>Create Citizen</button>;
  }
  
  return null;
}
```

### Using PermissionRoute

```jsx
<PermissionRoute requiredPermission="VIEW_REPORTS">
  <ReportsPage />
</PermissionRoute>
```

## API Endpoints

### GET /api/auth/me.php

Returns current user information, permissions, and menus.

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "admin",
    "role": {
      "id": 1,
      "name": "ADMIN",
      "description": "System Administrator"
    }
  },
  "permissions": [
    "VIEW_DASHBOARD",
    "CREATE_CITIZEN",
    "VIEW_CITIZEN",
    "UPDATE_CITIZEN",
    "VIEW_REPORTS",
    "MANAGE_USERS",
    "MANAGE_ROLES"
  ],
  "menus": [
    {
      "id": 1,
      "label": "Dashboard",
      "route": "/dashboard",
      "icon": "home",
      "children": []
    }
  ]
}
```

## Roles and Permissions

### Roles

- **ADMIN**: Full system access
- **OFFICER**: Citizen management
- **VIEWER**: Read-only access

### Permissions

- `VIEW_DASHBOARD` - Access dashboard
- `CREATE_CITIZEN` - Register new citizens
- `VIEW_CITIZEN` - View citizen details
- `UPDATE_CITIZEN` - Update citizen information
- `VIEW_REPORTS` - Access reports
- `MANAGE_USERS` - Manage system users
- `MANAGE_ROLES` - Manage roles and permissions

## Adding New Permissions

1. **Add to database:**
```sql
INSERT INTO permissions (code, description) 
VALUES ('NEW_PERMISSION', 'Description');
```

2. **Map to roles:**
```sql
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r, permissions p
WHERE r.name = 'ADMIN' AND p.code = 'NEW_PERMISSION';
```

3. **Map to menus (if needed):**
```sql
INSERT INTO menu_permissions (menu_id, permission_id)
SELECT m.id, p.id
FROM menus m, permissions p
WHERE m.route = '/new-route' AND p.code = 'NEW_PERMISSION';
```

## Security Notes

- **Backend is source of truth**: Never trust frontend permissions
- **Always check permissions on backend**: Use `requirePermission()` middleware
- **Session-based**: Uses PHP sessions, not JWT
- **Scalable**: Easy to add new roles, permissions, and menus

## Troubleshooting

### User sees no menus

1. Check user has a role assigned: `SELECT * FROM nira_users WHERE id = ?`
2. Check role has permissions: `SELECT * FROM role_permissions WHERE role_id = ?`
3. Check menus are mapped to permissions: `SELECT * FROM menu_permissions`

### Permission denied errors

1. Verify user has the required permission in database
2. Check session is active
3. Verify middleware is included correctly

