<?php
/**
 * Dashboard Service for NIRA System
 * Provides statistics and counts for dashboard display
 */

require_once __DIR__ . '/../config/database.php';

class DashboardService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Count total citizens
     * @return int Number of citizens
     */
    public function countCitizens() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM citizens");
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Dashboard Service Error (countCitizens): " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Count total users
     * @return int Number of users
     */
    public function countUsers() {
        try {
            // Count all users (if status column exists and is NULL, use COALESCE to include them)
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM nira_users");
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Dashboard Service Error (countUsers): " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Count total roles
     * @return int Number of roles
     */
    public function countRoles() {
        try {
            // Try to count from roles table first
            try {
                $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM roles");
                $stmt->execute();
                $result = $stmt->fetch();
                return (int)$result['count'];
            } catch (PDOException $e) {
                // If roles table doesn't exist, count distinct roles from nira_users as fallback
                if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), 'Unknown table') !== false) {
                    $stmt = $this->conn->prepare("SELECT COUNT(DISTINCT role) as count FROM nira_users");
                    $stmt->execute();
                    $result = $stmt->fetch();
                    return (int)$result['count'];
                }
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Dashboard Service Error (countRoles): " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Count total reports (status change log entries)
     * @return int Number of report entries
     */
    public function countReports() {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM status_change_log");
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Dashboard Service Error (countReports): " . $e->getMessage());
            return 0;
        }
    }
}

