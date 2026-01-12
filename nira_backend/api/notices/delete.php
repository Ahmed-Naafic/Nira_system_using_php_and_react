<?php
/**
 * Delete Notice Endpoint (Soft Delete)
 * POST /api/notices/delete.php
 * Requires: MANAGE_NOTICES permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/notice.service.php';
require_once __DIR__ . '/../../middleware/permission.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Require MANAGE_NOTICES permission
    requirePermission('MANAGE_NOTICES');
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    // Validate notice ID
    if (!isset($input['id']) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Notice ID is required'
        ]);
        exit;
    }
    
    $noticeId = (int)$input['id'];
    
    // Delete notice
    $noticeService = new NoticeService();
    $success = $noticeService->deleteNotice($noticeId);
    
    if (!$success) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Notice not found or already deleted'
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Notice deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete notice: ' . $e->getMessage()
    ]);
}

