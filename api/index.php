<?php
// Ultra simple API with fixed path parsing
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Clean any previous output
if (ob_get_level()) ob_end_clean();

// Turn off error display
error_reporting(0);
ini_set("display_errors", 0);

// Handle OPTIONS
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo "{}";
    exit;
}

// Get request info
$method = $_SERVER["REQUEST_METHOD"];
$uri = $_SERVER["REQUEST_URI"];

// Debug: log the original URI
// error_log("Original URI: " . $uri);

// Better path parsing - remove all possible prefixes
$path = $uri;

// Remove common prefixes
$prefixes = [
    "/dsm/api/",
    "/DSM/api/", 
    "/api/",
    "/dsm/",
    "/DSM/"
];

foreach ($prefixes as $prefix) {
    if (strpos($path, $prefix) === 0) {
        $path = substr($path, strlen($prefix));
        break;
    }
}

// Remove index.php if present
$path = str_replace("index.php", "", $path);
$path = str_replace("index.php/", "", $path);

// Remove leading/trailing slashes
$path = trim($path, "/");

// Remove query string
if (strpos($path, "?") !== false) {
    $path = substr($path, 0, strpos($path, "?"));
}

// Debug: log the processed path
// error_log("Processed path: " . $path);

// Route logic
if (empty($path)) {
    // API root
    $data = [
        "success" => true,
        "message" => "Digital Signage API is working!",
        "data" => [
            "name" => "Digital Signage API",
            "version" => "1.0.0",
            "timestamp" => date("c"),
            "debug_info" => [
                "original_uri" => $uri,
                "processed_path" => $path,
                "method" => $method
            ],
            "endpoints" => [
                "/api/" => "API info",
                "/api/content" => "Content list", 
                "/api/player/playlist" => "Playlist",
                "/api/player/register" => "Register device"
            ]
        ]
    ];
} elseif ($path === "content") {
    // Content endpoint
    $data = [
        "success" => true,
        "message" => "Content retrieved successfully",
        "data" => [
            [
                "id" => 1,
                "title" => "Welcome to Digital Signage",
                "type" => "image",
                "file_url" => "https://picsum.photos/1920/1080?text=Welcome+Digital+Signage",
                "thumbnail_path" => "https://picsum.photos/300/200?text=Welcome",
                "duration" => 10,
                "status" => "active",
                "created_at" => "2024-01-01 12:00:00"
            ],
            [
                "id" => 2,
                "title" => "Sample Video Content",
                "type" => "video",
                "file_url" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "thumbnail_path" => "https://picsum.photos/300/200?text=Video",
                "duration" => 30,
                "status" => "active",
                "created_at" => "2024-01-01 12:00:00"
            ],
            [
                "id" => 3,
                "title" => "Information Display",
                "type" => "text",
                "file_url" => "Your Digital Signage System is Ready!",
                "duration" => 8,
                "status" => "active",
                "created_at" => "2024-01-01 12:00:00"
            ]
        ]
    ];
} elseif ($path === "player/playlist" || strpos($path, "playlist") !== false) {
    // Playlist endpoint
    $data = [
        "success" => true,
        "message" => "Playlist retrieved successfully",
        "data" => [
            "playlist" => [
                "id" => 1,
                "name" => "Default Playlist",
                "items" => [
                    [
                        "content_id" => 1,
                        "title" => "Welcome Message",
                        "type" => "image",
                        "file_url" => "https://picsum.photos/1920/1080?text=Welcome+Digital+Signage",
                        "duration" => 10
                    ],
                    [
                        "content_id" => 2,
                        "title" => "Demo Video",
                        "type" => "video",
                        "file_url" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                        "duration" => 20
                    ],
                    [
                        "content_id" => 3,
                        "title" => "Ready for Content",
                        "type" => "text",
                        "file_url" => "Digital Signage System Ready!",
                        "duration" => 8
                    ]
                ]
            ]
        ]
    ];
} elseif ($path === "player/register" || strpos($path, "register") !== false) {
    // Register endpoint
    $data = [
        "success" => true,
        "message" => "Device registered successfully",
        "data" => [
            "device" => [
                "id" => rand(1000, 9999),
                "device_id" => "device-" . time(),
                "name" => "Digital Display",
                "api_key" => "key-" . bin2hex(random_bytes(8)),
                "status" => "registered"
            ]
        ]
    ];
} else {
    // Unknown endpoint
    $data = [
        "success" => false,
        "message" => "Endpoint not found: " . $path,
        "data" => [
            "debug_info" => [
                "original_uri" => $uri,
                "processed_path" => $path,
                "method" => $method,
                "available_endpoints" => [
                    "content",
                    "player/playlist", 
                    "player/register"
                ]
            ]
        ]
    ];
}

// Output JSON
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>