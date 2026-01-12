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
        
        // Check if username already exists (excluding deleted users)
        $stmt = $this->conn->prepare("SELECT id FROM nira_users WHERE username = ? AND deleted_at IS NULL");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists', 409);
        }
        
        // Get optional fields
        $phoneNumber = isset($data['phoneNumber']) ? trim($data['phoneNumber']) : null;
        $profilePicturePath = isset($data['profilePicturePath']) ? trim($data['profilePicturePath']) : null;
        
        // Validate phone number format if provided
        if ($phoneNumber && !preg_match('/^[+]?[0-9]{8,15}$/', $phoneNumber)) {
            throw new Exception('Invalid phone number format. Use 8-15 digits with optional country code');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        try {
            // Check if status column exists
            $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
            $hasStatus = $checkStatus->rowCount() > 0;
            
            // Check if phone_number and profile_picture_path columns exist
            $checkPhone = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'phone_number'");
            $hasPhone = $checkPhone->rowCount() > 0;
            $checkProfile = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'profile_picture_path'");
            $hasProfile = $checkProfile->rowCount() > 0;
            
            if ($hasStatus) {
                if ($hasPhone && $hasProfile) {
                    // Use all columns including phone and profile picture
                    $stmt = $this->conn->prepare("
                        INSERT INTO nira_users (username, password, phone_number, profile_picture_path, role_id, status)
                        VALUES (?, ?, ?, ?, ?, 'ACTIVE')
                    ");
                    $stmt->execute([$username, $hashedPassword, $phoneNumber, $profilePicturePath, $roleId]);
                } else {
                    // Use role_id and status without phone/profile
                    $stmt = $this->conn->prepare("
                        INSERT INTO nira_users (username, password, role_id, status)
                        VALUES (?, ?, ?, 'ACTIVE')
                    ");
                    $stmt->execute([$username, $hashedPassword, $roleId]);
                }
            } else {
                // Fallback to old schema (role ENUM)
                // Map role_id to role name
                $roleName = strtoupper($role['name']);
                if (!in_array($roleName, ['ADMIN', 'OFFICER'])) {
                    $roleName = 'OFFICER'; // Default fallback
                }
                if ($hasPhone && $hasProfile) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO nira_users (username, password, phone_number, profile_picture_path, role)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $hashedPassword, $phoneNumber, $profilePicturePath, $roleName]);
                } else {
                    $stmt = $this->conn->prepare("
                        INSERT INTO nira_users (username, password, role)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$username, $hashedPassword, $roleName]);
                }
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
        // Filter out deleted users using deleted_at (same pattern as citizens)
        $stmt = $this->conn->prepare("
            SELECT 
                u.id,
                u.username,
                u.phone_number,
                u.profile_picture_path,
                u.role_id,
                u.role,
                u.status,
                u.created_at,
                r.name as role_name,
                r.description as role_description
            FROM nira_users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.deleted_at IS NULL
            ORDER BY u.created_at DESC
        ");
        
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $results = [];
        foreach ($users as $user) {
            $result = [
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
            
            // Include phone number and profile picture if available
            if (isset($user['phone_number']) && !empty($user['phone_number'])) {
                $result['phoneNumber'] = $user['phone_number'];
            }
            if (isset($user['profile_picture_path']) && !empty($user['profile_picture_path'])) {
                require_once __DIR__ . '/../utils/file_upload.php';
                $result['profilePicturePath'] = $user['profile_picture_path'];
                $result['profilePictureUrl'] = FileUpload::getFileUrl($user['profile_picture_path']);
            }
            
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Get user by ID
     * @param int $id User ID
     * @param bool $includeDeleted Include deleted users (default: false)
     * @return array|null User data (without password) or null if not found
     */
    public function getUserById($id, $includeDeleted = false) {
        $sql = "
            SELECT 
                u.id,
                u.username,
                u.phone_number,
                u.profile_picture_path,
                u.role_id,
                u.role,
                u.status,
                u.created_at,
                u.deleted_at,
                r.name as role_name,
                r.description as role_description
            FROM nira_users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ";
        
        if (!$includeDeleted) {
            $sql .= " AND u.deleted_at IS NULL";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        $result = [
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
        
        // Include phone number and profile picture if available
        if (isset($user['phone_number']) && !empty($user['phone_number'])) {
            $result['phoneNumber'] = $user['phone_number'];
        }
        if (isset($user['profile_picture_path']) && !empty($user['profile_picture_path'])) {
            require_once __DIR__ . '/../utils/file_upload.php';
            $result['profilePicturePath'] = $user['profile_picture_path'];
            $result['profilePictureUrl'] = FileUpload::getFileUrl($user['profile_picture_path']);
        }
        
        // Include deletedAt if present
        if (isset($user['deleted_at'])) {
            $result['deletedAt'] = $user['deleted_at'];
        }
        
        return $result;
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
            
            // Check if username is already taken by another user (excluding deleted)
            $stmt = $this->conn->prepare("SELECT id FROM nira_users WHERE username = ? AND id != ? AND deleted_at IS NULL");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                throw new Exception('Username already exists', 409);
            }
            
            $updates[] = "username = ?";
            $params[] = $username;
        }
        
        // Update phone number if provided
        if (isset($data['phoneNumber'])) {
            $phoneNumber = trim($data['phoneNumber']);
            if (!empty($phoneNumber) && !preg_match('/^[+]?[0-9]{8,15}$/', $phoneNumber)) {
                throw new Exception('Invalid phone number format. Use 8-15 digits with optional country code');
            }
            
            // Check if phone_number column exists
            $checkPhone = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'phone_number'");
            if ($checkPhone->rowCount() > 0) {
                $updates[] = "phone_number = ?";
                $params[] = empty($phoneNumber) ? null : $phoneNumber;
            }
        }
        
        // Update profile picture if provided
        if (isset($data['profilePicturePath'])) {
            $profilePicturePath = trim($data['profilePicturePath']);
            
            // Check if profile_picture_path column exists
            $checkProfile = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'profile_picture_path'");
            if ($checkProfile->rowCount() > 0) {
                $updates[] = "profile_picture_path = ?";
                $params[] = empty($profilePicturePath) ? null : $profilePicturePath;
            }
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
        // Only update if not deleted (same pattern as citizens)
        $sql = "UPDATE nira_users SET " . implode(', ', $updates) . " WHERE id = ? AND deleted_at IS NULL";
        
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
        
        // Prevent disabling last ADMIN user (excluding deleted)
        if ($status === 'DISABLED' && $user['role']['name'] === 'ADMIN') {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM nira_users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE (r.name = 'ADMIN' OR u.role = 'ADMIN') 
                AND u.status = 'ACTIVE' 
                AND u.deleted_at IS NULL
                AND u.id != ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                throw new Exception('Cannot disable the last active admin user', 403);
            }
        }
        
        // Update status (only if not deleted)
        $stmt = $this->conn->prepare("UPDATE nira_users SET status = ? WHERE id = ? AND deleted_at IS NULL");
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
        
        // Update password (only if not deleted)
        $stmt = $this->conn->prepare("UPDATE nira_users SET password = ? WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$hashedPassword, $id]);
        
        return true;
    }
    
    /**
     * Soft delete user (move to trash) - matches citizen delete pattern
     * @param int $userId User ID to delete
     * @param int $currentUserId Current user ID (to prevent self-deletion)
     * @return bool Success
     * @throws Exception On error or user not found
     */
    public function deleteUser($userId, $currentUserId = null) {
        // Check if user exists and is not already deleted
        $existing = $this->getUserById($userId, false);
        if (!$existing) {
            throw new Exception('User not found', 404);
        }
        
        // Prevent deleting yourself
        if ($currentUserId && $userId == $currentUserId) {
            throw new Exception('Cannot delete your own account', 403);
        }
        
        // Prevent deleting last ADMIN user
        if ($existing['role']['name'] === 'ADMIN') {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM nira_users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE (r.name = 'ADMIN' OR u.role = 'ADMIN') 
                AND u.deleted_at IS NULL
                AND u.id != ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                throw new Exception('Cannot delete the last active admin user', 403);
            }
        }
        
        // Soft delete: set deleted_at timestamp and status = 'DELETED' (matches citizen pattern)
        // Check if status column exists for backward compatibility
        $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
        $hasStatus = $checkStatus->rowCount() > 0;
        
        if ($hasStatus) {
            $stmt = $this->conn->prepare("
                UPDATE nira_users 
                SET deleted_at = CURRENT_TIMESTAMP, status = 'DELETED'
                WHERE id = ? AND deleted_at IS NULL
            ");
        } else {
            $stmt = $this->conn->prepare("
                UPDATE nira_users 
                SET deleted_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND deleted_at IS NULL
            ");
        }
        
        try {
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('Failed to delete user: ' . $e->getMessage());
        }
    }
    
    /**
     * List deleted users (trash)
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Array of deleted users
     */
    public function listTrash($limit = 50, $offset = 0) {
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);
        
        $stmt = $this->conn->prepare("
            SELECT 
                u.id,
                u.username,
                u.phone_number,
                u.profile_picture_path,
                u.role_id,
                u.role,
                u.status,
                u.created_at,
                u.deleted_at,
                r.name as role_name,
                r.description as role_description
            FROM nira_users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.deleted_at IS NOT NULL
            ORDER BY u.deleted_at DESC, u.id DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        $users = $stmt->fetchAll();
        
        $results = [];
        foreach ($users as $user) {
            $result = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'role' => [
                    'id' => $user['role_id'] ? (int)$user['role_id'] : null,
                    'name' => $user['role_name'] ?? $user['role'] ?? 'OFFICER',
                    'description' => $user['role_description'] ?? null
                ],
                'status' => $user['status'] ?? 'ACTIVE',
                'createdAt' => $user['created_at'],
                'deletedAt' => $user['deleted_at']
            ];
            
            // Include phone number and profile picture if available
            if (isset($user['phone_number']) && !empty($user['phone_number'])) {
                $result['phoneNumber'] = $user['phone_number'];
            }
            if (isset($user['profile_picture_path']) && !empty($user['profile_picture_path'])) {
                require_once __DIR__ . '/../utils/file_upload.php';
                $result['profilePicturePath'] = $user['profile_picture_path'];
                $result['profilePictureUrl'] = FileUpload::getFileUrl($user['profile_picture_path']);
            }
            
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Restore user from trash
     * @param int $userId User ID
     * @return bool Success
     * @throws Exception On error or user not found
     */
    public function restoreUser($userId) {
        // Check if user exists in trash
        $existing = $this->getUserById($userId, true);
        if (!$existing || !isset($existing['deletedAt']) || !$existing['deletedAt']) {
            throw new Exception('User not found in trash', 404);
        }
        
        // Restore: clear deleted_at and restore status to ACTIVE
        $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
        $hasStatus = $checkStatus->rowCount() > 0;
        
        if ($hasStatus) {
            $stmt = $this->conn->prepare("
                UPDATE nira_users 
                SET deleted_at = NULL, status = 'ACTIVE'
                WHERE id = ? AND deleted_at IS NOT NULL
            ");
        } else {
            $stmt = $this->conn->prepare("
                UPDATE nira_users 
                SET deleted_at = NULL 
                WHERE id = ? AND deleted_at IS NOT NULL
            ");
        }
        
        try {
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('Failed to restore user: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore all users from trash
     * @return int Number of users restored
     */
    public function restoreAllUsers() {
        // Restore: clear deleted_at and restore status to ACTIVE
        $checkStatus = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'status'");
        $hasStatus = $checkStatus->rowCount() > 0;
        
        if ($hasStatus) {
            $stmt = $this->conn->prepare("
                UPDATE nira_users 
                SET deleted_at = NULL, status = 'ACTIVE'
                WHERE deleted_at IS NOT NULL
            ");
        } else {
            $stmt = $this->conn->prepare("
                UPDATE nira_users 
                SET deleted_at = NULL 
                WHERE deleted_at IS NOT NULL
            ");
        }
        
        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Failed to restore all users: ' . $e->getMessage());
        }
    }
    
    /**
     * Permanently delete user from trash
     * @param int $userId User ID
     * @return bool Success
     * @throws Exception On error or user not found
     */
    public function permanentlyDeleteUser($userId) {
        // Check if user exists in trash
        $existing = $this->getUserById($userId, true);
        if (!$existing || !isset($existing['deletedAt']) || !$existing['deletedAt']) {
            throw new Exception('User not found in trash', 404);
        }
        
        // Permanent delete: remove from database
        $stmt = $this->conn->prepare("
            DELETE FROM nira_users 
            WHERE id = ? AND deleted_at IS NOT NULL
        ");
        
        try {
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('Failed to permanently delete user: ' . $e->getMessage());
        }
    }
    
    /**
     * Permanently delete all users from trash
     * @return int Number of users deleted
     */
    public function permanentlyDeleteAllUsers() {
        $stmt = $this->conn->prepare("
            DELETE FROM nira_users 
            WHERE deleted_at IS NOT NULL
        ");
        
        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Failed to permanently delete all users: ' . $e->getMessage());
        }
    }
    
    /**
     * Update user profile picture
     * @param int $id User ID
     * @param string $profilePicturePath Profile picture path
     * @return array Updated user data
     * @throws Exception On error or user not found
     */
    public function updateUserProfilePicture($id, $profilePicturePath) {
        // Check if user exists
        $user = $this->getUserById($id);
        if (!$user) {
            throw new Exception('User not found', 404);
        }
        
        // Check if profile_picture_path column exists
        $checkProfile = $this->conn->query("SHOW COLUMNS FROM nira_users LIKE 'profile_picture_path'");
        if ($checkProfile->rowCount() == 0) {
            throw new Exception('Profile picture column does not exist in database');
        }
        
        // Update profile picture path (only if not deleted)
        $stmt = $this->conn->prepare("UPDATE nira_users SET profile_picture_path = ? WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$profilePicturePath, $id]);
        
        return $this->getUserById($id);
    }
}

