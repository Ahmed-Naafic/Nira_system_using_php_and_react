# NIRA System - National Identity Registration Authority

## Project Description

The NIRA System is a comprehensive web-based application for managing national identity registration and citizen records. The system provides a complete solution for registration authorities to manage citizen data, user accounts, and system operations with role-based access control.

The system consists of two main components:
- **Backend API** (PHP/MySQL): RESTful API server providing authentication, citizen management, user management, and reporting capabilities
- **Frontend Application** (React): Modern web interface for officers and administrators to interact with the system

### Key Features

- **User Authentication & Authorization**
  - Session-based authentication with automatic session expiration (5 minutes of inactivity)
  - Role-Based Access Control (RBAC) with three roles: Admin, Officer, Viewer
  - Secure password hashing using bcrypt
  - User profile management with phone numbers and profile pictures

- **Citizen Management**
  - Register new citizens with automatic National ID generation
  - Update citizen information (with image and document uploads)
  - Search and view citizen records
  - Soft delete with trash/restore functionality
  - Date of birth validation (100 years ago to today)
  - Image and document upload support

- **User Management**
  - Create, update, and manage system users
  - Role assignment and status management
  - Password reset functionality
  - Soft delete with trash/restore functionality
  - Profile picture and phone number management

- **Dashboard & Reporting**
  - Real-time statistics and analytics
  - Citizen registration reports
  - User activity reports
  - System summary reports

- **System Features**
  - System notices management
  - Activity logging and audit trail
  - File upload and management (images, documents, profiles)
  - Session management with expiration handling
  - CORS support for cross-origin requests

---

## Group Information

**Project Name:** NIRA System - National Identity Registration Authority

**Group Members:**
- Ahmednor Mahad Ahmed [C1220696]
- Mahmood Ahmed Hassan [C1221290]
- Mariama Farah Mohamuud [C1221346]
- Salma Mohamed Husein [C1221325]

**Course:** [Web Application Dev- PHP & MySQL]
**Institution:** [Jamhuuriya University]
**Academic Year:** [2025-2026/Semester 7]
**Submission Date:** [13/01/2026]

---

## Technology Stack

### Backend
- **Language:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Server:** Apache/Nginx with PHP
- **Authentication:** PHP Sessions with HTTP-only cookies
- **File Handling:** PHP file uploads

### Frontend
- **Framework:** React 19
- **Build Tool:** Vite
- **Routing:** React Router
- **HTTP Client:** Axios
- **Styling:** Tailwind CSS
- **State Management:** React Context API

---

## Project Structure

### Backend Structure (`nira_backend/`)

```
nira_backend/
├── api/                           # API Endpoints
│   ├── activities/                # Activity logging endpoints
│   │   └── recent.php
│   ├── auth/                      # Authentication endpoints
│   │   ├── check.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── me.php
│   ├── citizens/                  # Citizen management endpoints
│   │   ├── create.php
│   │   ├── delete.php
│   │   ├── get.php
│   │   ├── list.php
│   │   ├── search.php
│   │   ├── update.php
│   │   ├── update_status.php
│   │   └── trash/                 # Trash management
│   │       ├── delete_permanent.php
│   │       ├── list.php
│   │       └── restore.php
│   ├── dashboard/                 # Dashboard endpoints
│   │   └── stats.php
│   ├── files/                     # File serving endpoints
│   │   └── get.php
│   ├── notices/                   # System notices endpoints
│   │   ├── create.php
│   │   ├── delete.php
│   │   └── list.php
│   ├── reports/                   # Reporting endpoints
│   │   ├── citizens.php
│   │   ├── registrations.php
│   │   ├── summary.php
│   │   └── users.php
│   └── users/                     # User management endpoints
│       ├── create.php
│       ├── delete.php
│       ├── get.php
│       ├── list.php
│       ├── reset-password.php
│       ├── status.php
│       ├── update.php
│       └── trash/                 # Trash management
│           ├── delete_permanent.php
│           ├── list.php
│           └── restore.php
├── config/                        # Configuration files
│   ├── bootstrap.php              # Session and CORS configuration
│   └── database.php               # Database connection configuration
├── database/                      # Database files
│   ├── schema.sql                 # Main database schema
│   ├── setup.php                  # Database setup script
│   ├── add_citizen_files.sql      # Migration: Add file upload fields
│   ├── add_deleted_at_to_citizens.sql
│   ├── add_deleted_at_to_users.sql
│   ├── add_notices_activities_permissions.sql
│   ├── add_update_citizen_to_officer.sql
│   ├── add_user_phone_profile.sql # Migration: Add user profile fields
│   ├── add_view_reports_to_officer.sql
│   ├── create_system_activities_table.sql
│   ├── create_system_notices_activities_tables.sql
│   ├── create_system_notices_table.sql
│   └── remove_view_reports_from_viewer.sql
├── middleware/                    # Middleware classes
│   ├── auth.php                   # Authentication middleware
│   └── permission.php             # Permission/RBAC middleware
├── services/                      # Business logic services
│   ├── activity.service.php       # Activity logging service
│   ├── citizen.service.php        # Citizen management service
│   ├── dashboard.service.php      # Dashboard statistics service
│   ├── notice.service.php         # System notices service
│   ├── rbac.service.php           # Role-Based Access Control service
│   ├── report.service.php         # Reporting service
│   └── user.service.php           # User management service
├── utils/                         # Utility classes
│   ├── file_upload.php            # File upload handling
│   ├── national_id_generator.php  # National ID generation
│   └── session_config.php         # Session configuration (legacy)
├── uploads/                       # Uploaded files directory
│   ├── documents/                 # Citizen documents
│   ├── images/                    # Citizen images
│   └── profiles/                  # User profile pictures
├── index.php                      # API entry point
├── README.md                      # Backend documentation
├── API_ENDPOINTS.md               # Complete API documentation
├── TESTING.md                     # Testing documentation
└── UPLOAD_SETUP.md                # File upload setup guide
```

### Frontend Structure (`nira_frontend/client/`)

```
nira_frontend/
└── client/
    ├── src/
    │   ├── api/                   # API configuration
    │   │   └── api.js             # Axios instance with credentials
    │   ├── auth/                  # Authentication components
    │   │   ├── Login.jsx
    │   │   └── ProtectedRoute.jsx
    │   ├── citizens/              # Citizen management components
    │   │   ├── CitizenCreate.jsx
    │   │   ├── CitizenDetails.jsx
    │   │   ├── CitizenEdit.jsx
    │   │   └── CitizenList.jsx
    │   ├── components/            # Reusable components
    │   │   ├── Sidebar.jsx
    │   │   └── SessionExpiredModal.jsx
    │   ├── context/               # React Context providers
    │   │   └── AuthContext.jsx    # Authentication context
    │   ├── layout/                # Layout components
    │   │   └── DashboardLayout.jsx
    │   ├── pages/                 # Page components
    │   │   ├── Dashboard.jsx
    │   │   └── LoginPage.jsx
    │   ├── services/              # API service functions
    │   │   ├── citizenService.js
    │   │   └── userService.js
    │   ├── users/                 # User management components
    │   │   ├── UserCreate.jsx
    │   │   ├── UserEdit.jsx
    │   │   ├── Users.jsx
    │   │   └── ResetPasswordModal.jsx
    │   ├── App.jsx                # Main application component
    │   └── main.jsx               # Entry point
    ├── public/                    # Static assets
    ├── package.json               # Dependencies
    ├── vite.config.js             # Vite configuration
    └── README.md                  # Frontend documentation
```

---

## Database Files

All database files are located in `nira_backend/database/`:

### Main Schema
- **`schema.sql`** - Main database schema with tables: `nira_users`, `citizens`, `roles`, `permissions`, `role_permissions`, `menus`, `role_menus`, `status_change_log`

### Migration Files
- **`setup.php`** - Database setup script (creates default users)
- **`add_citizen_files.sql`** - Adds `image_path` and `document_path` columns to citizens table
- **`add_deleted_at_to_citizens.sql`** - Adds soft delete support to citizens table
- **`add_deleted_at_to_users.sql`** - Adds soft delete support to users table
- **`add_user_phone_profile.sql`** - Adds `phone_number` and `profile_picture_path` columns to users table
- **`add_notices_activities_permissions.sql`** - Adds system notices and activities permissions
- **`add_update_citizen_to_officer.sql`** - Adds UPDATE_CITIZEN permission to Officer role
- **`add_view_reports_to_officer.sql`** - Adds VIEW_REPORTS permission to Officer role
- **`create_system_activities_table.sql`** - Creates system_activities table
- **`create_system_notices_activities_tables.sql`** - Creates notices and activities tables
- **`create_system_notices_table.sql`** - Creates system_notices table
- **`remove_view_reports_from_viewer.sql`** - Removes VIEW_REPORTS permission from Viewer role

---

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- Node.js 16+ and npm (for frontend)
- Composer (optional, for PHP dependencies)

### Backend Setup

1. **Database Configuration**
   ```bash
   # Create database and tables
   mysql -u root -p < nira_backend/database/schema.sql
   
   # Run setup script to create default users
   php nira_backend/database/setup.php
   ```

2. **Configure Database Connection**
   Edit `nira_backend/config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'nira_system';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

3. **Configure Web Server**
   - Point web server document root to project directory
   - Ensure mod_rewrite and mod_headers are enabled (Apache)
   - Set proper file permissions for `uploads/` directory

4. **Session Configuration**
   - Session timeout is set to 300 seconds (5 minutes) in `config/bootstrap.php`
   - Session cookie path is automatically detected based on installation path

### Frontend Setup

1. **Install Dependencies**
   ```bash
   cd nira_frontend/client
   npm install
   ```

2. **Configure API URL**
   Create `.env` file in `nira_frontend/client/`:
   ```env
   VITE_API_BASE_URL=http://localhost:8000/niraSystem/nira_backend
   ```

3. **Start Development Server**
   ```bash
   npm run dev
   ```

4. **Build for Production**
   ```bash
   npm run build
   ```

---

## Default Credentials

**Admin User:**
- Username: `admin`
- Password: `admin123`
- Role: ADMIN

**Officer User:**
- Username: `officer1`
- Password: `admin123`
- Role: OFFICER

**⚠️ IMPORTANT:** Change these default passwords in production!

---

## Key Features Implementation

### Authentication & Sessions
- Session-based authentication with PHP sessions
- Automatic session expiration after 5 minutes of inactivity
- Session expiration modal in frontend
- Secure HTTP-only cookies with SameSite protection
- Remember Me functionality (30-day cookie lifetime)

### Role-Based Access Control (RBAC)
- Three roles: Admin, Officer, Viewer
- Granular permissions system
- Dynamic menu generation based on role
- Permission-based route protection

### File Upload System
- Image uploads for citizens (JPG, PNG, GIF, WEBP, max 5MB)
- Document uploads for citizens (PDF, DOC, DOCX, JPG, PNG, max 10MB)
- Profile picture uploads for users (JPG, PNG, GIF, WEBP, max 5MB)
- Secure file serving with authentication
- Automatic file cleanup on updates

### Data Validation
- Date of birth validation (100 years ago to today)
- Phone number validation (8-15 digits with optional country code)
- File type and size validation
- Input sanitization and SQL injection prevention

### Soft Delete System
- Soft delete for citizens and users
- Trash management with restore functionality
- Permanent delete option
- Audit trail maintenance

---

## Security Features

- **Password Security:** bcrypt hashing with PHP `password_hash()`
- **SQL Injection Prevention:** Prepared statements for all database queries
- **XSS Protection:** Input sanitization and output escaping
- **CSRF Protection:** SameSite cookies and session validation
- **Session Security:** HTTP-only cookies, secure session handling
- **File Upload Security:** File type validation, size limits, secure storage
- **Authentication:** Session-based with automatic expiration
- **Authorization:** Role-Based Access Control (RBAC)

---

## API Documentation

Complete API documentation is available in `nira_backend/API_ENDPOINTS.md`.

The system provides 28+ RESTful API endpoints for:
- Authentication (login, logout, session check, user info)
- Citizen management (CRUD operations, search, trash)
- User management (CRUD operations, password reset, trash)
- Dashboard statistics
- Reporting (citizens, registrations, users, summary)
- File serving
- System notices
- Activity logging

---

## Testing

Testing documentation is available in `nira_backend/TESTING.md`.

The system includes:
- Manual testing procedures
- API endpoint testing
- Authentication flow testing
- File upload testing
- Session expiration testing

---

## Project Files Summary

### PHP Files (Backend)
- **API Endpoints:** 28+ PHP files in `api/` directory
- **Middleware:** 2 PHP files in `middleware/` directory
- **Services:** 7 PHP service classes in `services/` directory
- **Utils:** 3 utility classes in `utils/` directory
- **Config:** 2 configuration files in `config/` directory

### Database Files
- **Main Schema:** 1 SQL file (`schema.sql`)
- **Migrations:** 11 SQL migration files
- **Setup Script:** 1 PHP file (`setup.php`)

### Frontend Files (React)
- **Components:** 20+ React components (.jsx files)
- **Services:** API service modules (.js files)
- **Context:** Authentication context provider
- **Configuration:** Vite config, package.json, etc.

### Documentation Files
- **README.md** (this file) - Project overview and setup
- **API_ENDPOINTS.md** - Complete API documentation
- **TESTING.md** - Testing procedures
- **UPLOAD_SETUP.md** - File upload setup guide
- **SESSION_EXPIRATION_FLOW.md** - Session expiration documentation

---

## License

This project is developed for educational/academic purposes.

---

## Contact & Support

For questions or issues regarding this project, please contact:
- **Email:** [Your Email]
- **Student ID:** [Student ID]
- **Course:** [Course Name]

---

## Acknowledgments

- PHP Development Team
- React Development Team
- MySQL/MariaDB Development Team
- Tailwind CSS Team
- All open-source contributors whose libraries and tools made this project possible

---

**Note:** This is a comprehensive system for managing national identity registration. All features are implemented and tested. For detailed API documentation, see `nira_backend/API_ENDPOINTS.md`. For setup instructions, see the Installation & Setup section above.
