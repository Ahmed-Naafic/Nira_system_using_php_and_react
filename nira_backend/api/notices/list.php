<?php
/**
 * List Active Notices Endpoint
 * GET /api/notices/list.php
 * Requires: VIEW_NOTICES permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/notice.service.php';
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
    // Require VIEW_NOTICES permission
    requirePermission('VIEW_NOTICES');
    
    // Get active notices
    $noticeService = new NoticeService();
    $notices = $noticeService->listActiveNotices();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $notices
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve notices: ' . $e->getMessage()
    ]);
}

