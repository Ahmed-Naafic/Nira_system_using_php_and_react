<?php
/**
 * National ID Generator for NIRA System
 * Generates unique numeric National IDs
 */

require_once __DIR__ . '/../config/database.php';

class NationalIdGenerator {
    const ID_LENGTH = 10;
    
    /**
     * Generate a unique national ID
     * @param PDO $conn Database connection
     * @return string
     */
    public static function generate($conn) {
        $maxAttempts = 100;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            // Generate a random numeric ID of fixed length
            $nationalId = self::generateRandomId();
            
            // Check if ID already exists
            if (!self::idExists($conn, $nationalId)) {
                return $nationalId;
            }
            
            $attempt++;
        }
        
        // If we've exhausted attempts, try sequential approach
        return self::generateSequentialId($conn);
    }
    
    /**
     * Generate random numeric ID
     * @return string
     */
    private static function generateRandomId() {
        $id = '';
        for ($i = 0; $i < self::ID_LENGTH; $i++) {
            $id .= mt_rand(0, 9);
        }
        return $id;
    }
    
    /**
     * Generate sequential ID based on database
     * @param PDO $conn
     * @return string
     */
    private static function generateSequentialId($conn) {
        try {
            // Get the highest numeric ID from database
            $stmt = $conn->query("SELECT MAX(CAST(national_id AS UNSIGNED)) as max_id FROM citizens");
            $result = $stmt->fetch();
            
            $nextId = 1;
            if ($result && $result['max_id']) {
                $nextId = intval($result['max_id']) + 1;
            }
            
            // Pad with zeros to reach required length
            $nationalId = str_pad($nextId, self::ID_LENGTH, '0', STR_PAD_LEFT);
            
            // Verify it doesn't exist (edge case)
            if (!self::idExists($conn, $nationalId)) {
                return $nationalId;
            }
            
            // If it exists, increment until we find a free one
            while (self::idExists($conn, $nationalId)) {
                $nextId++;
                $nationalId = str_pad($nextId, self::ID_LENGTH, '0', STR_PAD_LEFT);
            }
            
            return $nationalId;
            
        } catch (Exception $e) {
            // Fallback to timestamp-based ID
            $timestamp = time();
            $nationalId = str_pad(substr($timestamp, -self::ID_LENGTH), self::ID_LENGTH, '0', STR_PAD_LEFT);
            
            // Ensure uniqueness
            while (self::idExists($conn, $nationalId)) {
                $timestamp++;
                $nationalId = str_pad(substr($timestamp, -self::ID_LENGTH), self::ID_LENGTH, '0', STR_PAD_LEFT);
            }
            
            return $nationalId;
        }
    }
    
    /**
     * Check if national ID already exists
     * @param PDO $conn
     * @param string $nationalId
     * @return bool
     */
    private static function idExists($conn, $nationalId) {
        try {
            $stmt = $conn->prepare("SELECT id FROM citizens WHERE national_id = ?");
            $stmt->execute([$nationalId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return true; // Assume exists if error occurs
        }
    }
}



