<?php
/**
 * Create Notice Endpoint
 * POST /api/notices/create.php
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
    
    // Get current user ID from session
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit;
    }
    
    $createdBy = $_SESSION['user_id'];
    
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
    
    // Create notice
    $noticeService = new NoticeService();
    $notice = $noticeService->createNotice($input, $createdBy);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Notice created successfully',
        'data' => $notice
    ]);
    
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code === 400 || $code === 0) {
        http_response_code(400);
    } else {
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

