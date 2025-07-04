<?php
/*
=============================================================================
MAIN API ROUTER - Fixed Version
=============================================================================
*/

// Prevent any PHP errors from mixing with JSON output
error_reporting(0);
ini_set("display_errors", 0);

// Clear output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON headers
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo json_encode(["status" => "ok"]);
    exit;
}

// Safe JSON response
function apiResponse($data, $code = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Parse request
    $method = $_SERVER["REQUEST_METHOD"];
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $pathParts = explode("/", trim($path, "/"));
    
    // Find API index
    $apiIndex = array_search("api", $pathParts);
    $endpoint = $pathParts[$apiIndex + 1] ?? "";
    $id = $pathParts[$apiIndex + 2] ?? null;
    
    // Route to appropriate handler
    switch ($endpoint) {
        case "":
        case "index":
        case "index.php":
            // API root
            apiResponse([
                "success" => true,
                "message" => "Digital Signage API is online",
                "version" => "2.0.0",
                "endpoints" => [
                    "GET /api/" => "API information",
                    "GET /api/playlists" => "Get all playlists",
                    "POST /api/playlists" => "Create new playlist",
                    "GET /api/content" => "Get all content",
                    "POST /api/content" => "Create new content",
                    "GET /api/devices" => "Get all devices",
                    "POST /api/devices" => "Register new device"
                ],
                "timestamp" => date("Y-m-d H:i:s")
            ]);
            break;
            
        case "playlists":
            include "playlists.php";
            break;
            
        case "content":
            include "content.php";
            break;
            
        case "devices":
            include "devices.php";
            break;
            
        case "health":
            apiResponse([
                "success" => true,
                "message" => "System healthy",
                "status" => "online",
                "timestamp" => date("Y-m-d H:i:s"),
                "php_version" => PHP_VERSION,
                "memory_usage" => memory_get_usage(true)
            ]);
            break;
            
        default:
            apiResponse([
                "success" => false,
                "message" => "Endpoint not found: " . $endpoint,
                "available_endpoints" => ["playlists", "content", "devices", "health"]
            ], 404);
    }
    
} catch (Exception $e) {
    apiResponse([
        "success" => false,
        "message" => "API error: " . $e->getMessage()
    ], 500);
}
?>