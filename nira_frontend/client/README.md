# NIRA Frontend - Officer System

React frontend for the National Identification Registration Authority (NIRA) backend system.

## Features

- **Session-based Authentication**: Uses cookies with `withCredentials` for secure authentication
- **Officer Dashboard**: Centralized dashboard for NIRA officers
- **Citizen Registration**: Register new citizens and generate National IDs
- **Citizen Search**: Search for citizens by National ID
- **Citizen Details**: View detailed citizen information (read-only)

## Tech Stack

- React 19
- Vite
- React Router
- Axios (with credentials)
- Tailwind CSS

## Setup

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Configure API Base URL**
   
   Create a `.env` file in the `client` directory:
   ```env
   VITE_API_BASE_URL=http://localhost/nira_backend
   ```
   
   Update the URL to match your NIRA backend location.

3. **Start Development Server**
   ```bash
   npm run dev
   ```

4. **Build for Production**
   ```bash
   npm run build
   ```

## Project Structure

```
client/src/
├── api/
│   └── api.js              # Axios instance with withCredentials
├── auth/
│   ├── Login.jsx           # Login component
│   └── ProtectedRoute.jsx  # Route protection wrapper
├── citizens/
│   ├── CitizenCreate.jsx   # Register new citizen
│   ├── CitizenSearch.jsx   # Search citizen by National ID
│   └── CitizenDetails.jsx # View citizen details
├── layout/
│   └── DashboardLayout.jsx # Main layout with navigation
├── pages/
│   ├── Dashboard.jsx       # Dashboard page
│   └── LoginPage.jsx       # Login page wrapper
├── App.jsx                 # Main app with routing
└── main.jsx                # Entry point
```

## API Endpoints

The frontend expects the following backend endpoints:

### Authentication
- `POST /api/auth/login.php` - Officer login
- `POST /api/auth/logout.php` - Officer logout
- `GET /api/auth/check.php` - Verify session (optional)

### Citizens
- `POST /api/citizens/create.php` - Register new citizen
- `GET /api/citizens/get.php?nationalId=XXX` - Get citizen by National ID

## Authentication Flow

1. Officer logs in via `/login`
2. Backend sets session cookie
3. All subsequent requests include cookie via `withCredentials: true`
4. Protected routes verify session
5. On 401 response, user is redirected to login

## Security Notes

- **No JWT**: This system uses PHP sessions with cookies
- **No localStorage**: No tokens stored in browser storage
- **withCredentials**: All API requests include credentials
- **Officer-only**: This is an internal system, not public-facing

## Environment Variables

- `VITE_API_BASE_URL`: Base URL for the NIRA backend API

## Notes

- Ensure CORS is properly configured on the backend to accept credentials
- Backend must set appropriate cookie flags (HttpOnly, Secure in production)
- Session cookies are automatically managed by the browser
