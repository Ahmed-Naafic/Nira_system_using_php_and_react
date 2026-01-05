# Troubleshooting: Wrong Role Displayed

## Problem
When logging in as "officer1", the system shows admin dashboard instead of officer dashboard.

## Root Causes

### 1. User Has No Role Assigned (Most Common)
The user `officer1` doesn't have a `role_id` in the database.

**Check:**
```sql
SELECT id, username, role_id FROM nira_users WHERE username = 'officer1';
```

If `role_id` is `NULL`, that's the problem.

**Fix:**
```sql
UPDATE nira_users SET role_id = 2 WHERE username = 'officer1';
```

### 2. Wrong Role ID Assigned
The user has `role_id = 1` (ADMIN) instead of `role_id = 2` (OFFICER).

**Check:**
```sql
SELECT u.username, u.role_id, r.name 
FROM nira_users u 
LEFT JOIN roles r ON u.role_id = r.id 
WHERE u.username = 'officer1';
```

**Fix:**
```sql
UPDATE nira_users SET role_id = 2 WHERE username = 'officer1';
```

### 3. Session Issue
The session might be storing the wrong user_id.

**Check:**
- Clear browser cookies
- Logout and login again
- Check browser DevTools → Application → Cookies

## Quick Diagnostic Scripts

### Option 1: Check All Users
```bash
php backend/database/check_user_roles.php
```

This will show:
- All users and their role assignments
- Which users have no role
- Available roles in the system
- Specific check for 'officer1'

### Option 2: Quick Fix for officer1
```bash
php backend/database/fix_officer1_role.php
```

This will:
- Assign OFFICER role to 'officer1'
- Verify the assignment

## Manual Database Fix

### Step 1: Check Current State
```sql
SELECT 
    u.id,
    u.username,
    u.role_id,
    r.name as role_name
FROM nira_users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE u.username = 'officer1';
```

### Step 2: Check Available Roles
```sql
SELECT id, name, description FROM roles;
```

Expected output:
- ID 1: ADMIN
- ID 2: OFFICER
- ID 3: VIEWER

### Step 3: Assign Correct Role
```sql
-- Assign OFFICER role (role_id = 2)
UPDATE nira_users SET role_id = 2 WHERE username = 'officer1';
```

### Step 4: Verify
```sql
SELECT 
    u.username,
    u.role_id,
    r.name as role_name,
    r.description
FROM nira_users u
INNER JOIN roles r ON u.role_id = r.id
WHERE u.username = 'officer1';
```

Should show:
- username: officer1
- role_id: 2
- role_name: OFFICER

## After Fixing

1. **Clear browser session:**
   - Logout from the application
   - Clear cookies (or use incognito mode)
   - Login again as officer1

2. **Verify in frontend:**
   - Check browser DevTools → Network tab
   - Look at `/api/auth/me.php` response
   - Should show `role.name: "OFFICER"`

3. **Check dashboard:**
   - Should show "OFFICER" in header
   - Should show only officer-appropriate menus
   - Should show only officer permissions

## Role IDs Reference

- **1** = ADMIN (all permissions)
- **2** = OFFICER (citizen management)
- **3** = VIEWER (read-only)

## Common SQL Commands

```sql
-- List all users with roles
SELECT u.username, r.name as role 
FROM nira_users u 
LEFT JOIN roles r ON u.role_id = r.id;

-- Assign role to user
UPDATE nira_users SET role_id = 2 WHERE username = 'officer1';

-- Check user permissions
SELECT p.code 
FROM permissions p
INNER JOIN role_permissions rp ON rp.permission_id = p.id
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN nira_users u ON u.role_id = r.id
WHERE u.username = 'officer1';
```

## Still Not Working?

1. **Check backend logs** - Look for PHP errors
2. **Check database connection** - Verify `config/database.php` settings
3. **Verify session** - Check if `$_SESSION['user_id']` is correct
4. **Check RBAC service** - Verify `getUserRole()` returns correct data

## Prevention

Always assign roles when creating users:
```sql
INSERT INTO nira_users (username, password, role_id) 
VALUES ('newuser', 'hashed_password', 2);
```

Or update immediately after creation:
```sql
UPDATE nira_users SET role_id = 2 WHERE username = 'newuser';
```

