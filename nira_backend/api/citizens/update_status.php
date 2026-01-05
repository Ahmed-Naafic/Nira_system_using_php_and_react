<?php
/**
 * Update Citizen Status Endpoint
 * POST /api/citizens/update_status.php
 * Admin only - Updates citizen status (e.g., to DECEASED)
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Validate authentication (ADMIN only)
$user = AuthMiddleware::requireRole(['ADMIN']);

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['nationalId']) || empty(trim($input['nationalId']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'nationalId is required'
    ]);
    exit;
}

if (!isset($input['status']) || empty(trim($input['status']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'status is required'
    ]);
    exit;
}

// Validate status value
$status = strtoupper(trim($input['status']));
if (!in_array($status, ['ACTIVE', 'DECEASED'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => "Status must be 'ACTIVE' or 'DECEASED'"
    ]);
    exit;
}

$nationalId = trim($input['nationalId']);

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if citizen exists and get current status
    $stmt = $conn->prepare("SELECT id, status FROM citizens WHERE national_id = ?");
    $stmt->execute([$nationalId]);
    $citizen = $stmt->fetch();
    
    if (!$citizen) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Citizen not found'
        ]);
        exit;
    }
    
    // Check if status is already the same
    if ($citizen['status'] === $status) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Status is already set to ' . $status,
            'data' => [
                'nationalId' => $nationalId,
                'status' => $status
            ]
        ]);
        exit;
    }
    
    $oldStatus = $citizen['status'];
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Update citizen status
        $stmt = $conn->prepare("UPDATE citizens SET status = ? WHERE national_id = ?");
        $stmt->execute([$status, $nationalId]);
        
        // Log status change
        $stmt = $conn->prepare("
            INSERT INTO status_change_log (national_id, old_status, new_status, changed_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$nationalId, $oldStatus, $status, $user['userId']]);
        
        // Commit transaction
        $conn->commit();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Citizen status updated successfully',
            'data' => [
                'nationalId' => $nationalId,
                'oldStatus' => $oldStatus,
                'newStatus' => $status
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update citizen status: ' . $e->getMessage()
    ]);
}



