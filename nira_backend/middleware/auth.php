<?php
/**
 * Authentication Middleware for NIRA System
 * Validates PHP sessions for protected endpoints
 * 
 * STRICT AUTHENTICATION - Backend is source of truth
 * - Sessions are browser-scoped by design
 * - No cross-browser or cross-device access
 * - Returns 401 immediately if no valid session
 * - No fallbacks, no assumptions, no cached state
 */

require_once __DIR__ . '/../config/bootstrap.php';

class AuthMiddleware {
    /**
     * Validate session and check if user is authenticated
     * STRICT: Returns 401 if $_SESSION['user_id'] is not set
     * 
     * @param array $allowedRoles Optional array of allowed roles (e.g., ['ADMIN', 'OFFICER'])
     * @return array Returns user data if valid
     */
    public static function validateSession($allowedRoles = null) {
        // Session is already started by bootstrap.php
        // Session is browser-scoped via PHPSESSID cookie
        
        // STRICT CHECK: $_SESSION['user_id'] must exist
        // No fallbacks, no assumptions - backend is source of truth
        // Note: bootstrap.php already handles session expiration, so we just check if user_id exists
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required. Please login.',
                'expired' => isset($_SESSION['last_activity']) // Indicate if it was an expiration
            ]);
            exit; // Exit immediately - no access without valid session
        }
        
        // Check role if specified
        if ($allowedRoles !== null && !in_array($_SESSION['role'], $allowedRoles)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient permissions'
            ]);
            exit;
        }
        
        // Return user data from session
        return [
            'userId' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Validate session and require specific role
     * @param string|array $requiredRole
     * @return array
     */
    public static function requireRole($requiredRole) {
        $roles = is_array($requiredRole) ? $requiredRole : [$requiredRole];
        return self::validateSession($roles);
    }
}

