<?php
/**
 * Dashboard Statistics Endpoint
 * 
 * Returns dashboard statistics including:
 * - Total Citizens
 * - Total Users
 * - Total Roles
 * - Total Reports
 * 
 * Requires active session and VIEW_DASHBOARD permission.
 */

session_start();

// CORS headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Please login.'
    ]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middlewares/permission.php';

try {
    // Check permission
    requirePermission('VIEW_DASHBOARD');
    
    $pdo = getDBConnection();
    
    // Get statistics
    $stats = [];
    
    // Total Citizens
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM citizens");
    $result = $stmt->fetch();
    $stats['totalCitizens'] = (int)($result['count'] ?? 0);
    
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM nira_users");
    $result = $stmt->fetch();
    $stats['totalUsers'] = (int)($result['count'] ?? 0);
    
    // Total Roles
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM roles");
    $result = $stmt->fetch();
    $stats['totalRoles'] = (int)($result['count'] ?? 0);
    
    // Total Reports (if reports table exists, otherwise return 0)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM reports");
        $result = $stmt->fetch();
        $stats['totalReports'] = (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        // Reports table doesn't exist yet
        $stats['totalReports'] = 0;
    }
    
    // Build response
    $response = [
        'success' => true,
        'stats' => $stats
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    // Permission middleware throws exception for 403, but we need to handle other errors
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have access to dashboard statistics'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while fetching dashboard statistics',
            'error' => $e->getMessage()
        ]);
    }
}

