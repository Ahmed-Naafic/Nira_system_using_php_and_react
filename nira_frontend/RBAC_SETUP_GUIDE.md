# RBAC System Setup Guide

## Quick Start

### 1. Backend Setup

1. **Update database configuration:**
   - Edit `backend/config/database.php`
   - Set your database credentials

2. **Run database setup:**
   ```bash
   cd backend
   php database/setup.php
   ```

3. **Assign roles to users:**
   ```sql
   -- Connect to your database
   USE nira_system;
   
   -- Assign ADMIN role (role_id = 1)
   UPDATE nira_users SET role_id = 1 WHERE username = 'your_admin_username';
   
   -- Assign OFFICER role (role_id = 2)
   UPDATE nira_users SET role_id = 2 WHERE username = 'your_officer_username';
   
   -- Assign VIEWER role (role_id = 3)
   UPDATE nira_users SET role_id = 3 WHERE username = 'your_viewer_username';
   ```

### 2. Backend File Structure

Place the backend files in your XAMPP `htdocs/nira_system` directory:

```
nira_system/
├── api/
│   └── auth/
│       └── me.php
├── config/
│   └── database.php
├── database/
│   └── setup.php
├── middlewares/
│   └── permission.php
└── services/
    └── rbac.service.php
```

### 3. Frontend Setup

The frontend is already configured! Just ensure:

1. **Environment variable is set:**
   - File: `client/.env`
   - Content: `VITE_API_BASE_URL=http://localhost:8000/nira_system`

2. **Start frontend:**
   ```bash
   cd client
   npm install  # If not already done
   npm run dev
   ```

### 4. Test the System

1. **Login** with a user that has a role assigned
2. **Check dashboard** - should show user info and role
3. **Check sidebar** - should show menus based on permissions
4. **Test permissions** - try accessing routes you don't have permission for

## File Locations

### Backend Files (Place in XAMPP)

- `backend/database/setup.php` → `htdocs/nira_system/database/setup.php`
- `backend/config/database.php` → `htdocs/nira_system/config/database.php`
- `backend/services/rbac.service.php` → `htdocs/nira_system/services/rbac.service.php`
- `backend/api/auth/me.php` → `htdocs/nira_system/api/auth/me.php`
- `backend/middlewares/permission.php` → `htdocs/nira_system/middlewares/permission.php`

### Frontend Files (Already in place)

- `client/src/context/AuthContext.jsx` ✅
- `client/src/auth/PermissionRoute.jsx` ✅
- `client/src/components/Sidebar.jsx` ✅
- `client/src/layout/DashboardLayout.jsx` ✅ (updated)
- `client/src/pages/Dashboard.jsx` ✅ (updated)
- `client/src/App.jsx` ✅ (updated)
- `client/src/main.jsx` ✅ (updated)

## Testing Checklist

- [ ] Database setup script runs without errors
- [ ] Users have roles assigned in database
- [ ] `/api/auth/me.php` returns user info, permissions, and menus
- [ ] Frontend displays user role in header
- [ ] Sidebar shows correct menus based on permissions
- [ ] Dashboard shows role-appropriate content
- [ ] Permission-protected routes show "Access Denied" when needed
- [ ] Login/logout works correctly

## Common Issues

### "No menu items available"

- User doesn't have a role assigned
- Role doesn't have permissions
- Menus aren't mapped to permissions

**Fix:** Check database:
```sql
SELECT u.username, r.name as role, COUNT(rp.permission_id) as permission_count
FROM nira_users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN role_permissions rp ON r.id = rp.role_id
WHERE u.id = ?;
```

### "Access Denied" on all routes

- User role doesn't have required permissions
- Permission code mismatch between frontend and backend

**Fix:** Verify permissions in database match what's expected.

### CORS errors

- Backend CORS headers not set correctly
- Frontend URL doesn't match CORS allowed origin

**Fix:** Update CORS headers in backend files to match your frontend URL.

## Next Steps

1. **Protect existing endpoints:**
   - Add `requirePermission()` to your API endpoints
   - See `backend/api/citizens/create.php.example` for pattern

2. **Add more permissions:**
   - Add to database
   - Map to roles
   - Map to menus (if needed)

3. **Customize menus:**
   - Update menu data in database
   - Add more icons to `Sidebar.jsx` iconMap

4. **Add more roles:**
   - Insert into `roles` table
   - Map permissions via `role_permissions` table

