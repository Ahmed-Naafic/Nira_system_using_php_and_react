<?php
/**
 * User Service for NIRA System
 * Provides business logic for user management operations
 */

require_once __DIR__ . '/../config/database.php';

class UserService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create a new user
     * @param array $data User data (username, password, roleId)
     * @return array Created user data (without password)
     * @throws Exception On validation error or duplicate username
     */
    public function createUser($data) {
        // Validate required fields
        if (!isset($data['username']) || empty(trim($data['username']))) {
            throw new Exception('Username is required');
        }
        
        if (!isset($data['password']) || empty(trim($data['password']))) {
            throw new Exception('Password is required');
        }
        
        if (!isset($data['role_id']) || empty($data['role_id'])) {
            throw new Exception('Role ID is required');
        }
        
        $username = trim($data['username']);
        $password = trim($data['password']);
        $roleId = (int)$data['role_id'];
        
        // Validate username length
        if (strlen($username) < 3 || strlen($username) > 100) {
            throw new Exception('Username must be between 3 and 100 characters');
        }
        
        // Validate password length
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }
        
        // Check if role exists
        $stmt = $this->conn->prepare("SELECT id, name FROM roles WHERE id = ?");
        $stmt->execute([$roleId]);
        $role = $stmt->fetch();
        
        if (!$role) {
            throw new Exception('Invalid role ID');
        }
        
        // Check if username already exists
        $stmt = $this->conn->prepare("SELECT id FROM nira_users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists', 409);
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        try {
            // Check if status column exists
            $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
            $hasStatus = $checkStatus->rowCount() > 0;
            
            if ($hasStatus) {
                // Use role_id and status
                $stmt = $this->conn->prepare("
                    INSERT INTO nira_users (username, password, role_id, status)
                    VALUES (?, ?, ?, 'ACTIVE')
                ");
                $stmt->execute([$username, $hashedPassword, $roleId]);
            } else {
                // Fallback to old schema (role ENUM)
                // Map role_id to role name
                $roleName = strtoupper($role['name']);
                if (!in_array($roleName, ['ADMIN', 'OFFICER'])) {
                    $roleName = 'OFFICER'; // Default fallback
                }
                $stmt = $this->conn->prepare("
                    INSERT INTO nira_users (username, password, role)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$username, $hashedPassword, $roleName]);
            }
            
            $userId = $this->conn->lastInsertId();
            
            // Return created user (without password)
            return $this->getUserById($userId);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception('Username already exists', 409);
            }
            throw new Exception('Failed to create user: ' . $e->getMessage());
        }
    }
    
    /**
     * List all users (excluding deleted users)
     * @return array Array of users (without passwords)
     */
    public function listUsers() {
        // Check if status column exists to filter deleted users
        $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
        $hasStatus = $checkStatus->rowCount() > 0;
        
        if ($hasStatus) {
            // Filter out deleted users
            $stmt = $this->conn->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.role_id,
                    u.role,
                    u.status,
                    u.created_at,
                    r.name as role_name,
                    r.description as role_description
                FROM nira_users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE (u.status IS NULL OR u.status != 'DELETED')
                ORDER BY u.created_at DESC
            ");
        } else {
            // No status column, return all users
            $stmt = $this->conn->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.role_id,
                    u.role,
                    u.status,
                    u.created_at,
                    r.name as role_name,
                    r.description as role_description
                FROM nira_users u
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC
            ");
        }
        
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'role' => [
                    'id' => $user['role_id'] ? (int)$user['role_id'] : null,
                    'name' => $user['role_name'] ?? $user['role'] ?? 'OFFICER',
                    'description' => $user['role_description'] ?? null
                ],
                'status' => $user['status'] ?? 'ACTIVE',
                'createdAt' => $user['created_at']
            ];
        }
        
        return $results;
    }
    
    /**
     * Get user by ID
     * @param int $id User ID
     * @return array|null User data (without password) or null if not found
     */
    public function getUserById($id) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.id,
                u.username,
                u.role_id,
                u.role,
                u.status,
                u.created_at,
                r.name as role_name,
                r.description as role_description
            FROM nira_users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        return [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => [
                'id' => $user['role_id'] ? (int)$user['role_id'] : null,
                'name' => $user['role_name'] ?? $user['role'] ?? 'OFFICER',
                'description' => $user['role_description'] ?? null
            ],
            'status' => $user['status'] ?? 'ACTIVE',
            'createdAt' => $user['created_at']
        ];
    }
    
    /**
     * Update user information
     * @param int $id User ID
     * @param array $data Update data (username, roleId)
     * @return array Updated user data
     * @throws Exception On validation error
     */
    public function updateUser($id, $data) {
        // Get existing user
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        $updates = [];
        $params = [];
        
        // Update username if provided
        if (isset($data['username'])) {
            $username = trim($data['username']);
            if (strlen($username) < 3 || strlen($username) > 100) {
                throw new Exception('Username must be between 3 and 100 characters');
            }
            
            // Check if username is already taken by another user
            $stmt = $this->conn->prepare("SELECT id FROM nira_users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                throw new Exception('Username already exists', 409);
            }
            
            $updates[] = "username = ?";
            $params[] = $username;
        }
        
        // Update role if provided
        if (isset($data['role_id'])) {
            $roleId = (int)$data['role_id'];
            
            // Check if role exists
            $stmt = $this->conn->prepare("SELECT id FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid role ID');
            }
            
            // Check if status column exists
            $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
            $hasStatus = $checkStatus->rowCount() > 0;
            
            if ($hasStatus) {
                $updates[] = "role_id = ?";
                $params[] = $roleId;
            } else {
                // Fallback: get role name
                $stmt = $this->conn->prepare("SELECT name FROM roles WHERE id = ?");
                $stmt->execute([$roleId]);
                $role = $stmt->fetch();
                if ($role) {
                    $roleName = strtoupper($role['name']);
                    if (!in_array($roleName, ['ADMIN', 'OFFICER'])) {
                        $roleName = 'OFFICER';
                    }
                    $updates[] = "role = ?";
                    $params[] = $roleName;
                }
            }
        }
        
        if (empty($updates)) {
            throw new Exception('No fields to update');
        }
        
        $params[] = $id;
        $sql = "UPDATE nira_users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $this->getUserById($id);
    }
    
    /**
     * Change user status (ACTIVE/DISABLED)
     * @param int $id User ID
     * @param string $status New status
     * @param int $currentUserId Current user ID (to prevent self-disable)
     * @return array Updated user data
     * @throws Exception On validation error
     */
    public function changeUserStatus($id, $status, $currentUserId = null) {
        // Validate status
        $status = strtoupper(trim($status));
        if (!in_array($status, ['ACTIVE', 'DISABLED'])) {
            throw new Exception("Status must be 'ACTIVE' or 'DISABLED'");
        }
        
        // Prevent disabling own account
        if ($currentUserId && $id == $currentUserId && $status === 'DISABLED') {
            throw new Exception('Cannot disable your own account', 403);
        }
        
        // Get user
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        // Check if status column exists
        $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
        if ($checkStatus->rowCount() == 0) {
            throw new Exception('Status column does not exist in database');
        }
        
        // Prevent disabling last ADMIN user
        if ($status === 'DISABLED' && $user['role']['name'] === 'ADMIN') {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM nira_users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE (r.name = 'ADMIN' OR u.role = 'ADMIN') 
                AND u.status = 'ACTIVE' 
                AND u.id != ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                throw new Exception('Cannot disable the last active admin user', 403);
            }
        }
        
        // Update status
        $stmt = $this->conn->prepare("UPDATE nira_users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        return $this->getUserById($id);
    }
    
    /**
     * Reset user password
     * @param int $id User ID
     * @param string $newPassword New password
     * @return bool Success
     * @throws Exception On validation error
     */
    public function resetUserPassword($id, $newPassword) {
        if (empty(trim($newPassword))) {
            throw new Exception('Password is required');
        }
        
        if (strlen($newPassword) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }
        
        // Check if user exists
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $this->conn->prepare("UPDATE nira_users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $id]);
        
        return true;
    }
    
    /**
     * Delete user (soft delete - sets status to DELETED)
     * @param int $userId User ID to delete
     * @param int $currentUserId Current user ID (to prevent self-deletion)
     * @return bool Success
     * @throws Exception On validation error
     */
    public function deleteUser($userId, $currentUserId = null) {
        // Prevent deleting yourself
        if ($currentUserId && $userId == $currentUserId) {
            throw new Exception('Cannot delete your own account', 403);
        }
        
        // Get user
        $user = $this->getUserById($userId);
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        // Check if status column exists
        $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
        if ($checkStatus->rowCount() == 0) {
            throw new Exception('Status column does not exist in database');
        }
        
        // Prevent deleting last ADMIN user
        if ($user['role']['name'] === 'ADMIN') {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM nira_users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE (r.name = 'ADMIN' OR u.role = 'ADMIN') 
                AND u.status = 'ACTIVE' 
                AND u.id != ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                throw new Exception('Cannot delete the last active admin user', 403);
            }
        }
        
        // Soft delete: Set status to DELETED (do NOT remove database row)
        try {
            $stmt = $this->conn->prepare("UPDATE nira_users SET status = 'DELETED' WHERE id = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            // If DELETED is not in ENUM, the database will error
            // This is expected if the column hasn't been altered yet
            throw new Exception('Delete operation failed. Status column may need to support DELETED value: ' . $e->getMessage());
        }
    }
}

