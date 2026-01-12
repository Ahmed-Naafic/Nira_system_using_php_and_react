<?php
/**
 * Global Bootstrap File for NIRA System
 * Handles session configuration, CORS headers, and preflight requests
 */

// Set CORS headers for React frontend
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Allow React dev server and direct access
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:8000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8000'
];

if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}

// Allow credentials (cookies)
header('Access-Control-Allow-Credentials: true');

// Allow common headers
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle OPTIONS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configure session cookie parameters before starting session
// IMPORTANT: Sessions are browser-scoped by design
// - Each browser gets its own session ID (PHPSESSID cookie)
// - Sessions cannot be shared across browsers or devices
// - This is REQUIRED for security and correctness

// Determine the base path for the application
// Extract base path from script path (e.g., /niraSystem/nira_backend/api/auth/me.php -> /niraSystem)
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = '/';
if (strpos($scriptPath, '/niraSystem') !== false) {
    $basePath = '/niraSystem/';
} elseif (strpos($scriptPath, '/nira_system') !== false) {
    $basePath = '/nira_system/';
}

session_set_cookie_params([
    'lifetime' => 0, // Default: session cookie expires when browser closes
    'path' => $basePath, // Set to application base path
    'domain' => '', // Empty = current domain only (browser-scoped)
    'secure' => false, // Set to true in production with HTTPS
    'httponly' => true, // Prevent JavaScript access (security)
    'samesite' => 'Lax' // CSRF protection (Lax allows cross-site GET requests)
]);

// Session timeout configuration (5 minutes = 300 seconds)
define('SESSION_TIMEOUT', 300); // 5 minutes (300 seconds)

// Start session if not already started
// Session is automatically browser-scoped via PHPSESSID cookie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check and handle session expiration
// IMPORTANT: Update last_activity FIRST, then check expiration
// This ensures active users stay logged in
if (isset($_SESSION['user_id'])) {
    $currentTime = time();
    
    if (isset($_SESSION['last_activity'])) {
        $lastActivity = $_SESSION['last_activity'];
        
        // Check if session has expired BEFORE updating
        if (($currentTime - $lastActivity) > SESSION_TIMEOUT) {
            // Session expired - destroy session and clear data
            session_unset();
            session_destroy();
            
            // Set new session for response
            session_start();
        } else {
            // Session is still valid - UPDATE last activity timestamp NOW
            // This keeps the session alive as long as user is making requests
            $_SESSION['last_activity'] = $currentTime;
        }
    } else {
        // First request after login - initialize last_activity
        $_SESSION['last_activity'] = $currentTime;
    }
}

// Set content type to JSON by default (unless it's a file request or multipart)
$isFileRequest = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/files/') !== false;
$isMultipart = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;

if (!$isFileRequest && !$isMultipart) {
    header('Content-Type: application/json');
}

