# NIRA Frontend Setup - PHP Session Authentication

## Quick Fix Checklist

### ✅ Step 1: Create .env File

Create a file named `.env` in the `client` directory with this exact content:

```env
VITE_API_BASE_URL=http://localhost:8000/nira_system
```

**Important:** 
- File must be named `.env` (not `.env.local` or `.env.development`)
- Must be in the `client` directory (same level as `package.json`)
- No spaces around the `=` sign
- Restart dev server after creating/editing `.env`

### ✅ Step 2: Verify Axios Configuration

The axios instance in `src/api/api.js` is correctly configured:
- ✅ `withCredentials: true` - Sends cookies with requests
- ✅ `baseURL` uses `VITE_API_BASE_URL` from .env
- ✅ No Authorization headers
- ✅ No token storage

### ✅ Step 3: Test Login

1. Start dev server: `npm run dev`
2. Navigate to login page
3. Enter credentials
4. Check browser DevTools → Network tab:
   - Login request should include `Cookie` header
   - Response should set `Set-Cookie` header
   - Subsequent requests should include the session cookie

### ✅ Step 4: Verify Session Persistence

1. After successful login, refresh the page
2. You should remain logged in (cookie persists)
3. Check Application → Cookies in DevTools
4. Should see PHP session cookie (usually named `PHPSESSID`)

## Troubleshooting

### Login Fails

**Check:**
1. `.env` file exists and has correct URL
2. Backend is running on `http://localhost:8000`
3. CORS is configured on backend to accept credentials
4. Backend sets cookies with proper domain/path

**Backend CORS Requirements:**
```php
header('Access-Control-Allow-Origin: http://localhost:5173'); // Your Vite dev server URL
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

### Session Not Persisting

**Check:**
1. Cookies are being set (DevTools → Application → Cookies)
2. Cookie domain matches (should be `localhost` or your domain)
3. Cookie path is correct (usually `/`)
4. `HttpOnly` flag is set (security best practice)
5. No browser extensions blocking cookies

### 401 Errors on Protected Routes

**Check:**
1. Cookie is being sent with requests (Network tab → Request Headers)
2. Backend session is still valid
3. Backend checks session correctly
4. No CORS issues preventing cookie transmission

## API Endpoints Expected

- `POST /api/auth/login.php` - Login (sets session cookie)
- `POST /api/auth/logout.php` - Logout (destroys session)
- `GET /api/auth/check.php` - Optional: Check if session is valid
- `POST /api/citizens/create.php` - Create citizen (requires auth)
- `GET /api/citizens/get.php?nationalId=XXX` - Get citizen (requires auth)

## Response Format Expected

**Login Success:**
```json
{
  "success": true,
  "message": "Login successful"
}
```

**Login Failure:**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

Or:
```json
{
  "status": "error",
  "error": "Invalid credentials"
}
```

The frontend handles both formats.

