<?php
// Simple Playlists API - No Dependencies
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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
            // Return demo playlists
            sendJSON([
                "success" => true,
                "message" => "Playlists retrieved successfully",
                "data" => [
                    "playlists" => [
                        [
                            "id" => 1,
                            "name" => "Demo Playlist 1",
                            "description" => "Working demo playlist",
                            "is_active" => true,
                            "item_count" => 3,
                            "total_duration" => 60,
                            "created_at" => date("Y-m-d H:i:s")
                        ],
                        [
                            "id" => 2,
                            "name" => "Demo Playlist 2", 
                            "description" => "Another working demo",
                            "is_active" => true,
                            "item_count" => 2,
                            "total_duration" => 45,
                            "created_at" => date("Y-m-d H:i:s", strtotime("-1 hour"))
                        ]
                    ]
                ]
            ]);
            break;
            
        case "POST":
            // Simulate playlist creation
            if (empty($input["name"])) {
                sendJSON([
                    "success" => false,
                    "message" => "Playlist name is required"
                ]);
            }
            
            $newPlaylist = [
                "id" => rand(1000, 9999),
                "name" => $input["name"],
                "description" => $input["description"] ?? "",
                "is_active" => true,
                "item_count" => count($input["items"] ?? []),
                "total_duration" => array_sum(array_column($input["items"] ?? [], "duration")),
                "created_at" => date("Y-m-d H:i:s")
            ];
            
            sendJSON([
                "success" => true,
                "message" => "Playlist created successfully (demo mode)",
                "data" => [
                    "playlist" => $newPlaylist
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