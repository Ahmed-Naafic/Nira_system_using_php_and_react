<?php
/**
 * NIRA Officer Login Endpoint
 * POST /api/auth/login.php
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Find user by username (exclude deleted users using deleted_at - same pattern as citizens)
    $stmt = $conn->prepare("SELECT id, username, password, role FROM nira_users WHERE username = ? AND deleted_at IS NULL");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit;
    }
    
    // Check if "Remember Me" is enabled
    $rememberMe = isset($input['rememberMe']) && $input['rememberMe'] === true;
    
    // If "Remember Me" is enabled, extend session cookie lifetime
    if ($rememberMe) {
        // Close current session
        session_write_close();
        
        // Determine the base path for the application (same logic as bootstrap.php)
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = '/';
        if (strpos($scriptPath, '/niraSystem') !== false) {
            $basePath = '/niraSystem/';
        } elseif (strpos($scriptPath, '/nira_system') !== false) {
            $basePath = '/nira_system/';
        }
        
        // Set cookie params for 30 days
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 30, // 30 days
            'path' => $basePath, // Use same base path as bootstrap
            'domain' => '',
            'secure' => false, // Set to true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // Restart session with new cookie params
        session_start();
    }
    
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    
    // Store user data in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    
    // Set session creation and last activity timestamps
    $currentTime = time();
    $_SESSION['created_at'] = $currentTime;
    $_SESSION['last_activity'] = $currentTime;
    
    // If rememberMe, also set a flag in session (optional, for tracking)
    if ($rememberMe) {
        $_SESSION['remember_me'] = true;
        // Extend session timeout for remember me (e.g., 30 days)
        // Note: This is handled by the cookie lifetime, but we can track it
    }
    
    // Calculate session expiration time
    $sessionTimeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 300; // 5 minutes
    $expiresAt = $currentTime + $sessionTimeout;
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'sessionExpiresAt' => $expiresAt, // Unix timestamp when session expires
        'sessionTimeout' => $sessionTimeout // Session timeout in seconds
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed: ' . $e->getMessage()
    ]);
}

