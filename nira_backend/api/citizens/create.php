<?php
/**
 * Create Citizen Endpoint
 * POST /api/citizens/create.php
 * Requires: CREATE_CITIZEN permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/citizen.service.php';
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
    // Require CREATE_CITIZEN permission
    requirePermission('CREATE_CITIZEN');
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    // Create citizen using service
    $citizenService = new CitizenService();
    $citizen = $citizenService->createCitizen($input);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Citizen registered successfully',
        'data' => $citizen
    ]);
    
} catch (Exception $e) {
    // Handle duplicate national ID
    if ($e->getCode() == 409) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } 
    // Handle validation errors
    else if (strpos($e->getMessage(), 'required') !== false || 
             strpos($e->getMessage(), 'must be') !== false ||
             strpos($e->getMessage(), 'Invalid') !== false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    // Handle other errors
    else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to register citizen: ' . $e->getMessage()
        ]);
    }
}
