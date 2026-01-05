<?php
/**
 * Get Citizen by National ID Endpoint
 * GET /api/citizens/get.php?nationalId=XXXXXXXXX
 * Requires: VIEW_CITIZEN permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/citizen.service.php';
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
    // Require VIEW_CITIZEN permission
    requirePermission('VIEW_CITIZEN');
    
    // Get national ID from query parameter
    $nationalId = $_GET['nationalId'] ?? null;
    
    if (empty($nationalId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'National ID is required'
        ]);
        exit;
    }
    
    // Get citizen using service
    $citizenService = new CitizenService();
    $citizen = $citizenService->getCitizenByNationalId($nationalId);
    
    if (!$citizen) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Citizen not found'
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $citizen
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve citizen data: ' . $e->getMessage()
    ]);
}
