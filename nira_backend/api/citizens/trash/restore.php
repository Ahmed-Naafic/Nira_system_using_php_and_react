<?php
/**
 * Restore Citizen from Trash Endpoint
 * POST /api/citizens/trash/restore.php
 * Requires: RESTORE_CITIZEN permission
 */

require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../services/citizen.service.php';
require_once __DIR__ . '/../../../middleware/permission.php';

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
    // Require RESTORE_CITIZEN permission
    requirePermission('RESTORE_CITIZEN');
    
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
    
    $citizenService = new CitizenService();
    
    // Check if restoring all or single
    if (isset($input['restoreAll']) && $input['restoreAll'] === true) {
        // Restore all
        $count = $citizenService->restoreAllCitizens();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => "Successfully restored {$count} citizen(s)",
            'count' => $count
        ]);
    } else {
        // Restore single citizen
        if (!isset($input['nationalId']) || empty(trim($input['nationalId']))) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'nationalId is required'
            ]);
            exit;
        }
        
        $nationalId = trim($input['nationalId']);
        $success = $citizenService->restoreCitizen($nationalId);
        
        if (!$success) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Citizen not found in trash'
            ]);
            exit;
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Citizen restored successfully'
        ]);
    }
    
} catch (Exception $e) {
    $code = $e->getCode();
    if ($code === 404) {
        http_response_code(404);
    } else if ($code === 400 || $code === 0) {
        http_response_code(400);
    } else {
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

