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
    
    // Find user by username (exclude deleted users)
    // Check if status column exists
    $checkStatus = $conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
    $hasStatus = $checkStatus->rowCount() > 0;
    
    if ($hasStatus) {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM nira_users WHERE username = ? AND (status IS NULL OR status != 'DELETED')");
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM nira_users WHERE username = ?");
    }
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
        
        // Set cookie params for 30 days
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 30, // 30 days
            'path' => '/',
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
    
    // If rememberMe, also set a flag in session (optional, for tracking)
    if ($rememberMe) {
        $_SESSION['remember_me'] = true;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed: ' . $e->getMessage()
    ]);
}

