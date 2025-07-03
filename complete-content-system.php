<?php
/*
=============================================================================
COMPLETE CONTENT MANAGEMENT SYSTEM
=============================================================================
ระบบจัดการเนื้อหาที่สมบูรณ์แบบ พร้อมอัปโหลดไฟล์จริง
=============================================================================
*/

echo "<h1>🎬 Complete Content Management System</h1>";
echo "<h3>Creating full-featured content system...</h3>";

$steps = [];
$errors = [];

// ===============================================================
// Step 1: สร้าง File Upload Handler
// ===============================================================

echo "<h4>📤 Step 1: Creating File Upload System</h4>";

if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
    echo "✅ Created uploads directory<br>";
}

if (!is_dir('uploads/content')) {
    mkdir('uploads/content', 0755, true);
    echo "✅ Created uploads/content directory<br>";
}

if (!is_dir('uploads/thumbnails')) {
    mkdir('uploads/thumbnails', 0755, true);
    echo "✅ Created uploads/thumbnails directory<br>";
}

$uploadHandler = '<?php
// File Upload Handler for Content Management
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo "{}";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Only POST method allowed"]);
    exit;
}

// Configuration
$uploadDir = "../uploads/content/";
$thumbnailDir = "../uploads/thumbnails/";
$maxFileSize = 100 * 1024 * 1024; // 100MB
$allowedTypes = [
    "image/jpeg", "image/png", "image/gif", "image/webp",
    "video/mp4", "video/webm", "video/avi", "video/mov",
    "audio/mp3", "audio/wav", "audio/ogg",
    "text/html", "application/zip"
];

try {
    if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("No file uploaded or upload error");
    }

    $file = $_FILES["file"];
    $title = $_POST["title"] ?? pathinfo($file["name"], PATHINFO_FILENAME);
    $type = $_POST["type"] ?? getFileType($file["type"]);

    // Validate file size
    if ($file["size"] > $maxFileSize) {
        throw new Exception("File too large. Maximum size: 100MB");
    }

    // Validate file type
    if (!in_array($file["type"], $allowedTypes)) {
        throw new Exception("File type not allowed: " . $file["type"]);
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = uniqid() . "_" . time() . "." . $extension;
    $filepath = $uploadDir . $filename;

    // Create directories if they dont exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file["tmp_name"], $filepath)) {
        throw new Exception("Failed to move uploaded file");
    }

    // Get file info
    $fileInfo = [
        "id" => rand(1000, 9999),
        "title" => $title,
        "type" => $type,
        "filename" => $filename,
        "file_path" => $filepath,
        "file_url" => "/uploads/content/" . $filename,
        "file_size" => $file["size"],
        "mime_type" => $file["type"],
        "status" => "active",
        "created_at" => date("Y-m-d H:i:s")
    ];

    // Generate thumbnail for images
    if (strpos($file["type"], "image/") === 0) {
        $thumbnailPath = generateThumbnail($filepath, $thumbnailDir, $filename);
        if ($thumbnailPath) {
            $fileInfo["thumbnail_path"] = "/uploads/thumbnails/" . basename($thumbnailPath);
        }

        // Get image dimensions
        $imageInfo = getimagesize($filepath);
        if ($imageInfo) {
            $fileInfo["width"] = $imageInfo[0];
            $fileInfo["height"] = $imageInfo[1];
        }
    }

    // Get video info (basic)
    if (strpos($file["type"], "video/") === 0) {
        $fileInfo["duration"] = 30; // Default duration, would use ffmpeg in production
    }

    // Save to simple file-based "database"
    $contentFile = "../uploads/content_list.json";
    $contentList = [];
    if (file_exists($contentFile)) {
        $contentList = json_decode(file_get_contents($contentFile), true) ?? [];
    }
    $contentList[] = $fileInfo;
    file_put_contents($contentFile, json_encode($contentList, JSON_PRETTY_PRINT));

    echo json_encode([
        "success" => true,
        "message" => "File uploaded successfully",
        "data" => $fileInfo
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

function getFileType($mimeType) {
    if (strpos($mimeType, "image/") === 0) return "image";
    if (strpos($mimeType, "video/") === 0) return "video";
    if (strpos($mimeType, "audio/") === 0) return "audio";
    if (strpos($mimeType, "text/html") === 0) return "html";
    return "other";
}

function generateThumbnail($sourcePath, $thumbnailDir, $filename) {
    try {
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        $width = $info[0];
        $height = $info[1];
        $type = $info[2];

        // Create image resource
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        // Calculate thumbnail dimensions
        $thumbWidth = 300;
        $thumbHeight = 200;
        
        if ($width > $height) {
            $thumbHeight = ($height / $width) * $thumbWidth;
        } else {
            $thumbWidth = ($width / $height) * $thumbHeight;
        }

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        // Save thumbnail
        $thumbnailPath = $thumbnailDir . "thumb_" . $filename;
        $saved = imagejpeg($thumbnail, $thumbnailPath, 80);

        // Clean up
        imagedestroy($source);
        imagedestroy($thumbnail);

        return $saved ? $thumbnailPath : false;

    } catch (Exception $e) {
        return false;
    }
}
?>';

file_put_contents('api/upload.php', $uploadHandler);
echo "✅ Created file upload handler: api/upload.php<br>";
$steps[] = "File upload handler created";

// ===============================================================
// Step 2: อัปเดต API ให้รองรับ Content CRUD
// ===============================================================

echo "<h4>🔧 Step 2: Updating API for Content CRUD</h4>";

$enhancedAPI = '<?php
// Enhanced API with complete content management
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if (ob_get_level()) ob_end_clean();
error_reporting(0);
ini_set("display_errors", 0);

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo "{}";
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];
$uri = $_SERVER["REQUEST_URI"];

// Path processing
$path = $uri;
$prefixes = ["/dsm/api/", "/DSM/api/", "/api/", "/dsm/", "/DSM/"];
foreach ($prefixes as $prefix) {
    if (strpos($path, $prefix) === 0) {
        $path = substr($path, strlen($prefix));
        break;
    }
}

$path = str_replace(["index.php", "index.php/"], "", $path);
$path = trim($path, "/");

if (strpos($path, "?") !== false) {
    $path = substr($path, 0, strpos($path, "?"));
}

// Get input for POST/PUT requests
$input = [];
if (in_array($method, ["POST", "PUT", "PATCH"])) {
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    if (strpos($contentType, "application/json") !== false) {
        $input = json_decode(file_get_contents("php://input"), true) ?? [];
    } else {
        $input = $_POST;
    }
}

// Content management functions
function getContentList() {
    $contentFile = "../uploads/content_list.json";
    if (file_exists($contentFile)) {
        return json_decode(file_get_contents($contentFile), true) ?? [];
    }
    
    // Return sample content if no uploaded content
    return [
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
        ]
    ];
}

function saveContentList($contentList) {
    $contentFile = "../uploads/content_list.json";
    return file_put_contents($contentFile, json_encode($contentList, JSON_PRETTY_PRINT));
}

function findContentById($id, $contentList) {
    foreach ($contentList as $content) {
        if ($content["id"] == $id) {
            return $content;
        }
    }
    return null;
}

// Routing
try {
    switch ($path) {
        case "":
            // API root
            $data = [
                "success" => true,
                "message" => "Digital Signage Content API",
                "data" => [
                    "name" => "Digital Signage API",
                    "version" => "2.0.0",
                    "features" => [
                        "content_management",
                        "file_upload", 
                        "playlist_management",
                        "device_control"
                    ],
                    "endpoints" => [
                        "GET /api/" => "API info",
                        "GET /api/content" => "Get content list",
                        "POST /api/content" => "Create content",
                        "PUT /api/content/{id}" => "Update content",
                        "DELETE /api/content/{id}" => "Delete content",
                        "POST /api/upload" => "Upload file",
                        "GET /api/player/playlist" => "Get playlist"
                    ]
                ]
            ];
            break;

        case "content":
            if ($method === "GET") {
                // Get content list
                $contentList = getContentList();
                $data = [
                    "success" => true,
                    "message" => "Content retrieved successfully",
                    "data" => $contentList
                ];
            } elseif ($method === "POST") {
                // Create new content (text/html content)
                $newContent = [
                    "id" => rand(1000, 9999),
                    "title" => $input["title"] ?? "New Content",
                    "type" => $input["type"] ?? "text",
                    "file_url" => $input["content"] ?? $input["file_url"] ?? "Sample content",
                    "duration" => (int)($input["duration"] ?? 10),
                    "status" => "active",
                    "created_at" => date("Y-m-d H:i:s")
                ];

                $contentList = getContentList();
                $contentList[] = $newContent;
                saveContentList($contentList);

                $data = [
                    "success" => true,
                    "message" => "Content created successfully",
                    "data" => $newContent
                ];
            } else {
                throw new Exception("Method not allowed for content endpoint");
            }
            break;

        case (preg_match("/^content\/(\d+)$/", $path, $matches) ? true : false):
            // Individual content operations
            $contentId = $matches[1];
            $contentList = getContentList();
            $content = findContentById($contentId, $contentList);

            if (!$content) {
                throw new Exception("Content not found");
            }

            if ($method === "GET") {
                $data = [
                    "success" => true,
                    "message" => "Content retrieved",
                    "data" => $content
                ];
            } elseif ($method === "PUT") {
                // Update content
                foreach ($contentList as &$item) {
                    if ($item["id"] == $contentId) {
                        $item["title"] = $input["title"] ?? $item["title"];
                        $item["duration"] = (int)($input["duration"] ?? $item["duration"]);
                        $item["status"] = $input["status"] ?? $item["status"];
                        $item["updated_at"] = date("Y-m-d H:i:s");
                        $content = $item;
                        break;
                    }
                }
                saveContentList($contentList);

                $data = [
                    "success" => true,
                    "message" => "Content updated successfully",
                    "data" => $content
                ];
            } elseif ($method === "DELETE") {
                // Delete content
                $contentList = array_filter($contentList, function($item) use ($contentId) {
                    return $item["id"] != $contentId;
                });
                $contentList = array_values($contentList); // Re-index array
                saveContentList($contentList);

                $data = [
                    "success" => true,
                    "message" => "Content deleted successfully",
                    "data" => null
                ];
            }
            break;

        case "player/playlist":
            // Get playlist for player
            $contentList = getContentList();
            $activeContent = array_filter($contentList, function($item) {
                return $item["status"] === "active";
            });

            $data = [
                "success" => true,
                "message" => "Playlist retrieved successfully",
                "data" => [
                    "playlist" => [
                        "id" => 1,
                        "name" => "Main Playlist",
                        "items" => array_map(function($item) {
                            return [
                                "content_id" => $item["id"],
                                "title" => $item["title"],
                                "type" => $item["type"],
                                "file_url" => $item["file_url"],
                                "duration" => $item["duration"] ?? 10
                            ];
                        }, array_values($activeContent))
                    ]
                ]
            ];
            break;

        case "player/register":
            // Device registration
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
            break;

        case "stats":
            // Simple statistics
            $contentList = getContentList();
            $data = [
                "success" => true,
                "message" => "Statistics retrieved",
                "data" => [
                    "total_content" => count($contentList),
                    "active_content" => count(array_filter($contentList, function($item) {
                        return $item["status"] === "active";
                    })),
                    "content_types" => array_count_values(array_column($contentList, "type")),
                    "last_updated" => date("Y-m-d H:i:s")
                ]
            ];
            break;

        default:
            throw new Exception("Endpoint not found: " . $path);
    }

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => null
    ], JSON_PRETTY_PRINT);
}
?>';

file_put_contents('api/index.php', $enhancedAPI);
echo "✅ Enhanced API with complete content CRUD<br>";
$steps[] = "Enhanced API created";

// ===============================================================
// Step 3: สร้าง Complete Content Management Interface
// ===============================================================

echo "<h4>🎨 Step 3: Creating Complete Content Management Interface</h4>";

$completeContentManager = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Content Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; 
            padding: 30px 40px; 
            text-align: center;
        }
        
        .header h1 { 
            font-size: 2.5rem; 
            margin-bottom: 10px; 
            font-weight: 300;
        }
        
        .header p { 
            font-size: 1.2rem; 
            opacity: 0.9;
        }
        
        .main-content { 
            padding: 40px; 
        }
        
        .toolbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            flex-wrap: wrap; 
            gap: 15px;
        }
        
        .toolbar-left { 
            display: flex; 
            gap: 15px; 
            align-items: center; 
        }
        
        .toolbar-right { 
            display: flex; 
            gap: 15px; 
            align-items: center; 
        }
        
        .btn { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; 
            border: none; 
            padding: 12px 24px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success { 
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .btn-secondary { 
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }
        
        .search-box { 
            padding: 12px 16px; 
            border: 2px solid #e9ecef; 
            border-radius: 8px; 
            font-size: 14px; 
            width: 300px;
            transition: border-color 0.3s;
        }
        
        .search-box:focus { 
            outline: none; 
            border-color: #667eea;
        }
        
        .upload-area { 
            border: 3px dashed #667eea; 
            border-radius: 12px; 
            padding: 40px; 
            text-align: center; 
            margin: 20px 0; 
            cursor: pointer; 
            transition: all 0.3s ease;
            background: #f8f9ff;
        }
        
        .upload-area:hover { 
            border-color: #5a67d8; 
            background: #f0f4ff; 
            transform: translateY(-2px);
        }
        
        .upload-area.dragover { 
            border-color: #4c51bf; 
            background: #e6fffa; 
        }
        
        .upload-icon { 
            font-size: 3rem; 
            margin-bottom: 15px; 
            color: #667eea;
        }
        
        .content-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 25px; 
            margin-top: 30px;
        }
        
        .content-card { 
            background: white; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08); 
            transition: all 0.3s ease;
            border: 1px solid #f1f3f4;
        }
        
        .content-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .content-preview { 
            height: 200px; 
            background: #f8f9fa; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            position: relative;
            overflow: hidden;
        }
        
        .content-preview img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        
        .content-preview video { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        
        .content-type-badge { 
            position: absolute; 
            top: 10px; 
            right: 10px; 
            background: rgba(0,0,0,0.7); 
            color: white; 
            padding: 5px 10px; 
            border-radius: 15px; 
            font-size: 12px; 
            font-weight: 500;
        }
        
        .content-info { 
            padding: 20px; 
        }
        
        .content-title { 
            font-size: 1.1rem; 
            font-weight: 600; 
            margin-bottom: 8px; 
            color: #2d3748;
            line-height: 1.4;
        }
        
        .content-meta { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px; 
            font-size: 13px; 
            color: #718096;
        }
        
        .content-actions { 
            display: flex; 
            gap: 8px; 
        }
        
        .btn-small { 
            padding: 8px 16px; 
            font-size: 12px; 
            border-radius: 6px;
        }
        
        .status { 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 8px; 
            font-weight: 500;
        }
        
        .status.success { 
            background: #d4edda; 
            color: #155724; 
            border-left: 4px solid #28a745;
        }
        
        .status.error { 
            background: #f8d7da; 
            color: #721c24; 
            border-left: 4px solid #dc3545;
        }
        
        .status.info { 
            background: #d1ecf1; 
            color: #0c5460; 
            border-left: 4px solid #17a2b8;
        }
        
        .loading { 
            display: none; 
            text-align: center; 
            padding: 40px; 
        }
        
        .loading-spinner { 
            width: 40px; 
            height: 40px; 
            border: 4px solid #f3f3f3; 
            border-top: 4px solid #667eea; 
            border-radius: 50%; 
            animation: spin 1s linear infinite; 
            margin: 0 auto 20px;
        }
        
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content { 
            background: white; 
            margin: 5% auto; 
            padding: 30px; 
            border-radius: 12px; 
            width: 90%; 
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .modal-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px;
        }
        
        .modal-title { 
            font-size: 1.5rem; 
            font-weight: 600; 
            color: #2d3748;
        }
        
        .close { 
            font-size: 28px; 
            font-weight: bold; 
            cursor: pointer; 
            color: #aaa;
        }
        
        .close:hover { 
            color: #000; 
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500; 
            color: #2d3748;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus { 
            outline: none; 
            border-color: #667eea;
        }
        
        .stats-bar { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px;
        }
        
        .stat-item { 
            background: linear-gradient(135deg, #f8f9ff, #ffffff);
            padding: 20px; 
            border-radius: 12px; 
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        
        .stat-number { 
            font-size: 2rem; 
            font-weight: 700; 
            color: #667eea; 
            margin-bottom: 5px;
        }
        
        .stat-label { 
            color: #718096; 
            font-size: 14px; 
            font-weight: 500;
        }
        
        .empty-state { 
            text-align: center; 
            padding: 60px 20px; 
            color: #718096;
        }
        
        .empty-state-icon { 
            font-size: 4rem; 
            margin-bottom: 20px; 
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .container { 
                margin: 10px; 
                border-radius: 12px;
            }
            
            .header { 
                padding: 20px; 
            }
            
            .main-content { 
                padding: 20px; 
            }
            
            .toolbar { 
                flex-direction: column; 
                align-items: stretch;
            }
            
            .toolbar-left, 
            .toolbar-right { 
                justify-content: center; 
            }
            
            .search-box { 
                width: 100%; 
            }
            
            .content-grid { 
                grid-template-columns: 1fr; 
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Content Management System</h1>
            <p>Upload, manage, and organize your digital signage content</p>
        </div>
        
        <div class="main-content">
            <!-- Statistics Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number" id="total-content">0</div>
                    <div class="stat-label">Total Content</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="active-content">0</div>
                    <div class="stat-label">Active Content</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="total-size">0 MB</div>
                    <div class="stat-label">Total Size</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="last-upload">Never</div>
                    <div class="stat-label">Last Upload</div>
                </div>
            </div>
            
            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <input type="text" class="search-box" id="search-input" placeholder="🔍 Search content...">
                    <select id="type-filter" class="search-box" style="width: auto;">
                        <option value="">All Types</option>
                        <option value="image">Images</option>
                        <option value="video">Videos</option>
                        <option value="audio">Audio</option>
                        <option value="html">HTML</option>
                        <option value="text">Text</option>
                    </select>
                </div>
                <div class="toolbar-right">
                    <button class="btn" onclick="refreshContent()">
                        🔄 Refresh
                    </button>
                    <button class="btn btn-success" onclick="showCreateModal()">
                        ➕ Add Content
                    </button>
                    <button class="btn btn-secondary" onclick="showUploadArea()">
                        📤 Upload Files
                    </button>
                </div>
            </div>
            
            <!-- Upload Area -->
            <div class="upload-area" id="upload-area" style="display: none;">
                <div class="upload-icon">📁</div>
                <h3>Drag & Drop Files Here</h3>
                <p>Or click to browse and select files</p>
                <p><small>Supported: Images (JPG, PNG, GIF), Videos (MP4, WebM), Audio (MP3, WAV), HTML files</small></p>
                <input type="file" id="file-input" multiple accept="image/*,video/*,audio/*,.html" style="display: none;">
            </div>
            
            <!-- Status Messages -->
            <div id="status-container"></div>
            
            <!-- Loading Indicator -->
            <div id="loading" class="loading">
                <div class="loading-spinner"></div>
                <p>Loading content...</p>
            </div>
            
            <!-- Content Grid -->
            <div id="content-grid" class="content-grid"></div>
        </div>
    </div>
    
    <!-- Create/Edit Content Modal -->
    <div id="content-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-title">Add New Content</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="content-form">
                <div class="form-group">
                    <label for="content-title">Title</label>
                    <input type="text" id="content-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content-type">Type</label>
                    <select id="content-type" name="type" required onchange="handleTypeChange()">
                        <option value="">Select Type</option>
                        <option value="text">Text Content</option>
                        <option value="html">HTML Content</option>
                        <option value="image">Image URL</option>
                        <option value="video">Video URL</option>
                    </select>
                </div>
                <div class="form-group" id="content-input-group" style="display: none;">
                    <label for="content-input" id="content-input-label">Content</label>
                    <textarea id="content-input" name="content" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="content-duration">Duration (seconds)</label>
                    <input type="number" id="content-duration" name="duration" value="10" min="1">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success">💾 Save Content</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">❌ Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let apiBase = "/api";
        let currentContent = [];
        let filteredContent = [];
        let editingId = null;

        // Initialize
        document.addEventListener("DOMContentLoaded", function() {
            initializeApp();
            setupEventListeners();
            loadContent();
        });

        function initializeApp() {
            console.log("🎬 Content Management System initialized");
        }

        function setupEventListeners() {
            // Search functionality
            document.getElementById("search-input").addEventListener("input", filterContent);
            document.getElementById("type-filter").addEventListener("change", filterContent);
            
            // Upload area
            const uploadArea = document.getElementById("upload-area");
            const fileInput = document.getElementById("file-input");
            
            uploadArea.addEventListener("click", () => fileInput.click());
            uploadArea.addEventListener("dragover", handleDragOver);
            uploadArea.addEventListener("drop", handleDrop);
            uploadArea.addEventListener("dragleave", handleDragLeave);
            
            fileInput.addEventListener("change", handleFileSelect);
            
            // Modal form
            document.getElementById("content-form").addEventListener("submit", handleFormSubmit);
            
            // Close modal on outside click
            window.addEventListener("click", function(event) {
                const modal = document.getElementById("content-modal");
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        // Content Management Functions
        async function loadContent() {
            showLoading(true);
            try {
                const response = await fetch(`${apiBase}/content`);
                const data = await response.json();
                
                if (data.success) {
                    currentContent = data.data;
                    filteredContent = [...currentContent];
                    displayContent();
                    updateStats();
                    showStatus("Content loaded successfully", "success");
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error("Load content error:", error);
                showStatus("Failed to load content: " + error.message, "error");
                // Show sample content on error
                displaySampleContent();
            }
            showLoading(false);
        }

        function displayContent() {
            const grid = document.getElementById("content-grid");
            
            if (filteredContent.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div class="empty-state-icon">📭</div>
                        <h3>No content found</h3>
                        <p>Upload some content to get started!</p>
                        <button class="btn btn-success" onclick="showUploadArea()" style="margin-top: 20px;">
                            📤 Upload Content
                        </button>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = filteredContent.map(item => createContentCard(item)).join("");
        }

        function createContentCard(item) {
            const typeEmoji = {
                image: "🖼️",
                video: "🎥", 
                audio: "🎵",
                html: "🌐",
                text: "📝",
                other: "📄"
            };

            return `
                <div class="content-card" data-id="${item.id}">
                    <div class="content-preview">
                        ${getPreviewHTML(item)}
                        <div class="content-type-badge">
                            ${typeEmoji[item.type] || typeEmoji.other} ${item.type.toUpperCase()}
                        </div>
                    </div>
                    <div class="content-info">
                        <div class="content-title">${item.title}</div>
                        <div class="content-meta">
                            <span>Duration: ${item.duration || 10}s</span>
                            <span>Status: ${item.status || "active"}</span>
                        </div>
                        <div class="content-actions">
                            <button class="btn btn-small" onclick="editContent(${item.id})">
                                ✏️ Edit
                            </button>
                            <button class="btn btn-small btn-secondary" onclick="previewContent(${item.id})">
                                👁️ Preview
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteContent(${item.id})">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        function getPreviewHTML(item) {
            switch (item.type) {
                case "image":
                    return `<img src="${item.thumbnail_path || item.file_url}" alt="${item.title}" loading="lazy">`;
                
                case "video":
                    if (item.thumbnail_path) {
                        return `<img src="${item.thumbnail_path}" alt="${item.title}" loading="lazy">`;
                    } else {
                        return `<video src="${item.file_url}" muted preload="metadata"></video>`;
                    }
                
                case "audio":
                    return `<div style="display: flex; flex-direction: column; align-items: center; color: #667eea;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">🎵</div>
                        <div style="font-size: 14px;">${item.title}</div>
                    </div>`;
                
                case "html":
                    return `<div style="display: flex; flex-direction: column; align-items: center; color: #667eea;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">🌐</div>
                        <div style="font-size: 14px;">HTML Content</div>
                    </div>`;
                
                case "text":
                    return `<div style="display: flex; flex-direction: column; align-items: center; color: #667eea; padding: 20px; text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 10px;">📝</div>
                        <div style="font-size: 12px; opacity: 0.8; max-height: 60px; overflow: hidden;">
                            ${(item.file_url || "").substring(0, 100)}...
                        </div>
                    </div>`;
                
                default:
                    return `<div style="display: flex; flex-direction: column; align-items: center; color: #667eea;">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📄</div>
                        <div style="font-size: 14px;">${item.type.toUpperCase()}</div>
                    </div>`;
            }
        }

        // File Upload Functions
        function showUploadArea() {
            const uploadArea = document.getElementById("upload-area");
            uploadArea.style.display = uploadArea.style.display === "none" ? "block" : "none";
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.currentTarget.classList.add("dragover");
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.currentTarget.classList.remove("dragover");
        }

        function handleDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove("dragover");
            const files = e.dataTransfer.files;
            uploadFiles(files);
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            uploadFiles(files);
        }

        async function uploadFiles(files) {
            if (files.length === 0) return;
            
            showLoading(true);
            let successCount = 0;
            let errorCount = 0;
            
            for (let file of files) {
                try {
                    const formData = new FormData();
                    formData.append("file", file);
                    formData.append("title", file.name.split(".")[0]);
                    formData.append("type", getFileType(file.type));
                    
                    const response = await fetch(`${apiBase}/upload.php`, {
                        method: "POST",
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        successCount++;
                    } else {
                        errorCount++;
                        console.error(`Upload failed for ${file.name}:`, data.message);
                    }
                } catch (error) {
                    errorCount++;
                    console.error(`Upload error for ${file.name}:`, error);
                }
            }
            
            showLoading(false);
            
            if (successCount > 0) {
                showStatus(`Successfully uploaded ${successCount} file(s)`, "success");
                await loadContent(); // Refresh content list
            }
            
            if (errorCount > 0) {
                showStatus(`Failed to upload ${errorCount} file(s)`, "error");
            }
            
            // Reset file input
            document.getElementById("file-input").value = "";
            document.getElementById("upload-area").style.display = "none";
        }

        function getFileType(mimeType) {
            if (mimeType.startsWith("image/")) return "image";
            if (mimeType.startsWith("video/")) return "video";
            if (mimeType.startsWith("audio/")) return "audio";
            if (mimeType === "text/html") return "html";
            return "other";
        }

        // Content CRUD Functions
        function showCreateModal() {
            editingId = null;
            document.getElementById("modal-title").textContent = "Add New Content";
            document.getElementById("content-form").reset();
            document.getElementById("content-input-group").style.display = "none";
            document.getElementById("content-modal").style.display = "block";
        }

        function editContent(id) {
            const content = currentContent.find(item => item.id == id);
            if (!content) return;
            
            editingId = id;
            document.getElementById("modal-title").textContent = "Edit Content";
            document.getElementById("content-title").value = content.title;
            document.getElementById("content-type").value = content.type;
            document.getElementById("content-duration").value = content.duration || 10;
            
            if (content.type === "text" || content.type === "html") {
                document.getElementById("content-input").value = content.file_url || "";
                handleTypeChange();
            }
            
            document.getElementById("content-modal").style.display = "block";
        }

        async function handleFormSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const contentData = {
                title: formData.get("title"),
                type: formData.get("type"),
                content: formData.get("content"),
                duration: parseInt(formData.get("duration"))
            };
            
            try {
                let response;
                if (editingId) {
                    // Update existing content
                    response = await fetch(`${apiBase}/content/${editingId}`, {
                        method: "PUT",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(contentData)
                    });
                } else {
                    // Create new content
                    response = await fetch(`${apiBase}/content`, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(contentData)
                    });
                }
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus(editingId ? "Content updated successfully" : "Content created successfully", "success");
                    closeModal();
                    await loadContent();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showStatus("Error saving content: " + error.message, "error");
            }
        }

        async function deleteContent(id) {
            if (!confirm("Are you sure you want to delete this content?")) return;
            
            try {
                const response = await fetch(`${apiBase}/content/${id}`, {
                    method: "DELETE"
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus("Content deleted successfully", "success");
                    await loadContent();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                showStatus("Error deleting content: " + error.message, "error");
            }
        }

        function previewContent(id) {
            const content = currentContent.find(item => item.id == id);
            if (!content) return;
            
            // Open preview in new window/tab
            if (content.type === "image" || content.type === "video") {
                window.open(content.file_url, "_blank");
            } else if (content.type === "text") {
                alert(content.file_url);
            } else {
                showStatus("Preview not available for this content type", "info");
            }
        }

        // Filter and Search Functions
        function filterContent() {
            const searchTerm = document.getElementById("search-input").value.toLowerCase();
            const typeFilter = document.getElementById("type-filter").value;
            
            filteredContent = currentContent.filter(item => {
                const matchesSearch = !searchTerm || 
                    item.title.toLowerCase().includes(searchTerm);
                const matchesType = !typeFilter || item.type === typeFilter;
                return matchesSearch && matchesType;
            });
            
            displayContent();
        }

        function refreshContent() {
            loadContent();
        }

        // UI Helper Functions
        function handleTypeChange() {
            const type = document.getElementById("content-type").value;
            const inputGroup = document.getElementById("content-input-group");
            const inputLabel = document.getElementById("content-input-label");
            const input = document.getElementById("content-input");
            
            if (type === "text") {
                inputGroup.style.display = "block";
                inputLabel.textContent = "Text Content";
                input.placeholder = "Enter your text content here...";
            } else if (type === "html") {
                inputGroup.style.display = "block";
                inputLabel.textContent = "HTML Content";
                input.placeholder = "Enter HTML code here...";
            } else if (type === "image" || type === "video") {
                inputGroup.style.display = "block";
                inputLabel.textContent = "File URL";
                input.placeholder = "Enter the URL of your " + type + "...";
            } else {
                inputGroup.style.display = "none";
            }
        }

        function closeModal() {
            document.getElementById("content-modal").style.display = "none";
            editingId = null;
        }

        function showLoading(show) {
            document.getElementById("loading").style.display = show ? "block" : "none";
        }

        function showStatus(message, type) {
            const container = document.getElementById("status-container");
            const statusDiv = document.createElement("div");
            statusDiv.className = `status ${type}`;
            statusDiv.textContent = message;
            
            container.appendChild(statusDiv);
            
            setTimeout(() => {
                statusDiv.remove();
            }, 5000);
        }

        function updateStats() {
            const totalContent = currentContent.length;
            const activeContent = currentContent.filter(item => item.status === "active").length;
            const totalSize = currentContent.reduce((sum, item) => sum + (item.file_size || 0), 0);
            const lastUpload = currentContent.length > 0 ? 
                Math.max(...currentContent.map(item => new Date(item.created_at || "").getTime())) : 0;
            
            document.getElementById("total-content").textContent = totalContent;
            document.getElementById("active-content").textContent = activeContent;
            document.getElementById("total-size").textContent = (totalSize / 1024 / 1024).toFixed(1) + " MB";
            document.getElementById("last-upload").textContent = lastUpload > 0 ? 
                new Date(lastUpload).toLocaleDateString() : "Never";
        }

        function displaySampleContent() {
            currentContent = [
                {
                    id: 1,
                    title: "Welcome Message",
                    type: "image",
                    file_url: "https://picsum.photos/800/600?text=Welcome",
                    thumbnail_path: "https://picsum.photos/300/200?text=Welcome",
                    duration: 10,
                    status: "active",
                    created_at: "2024-01-01 12:00:00"
                },
                {
                    id: 2,
                    title: "Sample Video",
                    type: "video",
                    file_url: "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                    duration: 30,
                    status: "active",
                    created_at: "2024-01-01 12:00:00"
                }
            ];
            filteredContent = [...currentContent];
            displayContent();
            updateStats();
        }
    </script>
</body>
</html>';

file_put_contents('admin/content-complete.html', $completeContentManager);
echo "✅ Created complete content management interface<br>";
$steps[] = "Complete content interface created";

// ===============================================================
// Step 4: อัปเดต Admin Dashboard
// ===============================================================

echo "<h4>🏠 Step 4: Updating Admin Dashboard</h4>";

if (file_exists('admin/index.php')) {
    $adminContent = file_get_contents('admin/index.php');
    
    // Add link to new content manager
    $newLink = '<a href="content-complete.html" class="btn">📁 Complete Content Manager</a>';
    $adminContent = str_replace(
        '<a href="content.html" class="btn">📁 Manage Content</a>',
        $newLink . ' <a href="content.html" class="btn">📁 Basic Content</a>',
        $adminContent
    );
    
    file_put_contents('admin/index.php', $adminContent);
    echo "✅ Updated admin dashboard with link to complete content manager<br>";
    $steps[] = "Admin dashboard updated";
}

// ===============================================================
// Step 5: สร้าง .htaccess สำหรับ uploads security
// ===============================================================

echo "<h4>🔒 Step 5: Creating Security Files</h4>";

$uploadsHtaccess = 'Options -Indexes
<Files *.php>
    Deny from all
</Files>

# Allow only specific file types
<FilesMatch "\.(jpg|jpeg|png|gif|webp|mp4|webm|avi|mov|mp3|wav|ogg|html|zip)$">
    Allow from all
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options nosniff
    Header set X-Frame-Options DENY
</IfModule>';

file_put_contents('uploads/.htaccess', $uploadsHtaccess);
echo "✅ Created uploads security file<br>";

// ===============================================================
// Summary
// ===============================================================

echo "<h3>🎉 Complete Content Management System Created!</h3>";
echo "<p><strong>Components created:</strong></p>";
echo "<ul>";
foreach ($steps as $step) {
    echo "<li>✅ $step</li>";
}
echo "</ul>";

echo "<h3>🔗 Access Your New System:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='admin/content-complete.html' target='_blank' style='background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; margin: 10px;'>🎬 Complete Content Manager</a><br><br>";
echo "<a href='admin/' target='_blank' style='background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; margin: 10px;'>🏠 Admin Dashboard</a><br><br>";
echo "<a href='debug-test.html' target='_blank' style='background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; margin: 10px;'>🧪 API Test</a>";
echo "</div>";

echo "<h3>🎯 Features Available:</h3>";
echo "<ul>";
echo "<li>✅ <strong>File Upload</strong> - Drag & drop multiple files</li>";
echo "<li>✅ <strong>Content CRUD</strong> - Create, Read, Update, Delete content</li>";
echo "<li>✅ <strong>Search & Filter</strong> - Find content quickly</li>";
echo "<li>✅ <strong>Preview</strong> - Preview content before publishing</li>";
echo "<li>✅ <strong>Statistics</strong> - Track content usage</li>";
echo "<li>✅ <strong>Responsive Design</strong> - Works on all devices</li>";
echo "<li>✅ <strong>Real File Storage</strong> - Actual file upload to server</li>";
echo "<li>✅ <strong>Thumbnail Generation</strong> - Auto-generated thumbnails</li>";
echo "<li>✅ <strong>Multiple Content Types</strong> - Images, videos, text, HTML</li>";
echo "</ul>";

echo "<h3>📋 How to Use:</h3>";
echo "<ol>";
echo "<li><strong>Upload Files:</strong> Click 'Upload Files' and drag files or browse</li>";
echo "<li><strong>Add Text/HTML:</strong> Click 'Add Content' to create text or HTML content</li>";
echo "<li><strong>Edit Content:</strong> Click 'Edit' on any content card</li>";
echo "<li><strong>Search:</strong> Use the search box to find specific content</li>";
echo "<li><strong>Filter:</strong> Use the type dropdown to filter by content type</li>";
echo "<li><strong>Preview:</strong> Click 'Preview' to see content before publishing</li>";
echo "</ol>";

echo "<script>";
echo "setTimeout(() => {";
echo "  if (confirm('🎬 Complete Content Management System is ready! Open it now?')) {";
echo "    window.open('admin/content-complete.html', '_blank');";
echo "  }";
echo "}, 2000);";
echo "</script>";
?>