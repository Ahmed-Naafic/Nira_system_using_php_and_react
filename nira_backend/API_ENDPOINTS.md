# NIRA System - API Endpoints Documentation

Complete list of all available API endpoints in the NIRA backend system.

Base URL: `http://localhost:8000/niraSystem/nira_backend/api/`

---

## Authentication Endpoints

### POST `/api/auth/login.php`
**Description:** User login (creates session)  
**Method:** POST  
**Auth Required:** No  
**Request Body:**
```json
{
  "username": "string",
  "password": "string",
  "rememberMe": boolean (optional)
}
```
**Response:**
```json
{
  "success": true,
  "message": "Login successful"
}
```

### POST `/api/auth/logout.php`
**Description:** User logout (destroys session)  
**Method:** POST  
**Auth Required:** Yes  
**Response:**
```json
{
  "success": true
}
```

### GET `/api/auth/check.php`
**Description:** Check if user is authenticated  
**Method:** GET  
**Auth Required:** Yes  
**Response:**
```json
{
  "success": true,
  "authenticated": true,
  "user": {
    "id": 1,
    "username": "string",
    "role": "string"
  }
}
```

### GET `/api/auth/me.php`
**Description:** Get current user information, permissions, and menus  
**Method:** GET  
**Auth Required:** Yes  
**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "username": "string",
    "phoneNumber": "string (optional)",
    "profilePictureUrl": "string (optional)",
    "role": {
      "id": 1,
      "name": "string",
      "description": "string"
    },
    "status": "ACTIVE"
  },
  "permissions": ["string"],
  "menus": [{}]
}
```

---

## Citizen Endpoints

### POST `/api/citizens/create.php`
**Description:** Register a new citizen  
**Method:** POST  
**Auth Required:** Yes (CREATE_CITIZEN permission)  
**Content-Type:** `application/json` or `multipart/form-data`  
**Request Body (JSON):**
```json
{
  "firstName": "string",
  "middleName": "string (optional)",
  "lastName": "string",
  "gender": "Male" | "Female",
  "dateOfBirth": "YYYY-MM-DD",
  "placeOfBirth": "string",
  "nationality": "string (optional, default: Somali)"
}
```
**Request Body (FormData - with file uploads):**
- All above fields as form fields
- `image`: File (optional, max 5MB, formats: JPG, PNG, GIF, WEBP)
- `document`: File (optional, max 10MB, formats: PDF, DOC, DOCX, JPG, PNG)
**Response:**
```json
{
  "success": true,
  "message": "Citizen registered successfully",
  "data": {
    "nationalId": "string",
    "firstName": "string",
    "imageUrl": "string (optional)",
    "documentUrl": "string (optional)",
    ...
  }
}
```

### GET `/api/citizens/get.php?nationalId=XXXXXXXXXX`
**Description:** Get citizen by National ID  
**Method:** GET  
**Auth Required:** Yes (VIEW_CITIZEN permission)  
**Query Parameters:**
- `nationalId` (required): National ID of the citizen
**Response:**
```json
{
  "success": true,
  "data": {
    "nationalId": "string",
    "firstName": "string",
    "imageUrl": "string (optional)",
    "documentUrl": "string (optional)",
    ...
  }
}
```

### POST `/api/citizens/update.php`
**Description:** Update citizen information  
**Method:** POST  
**Auth Required:** Yes (UPDATE_CITIZEN permission)  
**Content-Type:** `application/json` or `multipart/form-data`  
**Request Body (JSON):**
```json
{
  "nationalId": "string",
  "firstName": "string (optional)",
  "middleName": "string (optional)",
  "lastName": "string (optional)",
  "gender": "Male" | "Female" (optional),
  "dateOfBirth": "YYYY-MM-DD" (optional),
  "placeOfBirth": "string (optional)",
  "nationality": "string (optional)",
  "status": "ACTIVE" | "DECEASED" (optional)
}
```
**Request Body (FormData - with file uploads):**
- All above fields as form fields
- `image`: File (optional, replaces existing image)
- `document`: File (optional, replaces existing document)
**Response:**
```json
{
  "success": true,
  "citizen": {}
}
```

### POST `/api/citizens/delete.php`
**Description:** Soft delete citizen (move to trash)  
**Method:** POST  
**Auth Required:** Yes (DELETE_CITIZEN permission)  
**Request Body:**
```json
{
  "nationalId": "string"
}
```

### GET `/api/citizens/list.php?limit=50&offset=0`
**Description:** List all citizens with pagination  
**Method:** GET  
**Auth Required:** Yes (VIEW_CITIZEN permission)  
**Query Parameters:**
- `limit` (optional): Number of results (default: 50, max: 100)
- `offset` (optional): Offset for pagination (default: 0)

### GET `/api/citizens/search.php?query=SEARCH_TERM&limit=50&offset=0`
**Description:** Search citizens by name or National ID  
**Method:** GET  
**Auth Required:** Yes (VIEW_CITIZEN permission)  
**Query Parameters:**
- `query` (required): Search term
- `limit` (optional): Number of results
- `offset` (optional): Offset for pagination

### POST `/api/citizens/update_status.php`
**Description:** Update citizen status (ADMIN only)  
**Method:** POST  
**Auth Required:** Yes (ADMIN role)  
**Request Body:**
```json
{
  "nationalId": "string",
  "status": "ACTIVE" | "DECEASED"
}
```

### Citizen Trash Endpoints

#### GET `/api/citizens/trash/list.php?limit=50&offset=0`
**Description:** List deleted citizens (trash)  
**Method:** GET  
**Auth Required:** Yes (VIEW_CITIZEN permission)

#### POST `/api/citizens/trash/restore.php`
**Description:** Restore citizen from trash  
**Method:** POST  
**Auth Required:** Yes (DELETE_CITIZEN permission)  
**Request Body:**
```json
{
  "nationalId": "string"
}
```

#### POST `/api/citizens/trash/delete_permanent.php`
**Description:** Permanently delete citizen from trash  
**Method:** POST  
**Auth Required:** Yes (DELETE_CITIZEN permission)  
**Request Body:**
```json
{
  "nationalId": "string"
}
```

---

## User Management Endpoints

### POST `/api/users/create.php`
**Description:** Create a new user  
**Method:** POST  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Content-Type:** `application/json` or `multipart/form-data`  
**Request Body (JSON):**
```json
{
  "username": "string",
  "password": "string",
  "role_id": number,
  "phoneNumber": "string (optional)",
  "status": "ACTIVE" | "DISABLED" (optional, default: ACTIVE)
}
```
**Request Body (FormData - with profile picture):**
- All above fields as form fields
- `profilePicture`: File (optional, max 5MB, formats: JPG, PNG, GIF, WEBP)
**Response:**
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "id": 1,
    "username": "string",
    "phoneNumber": "string (optional)",
    "profilePictureUrl": "string (optional)",
    "role": {},
    "status": "ACTIVE"
  }
}
```

### GET `/api/users/list.php`
**Description:** List all users  
**Method:** GET  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "string",
      "phoneNumber": "string (optional)",
      "profilePictureUrl": "string (optional)",
      "role": {},
      "status": "ACTIVE"
    }
  ]
}
```

### GET `/api/users/get.php?id=1`
**Description:** Get user by ID  
**Method:** GET  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Query Parameters:**
- `id` (required): User ID

### POST `/api/users/update.php`
**Description:** Update user information  
**Method:** POST  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Content-Type:** `application/json` or `multipart/form-data`  
**Request Body (JSON):**
```json
{
  "id": number,
  "username": "string (optional)",
  "role_id": number (optional),
  "phoneNumber": "string (optional)",
  "status": "ACTIVE" | "DISABLED" (optional)
}
```
**Request Body (FormData - with profile picture):**
- All above fields as form fields
- `profilePicture`: File (optional, replaces existing)

### POST `/api/users/delete.php`
**Description:** Soft delete user (move to trash)  
**Method:** POST  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Request Body:**
```json
{
  "userId": number
}
```

### POST `/api/users/status.php`
**Description:** Change user status (ACTIVE/DISABLED)  
**Method:** POST  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Request Body:**
```json
{
  "userId": number,
  "status": "ACTIVE" | "DISABLED"
}
```

### POST `/api/users/reset-password.php`
**Description:** Reset user password  
**Method:** POST  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Request Body:**
```json
{
  "userId": number,
  "newPassword": "string"
}
```

### User Trash Endpoints

#### GET `/api/users/trash/list.php?limit=50&offset=0`
**Description:** List deleted users (trash)  
**Method:** GET  
**Auth Required:** Yes (MANAGE_USERS permission)

#### POST `/api/users/trash/restore.php`
**Description:** Restore user from trash  
**Method:** POST  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Request Body:**
```json
{
  "userId": number
}
```

#### POST `/api/users/trash/delete_permanent.php`
**Description:** Permanently delete user from trash  
**Method:** POST  
**Auth Required:** Yes (MANAGE_USERS permission)  
**Request Body:**
```json
{
  "userId": number
}
```

---

## Dashboard Endpoints

### GET `/api/dashboard/stats.php`
**Description:** Get dashboard statistics  
**Method:** GET  
**Auth Required:** Yes (VIEW_DASHBOARD permission)  
**Response:**
```json
{
  "success": true,
  "stats": {
    "totalCitizens": number,
    "activeCitizens": number,
    "totalUsers": number,
    "activeUsers": number
  }
}
```

---

## File Serving Endpoints

### GET `/api/files/get.php?path=images/filename.jpg`
**Description:** Serve uploaded files (images/documents) securely  
**Method:** GET  
**Auth Required:** Yes  
**Query Parameters:**
- `path` (required): Relative path from uploads directory (e.g., `images/filename.jpg`, `profiles/user123.jpg`)
**Response:** File content with appropriate MIME type

---

## Activities Endpoints

### GET `/api/activities/recent.php?limit=20`
**Description:** Get recent system activities  
**Method:** GET  
**Auth Required:** Yes  
**Query Parameters:**
- `limit` (optional): Number of activities (default: 20)

---

## System Notices Endpoints

### GET `/api/notices/list.php`
**Description:** List all system notices  
**Method:** GET  
**Auth Required:** Yes

### POST `/api/notices/create.php`
**Description:** Create a new system notice  
**Method:** POST  
**Auth Required:** Yes  
**Request Body:**
```json
{
  "title": "string",
  "message": "string",
  "priority": "LOW" | "MEDIUM" | "HIGH" | "URGENT",
  "expiresAt": "YYYY-MM-DD HH:MM:SS (optional)"
}
```

### POST `/api/notices/delete.php`
**Description:** Delete a system notice  
**Method:** POST  
**Auth Required:** Yes  
**Request Body:**
```json
{
  "id": number
}
```

---

## Reports Endpoints

### GET `/api/reports/summary.php`
**Description:** Get summary report  
**Method:** GET  
**Auth Required:** Yes (VIEW_REPORTS permission)

### GET `/api/reports/citizens.php?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD`
**Description:** Get citizen registration report  
**Method:** GET  
**Auth Required:** Yes (VIEW_REPORTS permission)  
**Query Parameters:**
- `startDate` (optional)
- `endDate` (optional)

### GET `/api/reports/registrations.php?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD`
**Description:** Get registration statistics report  
**Method:** GET  
**Auth Required:** Yes (VIEW_REPORTS permission)

### GET `/api/reports/users.php`
**Description:** Get user activity report  
**Method:** GET  
**Auth Required:** Yes (VIEW_REPORTS permission)

---

## Notes

1. **Authentication:** Most endpoints require authentication via PHP session cookies (except `/api/auth/login.php`)
2. **Permissions:** Endpoints require specific permissions based on RBAC system
3. **File Uploads:** Endpoints that accept file uploads support both `application/json` (backward compatible) and `multipart/form-data`
4. **Error Responses:** All endpoints return JSON with `success: false` and `message` on error
5. **CORS:** Configured in `config/bootstrap.php` for React frontend
6. **Base Path:** If your backend is at `/niraSystem/nira_backend`, adjust the base URL accordingly

---

## Common Response Formats

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description"
}
```

### Status Codes
- `200`: Success
- `201`: Created
- `400`: Bad Request (validation error)
- `401`: Unauthorized (not authenticated)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found
- `405`: Method Not Allowed
- `409`: Conflict (duplicate)
- `500`: Internal Server Error
