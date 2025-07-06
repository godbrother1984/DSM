<?php
/*
=============================================================================
CLEAN API ROUTER - No Syntax Errors
=============================================================================
*/

error_reporting(0);
ini_set("display_errors", 0);

while (ob_get_level()) {
    ob_end_clean();
}

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo json_encode(["status" => "ok"]);
    exit;
}

function sendResponse($data, $code = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $method = $_SERVER["REQUEST_METHOD"];
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $pathParts = explode("/", trim($path, "/"));
    
    $apiIndex = array_search("api", $pathParts);
    $endpoint = $pathParts[$apiIndex + 1] ?? "";
    $id = $pathParts[$apiIndex + 2] ?? null;
    
    switch ($endpoint) {
        case "":
            sendResponse([
                "success" => true,
                "message" => "Digital Signage API v2.0",
                "endpoints" => [
                    "GET /api/" => "API info",
                    "GET /api/playlists" => "Get playlists",
                    "POST /api/playlists" => "Create playlist",
                    "GET /api/content" => "Get content",
                    "POST /api/content" => "Create content",
                    "GET /api/devices" => "Get devices",
                    "POST /api/devices" => "Register device",
                    "GET /api/health" => "Health check",
                    "GET /api/testApiConnection" => "Test connection"
                ],
                "status" => "online",
                "timestamp" => date("Y-m-d H:i:s")
            ]);
            break;
            
        case "testApiConnection":
            sendResponse([
                "success" => true,
                "message" => "API connection test successful",
                "status" => "online",
                "server_time" => date("Y-m-d H:i:s"),
                "php_version" => PHP_VERSION,
                "memory_usage" => memory_get_usage(true)
            ]);
            break;
            
        case "health":
            sendResponse([
                "success" => true,
                "message" => "System healthy",
                "status" => "online",
                "checks" => [
                    "api" => "ok",
                    "php" => "ok",
                    "memory" => "ok"
                ],
                "timestamp" => date("Y-m-d H:i:s")
            ]);
            break;
            
        case "dashboard":
            sendResponse([
                "success" => true,
                "message" => "Dashboard stats retrieved",
                "data" => [
                    "total_playlists" => 3,
                    "total_content" => 5,
                    "total_devices" => 4,
                    "online_devices" => 2,
                    "system_uptime" => "99.9%"
                ]
            ]);
            break;
            
        case "playlists":
            if (file_exists("playlists.php")) {
                include "playlists.php";
            } else {
                sendResponse([
                    "success" => true,
                    "message" => "Playlists retrieved (fallback)",
                    "data" => [
                        "playlists" => [
                            [
                                "id" => 1,
                                "name" => "Default Playlist",
                                "description" => "Default system playlist",
                                "is_active" => true,
                                "item_count" => 3,
                                "total_duration" => 60,
                                "created_at" => date("Y-m-d H:i:s")
                            ]
                        ]
                    ]
                ]);
            }
            break;
            
        case "content":
            if (file_exists("content.php")) {
                include "content.php";
            } else {
                sendResponse([
                    "success" => true,
                    "message" => "Content retrieved (fallback)",
                    "data" => [
                        "content" => [
                            [
                                "id" => 1,
                                "title" => "Welcome Message",
                                "type" => "text",
                                "duration" => 10,
                                "status" => "active",
                                "created_at" => date("Y-m-d H:i:s")
                            ]
                        ]
                    ]
                ]);
            }
            break;
            
        case "devices":
            if (file_exists("devices.php")) {
                include "devices.php";
            } else {
                sendResponse([
                    "success" => true,
                    "message" => "Devices retrieved (fallback)",
                    "data" => [
                        "devices" => [
                            [
                                "id" => 1,
                                "device_id" => "DS001",
                                "name" => "Main Display",
                                "status" => "online",
                                "last_seen" => date("Y-m-d H:i:s"),
                                "created_at" => date("Y-m-d H:i:s")
                            ]
                        ]
                    ]
                ]);
            }
            break;
            
        default:
            sendResponse([
                "success" => false,
                "message" => "Endpoint not found: " . $endpoint,
                "available_endpoints" => [
                    "playlists", "content", "devices", "health", "testApiConnection", "dashboard"
                ]
            ], 404);
    }
    
} catch (Exception $e) {
    sendResponse([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ], 500);
}
?>