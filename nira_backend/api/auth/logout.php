<?php
/**
 * NIRA Officer Logout Endpoint
 * POST /api/auth/logout.php
 */

require_once __DIR__ . '/../../config/bootstrap.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Clear all session data
session_unset();

// Destroy the session
if (session_destroy()) {
    // Clear session cookie (works for both normal and remembered sessions)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        $cookieName = session_name();
        $expireTime = time() - 3600; // Expire immediately
        
        // Use array syntax for PHP 7.3+ (supports samesite)
        if (PHP_VERSION_ID >= 70300) {
            setcookie(
                $cookieName,
                '',
                [
                    'expires' => $expireTime,
                    'path' => $params["path"],
                    'domain' => $params["domain"],
                    'secure' => $params["secure"],
                    'httponly' => $params["httponly"],
                    'samesite' => $params["samesite"] ?? 'Lax'
                ]
            );
        } else {
            // Fallback for older PHP versions
            setcookie(
                $cookieName,
                '',
                $expireTime,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to logout'
    ]);
}



