<?php
/**
 * Citizen Service for NIRA System
 * Provides business logic for citizen operations
 */

require_once __DIR__ . '/../config/database.php';

class CitizenService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create a new citizen
     * @param array $data Citizen data
     * @return array Created citizen data
     * @throws Exception On validation error or duplicate national ID
     */
    public function createCitizen($data) {
        // Validate required fields
        $requiredFields = ['firstName', 'lastName', 'gender', 'dateOfBirth', 'placeOfBirth'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Validate gender
        $gender = strtoupper(trim($data['gender']));
        if (!in_array($gender, ['MALE', 'FEMALE'])) {
            throw new Exception("Gender must be 'MALE' or 'FEMALE'");
        }
        
        // Validate date format
        $dateOfBirth = trim($data['dateOfBirth']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
            throw new Exception('Date of birth must be in YYYY-MM-DD format');
        }
        
        // Validate date is valid
        $dateParts = explode('-', $dateOfBirth);
        if (!checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
            throw new Exception('Invalid date of birth');
        }
        
        // Validate date range: 100 years ago to today
        $today = date('Y-m-d');
        $todayTimestamp = strtotime($today);
        $dateOfBirthTimestamp = strtotime($dateOfBirth);
        
        // Check if date is in the future
        if ($dateOfBirthTimestamp > $todayTimestamp) {
            throw new Exception('Date of birth cannot be in the future');
        }
        
        // Check if date is more than 100 years ago
        $hundredYearsAgo = date('Y-m-d', strtotime('-100 years'));
        $hundredYearsAgoTimestamp = strtotime($hundredYearsAgo);
        
        if ($dateOfBirthTimestamp < $hundredYearsAgoTimestamp) {
            throw new Exception('Date of birth cannot be more than 100 years ago');
        }
        
        // Prepare data
        $firstName = trim($data['firstName']);
        $middleName = isset($data['middleName']) ? trim($data['middleName']) : null;
        $lastName = trim($data['lastName']);
        $placeOfBirth = trim($data['placeOfBirth']);
        $nationality = isset($data['nationality']) ? trim($data['nationality']) : 'Somali';
        
        // Generate unique national ID
        require_once __DIR__ . '/../utils/national_id_generator.php';
        $nationalId = NationalIdGenerator::generate($this->conn);
        
        // Insert citizen
        $stmt = $this->conn->prepare("
            INSERT INTO citizens 
            (national_id, first_name, middle_name, last_name, gender, date_of_birth, place_of_birth, nationality, status, image_path, document_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NULL, NULL)
        ");
        
        try {
            $stmt->execute([
                $nationalId,
                $firstName,
                $middleName,
                $lastName,
                $gender,
                $dateOfBirth,
                $placeOfBirth,
                $nationality
            ]);
            
            // Return created citizen using normalized structure
            $createdCitizen = $this->getCitizenByNationalId($nationalId);
            if (!$createdCitizen) {
                throw new Exception('Failed to retrieve created citizen');
            }
            return $createdCitizen;
        } catch (PDOException $e) {
            // Check for duplicate national_id
            if ($e->getCode() == 23000) {
                throw new Exception('National ID already exists', 409);
            }
            throw new Exception('Failed to create citizen: ' . $e->getMessage());
        }
    }
    
    /**
     * Get citizen by national ID
     * @param string $nationalId National ID
     * @return array|null Citizen data or null if not found
     */
    public function getCitizenByNationalId($nationalId, $includeDeleted = false) {
        $sql = "
            SELECT 
                id,
                national_id,
                first_name,
                middle_name,
                last_name,
                gender,
                date_of_birth,
                place_of_birth,
                nationality,
                status,
                image_path,
                document_path,
                created_at,
                deleted_at
            FROM citizens
            WHERE national_id = ?
        ";
        
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$nationalId]);
        $citizen = $stmt->fetch();
        
        if (!$citizen) {
            return null;
        }
        
        // Format response - standardized structure
        return $this->normalizeCitizen($citizen);
    }
    
    /**
     * Search citizens by name or national ID
     * @param string $query Search query
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Array of matching citizens
     */
    public function searchCitizens($query, $limit = 50, $offset = 0) {
        $searchTerm = '%' . trim($query) . '%';
        
        $stmt = $this->conn->prepare("
            SELECT 
                id,
                national_id,
                first_name,
                middle_name,
                last_name,
                gender,
                date_of_birth,
                place_of_birth,
                nationality,
                status,
                image_path,
                document_path,
                created_at,
                deleted_at
            FROM citizens
            WHERE 
                deleted_at IS NULL
                AND (
                    national_id LIKE ? OR
                    first_name LIKE ? OR
                    middle_name LIKE ? OR
                    last_name LIKE ? OR
                    CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?
                )
            ORDER BY last_name, first_name
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $limit,
            $offset
        ]);
        
        $citizens = $stmt->fetchAll();
        
        // Format results - standardized structure
        $results = [];
        foreach ($citizens as $citizen) {
            $results[] = $this->normalizeCitizen($citizen);
        }
        
        return $results;
    }
    
    /**
     * List citizens with pagination
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Array of citizens
     */
    public function listCitizens($limit = 50, $offset = 0) {
        // Validate limit and offset
        $limit = max(1, min(100, (int)$limit)); // Between 1 and 100
        $offset = max(0, (int)$offset);
        
        $stmt = $this->conn->prepare("
            SELECT 
                id,
                national_id,
                first_name,
                middle_name,
                last_name,
                gender,
                date_of_birth,
                place_of_birth,
                nationality,
                status,
                image_path,
                document_path,
                created_at,
                deleted_at
            FROM citizens
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC, last_name, first_name
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        $citizens = $stmt->fetchAll();
        
        // Format results - standardized structure
        $results = [];
        foreach ($citizens as $citizen) {
            $results[] = $this->normalizeCitizen($citizen);
        }
        
        return $results;
    }
    
    /**
     * Normalize citizen data from database to standard structure
     * @param array $citizen Raw citizen data from database
     * @return array Normalized citizen data
     */
    private function normalizeCitizen($citizen) {
        $normalized = [
            'id' => (int)$citizen['id'],
            'nationalId' => $citizen['national_id'],
            'firstName' => $citizen['first_name'],
            'middleName' => $citizen['middle_name'],
            'lastName' => $citizen['last_name'],
            'gender' => $citizen['gender'],
            'dateOfBirth' => $citizen['date_of_birth'],
            'placeOfBirth' => $citizen['place_of_birth'],
            'nationality' => $citizen['nationality'],
            'status' => $citizen['status'],
            'createdAt' => $citizen['created_at']
        ];
        
        // Include file paths if present
        if (isset($citizen['image_path']) && !empty($citizen['image_path'])) {
            require_once __DIR__ . '/../utils/file_upload.php';
            $normalized['imagePath'] = $citizen['image_path'];
            $normalized['imageUrl'] = FileUpload::getFileUrl($citizen['image_path']);
        }
        
        if (isset($citizen['document_path']) && !empty($citizen['document_path'])) {
            require_once __DIR__ . '/../utils/file_upload.php';
            $normalized['documentPath'] = $citizen['document_path'];
            $normalized['documentUrl'] = FileUpload::getFileUrl($citizen['document_path']);
        }
        
        // Include deletedAt if present
        if (isset($citizen['deleted_at'])) {
            $normalized['deletedAt'] = $citizen['deleted_at'];
        }
        
        return $normalized;
    }
    
    /**
     * Update citizen by national ID
     * @param string $nationalId National ID (immutable identifier)
     * @param array $data Update data (camelCase fields)
     * @return array Updated citizen data
     * @throws Exception On validation error or citizen not found
     */
    public function updateCitizen($nationalId, $data) {
        // Check if citizen exists
        $existing = $this->getCitizenByNationalId($nationalId);
        if (!$existing) {
            throw new Exception('Citizen not found', 404);
        }
        
        // National ID is immutable - remove if present
        if (isset($data['nationalId'])) {
            unset($data['nationalId']);
        }
        
        // Define allowed fields to update (camelCase from frontend)
        $allowedFields = [
            'firstName' => 'first_name',
            'middleName' => 'middle_name',
            'lastName' => 'last_name',
            'gender' => 'gender',
            'dateOfBirth' => 'date_of_birth',
            'placeOfBirth' => 'place_of_birth',
            'nationality' => 'nationality',
            'status' => 'status'
        ];
        
        // Build update fields
        $updateFields = [];
        $updateValues = [];
        
        foreach ($allowedFields as $camelField => $dbField) {
            if (isset($data[$camelField])) {
                $value = trim($data[$camelField]);
                
                // Validate required fields
                if (in_array($camelField, ['firstName', 'lastName', 'gender', 'dateOfBirth', 'placeOfBirth']) && empty($value)) {
                    throw new Exception("Field '{$camelField}' is required");
                }
                
                // Validate gender
                if ($camelField === 'gender') {
                    $value = strtoupper($value);
                    if (!in_array($value, ['MALE', 'FEMALE'])) {
                        throw new Exception("Gender must be 'MALE' or 'FEMALE'");
                    }
                }
                
                // Validate date format
                if ($camelField === 'dateOfBirth') {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        throw new Exception('Date of birth must be in YYYY-MM-DD format');
                    }
                    $dateParts = explode('-', $value);
                    if (!checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
                        throw new Exception('Invalid date of birth');
                    }
                    // Validate date range: 100 years ago to today
                    $today = date('Y-m-d');
                    $todayTimestamp = strtotime($today);
                    $dateOfBirthTimestamp = strtotime($value);
                    
                    // Check if date is in the future
                    if ($dateOfBirthTimestamp > $todayTimestamp) {
                        throw new Exception('Date of birth cannot be in the future');
                    }
                    
                    // Check if date is more than 100 years ago
                    $hundredYearsAgo = date('Y-m-d', strtotime('-100 years'));
                    $hundredYearsAgoTimestamp = strtotime($hundredYearsAgo);
                    
                    if ($dateOfBirthTimestamp < $hundredYearsAgoTimestamp) {
                        throw new Exception('Date of birth cannot be more than 100 years ago');
                    }
                }
                
                // Validate status
                if ($camelField === 'status') {
                    $value = strtoupper($value);
                    if (!in_array($value, ['ACTIVE', 'DECEASED'])) {
                        throw new Exception("Status must be 'ACTIVE' or 'DECEASED'");
                    }
                }
                
                $updateFields[] = "{$dbField} = ?";
                $updateValues[] = empty($value) && $camelField !== 'status' ? null : $value;
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No valid fields to update');
        }
        
        // Add nationalId for WHERE clause
        $updateValues[] = $nationalId;
        
        // Build and execute UPDATE query (only update if not deleted)
        $sql = "UPDATE citizens SET " . implode(', ', $updateFields) . " WHERE national_id = ? AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($sql);
        
        try {
            $stmt->execute($updateValues);
            
            // Return updated citizen
            return $this->getCitizenByNationalId($nationalId);
        } catch (PDOException $e) {
            throw new Exception('Failed to update citizen: ' . $e->getMessage());
        }
    }
    
    /**
     * Soft delete citizen (move to trash)
     * @param string $nationalId National ID
     * @return bool Success
     * @throws Exception On error or citizen not found
     */
    public function deleteCitizen($nationalId) {
        // Check if citizen exists and is not already deleted
        $existing = $this->getCitizenByNationalId($nationalId, false);
        if (!$existing) {
            throw new Exception('Citizen not found', 404);
        }
        
        // Soft delete: set deleted_at timestamp
        $stmt = $this->conn->prepare("
            UPDATE citizens 
            SET deleted_at = CURRENT_TIMESTAMP 
            WHERE national_id = ? AND deleted_at IS NULL
        ");
        
        try {
            $stmt->execute([$nationalId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('Failed to delete citizen: ' . $e->getMessage());
        }
    }
    
    /**
     * List deleted citizens (trash)
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Array of deleted citizens
     */
    public function listTrash($limit = 50, $offset = 0) {
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);
        
        $stmt = $this->conn->prepare("
            SELECT 
                id,
                national_id,
                first_name,
                middle_name,
                last_name,
                gender,
                date_of_birth,
                place_of_birth,
                nationality,
                status,
                image_path,
                document_path,
                created_at,
                deleted_at
            FROM citizens
            WHERE deleted_at IS NOT NULL
            ORDER BY deleted_at DESC, id DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        $citizens = $stmt->fetchAll();
        
        $results = [];
        foreach ($citizens as $citizen) {
            $results[] = $this->normalizeCitizen($citizen);
        }
        
        return $results;
    }
    
    /**
     * Restore citizen from trash
     * @param string $nationalId National ID
     * @return bool Success
     * @throws Exception On error or citizen not found
     */
    public function restoreCitizen($nationalId) {
        // Check if citizen exists in trash
        $existing = $this->getCitizenByNationalId($nationalId, true);
        if (!$existing || !isset($existing['deletedAt']) || !$existing['deletedAt']) {
            throw new Exception('Citizen not found in trash', 404);
        }
        
        // Restore: clear deleted_at
        $stmt = $this->conn->prepare("
            UPDATE citizens 
            SET deleted_at = NULL 
            WHERE national_id = ? AND deleted_at IS NOT NULL
        ");
        
        try {
            $stmt->execute([$nationalId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('Failed to restore citizen: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore all citizens from trash
     * @return int Number of citizens restored
     */
    public function restoreAllCitizens() {
        $stmt = $this->conn->prepare("
            UPDATE citizens 
            SET deleted_at = NULL 
            WHERE deleted_at IS NOT NULL
        ");
        
        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Failed to restore all citizens: ' . $e->getMessage());
        }
    }
    
    /**
     * Permanently delete citizen from trash
     * @param string $nationalId National ID
     * @return bool Success
     * @throws Exception On error or citizen not found
     */
    public function permanentlyDeleteCitizen($nationalId) {
        // Check if citizen exists in trash
        $existing = $this->getCitizenByNationalId($nationalId, true);
        if (!$existing || !isset($existing['deletedAt']) || !$existing['deletedAt']) {
            throw new Exception('Citizen not found in trash', 404);
        }
        
        // Permanent delete: remove from database
        $stmt = $this->conn->prepare("
            DELETE FROM citizens 
            WHERE national_id = ? AND deleted_at IS NOT NULL
        ");
        
        try {
            $stmt->execute([$nationalId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception('Failed to permanently delete citizen: ' . $e->getMessage());
        }
    }
    
    /**
     * Permanently delete all citizens from trash
     * @return int Number of citizens deleted
     */
    public function permanentlyDeleteAllCitizens() {
        $stmt = $this->conn->prepare("
            DELETE FROM citizens 
            WHERE deleted_at IS NOT NULL
        ");
        
        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('Failed to permanently delete all citizens: ' . $e->getMessage());
        }
    }
    
    /**
     * Update citizen file paths (image and/or document)
     * @param string $nationalId National ID
     * @param array $data File path data (imagePath, documentPath)
     * @return array Updated citizen data
     * @throws Exception On error or citizen not found
     */
    public function updateCitizenFiles($nationalId, $data) {
        // Check if citizen exists
        $existing = $this->getCitizenByNationalId($nationalId);
        if (!$existing) {
            throw new Exception('Citizen not found', 404);
        }
        
        // Build update fields
        $updateFields = [];
        $updateValues = [];
        
        if (isset($data['imagePath'])) {
            $updateFields[] = "image_path = ?";
            $updateValues[] = $data['imagePath'];
        }
        
        if (isset($data['documentPath'])) {
            $updateFields[] = "document_path = ?";
            $updateValues[] = $data['documentPath'];
        }
        
        if (empty($updateFields)) {
            throw new Exception('No file paths to update');
        }
        
        // Add nationalId for WHERE clause
        $updateValues[] = $nationalId;
        
        // Build and execute UPDATE query
        $sql = "UPDATE citizens SET " . implode(', ', $updateFields) . " WHERE national_id = ? AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($sql);
        
        try {
            $stmt->execute($updateValues);
            
            // Return updated citizen
            return $this->getCitizenByNationalId($nationalId);
        } catch (PDOException $e) {
            throw new Exception('Failed to update citizen files: ' . $e->getMessage());
        }
    }
}

