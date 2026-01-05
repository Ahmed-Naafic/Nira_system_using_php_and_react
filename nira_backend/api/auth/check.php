<?php
/**
 * Authentication Check Endpoint
 * GET /api/auth/check.php
 * 
 * STRICT authentication validation - backend is source of truth
 * Returns 401 if no valid session exists
 * 
 * This endpoint is called by frontend to verify authentication status.
 * Session is browser-scoped by design - no cross-browser access.
 */

require_once __DIR__ . '/../../config/bootstrap.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// STRICT CHECK: Session must be started and user_id must exist
// No fallbacks, no assumptions, no cached state
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // No valid session - return 401 Unauthorized
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'message' => 'Authentication required. Please login.'
    ]);
    exit;
}

// Session is valid - return user information
// Only return data if session is confirmed valid
http_response_code(200);
echo json_encode([
    'success' => true,
    'authenticated' => true,
    'user' => [
        'id' => (int)$_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ]
]);
