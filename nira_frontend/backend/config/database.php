<?php
/**
 * Database Configuration
 * 
 * Update these values to match your database configuration
 */

function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'nira_system'; // Update with your database name
        $username = 'root'; // Update with your database username
        $password = ''; // Update with your database password
        
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

