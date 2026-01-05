# Troubleshooting: Citizen Search Not Working

## Problem
After creating a citizen, searching by National ID returns "Citizen not found".

## Debugging Steps

### 1. Check Browser Console

Open DevTools → Console and look for:
- "Searching for National ID: [ID]"
- "Search response: [response data]"
- "Error response: [error details]"

This will show:
- What National ID is being sent
- What the backend is returning
- Any errors

### 2. Check Network Tab

1. Open DevTools → Network tab
2. Search for a citizen
3. Find the request to `/api/citizens/get.php` or `/api/citizens/search.php`
4. Check:
   - Request URL (verify parameter name)
   - Request parameters
   - Response status code
   - Response body

### 3. Verify Backend Endpoint

The frontend expects:

**For direct lookup:**
```
GET /api/citizens/get.php?nationalId=1234567890
```

**For search:**
```
GET /api/citizens/search.php?q=1234567890
```

### 4. Check Backend Response Format

**Expected response from get.php:**
```json
{
  "success": true,
  "citizen": {
    "nationalId": "1234567890",
    "firstName": "John",
    "lastName": "Doe",
    ...
  }
}
```

**Expected response from search.php:**
```json
{
  "success": true,
  "citizens": [
    {
      "nationalId": "1234567890",
      "firstName": "John",
      "lastName": "Doe",
      ...
    }
  ]
}
```

### 5. Common Issues

#### Issue 1: Parameter Name Mismatch

**Problem:** Backend expects `national_id` but frontend sends `nationalId`

**Fix:** Update backend to accept both:
```php
$nationalId = $_GET['nationalId'] ?? $_GET['national_id'] ?? '';
```

#### Issue 2: Field Name Mismatch

**Problem:** Database uses `national_id` but response uses `nationalId`

**Fix:** Use aliases in SQL:
```php
SELECT national_id as nationalId, ...
```

#### Issue 3: National ID Not Saved

**Problem:** Citizen created but National ID not saved to database

**Fix:** Check create.php - ensure it saves the generated ID

#### Issue 4: Whitespace Issues

**Problem:** National ID has leading/trailing spaces

**Fix:** Frontend now trims, but verify backend also trims:
```php
$nationalId = trim($_GET['nationalId']);
```

### 6. Test Direct API Call

Test the endpoint directly in browser (while logged in):
```
http://localhost:8000/nira_system/api/citizens/get.php?nationalId=YOUR_NATIONAL_ID
```

Should return JSON with citizen data.

### 7. Verify Database

Check if citizen was actually saved:
```sql
SELECT * FROM citizens WHERE national_id = 'YOUR_NATIONAL_ID';
```

## Quick Fixes

### If get.php doesn't exist:

Create `backend/api/citizens/get.php`:
```php
<?php
session_start();
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getDBConnection();

$nationalId = trim($_GET['nationalId'] ?? $_GET['national_id'] ?? '');

if (empty($nationalId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'National ID required']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        national_id as nationalId,
        first_name as firstName,
        middle_name as middleName,
        last_name as lastName,
        gender,
        date_of_birth as dateOfBirth,
        place_of_birth as placeOfBirth,
        nationality,
        status
    FROM citizens
    WHERE national_id = ?
");

$stmt->execute([$nationalId]);
$citizen = $stmt->fetch(PDO::FETCH_ASSOC);

if ($citizen) {
    echo json_encode([
        'success' => true,
        'citizen' => $citizen
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Citizen not found'
    ]);
}
```

### If search.php doesn't exist:

See `backend/api/citizens/search.php.example` for reference.

## Still Not Working?

1. Check console logs for exact error messages
2. Verify the National ID format matches what was created
3. Ensure backend endpoints exist and are accessible
4. Check CORS configuration
5. Verify session is active (check cookies)

