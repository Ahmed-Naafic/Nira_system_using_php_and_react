# Session Expiration Flow

## What Happens When a Session Expires

### Backend Flow (PHP)

1. **On Every Request** (`config/bootstrap.php`):
   - Checks if user is authenticated (`$_SESSION['user_id']` exists)
   - Compares current time with `$_SESSION['last_activity']`
   - If `(current_time - last_activity) > SESSION_TIMEOUT` (300 seconds = 5 minutes):
     - Destroys the session (`session_unset()`, `session_destroy()`)
     - Starts a new empty session for the response

2. **In Authentication Middleware** (`middleware/auth.php`):
   - Double-checks session expiration (in case bootstrap didn't catch it)
   - If session expired:
     - Returns HTTP 401 status code
     - Returns JSON response:
       ```json
       {
         "success": false,
         "message": "Your session has expired. Please login again.",
         "expired": true
       }
       ```
   - If session is valid:
     - Updates `$_SESSION['last_activity']` to current time
     - Allows request to proceed

3. **Session Timeout Configuration**:
   - Default: 300 seconds (5 minutes)
   - Defined in `config/bootstrap.php`: `define('SESSION_TIMEOUT', 300)`
   - Can be changed for testing (you changed it to 20 seconds in `me.php`)

### Frontend Flow (React)

1. **API Request Interceptor** (`api/api.js`):
   - Intercepts all API responses
   - On HTTP 401 with `expired: true`:
     - Clears `sessionExpiresAt` from localStorage
     - Dispatches `sessionExpired` custom event
     - Lets error propagate to calling component

2. **AuthContext** (`context/AuthContext.jsx`):
   - Listens for `sessionExpired` event
   - On session expiration:
     - Sets `sessionExpired` state to `true`
     - Clears user data (user, permissions, menus)
     - Stops session check interval
   - Renders `SessionExpiredModal` when `sessionExpired === true`

3. **Session Check Timer**:
   - Checks every 30 seconds if `sessionExpiresAt` has passed
   - If expired:
     - Sets `sessionExpired` state to `true`
     - Shows modal

4. **SessionExpiredModal Component** (`components/SessionExpiredModal.jsx`):
   - Shows a modal overlay with:
     - Warning icon
     - "Session Expired" title
     - Explanation message
     - 3-second countdown timer
     - "Go to Login Page" button
   - After 3 seconds OR when button clicked:
     - Navigates to `/login` page
     - Passes message: "Your session has expired. Please login again."

5. **User Login Again**:
   - User can login normally
   - New session is created
   - Session expiration time is reset
   - User is redirected to dashboard

## Visual Flow

```
User is logged in
    ↓
No activity for 5 minutes (or configured timeout)
    ↓
User makes a request (or timer checks)
    ↓
Backend checks: (current_time - last_activity) > 5 minutes?
    ↓ YES (Session Expired)
Backend destroys session
    ↓
Backend returns 401 with expired: true
    ↓
Frontend API interceptor catches 401
    ↓
Dispatches 'sessionExpired' event
    ↓
AuthContext receives event
    ↓
Sets sessionExpired = true
Clears user data
    ↓
SessionExpiredModal appears
    ↓
Shows countdown (3 seconds)
    ↓
User clicks button OR countdown ends
    ↓
Redirects to /login page
    ↓
User can login again
```

## Key Features

1. **Automatic Detection**: Session expiration is checked on every backend request
2. **User-Friendly**: Shows a clear modal instead of just an error
3. **Smooth Transition**: 3-second countdown before redirect
4. **Secure**: Session is completely destroyed on backend
5. **Re-login**: User can immediately login again after expiration

## Session Timeout Configuration

- **Default**: 5 minutes (300 seconds)
- **Location**: `nira_backend/config/bootstrap.php`
- **Testing**: You can temporarily change it (e.g., 20 seconds for quick testing)
- **Production**: Should be set to appropriate value (e.g., 15-30 minutes)

## Important Notes

1. **Activity Tracking**: Each API request updates `last_activity` timestamp, resetting the timeout
2. **Multiple Checks**: Both `bootstrap.php` and `auth.php` check expiration for security
3. **Client-Side Timer**: Frontend also checks expiration every 30 seconds as backup
4. **No Warning**: Currently, there's no warning before expiration (could be added as enhancement)
5. **Remember Me**: If "Remember Me" is checked, the cookie lifetime is extended, but session timeout still applies
