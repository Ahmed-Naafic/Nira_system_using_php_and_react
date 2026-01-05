<?php
/**
 * NIRA System Entry Point
 * National Identity Registration Authority - Backend API
 */

header('Content-Type: application/json');

// Simple routing for API endpoints
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);
$path = trim($path, '/');

// Route to appropriate endpoint
if (empty($path) || $path === '') {
    // Root endpoint - API information
    echo json_encode([
        'name' => 'NIRA System API',
        'version' => '1.0.0',
        'description' => 'National Identity Registration Authority - Backend API',
        'endpoints' => [
            'POST /api/auth/login.php' => 'NIRA officer login',
            'POST /api/citizens/create.php' => 'Register new citizen (OFFICER/ADMIN)',
            'GET /api/citizens/get.php?nationalId=XXX' => 'Get citizen by National ID (read-only)',
            'POST /api/citizens/update_status.php' => 'Update citizen status (ADMIN only)'
        ],
        'status' => 'operational'
    ]);
    exit;
}

// For direct API access, this file should not be used
// API endpoints should be accessed directly
http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint not found. Access API endpoints directly.'
]);



