<?php
/**
 * Quick Fix: Assign OFFICER Role to officer1
 * 
 * Run this script to assign the OFFICER role to user 'officer1'
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "Fixing role assignment for 'officer1'...\n\n";
    
    // Get OFFICER role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'OFFICER'");
    $stmt->execute();
    $role = $stmt->fetch();
    
    if (!$role) {
        echo "❌ ERROR: OFFICER role not found in database!\n";
        echo "   Please run database/setup.php first.\n";
        exit(1);
    }
    
    $officerRoleId = $role['id'];
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, role_id FROM nira_users WHERE username = ?");
    $stmt->execute(['officer1']);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ ERROR: User 'officer1' not found in database!\n";
        exit(1);
    }
    
    echo "Found user: {$user['username']} (ID: {$user['id']})\n";
    echo "Current role_id: " . ($user['role_id'] ?? 'NULL') . "\n\n";
    
    // Update role
    $stmt = $pdo->prepare("UPDATE nira_users SET role_id = ? WHERE username = ?");
    $stmt->execute([$officerRoleId, 'officer1']);
    
    echo "✅ Successfully assigned OFFICER role (role_id = {$officerRoleId}) to 'officer1'\n\n";
    
    // Verify
    $stmt = $pdo->prepare("
        SELECT u.username, u.role_id, r.name as role_name
        FROM nira_users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.username = ?
    ");
    $stmt->execute(['officer1']);
    $updated = $stmt->fetch();
    
    echo "Verification:\n";
    echo "  Username: {$updated['username']}\n";
    echo "  Role ID: {$updated['role_id']}\n";
    echo "  Role Name: {$updated['role_name']}\n";
    
    if ($updated['role_name'] === 'OFFICER') {
        echo "\n✅ Role assignment is correct!\n";
    } else {
        echo "\n⚠ Warning: Role name doesn't match expected 'OFFICER'\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

