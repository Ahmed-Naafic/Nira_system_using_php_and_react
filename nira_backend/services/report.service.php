<?php
/**
 * Report Service for NIRA System
 * Provides read-only statistical reports
 */

require_once __DIR__ . '/../config/database.php';

class ReportService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get comprehensive summary report
     * @return array Summary statistics
     */
    public function getSummary() {
        try {
            return [
                'citizens' => $this->getCitizenSummary(),
                'users' => $this->getUserSummary(),
                'registrations' => $this->getRegistrationSummary()
            ];
        } catch (Exception $e) {
            error_log("Report Service Error (getSummary): " . $e->getMessage());
            throw new Exception('Failed to generate summary report: ' . $e->getMessage());
        }
    }
    
    /**
     * Get citizen summary statistics
     * @return array Citizen statistics
     */
    public function getCitizenSummary() {
        try {
            // Total citizens (excluding soft-deleted)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM citizens 
                WHERE deleted_at IS NULL
            ");
            $stmt->execute();
            $total = (int)$stmt->fetch()['total'];
            
            // Total male citizens
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM citizens 
                WHERE gender = 'MALE' AND deleted_at IS NULL
            ");
            $stmt->execute();
            $male = (int)$stmt->fetch()['count'];
            
            // Total female citizens
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM citizens 
                WHERE gender = 'FEMALE' AND deleted_at IS NULL
            ");
            $stmt->execute();
            $female = (int)$stmt->fetch()['count'];
            
            // Total deceased citizens
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM citizens 
                WHERE status = 'DECEASED' AND deleted_at IS NULL
            ");
            $stmt->execute();
            $deceased = (int)$stmt->fetch()['count'];
            
            return [
                'total' => $total,
                'male' => $male,
                'female' => $female,
                'deceased' => $deceased,
                'active' => $total - $deceased
            ];
        } catch (Exception $e) {
            error_log("Report Service Error (getCitizenSummary): " . $e->getMessage());
            throw new Exception('Failed to generate citizen summary: ' . $e->getMessage());
        }
    }
    
    /**
     * Get registration summary (births, marriages, divorces, deaths)
     * Note: These tables may not exist yet, so we handle gracefully
     * @return array Registration statistics
     */
    public function getRegistrationSummary() {
        try {
            $summary = [
                'births' => 0,
                'marriages' => 0,
                'divorces' => 0,
                'deaths' => 0
            ];
            
            // Check if births table exists
            $tables = $this->getExistingTables();
            
            if (in_array('births', $tables)) {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM births 
                    WHERE deleted_at IS NULL
                ");
                $stmt->execute();
                $summary['births'] = (int)$stmt->fetch()['count'];
            }
            
            if (in_array('marriages', $tables)) {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM marriages 
                    WHERE deleted_at IS NULL
                ");
                $stmt->execute();
                $summary['marriages'] = (int)$stmt->fetch()['count'];
            }
            
            if (in_array('divorces', $tables)) {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM divorces 
                    WHERE deleted_at IS NULL
                ");
                $stmt->execute();
                $summary['divorces'] = (int)$stmt->fetch()['count'];
            }
            
            if (in_array('deaths', $tables)) {
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM deaths 
                    WHERE deleted_at IS NULL
                ");
                $stmt->execute();
                $summary['deaths'] = (int)$stmt->fetch()['count'];
            }
            
            return $summary;
        } catch (Exception $e) {
            error_log("Report Service Error (getRegistrationSummary): " . $e->getMessage());
            // Return zeros if tables don't exist
            return [
                'births' => 0,
                'marriages' => 0,
                'divorces' => 0,
                'deaths' => 0
            ];
        }
    }
    
    /**
     * Get user activity summary
     * @return array User statistics
     */
    public function getUserSummary() {
        try {
            // Total users (excluding soft-deleted)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM nira_users 
                WHERE deleted_at IS NULL
            ");
            $stmt->execute();
            $total = (int)$stmt->fetch()['total'];
            
            // Users by role
            $stmt = $this->conn->prepare("
                SELECT r.name as role_name, COUNT(u.id) as count
                FROM nira_users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.deleted_at IS NULL
                GROUP BY r.name
            ");
            $stmt->execute();
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $usersByRole = [];
            foreach ($roles as $role) {
                $usersByRole[$role['role_name'] ?: 'UNASSIGNED'] = (int)$role['count'];
            }
            
            // Active vs deleted users
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(CASE WHEN deleted_at IS NULL AND status = 'ACTIVE' THEN 1 END) as active,
                    COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as deleted
                FROM nira_users
            ");
            $stmt->execute();
            $status = $stmt->fetch();
            
            return [
                'total' => $total,
                'byRole' => $usersByRole,
                'active' => (int)$status['active'],
                'deleted' => (int)$status['deleted']
            ];
        } catch (Exception $e) {
            error_log("Report Service Error (getUserSummary): " . $e->getMessage());
            throw new Exception('Failed to generate user summary: ' . $e->getMessage());
        }
    }
    
    /**
     * Get time-based registration statistics
     * @param string $period 'day', 'month', or 'year'
     * @return array Time-based statistics
     */
    public function getTimeBasedReport($period = 'month') {
        try {
            // Validate and sanitize period
            $period = strtolower($period);
            if (!in_array($period, ['day', 'month', 'year'])) {
                $period = 'month';
            }
            
            $results = [];
            
            // Get safe SQL grouping based on period
            $groupByClause = $this->getGroupByClause($period);
            $selectLabel = $this->getSelectLabel($period);
            
            // Citizens by registration date
            try {
                $sql = "
                    SELECT 
                        {$selectLabel} as label,
                        COUNT(*) as total
                    FROM citizens
                    WHERE deleted_at IS NULL
                    GROUP BY {$groupByClause}
                    ORDER BY label DESC
                    LIMIT 12
                ";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $results['citizens'] = array_map(function($row) {
                    return [
                        'label' => $row['label'],
                        'total' => (int)$row['total']
                    ];
                }, $citizens);
            } catch (PDOException $e) {
                error_log("Report Service Error (getTimeBasedReport - citizens): " . $e->getMessage());
                $results['citizens'] = [];
            }
            
            // Check for registration tables and add their stats
            $tables = $this->getExistingTables();
            
            if (in_array('births', $tables)) {
                try {
                    $sql = "
                        SELECT 
                            {$selectLabel} as label,
                            COUNT(*) as total
                        FROM births
                        WHERE deleted_at IS NULL
                        GROUP BY {$groupByClause}
                        ORDER BY label DESC
                        LIMIT 12
                    ";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute();
                    $births = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $results['births'] = array_map(function($row) {
                        return [
                            'label' => $row['label'],
                            'total' => (int)$row['total']
                        ];
                    }, $births);
                } catch (PDOException $e) {
                    error_log("Report Service Error (getTimeBasedReport - births): " . $e->getMessage());
                    $results['births'] = [];
                }
            }
            
            if (in_array('marriages', $tables)) {
                try {
                    $sql = "
                        SELECT 
                            {$selectLabel} as label,
                            COUNT(*) as total
                        FROM marriages
                        WHERE deleted_at IS NULL
                        GROUP BY {$groupByClause}
                        ORDER BY label DESC
                        LIMIT 12
                    ";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute();
                    $marriages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $results['marriages'] = array_map(function($row) {
                        return [
                            'label' => $row['label'],
                            'total' => (int)$row['total']
                        ];
                    }, $marriages);
                } catch (PDOException $e) {
                    error_log("Report Service Error (getTimeBasedReport - marriages): " . $e->getMessage());
                    $results['marriages'] = [];
                }
            }
            
            if (in_array('divorces', $tables)) {
                try {
                    $sql = "
                        SELECT 
                            {$selectLabel} as label,
                            COUNT(*) as total
                        FROM divorces
                        WHERE deleted_at IS NULL
                        GROUP BY {$groupByClause}
                        ORDER BY label DESC
                        LIMIT 12
                    ";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute();
                    $divorces = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $results['divorces'] = array_map(function($row) {
                        return [
                            'label' => $row['label'],
                            'total' => (int)$row['total']
                        ];
                    }, $divorces);
                } catch (PDOException $e) {
                    error_log("Report Service Error (getTimeBasedReport - divorces): " . $e->getMessage());
                    $results['divorces'] = [];
                }
            }
            
            if (in_array('deaths', $tables)) {
                try {
                    $sql = "
                        SELECT 
                            {$selectLabel} as label,
                            COUNT(*) as total
                        FROM deaths
                        WHERE deleted_at IS NULL
                        GROUP BY {$groupByClause}
                        ORDER BY label DESC
                        LIMIT 12
                    ";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute();
                    $deaths = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $results['deaths'] = array_map(function($row) {
                        return [
                            'label' => $row['label'],
                            'total' => (int)$row['total']
                        ];
                    }, $deaths);
                } catch (PDOException $e) {
                    error_log("Report Service Error (getTimeBasedReport - deaths): " . $e->getMessage());
                    $results['deaths'] = [];
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Report Service Error (getTimeBasedReport): " . $e->getMessage());
            throw new Exception('Failed to generate time-based report: ' . $e->getMessage());
        }
    }
    
    /**
     * Get list of existing tables in database
     * @return array List of table names
     */
    private function getExistingTables() {
        try {
            $stmt = $this->conn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return array_map('strtolower', $tables);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get safe GROUP BY clause based on period
     * @param string $period 'day', 'month', or 'year'
     * @return string Safe SQL GROUP BY clause
     */
    private function getGroupByClause($period) {
        switch (strtolower($period)) {
            case 'day':
                return 'DATE(created_at)';
            case 'year':
                return 'YEAR(created_at)';
            case 'month':
            default:
                return 'YEAR(created_at), MONTH(created_at)';
        }
    }
    
    /**
     * Get SELECT label expression based on period
     * @param string $period 'day', 'month', or 'year'
     * @return string Safe SQL SELECT expression
     */
    private function getSelectLabel($period) {
        switch (strtolower($period)) {
            case 'day':
                return "DATE_FORMAT(created_at, '%Y-%m-%d')";
            case 'year':
                return "YEAR(created_at)";
            case 'month':
            default:
                return "DATE_FORMAT(created_at, '%Y-%m')";
        }
    }
}

