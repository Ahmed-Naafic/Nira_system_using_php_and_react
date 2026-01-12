<?php
/**
 * User Reports Endpoint
 * GET /api/reports/users.php
 * Requires: VIEW_REPORTS permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/report.service.php';
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
    // Require VIEW_REPORTS permission
    requirePermission('VIEW_REPORTS');
    
    // Get user report
    $reportService = new ReportService();
    $userReport = $reportService->getUserSummary();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $userReport
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate user report: ' . $e->getMessage()
    ]);
}

