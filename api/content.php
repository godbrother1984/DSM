<?php
/*
=============================================================================
CONTENT API - JSON Fixed Version
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

function contentResponse($data, $code = 200) {
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
            contentResponse([
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
                        ],
                        [
                            "id" => 5,
                            "title" => "Promotional Text",
                            "type" => "text",
                            "duration" => 8,
                            "file_url" => "Special offers available now!",
                            "status" => "active",
                            "created_at" => date("Y-m-d H:i:s", strtotime("-4 hours"))
                        ]
                    ]
                ],
                "count" => 5
            ]);
            break;
            
        case "POST":
            if (empty($input["title"])) {
                contentResponse([
                    "success" => false,
                    "message" => "Content title is required"
                ], 400);
            }
            
            $newContent = [
                "id" => rand(1000, 9999),
                "title" => $input["title"],
                "type" => $input["type"] ?? "text",
                "duration" => intval($input["duration"] ?? 10),
                "file_url" => $input["file_url"] ?? $input["title"],
                "status" => "active",
                "created_at" => date("Y-m-d H:i:s")
            ];
            
            contentResponse([
                "success" => true,
                "message" => "Content created successfully",
                "data" => ["content" => $newContent]
            ], 201);
            break;
            
        default:
            contentResponse([
                "success" => false,
                "message" => "Method not allowed"
            ], 405);
    }
    
} catch (Exception $e) {
    contentResponse([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ], 500);
}
?>