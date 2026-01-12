<?php
/**
 * File Serving Endpoint
 * GET /api/files/get.php?path=images/filename.jpg
 * Serves uploaded citizen images and documents securely
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../middleware/auth.php';

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
    // Require authentication
    AuthMiddleware::validateSession();
    
    // Get file path from query parameter
    $filePath = isset($_GET['path']) ? $_GET['path'] : '';
    
    if (empty($filePath)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'File path is required'
        ]);
        exit;
    }
    
    // Sanitize file path to prevent directory traversal
    $filePath = str_replace('..', '', $filePath);
    $filePath = ltrim($filePath, '/');
    
    // Construct full file path
    $fullPath = __DIR__ . '/../../uploads/' . $filePath;
    
    // Check if file exists
    if (!file_exists($fullPath) || !is_file($fullPath)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'File not found'
        ]);
        exit;
    }
    
    // Get file info
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fullPath);
    finfo_close($finfo);
    
    // Set appropriate headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($fullPath));
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    
    // Output file
    readfile($fullPath);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to serve file: ' . $e->getMessage()
    ]);
}
