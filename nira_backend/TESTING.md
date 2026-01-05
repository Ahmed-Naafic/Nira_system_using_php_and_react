# NIRA System - API Testing Guide

## Quick Test Examples

### 1. Test Login

```bash
curl -X POST http://localhost/nira_system/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"officer1","password":"admin123"}'
```

Expected response:
```json
{
  "success": true,
  "message": "Login successful"
}
```

Note: A secure HTTP-only session cookie is automatically set. Your client must support cookies.

---

### 2. Test Create Citizen

First login, then use the session cookie:

```bash
# Login and save cookies
curl -X POST http://localhost/nira_system/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"officer1","password":"admin123"}' \
  -c cookies.txt

# Use saved cookies for authenticated request
curl -X POST http://localhost/nira_system/api/citizens/create.php \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "firstName": "Ahmed",
    "middleName": "Hassan",
    "lastName": "Mohamed",
    "gender": "MALE",
    "dateOfBirth": "1990-05-15",
    "placeOfBirth": "Mogadishu",
    "nationality": "Somali"
  }'
```

---

### 3. Test Logout

```bash
curl -X POST http://localhost/nira_system/api/auth/logout.php \
  -b cookies.txt
```

---

### 4. Test Get Citizen (Read-only)

```bash
curl -X GET "http://localhost/nira_system/api/citizens/get.php?nationalId=1234567890"
```

---

### 5. Test Update Status (Admin Only)

First login as admin, then use the session cookie:

```bash
# Login as admin and save cookies
curl -X POST http://localhost/nira_system/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}' \
  -c admin_cookies.txt

# Use saved cookies for authenticated request
curl -X POST http://localhost/nira_system/api/citizens/update_status.php \
  -H "Content-Type: application/json" \
  -b admin_cookies.txt \
  -d '{
    "nationalId": "1234567890",
    "status": "DECEASED"
  }'
```

---

## Using Postman

1. **Login Endpoint**
   - Method: POST
   - URL: `http://localhost/nira_system/api/auth/login.php`
   - Headers: `Content-Type: application/json`
   - Body (raw JSON):
     ```json
     {
       "username": "officer1",
       "password": "admin123"
     }
     ```
   - Note: Postman will automatically handle session cookies. Ensure "Cookies" is enabled in settings.

2. **Create Citizen**
   - Method: POST
   - URL: `http://localhost/nira_system/api/citizens/create.php`
   - Headers:
     - `Content-Type: application/json`
   - Cookies: Automatically sent by Postman after login
   - Body (raw JSON):
     ```json
     {
       "firstName": "Fatima",
       "middleName": "Ali",
       "lastName": "Hassan",
       "gender": "FEMALE",
       "dateOfBirth": "1985-03-20",
       "placeOfBirth": "Hargeisa",
       "nationality": "Somali"
     }
     ```

3. **Get Citizen**
   - Method: GET
   - URL: `http://localhost/nira_system/api/citizens/get.php?nationalId=<national_id>`
   - No authentication required

4. **Update Status**
   - Method: POST
   - URL: `http://localhost/nira_system/api/citizens/update_status.php`
   - Headers:
     - `Content-Type: application/json`
   - Cookies: Automatically sent by Postman after login as admin
   - Body (raw JSON):
     ```json
     {
       "nationalId": "<national_id>",
       "status": "DECEASED"
     }
     ```

---

## Testing Workflow

1. Run database schema: `mysql -u root -p < database/schema.sql`
2. Run setup script: `php database/setup.php`
3. Test login endpoint (session cookie will be set automatically)
4. Use session cookie to create a citizen
5. Get the national ID from response
6. Test get citizen endpoint with the national ID (no auth needed)
7. Login as admin and test status update

---

## Error Testing

### Invalid Login
```json
{
  "username": "wrong",
  "password": "wrong"
}
```
Expected: 401 Unauthorized

### Missing Fields
```json
{
  "firstName": "Test"
}
```
Expected: 400 Bad Request with field requirements

### No Session Cookie
If you try to access protected endpoints without logging in first:
Expected: 401 Unauthorized with message "Authentication required. Please login."

### Wrong Role (Officer trying to update status)
Expected: 403 Forbidden

---

## Notes

- All endpoints return JSON
- Success responses have `success: true`
- Error responses have `success: false` with `message`
- Sessions use HTTP-only cookies (secure, not accessible via JavaScript)
- Sessions persist until logout or browser closes
- National IDs are 10 digits and auto-generated

