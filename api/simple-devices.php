<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(json_encode(["status" => "ok"]));
}

function sendJSON($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $method = $_SERVER["REQUEST_METHOD"];
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    
    switch ($method) {
        case "GET":
            sendJSON([
                "success" => true,
                "message" => "Devices retrieved successfully",
                "data" => [
                    "devices" => [
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
                    ]
                ]
            ]);
            break;
            
        case "POST":
            if (empty($input["name"]) || empty($input["device_id"])) {
                sendJSON([
                    "success" => false,
                    "message" => "Device name and ID are required"
                ]);
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
            
            sendJSON([
                "success" => true,
                "message" => "Device registered successfully",
                "data" => [
                    "device" => $newDevice
                ]
            ]);
            break;
            
        default:
            sendJSON([
                "success" => false,
                "message" => "Method not allowed"
            ]);
    }
} catch (Exception $e) {
    sendJSON([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>