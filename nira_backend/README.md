# NIRA System - National Identity Registration Authority

Backend API system for citizen identity management. This system serves as the authoritative identity backend for CRVS (Civil Registration and Vital Statistics) systems.

## Technology Stack

- **PHP 7.4+**
- **MySQL 5.7+**
- **PHP Sessions** with HTTP-only cookies for authentication

## Project Structure

```
nira_system/
├── api/
│   ├── auth/
│   │   └── login.php              # Officer login endpoint
│   ├── citizens/
│   │   ├── create.php             # Register citizen (OFFICER/ADMIN)
│   │   ├── get.php                # Get citizen by National ID (read-only)
│   │   └── update_status.php      # Update citizen status (ADMIN only)
├── config/
│   └── database.php               # Database configuration
├── middleware/
│   └── auth.php                   # Session-based authentication middleware
├── utils/
│   ├── session_config.php         # Secure session configuration
│   └── national_id_generator.php  # Unique National ID generator
├── database/
│   └── schema.sql                 # Database schema
└── index.php                      # API entry point
```

## Installation

1. **Database Setup**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   
   Then run the setup script to initialize default users with proper password hashes:
   ```bash
   php database/setup.php
   ```

2. **Database Configuration**
   Edit `config/database.php` with your MySQL credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'nira_system';
   private $username = 'root';
   private $password = '';
   ```

3. **Web Server Configuration**
   - Ensure PHP 7.4+ is installed
   - Point web server document root to project directory
   - Enable mod_rewrite for clean URLs (optional)
   - Ensure Apache mod_rewrite and mod_headers are enabled

## Default Credentials

The schema includes default users:
- **Admin**: username: `admin`, password: `admin123`
- **Officer**: username: `officer1`, password: `admin123`

**IMPORTANT**: Change these passwords in production!

## API Endpoints

### 1. Login

**POST** `/api/auth/login.php`

### 2. Logout

**POST** `/api/auth/logout.php`

### 3. Register Citizen
**POST** `/api/auth/login.php`

Request:
```json
{
  "username": "officer1",
  "password": "admin123"
}
```

Response:
```json
{
  "success": true,
  "message": "Login successful"
}
```

Note: A secure HTTP-only session cookie is automatically set. Include this cookie in subsequent requests.

---

### 2. Register Citizen
**POST** `/api/citizens/create.php`

**Authentication**: Session cookie (OFFICER or ADMIN)

The session cookie from login must be included automatically by the browser/client.

Request:
```json
{
  "firstName": "Ahmed",
  "middleName": "Hassan",
  "lastName": "Mohamed",
  "gender": "MALE",
  "dateOfBirth": "1990-05-15",
  "placeOfBirth": "Mogadishu",
  "nationality": "Somali"
}
```

Response:
```json
{
  "success": true,
  "message": "Citizen registered successfully",
  "data": {
    "nationalId": "1234567890",
    "firstName": "Ahmed",
    "middleName": "Hassan",
    "lastName": "Mohamed",
    "gender": "MALE",
    "dateOfBirth": "1990-05-15",
    "placeOfBirth": "Mogadishu",
    "nationality": "Somali",
    "status": "ACTIVE"
  }
}
```

---

### 4. Get Citizen by National ID
**GET** `/api/citizens/get.php?nationalId=1234567890`

**Authentication**: None required (read-only for CRVS integration)

Response:
```json
{
  "success": true,
  "data": {
    "nationalId": "1234567890",
    "fullName": "Ahmed Hassan Mohamed",
    "gender": "MALE",
    "dateOfBirth": "1990-05-15",
    "nationality": "Somali",
    "status": "ACTIVE"
  }
}
```

---

### 5. Update Citizen Status
**POST** `/api/citizens/update_status.php`

**Authentication**: Session cookie (ADMIN only)

The session cookie from login must be included automatically by the browser/client.

Request:
```json
{
  "nationalId": "1234567890",
  "status": "DECEASED"
}
```

Response:
```json
{
  "success": true,
  "message": "Citizen status updated successfully",
  "data": {
    "nationalId": "1234567890",
    "oldStatus": "ACTIVE",
    "newStatus": "DECEASED"
  }
}
```

## Security Features

- **Session-Based Authentication**: All write operations require valid PHP sessions with HTTP-only cookies
- **Session Security**: HTTP-only cookies prevent XSS attacks, SameSite=Strict prevents CSRF
- **Role-Based Access Control**: 
  - OFFICER: Can create citizens
  - ADMIN: Can create citizens and update status
- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Protection**: All queries use prepared statements
- **Status Change Logging**: All status changes are audited

## National ID Generation

- **Format**: 10-digit numeric string
- **Uniqueness**: Guaranteed by database constraint and generation logic
- **Generation**: Server-side only, never exposed to clients

## CRVS Integration

The `/api/citizens/get.php` endpoint is designed for CRVS integration:
- No authentication required (or configurable)
- Returns standardized citizen data format
- Read-only access

## Database Schema

### citizens
- `id` (INT, PK)
- `national_id` (VARCHAR, UNIQUE)
- `first_name`, `middle_name`, `last_name`
- `gender` (ENUM: MALE/FEMALE)
- `date_of_birth` (DATE)
- `place_of_birth` (VARCHAR)
- `nationality` (VARCHAR, default: Somali)
- `status` (ENUM: ACTIVE/DECEASED)
- `created_at`, `updated_at` (TIMESTAMP)

### nira_users
- `id` (INT, PK)
- `username` (VARCHAR, UNIQUE)
- `password` (VARCHAR, hashed)
- `role` (ENUM: ADMIN/OFFICER)
- `created_at` (TIMESTAMP)

### status_change_log
- Audit trail for citizen status changes

## Production Checklist

- [ ] Enable `secure` flag in `utils/session_config.php` for HTTPS
- [ ] Update database credentials in `config/database.php`
- [ ] Change default admin/officer passwords
- [ ] Configure HTTPS
- [ ] Set up proper error logging
- [ ] Implement rate limiting
- [ ] Add CORS headers if needed for frontend
- [ ] Set up database backups

## License

This is a production system for National Identity Registration Authority.

