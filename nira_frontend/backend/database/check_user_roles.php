<?php
/**
 * Diagnostic Script: Check User Roles
 * 
 * Run this script to check which users have roles assigned
 * and verify the role assignments are correct.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "=== User Role Assignment Check ===\n\n";
    
    // Get all users with their role information
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.role_id,
            r.name as role_name,
            r.description as role_description
        FROM nira_users u
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY u.id
    ");
    
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No users found in database.\n";
        exit;
    }
    
    echo "Found " . count($users) . " user(s):\n\n";
    
    foreach ($users as $user) {
        echo "User ID: {$user['id']}\n";
        echo "Username: {$user['username']}\n";
        
        if ($user['role_id'] === null) {
            echo "❌ Role: NOT ASSIGNED (role_id is NULL)\n";
            echo "   → Fix: UPDATE nira_users SET role_id = 2 WHERE username = '{$user['username']}';\n";
        } else {
            if ($user['role_name']) {
                echo "✓ Role ID: {$user['role_id']}\n";
                echo "✓ Role Name: {$user['role_name']}\n";
                echo "✓ Description: {$user['role_description']}\n";
            } else {
                echo "⚠ Role ID: {$user['role_id']} (but role doesn't exist in roles table)\n";
            }
        }
        echo "\n";
    }
    
    // Show available roles
    echo "\n=== Available Roles ===\n\n";
    $stmt = $pdo->query("SELECT id, name, description FROM roles ORDER BY id");
    $roles = $stmt->fetchAll();
    
    foreach ($roles as $role) {
        echo "ID: {$role['id']} - {$role['name']}\n";
        echo "   {$role['description']}\n\n";
    }
    
    // Check specific user
    echo "\n=== Checking 'officer1' ===\n\n";
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.role_id,
            r.name as role_name
        FROM nira_users u
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE u.username = ?
    ");
    $stmt->execute(['officer1']);
    $officer1 = $stmt->fetch();
    
    if ($officer1) {
        echo "Found user: {$officer1['username']}\n";
        echo "User ID: {$officer1['id']}\n";
        
        if ($officer1['role_id'] === null) {
            echo "❌ PROBLEM: No role assigned!\n";
            echo "   Fix with: UPDATE nira_users SET role_id = 2 WHERE username = 'officer1';\n";
        } elseif ($officer1['role_id'] == 1) {
            echo "❌ PROBLEM: Has ADMIN role (role_id = 1) instead of OFFICER!\n";
            echo "   Fix with: UPDATE nira_users SET role_id = 2 WHERE username = 'officer1';\n";
        } elseif ($officer1['role_id'] == 2) {
            echo "✓ Correct: Has OFFICER role (role_id = 2)\n";
            echo "✓ Role Name: {$officer1['role_name']}\n";
        } else {
            echo "⚠ Has role_id = {$officer1['role_id']} (Role: {$officer1['role_name']})\n";
        }
    } else {
        echo "❌ User 'officer1' not found in database!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

