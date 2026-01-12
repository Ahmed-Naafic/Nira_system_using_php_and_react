<?php
/**
 * Delete Citizen Endpoint (Soft Delete)
 * POST /api/citizens/delete.php
 * Requires: DELETE_CITIZEN permission
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../services/citizen.service.php';
require_once __DIR__ . '/../../services/activity.service.php';
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
    // Require DELETE_CITIZEN permission
    requirePermission('DELETE_CITIZEN');
    
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
    
    // Validate nationalId
    if (!isset($input['nationalId']) || empty(trim($input['nationalId']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'nationalId is required'
        ]);
        exit;
    }
    
    $nationalId = trim($input['nationalId']);
    
    // Get current user ID
    $performedBy = $_SESSION['user_id'] ?? null;
    
    // Get citizen info before deleting (for activity log)
    $citizenService = new CitizenService();
    $citizen = $citizenService->getCitizenByNationalId($nationalId);
    $citizenName = $citizen ? "{$citizen['firstName']} {$citizen['lastName']}" : $nationalId;
    
    // Soft delete citizen using service
    $success = $citizenService->deleteCitizen($nationalId);
    
    if (!$success) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Citizen not found or already deleted'
        ]);
        exit;
    }
    
    // Log activity
    if ($performedBy) {
        $activityService = new ActivityService();
        $activityService->logActivity(
            'DELETE_CITIZEN',
            'citizen',
            $nationalId,
            "Deleted citizen: {$citizenName} (ID: {$nationalId})",
            $performedBy
        );
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Citizen moved to trash successfully'
    ]);
    
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

