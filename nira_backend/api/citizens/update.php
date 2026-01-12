<?php
/**
 * Update Citizen Endpoint
 * POST /api/citizens/update.php
 * Requires: UPDATE_CITIZEN permission
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
    // Require UPDATE_CITIZEN permission
    requirePermission('UPDATE_CITIZEN');
    
    // Check if request is multipart/form-data (file upload) or JSON
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && 
                   strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    
    if ($isMultipart) {
        // Handle multipart/form-data (with file uploads)
        $nationalId = $_POST['nationalId'] ?? '';
        
        if (empty($nationalId)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'nationalId is required'
            ]);
            exit;
        }
        
        $input = [
            'firstName' => $_POST['firstName'] ?? '',
            'middleName' => $_POST['middleName'] ?? '',
            'lastName' => $_POST['lastName'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'dateOfBirth' => $_POST['dateOfBirth'] ?? '',
            'placeOfBirth' => $_POST['placeOfBirth'] ?? '',
            'nationality' => $_POST['nationality'] ?? 'Somali',
            'status' => $_POST['status'] ?? 'ACTIVE'
        ];
    } else {
        // Handle JSON input (backward compatibility)
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
    }
    
    // Remove nationalId from data (it's immutable)
    unset($input['nationalId']);
    
    // Get current user ID
    $performedBy = $_SESSION['user_id'] ?? null;
    
    // Update citizen using service
    $citizenService = new CitizenService();
    $citizen = $citizenService->updateCitizen($nationalId, $input);
    
    // Handle file uploads if present
    require_once __DIR__ . '/../../utils/file_upload.php';
    
    $imagePath = null;
    $documentPath = null;
    $updateFiles = false;
    
    // Upload new image if provided
    if ($isMultipart && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            // Delete old image if exists
            if (!empty($citizen['imagePath'])) {
                FileUpload::deleteFile($citizen['imagePath']);
            }
            $imagePath = FileUpload::uploadImage($_FILES['image'], $nationalId);
            $updateFiles = true;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Image upload failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Upload new document if provided
    if ($isMultipart && isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        try {
            // Delete old document if exists
            if (!empty($citizen['documentPath'])) {
                FileUpload::deleteFile($citizen['documentPath']);
            }
            $documentPath = FileUpload::uploadDocument($_FILES['document'], $nationalId);
            $updateFiles = true;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Document upload failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Update citizen with file paths if files were uploaded
    if ($updateFiles) {
        $updateData = [];
        if ($imagePath !== null) {
            $updateData['imagePath'] = $imagePath;
        }
        if ($documentPath !== null) {
            $updateData['documentPath'] = $documentPath;
        }
        $citizen = $citizenService->updateCitizenFiles($nationalId, $updateData);
    }
    
    // Log activity
    if ($performedBy) {
        $activityService = new ActivityService();
        $activityService->logActivity(
            'UPDATE_CITIZEN',
            'citizen',
            $citizen['nationalId'],
            "Updated citizen: {$citizen['firstName']} {$citizen['lastName']} (ID: {$citizen['nationalId']})",
            $performedBy
        );
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'citizen' => $citizen
    ]);
    
} catch (Exception $e) {
    // Handle different error codes
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

