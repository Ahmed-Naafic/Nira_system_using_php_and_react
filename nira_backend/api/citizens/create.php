<?php
/**
 * Create Citizen Endpoint
 * POST /api/citizens/create.php
 * Requires: CREATE_CITIZEN permission
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
    // Require CREATE_CITIZEN permission
    requirePermission('CREATE_CITIZEN');
    
    // Check if request is multipart/form-data (file upload) or JSON
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && 
                   strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    
    if ($isMultipart) {
        // Handle multipart/form-data (with file uploads)
        $input = [
            'firstName' => $_POST['firstName'] ?? '',
            'middleName' => $_POST['middleName'] ?? '',
            'lastName' => $_POST['lastName'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'dateOfBirth' => $_POST['dateOfBirth'] ?? '',
            'placeOfBirth' => $_POST['placeOfBirth'] ?? '',
            'nationality' => $_POST['nationality'] ?? 'Somali'
        ];
        
        // Validate required fields
        $requiredFields = ['firstName', 'lastName', 'gender', 'dateOfBirth', 'placeOfBirth'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Field '{$field}' is required"
                ]);
                exit;
            }
        }
    } else {
        // Handle JSON input (backward compatibility)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid JSON input'
            ]);
            exit;
        }
    }
    
    // Get current user ID
    $performedBy = $_SESSION['user_id'] ?? null;
    
    // Create citizen using service (will generate national ID)
    $citizenService = new CitizenService();
    $citizen = $citizenService->createCitizen($input);
    
    // Handle file uploads if present (after citizen is created so we have national ID)
    require_once __DIR__ . '/../../utils/file_upload.php';
    
    $imagePath = null;
    $documentPath = null;
    
    // Upload image if provided
    if ($isMultipart && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            $imagePath = FileUpload::uploadImage($_FILES['image'], $citizen['nationalId']);
        } catch (Exception $e) {
            // If image upload fails, delete the citizen and return error
            // Or you could choose to continue without image
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Image upload failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Upload document if provided
    if ($isMultipart && isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        try {
            $documentPath = FileUpload::uploadDocument($_FILES['document'], $citizen['nationalId']);
        } catch (Exception $e) {
            // If document upload fails, delete the citizen and return error
            // Or you could choose to continue without document
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Document upload failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // Update citizen with file paths if files were uploaded
    if ($imagePath !== null || $documentPath !== null) {
        $updateData = [];
        if ($imagePath !== null) {
            $updateData['imagePath'] = $imagePath;
        }
        if ($documentPath !== null) {
            $updateData['documentPath'] = $documentPath;
        }
        $citizen = $citizenService->updateCitizenFiles($citizen['nationalId'], $updateData);
    }
    
    // Log activity
    if ($performedBy) {
        $activityService = new ActivityService();
        $activityService->logActivity(
            'CREATE_CITIZEN',
            'citizen',
            $citizen['nationalId'],
            "Created citizen: {$citizen['firstName']} {$citizen['lastName']} (ID: {$citizen['nationalId']})",
            $performedBy
        );
    }
    
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
