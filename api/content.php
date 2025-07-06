<?php
header("Content-Type: application/json; charset=utf-8");
echo json_encode([
    "success" => true,
    "message" => "Content API working",
    "data" => [
        "content" => [
            ["id" => 1, "title" => "Welcome Banner", "type" => "image"],
            ["id" => 2, "title" => "Product Video", "type" => "video"],
            ["id" => 3, "title" => "News Feed", "type" => "widget"]
        ]
    ]
], JSON_UNESCAPED_UNICODE);
?>