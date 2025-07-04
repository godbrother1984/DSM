<?php
/*
=============================================================================
DEVICES API - Working Version
=============================================================================
*/

// Prevent PHP errors from mixing with JSON
error_reporting(0);
ini_set("display_errors", 0);

// Clear any output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Set proper headers
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo json_encode(["status" => "ok"]);
    exit;
}

// Safe JSON response function
function sendDeviceResponse($data, $statusCode = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($statusCode);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if ($json === false) {
        echo json_encode([
            "success" => false,
            "message" => "JSON encoding error",
            "error_code" => json_last_error()
        ]);
    } else {
        echo $json;
    }
    exit;
}

try {
    $method = $_SERVER["REQUEST_METHOD"];
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    
    // Parse URL for device ID
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $pathParts = explode("/", trim($path, "/"));
    $deviceId = null;
    
    // Find device ID in path
    foreach ($pathParts as $i => $part) {
        if ($part === "devices" && isset($pathParts[$i + 1]) && is_numeric($pathParts[$i + 1])) {
            $deviceId = intval($pathParts[$i + 1]);
            break;
        }
    }
    
    switch ($method) {
        case "GET":
            if ($deviceId) {
                // Get single device
                $demoDevices = getDemoDevices();
                $device = null;
                
                foreach ($demoDevices as $d) {
                    if ($d["id"] == $deviceId) {
                        $device = $d;
                        break;
                    }
                }
                
                if ($device) {
                    sendDeviceResponse([
                        "success" => true,
                        "message" => "Device retrieved successfully",
                        "data" => ["device" => $device]
                    ]);
                } else {
                    sendDeviceResponse([
                        "success" => false,
                        "message" => "Device not found"
                    ], 404);
                }
            } else {
                // Get all devices
                sendDeviceResponse([
                    "success" => true,
                    "message" => "Devices retrieved successfully",
                    "data" => [
                        "devices" => getDemoDevices()
                    ],
                    "count" => count(getDemoDevices())
                ]);
            }
            break;
            
        case "POST":
            // Create new device
            if (empty($input["name"]) || empty($input["device_id"])) {
                sendDeviceResponse([
                    "success" => false,
                    "message" => "Device name and device_id are required"
                ], 400);
            }
            
            $newDevice = [
                "id" => rand(1000, 9999),
                "device_id" => $input["device_id"],
                "name" => $input["name"],
                "location" => $input["location"] ?? "",
                "description" => $input["description"] ?? "",
                "status" => "offline",
                "last_seen" => date("Y-m-d H:i:s"),
                "screen_width" => 1920,
                "screen_height" => 1080,
                "current_playlist" => null,
                "created_at" => date("Y-m-d H:i:s")
            ];
            
            sendDeviceResponse([
                "success" => true,
                "message" => "Device registered successfully",
                "data" => ["device" => $newDevice]
            ], 201);
            break;
            
        case "PUT":
            // Update device
            if (!$deviceId) {
                sendDeviceResponse([
                    "success" => false,
                    "message" => "Device ID is required for update"
                ], 400);
            }
            
            sendDeviceResponse([
                "success" => true,
                "message" => "Device updated successfully (demo mode)",
                "data" => ["device_id" => $deviceId]
            ]);
            break;
            
        case "DELETE":
            // Delete device
            if (!$deviceId) {
                sendDeviceResponse([
                    "success" => false,
                    "message" => "Device ID is required for delete"
                ], 400);
            }
            
            sendDeviceResponse([
                "success" => true,
                "message" => "Device deleted successfully (demo mode)"
            ]);
            break;
            
        default:
            sendDeviceResponse([
                "success" => false,
                "message" => "Method not allowed"
            ], 405);
    }
    
} catch (Exception $e) {
    sendDeviceResponse([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ], 500);
}

function getDemoDevices() {
    return [
        [
            "id" => 1,
            "device_id" => "DS001",
            "name" => "Main Lobby Display",
            "location" => "Main Lobby",
            "description" => "Primary display in main lobby",
            "status" => "online",
            "last_seen" => date("Y-m-d H:i:s"),
            "screen_width" => 1920,
            "screen_height" => 1080,
            "current_playlist" => "Welcome Playlist",
            "created_at" => date("Y-m-d H:i:s", strtotime("-1 day"))
        ],
        [
            "id" => 2,
            "device_id" => "DS002",
            "name" => "Reception Display",
            "location" => "Reception Area",
            "description" => "Welcome display at reception",
            "status" => "online",
            "last_seen" => date("Y-m-d H:i:s", strtotime("-5 minutes")),
            "screen_width" => 1366,
            "screen_height" => 768,
            "current_playlist" => "Product Showcase",
            "created_at" => date("Y-m-d H:i:s", strtotime("-7 days"))
        ],
        [
            "id" => 3,
            "device_id" => "DS003",
            "name" => "Cafeteria TV",
            "location" => "Staff Cafeteria",
            "description" => "Entertainment display in cafeteria",
            "status" => "offline",
            "last_seen" => date("Y-m-d H:i:s", strtotime("-2 hours")),
            "screen_width" => 1920,
            "screen_height" => 1080,
            "current_playlist" => null,
            "created_at" => date("Y-m-d H:i:s", strtotime("-14 days"))
        ],
        [
            "id" => 4,
            "device_id" => "DS004",
            "name" => "Conference Room A",
            "location" => "Conference Room A",
            "description" => "Meeting room display",
            "status" => "maintenance",
            "last_seen" => date("Y-m-d H:i:s", strtotime("-30 minutes")),
            "screen_width" => 1920,
            "screen_height" => 1080,
            "current_playlist" => "News & Updates",
            "created_at" => date("Y-m-d H:i:s", strtotime("-3 days"))
        ]
    ];
}
?>