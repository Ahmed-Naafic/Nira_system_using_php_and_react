<?php
/**
 * Citizen Reports Endpoint
 * GET /api/reports/citizens.php
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
    
    // Get citizen report
    $reportService = new ReportService();
    $citizenReport = $reportService->getCitizenSummary();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $citizenReport
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate citizen report: ' . $e->getMessage()
    ]);
}

