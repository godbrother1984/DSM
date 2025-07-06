<?php
header("Content-Type: application/json; charset=utf-8");
echo json_encode([
    "success" => true,
    "message" => "Playlists API working",
    "data" => [
        "playlists" => [
            ["id" => 1, "name" => "Default Playlist", "is_active" => true],
            ["id" => 2, "name" => "Welcome Messages", "is_active" => true],
            ["id" => 3, "name" => "Product Showcase", "is_active" => true]
        ]
    ]
], JSON_UNESCAPED_UNICODE);
?>