<?php
/*
=============================================================================
WORKING API ROUTER - FIXED VERSION
=============================================================================
*/

// Start clean
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Headers first
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Device-ID");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Simple response functions
function apiSuccess($data = null, $message = "Success") {
    ob_clean();
    echo json_encode([
        "success" => true,
        "message" => $message,
        "data" => $data,
        "timestamp" => date("c")
    ]);
    exit;
}

function apiError($message = "Error", $code = 400) {
    ob_clean();
    http_response_code($code);
    echo json_encode([
        "success" => false,
        "message" => $message,
        "timestamp" => date("c")
    ]);
    exit;
}

// Parse request
$path = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
$segments = explode("/", $path);
$method = $_SERVER["REQUEST_METHOD"];

// Remove api from path if present
if ($segments[0] === "api" || (isset($segments[1]) && $segments[1] === "api")) {
    if ($segments[0] === "api") {
        array_shift($segments);
    } else {
        $segments = array_slice($segments, 2);
    }
}

$resource = $segments[0] ?? "";
$id = $segments[1] ?? "";
$action = $segments[2] ?? "";

// Get input
$input = [];
if (in_array($method, ["POST", "PUT", "PATCH"])) {
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    if (strpos($contentType, "application/json") !== false) {
        $input = json_decode(file_get_contents("php://input"), true) ?? [];
    } else {
        $input = $_POST;
    }
}

// Route handling
switch ($resource) {
    case "":
        apiSuccess([
            "name" => "Digital Signage API",
            "version" => "1.0.0",
            "status" => "working",
            "endpoints" => [
                "content" => "/api/content",
                "player" => "/api/player",
                "device" => "/api/device"
            ]
        ], "API is working!");
        break;

    case "content":
        handleContentAPI($method, $id, $action, $input);
        break;

    case "player":
        handlePlayerAPI($method, $id, $action, $input);
        break;

    case "device":
        handleDeviceAPI($method, $id, $action, $input);
        break;

    default:
        apiError("Endpoint not found: " . $resource, 404);
}

// Content API Handler
function handleContentAPI($method, $id, $action, $input) {
    switch ($method) {
        case "GET":
            if ($id) {
                // Get single content
                apiSuccess([
                    "id" => $id,
                    "title" => "Sample Content " . $id,
                    "type" => "image",
                    "file_url" => "https://picsum.photos/800/600?random=" . $id,
                    "status" => "active"
                ]);
            } else {
                // Get all content
                $sampleContent = [];
                for ($i = 1; $i <= 5; $i++) {
                    $sampleContent[] = [
                        "id" => $i,
                        "title" => "Sample Content " . $i,
                        "type" => ($i % 2 == 0) ? "video" : "image", 
                        "file_url" => ($i % 2 == 0) 
                            ? "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4"
                            : "https://picsum.photos/800/600?random=" . $i,
                        "thumbnail_path" => "https://picsum.photos/300/200?random=" . $i,
                        "duration" => ($i % 2 == 0) ? 30 : 10,
                        "status" => "active",
                        "created_at" => date("Y-m-d H:i:s", time() - ($i * 86400))
                    ];
                }
                apiSuccess($sampleContent);
            }
            break;

        case "POST":
            // Create content
            $newContent = [
                "id" => rand(100, 999),
                "title" => $input["title"] ?? "New Content",
                "type" => $input["type"] ?? "image",
                "status" => "active",
                "created_at" => date("Y-m-d H:i:s")
            ];
            apiSuccess($newContent, "Content created successfully");
            break;

        default:
            apiError("Method not allowed", 405);
    }
}

// Player API Handler  
function handlePlayerAPI($method, $id, $action, $input) {
    switch ($method) {
        case "POST":
            if ($action === "register") {
                $device = [
                    "id" => rand(1000, 9999),
                    "device_id" => $input["device_id"] ?? "device-" . uniqid(),
                    "name" => $input["name"] ?? "Digital Display",
                    "api_key" => "key-" . bin2hex(random_bytes(16))
                ];
                apiSuccess(["device" => $device], "Device registered successfully");
            } elseif ($action === "heartbeat") {
                apiSuccess(null, "Heartbeat received");
            }
            break;

        case "GET":
            if ($action === "playlist") {
                $playlist = [
                    "id" => 1,
                    "name" => "Sample Playlist",
                    "items" => [
                        [
                            "content_id" => 1,
                            "title" => "Welcome Message",
                            "type" => "image",
                            "file_url" => "https://picsum.photos/1920/1080?text=Welcome",
                            "duration" => 10
                        ],
                        [
                            "content_id" => 2,
                            "title" => "Promotional Video",
                            "type" => "video", 
                            "file_url" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                            "duration" => 30
                        ]
                    ]
                ];
                apiSuccess(["playlist" => $playlist]);
            }
            break;

        default:
            apiError("Method not allowed", 405);
    }
}

// Device API Handler
function handleDeviceAPI($method, $id, $action, $input) {
    switch ($method) {
        case "GET":
            $devices = [
                [
                    "id" => 1,
                    "device_id" => "device-001",
                    "name" => "Main Display",
                    "status" => "online",
                    "last_seen" => date("Y-m-d H:i:s")
                ]
            ];
            apiSuccess($devices);
            break;

        default:
            apiError("Method not allowed", 405);
    }
}

ob_end_flush();
?>