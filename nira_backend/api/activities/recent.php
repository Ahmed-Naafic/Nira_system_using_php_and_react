<?php
/**
 * Recent Activities Endpoint
 * GET /api/activities/recent.php?limit=20
 * Requires: VIEW_ACTIVITIES permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/activity.service.php';
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
    // Require VIEW_ACTIVITIES permission
    requirePermission('VIEW_ACTIVITIES');
    
    // Get limit parameter (default: 20)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $limit = max(1, min(100, $limit)); // Clamp between 1 and 100
    
    // Get recent activities
    $activityService = new ActivityService();
    $activities = $activityService->getRecentActivities($limit);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $activities
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve activities: ' . $e->getMessage()
    ]);
}

