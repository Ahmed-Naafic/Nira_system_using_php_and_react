<?php
/**
 * Notice Service for NIRA System
 * Handles system notices (manual announcements)
 */

require_once __DIR__ . '/../config/database.php';

class NoticeService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create a new notice
     * @param array $data Notice data
     * @param int $createdBy User ID creating the notice
     * @return array Created notice data
     * @throws Exception On validation error
     */
    public function createNotice($data, $createdBy) {
        // Validate required fields
        if (!isset($data['title']) || empty(trim($data['title']))) {
            throw new Exception('Title is required');
        }
        
        if (!isset($data['message']) || empty(trim($data['message']))) {
            throw new Exception('Message is required');
        }
        
        // Validate type
        $type = isset($data['type']) ? strtoupper(trim($data['type'])) : 'INFO';
        if (!in_array($type, ['INFO', 'WARNING', 'ALERT'])) {
            $type = 'INFO';
        }
        
        $title = trim($data['title']);
        $message = trim($data['message']);
        
        // Validate expires_at if provided
        $expiresAt = null;
        if (isset($data['expiresAt']) && !empty(trim($data['expiresAt']))) {
            $expiresAt = trim($data['expiresAt']);
            
            // Handle different date formats
            // Format 1: YYYY-MM-DD HH:MM:SS (MySQL datetime)
            // Format 2: YYYY-MM-DDTHH:MM (HTML datetime-local)
            // Format 3: YYYY-MM-DD (date only)
            
            // Convert datetime-local format (YYYY-MM-DDTHH:MM) to MySQL format
            if (strpos($expiresAt, 'T') !== false) {
                $expiresAt = str_replace('T', ' ', $expiresAt);
                // Add seconds if not present
                if (substr_count($expiresAt, ':') === 1) {
                    $expiresAt .= ':00';
                }
            }
            
            // Validate date format (YYYY-MM-DD HH:MM:SS or YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $expiresAt)) {
                throw new Exception('Invalid expires_at format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS');
            }
            
            // Validate that the date is in the future
            $expiresTimestamp = strtotime($expiresAt);
            if ($expiresTimestamp === false) {
                throw new Exception('Invalid expires_at date');
            }
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_notices (title, message, type, created_by, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$title, $message, $type, $createdBy, $expiresAt]);
            
            $noticeId = $this->conn->lastInsertId();
            return $this->getNoticeById($noticeId);
        } catch (PDOException $e) {
            error_log("Notice Service Error (createNotice): " . $e->getMessage());
            throw new Exception('Failed to create notice: ' . $e->getMessage());
        }
    }
    
    /**
     * List active notices (non-deleted and not expired)
     * @return array Array of active notices
     */
    public function listActiveNotices() {
        try {
            // Check if table exists
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'system_notices'");
            if ($checkTable->rowCount() === 0) {
                error_log("Notice Service Error: system_notices table does not exist. Please run migrations.");
                return [];
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    n.id,
                    n.title,
                    n.message,
                    n.type,
                    n.created_by,
                    n.created_at,
                    n.expires_at,
                    u.username as created_by_username
                FROM system_notices n
                INNER JOIN nira_users u ON n.created_by = u.id
                WHERE n.deleted_at IS NULL
                AND (n.expires_at IS NULL OR n.expires_at > NOW())
                ORDER BY n.created_at DESC
            ");
            
            $stmt->execute();
            $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(function($notice) {
                return [
                    'id' => (int)$notice['id'],
                    'title' => $notice['title'],
                    'message' => $notice['message'],
                    'type' => $notice['type'],
                    'createdBy' => (int)$notice['created_by'],
                    'createdByUsername' => $notice['created_by_username'],
                    'createdAt' => $notice['created_at'],
                    'expiresAt' => $notice['expires_at']
                ];
            }, $notices);
        } catch (Exception $e) {
            error_log("Notice Service Error (listActiveNotices): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Soft delete a notice
     * @param int $noticeId Notice ID
     * @return bool Success status
     */
    public function deleteNotice($noticeId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE system_notices 
                SET deleted_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ");
            
            $stmt->execute([$noticeId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Notice Service Error (deleteNotice): " . $e->getMessage());
            throw new Exception('Failed to delete notice: ' . $e->getMessage());
        }
    }
    
    /**
     * Get notice by ID
     * @param int $noticeId Notice ID
     * @return array|null Notice data or null if not found
     */
    private function getNoticeById($noticeId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    n.id,
                    n.title,
                    n.message,
                    n.type,
                    n.created_by,
                    n.created_at,
                    n.expires_at,
                    u.username as created_by_username
                FROM system_notices n
                INNER JOIN nira_users u ON n.created_by = u.id
                WHERE n.id = ?
            ");
            
            $stmt->execute([$noticeId]);
            $notice = $stmt->fetch();
            
            if (!$notice) {
                return null;
            }
            
            return [
                'id' => (int)$notice['id'],
                'title' => $notice['title'],
                'message' => $notice['message'],
                'type' => $notice['type'],
                'createdBy' => (int)$notice['created_by'],
                'createdByUsername' => $notice['created_by_username'],
                'createdAt' => $notice['created_at'],
                'expiresAt' => $notice['expires_at']
            ];
        } catch (Exception $e) {
            error_log("Notice Service Error (getNoticeById): " . $e->getMessage());
            return null;
        }
    }
}

