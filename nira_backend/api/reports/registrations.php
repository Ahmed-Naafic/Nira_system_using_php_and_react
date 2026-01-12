<?php
/**
 * Registration Reports Endpoint
 * GET /api/reports/registrations.php?period=month
 * Requires: VIEW_REPORTS permission
 * 
 * period: 'day', 'month', or 'year' (default: 'month')
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
    
    // Validate period parameter (default: month)
    $period = isset($_GET['period']) ? trim($_GET['period']) : 'month';
    $allowedPeriods = ['day', 'month', 'year'];
    if (!in_array(strtolower($period), $allowedPeriods)) {
        $period = 'month';
    }
    $period = strtolower($period);
    
    // Get registration reports
    $reportService = new ReportService();
    
    try {
        $summary = $reportService->getRegistrationSummary();
    } catch (Exception $e) {
        error_log("Registration Report Error (summary): " . $e->getMessage());
        $summary = [
            'births' => 0,
            'marriages' => 0,
            'divorces' => 0,
            'deaths' => 0
        ];
    }
    
    try {
        $timeBased = $reportService->getTimeBasedReport($period);
    } catch (Exception $e) {
        error_log("Registration Report Error (timeBased): " . $e->getMessage());
        $timeBased = [];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => $summary,
            'timeBased' => $timeBased
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Registration Report Endpoint Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate registration report: ' . $e->getMessage()
    ]);
}

