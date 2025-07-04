<?php
// Simple Content API - No Dependencies
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
            // Return demo content
            sendJSON([
                "success" => true,
                "message" => "Content retrieved successfully",
                "data" => [
                    "content" => [
                        [
                            "id" => 1,
                            "title" => "Welcome Banner",
                            "type" => "image",
                            "duration" => 10,
                            "file_url" => "/demo/welcome.jpg",
                            "status" => "active",
                            "created_at" => date("Y-m-d H:i:s")
                        ],
                        [
                            "id" => 2,
                            "title" => "Product Demo Video",
                            "type" => "video", 
                            "duration" => 30,
                            "file_url" => "/demo/product.mp4",
                            "status" => "active",
                            "created_at" => date("Y-m-d H:i:s", strtotime("-1 hour"))
                        ],
                        [
                            "id" => 3,
                            "title" => "News Widget",
                            "type" => "widget",
                            "duration" => 15,
                            "file_url" => "/demo/news.html",
                            "status" => "active",
                            "created_at" => date("Y-m-d H:i:s", strtotime("-2 hours"))
                        ],
                        [
                            "id" => 4,
                            "title" => "Company Logo",
                            "type" => "image",
                            "duration" => 5,
                            "file_url" => "/demo/logo.png",
                            "status" => "active",
                            "created_at" => date("Y-m-d H:i:s", strtotime("-3 hours"))
                        ]
                    ]
                ]
            ]);
            break;
            
        case "POST":
            // Simulate content creation
            if (empty($input["title"])) {
                sendJSON([
                    "success" => false,
                    "message" => "Content title is required"
                ]);
            }
            
            $newContent = [
                "id" => rand(1000, 9999),
                "title" => $input["title"],
                "type" => $input["type"] ?? "text",
                "duration" => intval($input["duration"] ?? 10),
                "file_url" => $input["file_url"] ?? "",
                "status" => "active",
                "created_at" => date("Y-m-d H:i:s")
            ];
            
            sendJSON([
                "success" => true,
                "message" => "Content created successfully (demo mode)",
                "data" => [
                    "content" => $newContent
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