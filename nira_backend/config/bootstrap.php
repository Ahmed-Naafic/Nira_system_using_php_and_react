<?php
/**
 * Global Bootstrap File for NIRA System
 * Handles session configuration, CORS headers, and preflight requests
 */

// Set CORS headers for React frontend
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Allow React dev server
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
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
session_set_cookie_params([
    'lifetime' => 0, // Default: session cookie expires when browser closes
    'path' => '/',
    'domain' => '', // Empty = current domain only (browser-scoped)
    'secure' => false, // Set to true in production with HTTPS
    'httponly' => true, // Prevent JavaScript access (security)
    'samesite' => 'Lax' // CSRF protection (Lax allows cross-site GET requests)
]);

// Start session if not already started
// Session is automatically browser-scoped via PHPSESSID cookie
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON by default
header('Content-Type: application/json');

