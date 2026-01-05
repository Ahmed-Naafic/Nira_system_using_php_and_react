<?php
/**
 * Dashboard Statistics Endpoint
 * GET /api/dashboard/stats.php
 * 
 * Returns dashboard statistics for authorized users
 * Requires: Active session + VIEW_DASHBOARD permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/dashboard.service.php';
require_once __DIR__ . '/../../middleware/permission.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Require authentication and VIEW_DASHBOARD permission
    // This will return 401 if not authenticated or 403 if permission missing
    requirePermission('VIEW_DASHBOARD');
    
    // Get dashboard statistics
    $dashboardService = new DashboardService();
    
    $stats = [
        'citizens' => $dashboardService->countCitizens(),
        'users' => $dashboardService->countUsers(),
        'roles' => $dashboardService->countRoles(),
        'reports' => $dashboardService->countReports()
    ];
    
    // Return statistics
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    // If exception is thrown, it's likely already handled by middleware
    // But catch any unexpected errors
    if (!headers_sent()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to retrieve dashboard statistics: ' . $e->getMessage()
        ]);
    }
}

