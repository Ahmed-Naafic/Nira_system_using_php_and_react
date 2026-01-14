<?php
/**
 * NIRA System - Complete Database Installation Script (PHP)
 * 
 * This PHP script executes all database SQL files in the correct order.
 * It's an alternative to install_all.sql for environments where direct SQL execution is preferred.
 * 
 * Usage: php install_all.php
 */

require_once __DIR__ . '/../config/database.php';

echo "=====================================================\n";
echo "NIRA System - Complete Database Installation\n";
echo "=====================================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Read and execute install_all.sql
    $sqlFile = __DIR__ . '/install_all.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    echo "Reading SQL file: install_all.sql\n";
    $sql = file_get_contents($sqlFile);
    
    // Remove USE statement if database is already selected
    $sql = preg_replace('/^USE\s+\w+;?\s*/mi', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt) &&
                   !preg_match('/^\s*SELECT\s+/i', $stmt); // Skip SELECT statements for now
        }
    );
    
    $totalStatements = count($statements);
    $executed = 0;
    $errors = 0;
    
    echo "Executing $totalStatements SQL statements...\n\n";
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            $conn->exec($statement);
            $executed++;
            
            // Show progress for important operations
            if (preg_match('/CREATE TABLE/i', $statement)) {
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "✓ Created table: {$matches[1]}\n";
                }
            } elseif (preg_match('/ALTER TABLE/i', $statement)) {
                if (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "✓ Altered table: {$matches[1]}\n";
                }
            } elseif (preg_match('/INSERT INTO/i', $statement)) {
                if (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                    echo "✓ Inserted data into: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            // Ignore "already exists" errors for CREATE TABLE IF NOT EXISTS
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                $errors++;
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Execute SELECT statements separately to show results
    echo "\n";
    echo "=====================================================\n";
    echo "Installation Summary\n";
    echo "=====================================================\n";
    echo "Total statements: $totalStatements\n";
    echo "Executed: $executed\n";
    echo "Errors: $errors\n\n";
    
    // Show default users
    echo "Default users created:\n";
    echo "  - Username: admin, Password: admin123 (ADMIN role)\n";
    echo "  - Username: officer1, Password: admin123 (OFFICER role)\n\n";
    
    echo "⚠ IMPORTANT: Change default passwords in production!\n\n";
    
    echo "=====================================================\n";
    echo "Installation completed successfully!\n";
    echo "=====================================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Installation failed!\n";
    exit(1);
}
