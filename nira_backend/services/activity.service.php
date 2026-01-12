<?php
/**
 * Activity Service for NIRA System
 * Handles automatic logging of system events
 * WRITE-ONLY: Activities are never edited or deleted
 */

require_once __DIR__ . '/../config/database.php';

class ActivityService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Log a system activity
     * @param string $action Action code (e.g., CREATE_CITIZEN, UPDATE_USER)
     * @param string $entityType Entity type (e.g., citizen, user, report)
     * @param string|null $entityId Entity ID
     * @param string $description Human-readable description
     * @param int $performedBy User ID performing the action
     * @return bool Success status
     */
    public function logActivity($action, $entityType, $entityId, $description, $performedBy) {
        try {
            // Validate required fields
            if (empty(trim($action))) {
                error_log("Activity Service Error: Action is required");
                return false;
            }
            
            if (empty(trim($entityType))) {
                error_log("Activity Service Error: Entity type is required");
                return false;
            }
            
            if (empty(trim($description))) {
                error_log("Activity Service Error: Description is required");
                return false;
            }
            
            if (empty($performedBy)) {
                error_log("Activity Service Error: Performed by is required");
                return false;
            }
            
            // Check if table exists
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'system_activities'");
            if ($checkTable->rowCount() === 0) {
                error_log("Activity Service Error: system_activities table does not exist. Please run migrations.");
                return false;
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO system_activities (action, entity_type, entity_id, description, performed_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                trim($action),
                trim($entityType),
                $entityId ? trim($entityId) : null,
                trim($description),
                $performedBy
            ]);
            
            return true;
        } catch (PDOException $e) {
            // Log error but don't throw - activity logging should not break main operations
            error_log("Activity Service Error (logActivity): " . $e->getMessage());
            error_log("Activity Service Error Code: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            error_log("Activity Service Error (logActivity): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent activities
     * @param int $limit Maximum number of activities to return (default: 20)
     * @return array Array of recent activities
     */
    public function getRecentActivities($limit = 20) {
        try {
            // Check if table exists
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'system_activities'");
            if ($checkTable->rowCount() === 0) {
                error_log("Activity Service Error: system_activities table does not exist. Please run migrations.");
                return [];
            }
            
            $limit = max(1, min(100, (int)$limit)); // Clamp between 1 and 100
            
            $stmt = $this->conn->prepare("
                SELECT 
                    a.id,
                    a.action,
                    a.entity_type,
                    a.entity_id,
                    a.description,
                    a.created_at,
                    a.performed_by,
                    u.username as performed_by_username
                FROM system_activities a
                INNER JOIN nira_users u ON a.performed_by = u.id
                ORDER BY a.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(function($activity) {
                return [
                    'id' => (int)$activity['id'],
                    'action' => $activity['action'],
                    'entityType' => $activity['entity_type'],
                    'entityId' => $activity['entity_id'],
                    'description' => $activity['description'],
                    'performedBy' => (int)$activity['performed_by'],
                    'performedByUsername' => $activity['performed_by_username'],
                    'createdAt' => $activity['created_at']
                ];
            }, $activities);
        } catch (Exception $e) {
            error_log("Activity Service Error (getRecentActivities): " . $e->getMessage());
            return [];
        }
    }
}

