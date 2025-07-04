<?php
/*
=============================================================================
PLAYLISTS API - JSON Fixed Version
=============================================================================
*/

// Prevent PHP errors
error_reporting(0);
ini_set("display_errors", 0);

// Clear output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo json_encode(["status" => "ok"]);
    exit;
}

function playlistResponse($data, $code = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $method = $_SERVER["REQUEST_METHOD"];
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    
    switch ($method) {
        case "GET":
            playlistResponse([
                "success" => true,
                "message" => "Playlists retrieved successfully",
                "data" => [
                    "playlists" => [
                        [
                            "id" => 1,
                            "name" => "Welcome Playlist",
                            "description" => "Welcome messages and announcements",
                            "is_active" => true,
                            "item_count" => 3,
                            "total_duration" => 60,
                            "created_at" => date("Y-m-d H:i:s")
                        ],
                        [
                            "id" => 2,
                            "name" => "Product Showcase",
                            "description" => "Featured products and services",
                            "is_active" => true,
                            "item_count" => 5,
                            "total_duration" => 120,
                            "created_at" => date("Y-m-d H:i:s", strtotime("-1 hour"))
                        ],
                        [
                            "id" => 3,
                            "name" => "News & Updates",
                            "description" => "Latest news and company updates",
                            "is_active" => true,
                            "item_count" => 4,
                            "total_duration" => 80,
                            "created_at" => date("Y-m-d H:i:s", strtotime("-2 hours"))
                        ]
                    ]
                ],
                "count" => 3
            ]);
            break;
            
        case "POST":
            if (empty($input["name"])) {
                playlistResponse([
                    "success" => false,
                    "message" => "Playlist name is required"
                ], 400);
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
            
            playlistResponse([
                "success" => true,
                "message" => "Playlist created successfully",
                "data" => ["playlist" => $newPlaylist]
            ], 201);
            break;
            
        default:
            playlistResponse([
                "success" => false,
                "message" => "Method not allowed"
            ], 405);
    }
    
} catch (Exception $e) {
    playlistResponse([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ], 500);
}
?>