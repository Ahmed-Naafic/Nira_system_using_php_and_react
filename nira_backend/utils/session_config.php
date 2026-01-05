<?php
/**
 * Session Configuration for NIRA System
 * Configures secure session cookies with HTTP-only and SameSite settings
 */

function initSecureSession() {
    // Configure session cookie parameters before starting session
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie expires when browser closes (or 24 hours)
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true, // Prevent JavaScript access
        'samesite' => 'Strict' // CSRF protection
    ]);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}



