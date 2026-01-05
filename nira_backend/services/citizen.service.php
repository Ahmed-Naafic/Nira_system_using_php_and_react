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
            (national_id, first_name, middle_name, last_name, gender, date_of_birth, place_of_birth, nationality, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
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
            
            // Return created citizen data
            return [
                'nationalId' => $nationalId,
                'firstName' => $firstName,
                'middleName' => $middleName,
                'lastName' => $lastName,
                'gender' => $gender,
                'dateOfBirth' => $dateOfBirth,
                'placeOfBirth' => $placeOfBirth,
                'nationality' => $nationality,
                'status' => 'ACTIVE'
            ];
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
    public function getCitizenByNationalId($nationalId) {
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
                created_at,
                updated_at
            FROM citizens
            WHERE national_id = ?
        ");
        
        $stmt->execute([$nationalId]);
        $citizen = $stmt->fetch();
        
        if (!$citizen) {
            return null;
        }
        
        // Format response
        return [
            'id' => (int)$citizen['id'],
            'nationalId' => $citizen['national_id'],
            'firstName' => $citizen['first_name'],
            'middleName' => $citizen['middle_name'],
            'lastName' => $citizen['last_name'],
            'fullName' => trim($citizen['first_name'] . ' ' . 
                            ($citizen['middle_name'] ? $citizen['middle_name'] . ' ' : '') . 
                            $citizen['last_name']),
            'gender' => $citizen['gender'],
            'dateOfBirth' => $citizen['date_of_birth'],
            'placeOfBirth' => $citizen['place_of_birth'],
            'nationality' => $citizen['nationality'],
            'status' => $citizen['status'],
            'createdAt' => $citizen['created_at'],
            'updatedAt' => $citizen['updated_at']
        ];
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
                created_at
            FROM citizens
            WHERE 
                national_id LIKE ? OR
                first_name LIKE ? OR
                middle_name LIKE ? OR
                last_name LIKE ? OR
                CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?
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
        
        // Format results
        $results = [];
        foreach ($citizens as $citizen) {
            $results[] = [
                'id' => (int)$citizen['id'],
                'nationalId' => $citizen['national_id'],
                'firstName' => $citizen['first_name'],
                'middleName' => $citizen['middle_name'],
                'lastName' => $citizen['last_name'],
                'fullName' => trim($citizen['first_name'] . ' ' . 
                                ($citizen['middle_name'] ? $citizen['middle_name'] . ' ' : '') . 
                                $citizen['last_name']),
                'gender' => $citizen['gender'],
                'dateOfBirth' => $citizen['date_of_birth'],
                'placeOfBirth' => $citizen['place_of_birth'],
                'nationality' => $citizen['nationality'],
                'status' => $citizen['status'],
                'createdAt' => $citizen['created_at']
            ];
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
                created_at
            FROM citizens
            ORDER BY created_at DESC, last_name, first_name
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$limit, $offset]);
        $citizens = $stmt->fetchAll();
        
        // Format results
        $results = [];
        foreach ($citizens as $citizen) {
            $results[] = [
                'id' => (int)$citizen['id'],
                'nationalId' => $citizen['national_id'],
                'firstName' => $citizen['first_name'],
                'middleName' => $citizen['middle_name'],
                'lastName' => $citizen['last_name'],
                'fullName' => trim($citizen['first_name'] . ' ' . 
                                ($citizen['middle_name'] ? $citizen['middle_name'] . ' ' : '') . 
                                $citizen['last_name']),
                'gender' => $citizen['gender'],
                'dateOfBirth' => $citizen['date_of_birth'],
                'placeOfBirth' => $citizen['place_of_birth'],
                'nationality' => $citizen['nationality'],
                'status' => $citizen['status'],
                'createdAt' => $citizen['created_at']
            ];
        }
        
        return $results;
    }
}

