<?php
header("Content-Type: application/json; charset=utf-8");
echo json_encode([
    "success" => true,
    "message" => "Devices API working",
    "data" => [
        "devices" => [
            ["id" => 1, "name" => "Main Display", "status" => "online"],
            ["id" => 2, "name" => "Reception TV", "status" => "online"],
            ["id" => 3, "name" => "Lobby Screen", "status" => "offline"]
        ]
    ]
], JSON_UNESCAPED_UNICODE);
?>