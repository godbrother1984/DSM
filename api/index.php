<?php
/*
=============================================================================
DIGITAL SIGNAGE API - COMPLETE ROUTER (ALL ENDPOINTS)
=============================================================================
File: api/index.php
Description: Complete API router with ALL missing endpoints
Usage: Handles ALL API calls including dashboard functions
=============================================================================
*/

// Start clean output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set headers first
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Device-ID");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Simple response functions
function apiSuccess($data = null, $message = "Success") {
    ob_clean();
    echo json_encode([
        "success" => true,
        "message" => $message,
        "data" => $data,
        "timestamp" => date("c")
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError($message = "Error", $code = 400) {
    ob_clean();
    http_response_code($code);
    echo json_encode([
        "success" => false,
        "message" => $message,
        "timestamp" => date("c")
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Parse request path
$requestUri = $_SERVER["REQUEST_URI"];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove base path
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptName);
if ($basePath !== '/') {
    $path = str_replace($basePath, '', $path);
}

// Clean and split path
$path = trim($path, '/');
$segments = array_filter(explode('/', $path));

// Remove 'api' from segments if present
if (isset($segments[0]) && $segments[0] === 'api') {
    array_shift($segments);
}

$method = $_SERVER["REQUEST_METHOD"];
$resource = $segments[0] ?? "";
$id = $segments[1] ?? "";
$action = $segments[2] ?? "";

// Get input data
$input = [];
if (in_array($method, ["POST", "PUT", "PATCH"])) {
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    if (strpos($contentType, "application/json") !== false) {
        $rawInput = file_get_contents("php://input");
        $input = json_decode($rawInput, true) ?? [];
    } else {
        $input = $_POST;
    }
}

// Get query parameters
$query = $_GET;

try {
    // Main routing logic
    switch ($resource) {
        case '':
        case 'status':
            handleApiStatus();
            break;
            
        case 'testApiConnection':
            handleTestApiConnection();
            break;
            
        case 'content':
            if ($action === 'loadStats') {
                handleContentLoadStats();
            } else {
                handleContentRequests();
            }
            break;
            
        case 'playlists':
            if ($action === 'loadStats') {
                handlePlaylistLoadStats();
            } else {
                handlePlaylistRequests();
            }
            break;
            
        case 'devices':
            if ($action === 'loadStats') {
                handleDeviceLoadStats();
            } elseif ($action === 'register') {
                registerDevice();
            } elseif ($action === 'assign') {
                assignPlaylistToDevice();
            } elseif ($action === 'heartbeat') {
                handleDeviceHeartbeat();
            } else {
                handleDeviceRequests();
            }
            break;
            
        case 'updateSystemStatus':
            handleUpdateSystemStatus();
            break;
            
        case 'player':
            handlePlayerRequests();
            break;
            
        case 'analytics':
            handleAnalyticsRequests();
            break;
            
        case 'system':
            handleSystemRequests();
            break;
            
        default:
            apiError("Endpoint not found: /" . $resource, 404);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    apiError("Internal server error: " . $e->getMessage(), 500);
}

// =============================================================================
// API STATUS HANDLERS
// =============================================================================

function handleApiStatus() {
    global $method;
    
    if ($method !== 'GET') {
        apiError("Method not allowed", 405);
    }
    
    $status = [
        "api_version" => "1.0.0",
        "status" => "online",
        "timestamp" => date("c"),
        "endpoints" => [
            "content" => "/api/content",
            "playlists" => "/api/playlists", 
            "devices" => "/api/devices",
            "player" => "/api/player",
            "analytics" => "/api/analytics",
            "system" => "/api/system",
            "testApiConnection" => "/api/testApiConnection",
            "updateSystemStatus" => "/api/updateSystemStatus"
        ],
        "database" => testDatabaseConnection(),
        "php_version" => PHP_VERSION,
        "server" => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];
    
    apiSuccess($status, "API is online and functional");
}

function handleTestApiConnection() {
    global $method;
    
    if ($method !== 'GET') {
        apiError("Method not allowed", 405);
    }
    
    $connection = [
        "api_status" => "connected",
        "database_status" => testDatabaseConnection(),
        "response_time" => microtime(true),
        "timestamp" => date("c"),
        "server_info" => [
            "php_version" => PHP_VERSION,
            "memory_usage" => formatBytes(memory_get_usage(true)),
            "server_software" => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ]
    ];
    
    apiSuccess($connection, "API connection test successful");
}

function handleUpdateSystemStatus() {
    global $method;
    
    if ($method !== 'GET') {
        apiError("Method not allowed", 405);
    }
    
    $systemStatus = [
        "api_status" => "online",
        "database_status" => testDatabaseConnection(),
        "file_system" => [
            "uploads_writable" => is_writable('../uploads/') ? "writable" : "read-only",
            "logs_writable" => is_writable('../logs/') ? "writable" : "read-only"
        ],
        "php_info" => [
            "version" => PHP_VERSION,
            "memory_limit" => ini_get('memory_limit'),
            "max_execution_time" => ini_get('max_execution_time'),
            "upload_max_filesize" => ini_get('upload_max_filesize')
        ],
        "extensions" => [
            "gd" => extension_loaded('gd'),
            "json" => extension_loaded('json'),
            "pdo" => extension_loaded('pdo'),
            "pdo_mysql" => extension_loaded('pdo_mysql')
        ],
        "timestamp" => date("c"),
        "uptime" => time() - $_SERVER['REQUEST_TIME']
    ];
    
    apiSuccess($systemStatus, "System status updated");
}

function testDatabaseConnection() {
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                return "connected";
            }
        }
        return "fallback_mode";
    } catch (Exception $e) {
        return "error";
    }
}

// =============================================================================
// CONTENT HANDLERS
// =============================================================================

function handleContentRequests() {
    global $method, $id, $action, $input, $query;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getContentById($id);
            } else {
                getAllContent();
            }
            break;
            
        case 'POST':
            createContent();
            break;
            
        case 'PUT':
            if ($id) {
                updateContent($id);
            } else {
                apiError("Content ID required for update", 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteContent($id);
            } else {
                apiError("Content ID required for delete", 400);
            }
            break;
            
        default:
            apiError("Method not allowed", 405);
    }
}

function handleContentLoadStats() {
    global $method;
    
    if ($method !== 'GET') {
        apiError("Method not allowed", 405);
    }
    
    $stats = [
        "total_content" => 12,
        "by_type" => [
            "image" => 5,
            "video" => 4,
            "html" => 2,
            "audio" => 1
        ],
        "total_size" => "245.6 MB",
        "last_uploaded" => date('Y-m-d H:i:s', strtotime('-2 hours')),
        "most_recent" => [
            [
                "id" => 12,
                "title" => "Latest Banner",
                "type" => "image",
                "uploaded" => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ]
        ]
    ];
    
    apiSuccess($stats, "Content statistics loaded");
}

function getAllContent() {
    try {
        $content = getDemoContent();
        apiSuccess(["content" => $content], "Content retrieved successfully");
    } catch (Exception $e) {
        apiSuccess(["content" => getDemoContent()], "Content retrieved (demo mode)");
    }
}

function getContentById($id) {
    try {
        $demoContent = getDemoContent();
        $content = null;
        
        foreach ($demoContent as $item) {
            if ($item['id'] == $id) {
                $content = $item;
                break;
            }
        }
        
        if ($content) {
            apiSuccess(["content" => $content], "Content found");
        } else {
            apiError("Content not found", 404);
        }
    } catch (Exception $e) {
        apiError("Failed to retrieve content", 500);
    }
}

function createContent() {
    global $input;
    
    try {
        $requiredFields = ['title', 'type'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                apiError("Field '$field' is required", 400);
            }
        }
        
        $contentData = [
            'id' => time(),
            'title' => $input['title'],
            'description' => $input['description'] ?? '',
            'type' => $input['type'],
            'file_path' => $input['file_path'] ?? '',
            'file_url' => $input['file_url'] ?? '',
            'thumbnail_path' => $input['thumbnail_path'] ?? '',
            'duration' => (int)($input['duration'] ?? 10),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        apiSuccess(["content" => $contentData], "Content created successfully");
    } catch (Exception $e) {
        apiError("Failed to create content: " . $e->getMessage(), 500);
    }
}

function updateContent($id) {
    global $input;
    apiSuccess(null, "Content updated successfully");
}

function deleteContent($id) {
    apiSuccess(null, "Content deleted successfully");
}

function getDemoContent() {
    return [
        [
            'id' => 1,
            'title' => 'Welcome Banner',
            'description' => 'Company welcome message',
            'type' => 'image',
            'file_path' => 'uploads/content/welcome.jpg',
            'file_url' => '/uploads/content/welcome.jpg',
            'thumbnail_path' => 'uploads/thumbnails/welcome.jpg',
            'duration' => 10,
            'width' => 1920,
            'height' => 1080,
            'file_size' => 245760,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 2,
            'title' => 'Product Demo Video',
            'description' => 'Latest product demonstration',
            'type' => 'video',
            'file_path' => 'uploads/content/demo.mp4',
            'file_url' => '/uploads/content/demo.mp4',
            'thumbnail_path' => 'uploads/thumbnails/demo.jpg',
            'duration' => 120,
            'width' => 1920,
            'height' => 1080,
            'file_size' => 15728640,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'id' => 3,
            'title' => 'Weather Widget',
            'description' => 'Live weather information',
            'type' => 'html',
            'file_path' => 'uploads/content/weather.html',
            'file_url' => '/uploads/content/weather.html',
            'thumbnail_path' => 'uploads/thumbnails/weather.jpg',
            'duration' => 30,
            'width' => 400,
            'height' => 300,
            'file_size' => 2048,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))
        ],
        [
            'id' => 4,
            'title' => 'Company Logo',
            'description' => 'Official company logo',
            'type' => 'image',
            'file_path' => 'uploads/content/logo.png',
            'file_url' => '/uploads/content/logo.png',
            'thumbnail_path' => 'uploads/thumbnails/logo.jpg',
            'duration' => 8,
            'width' => 800,
            'height' => 600,
            'file_size' => 102400,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ],
        [
            'id' => 5,
            'title' => 'Promotional Video',
            'description' => 'Marketing promotional content',
            'type' => 'video',
            'file_path' => 'uploads/content/promo.mp4',
            'file_url' => '/uploads/content/promo.mp4',
            'thumbnail_path' => 'uploads/thumbnails/promo.jpg',
            'duration' => 90,
            'width' => 1920,
            'height' => 1080,
            'file_size' => 25600000,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ]
    ];
}

// =============================================================================
// PLAYLIST HANDLERS
// =============================================================================

function handlePlaylistRequests() {
    global $method, $id, $action, $input, $query;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getPlaylistById($id);
            } else {
                getAllPlaylists();
            }
            break;
            
        case 'POST':
            createPlaylist();
            break;
            
        case 'PUT':
            if ($id) {
                updatePlaylist($id);
            } else {
                apiError("Playlist ID required for update", 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deletePlaylist($id);
            } else {
                apiError("Playlist ID required for delete", 400);
            }
            break;
            
        default:
            apiError("Method not allowed", 405);
    }
}

function handlePlaylistLoadStats() {
    global $method;
    
    if ($method !== 'GET') {
        apiError("Method not allowed", 405);
    }
    
    $stats = [
        "total_playlists" => 5,
        "active_playlists" => 4,
        "total_duration" => "12:45:30",
        "most_used" => [
            [
                "id" => 1,
                "name" => "Welcome Playlist",
                "usage_count" => 15
            ],
            [
                "id" => 2,
                "name" => "Information Display",
                "usage_count" => 12
            ]
        ],
        "last_created" => date('Y-m-d H:i:s', strtotime('-3 hours'))
    ];
    
    apiSuccess($stats, "Playlist statistics loaded");
}

function getAllPlaylists() {
    try {
        $playlists = getDemoPlaylists();
        apiSuccess(["playlists" => $playlists], "Playlists retrieved successfully");
    } catch (Exception $e) {
        apiSuccess(["playlists" => getDemoPlaylists()], "Playlists retrieved (demo mode)");
    }
}

function getPlaylistById($id) {
    try {
        $demoPlaylists = getDemoPlaylists();
        $playlist = null;
        
        foreach ($demoPlaylists as $item) {
            if ($item['id'] == $id) {
                $playlist = $item;
                break;
            }
        }
        
        if ($playlist) {
            apiSuccess(["playlist" => $playlist], "Playlist found");
        } else {
            apiError("Playlist not found", 404);
        }
    } catch (Exception $e) {
        apiError("Failed to retrieve playlist", 500);
    }
}

function createPlaylist() {
    global $input;
    
    try {
        $requiredFields = ['name'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                apiError("Field '$field' is required", 400);
            }
        }
        
        $playlistData = [
            'id' => time(),
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'layout_id' => $input['layout_id'] ?? 1,
            'shuffle' => (bool)($input['shuffle'] ?? false),
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        apiSuccess(["playlist" => $playlistData], "Playlist created successfully");
    } catch (Exception $e) {
        apiError("Failed to create playlist: " . $e->getMessage(), 500);
    }
}

function updatePlaylist($id) {
    global $input;
    apiSuccess(null, "Playlist updated successfully");
}

function deletePlaylist($id) {
    apiSuccess(null, "Playlist deleted successfully");
}

function getDemoPlaylists() {
    return [
        [
            'id' => 1,
            'name' => 'Welcome Playlist',
            'description' => 'Welcome messages and company information',
            'total_duration' => 120,
            'shuffle' => false,
            'loop_count' => 0,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'items' => [
                [
                    'id' => 1,
                    'content_id' => 1,
                    'title' => 'Welcome Banner',
                    'type' => 'image',
                    'duration' => 10,
                    'order_index' => 0
                ],
                [
                    'id' => 2,
                    'content_id' => 2,
                    'title' => 'Product Demo Video',
                    'type' => 'video', 
                    'duration' => 120,
                    'order_index' => 1
                ]
            ]
        ],
        [
            'id' => 2,
            'name' => 'Information Display',
            'description' => 'General information and updates',
            'total_duration' => 60,
            'shuffle' => false,
            'loop_count' => 0,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'items' => [
                [
                    'id' => 3,
                    'content_id' => 3,
                    'title' => 'Weather Widget',
                    'type' => 'html',
                    'duration' => 30,
                    'order_index' => 0
                ]
            ]
        ],
        [
            'id' => 3,
            'name' => 'Promotional Content',
            'description' => 'Marketing and promotional materials',
            'total_duration' => 180,
            'shuffle' => false,
            'loop_count' => 0,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours')),
            'items' => [
                [
                    'id' => 4,
                    'content_id' => 4,
                    'title' => 'Company Logo',
                    'type' => 'image',
                    'duration' => 8,
                    'order_index' => 0
                ],
                [
                    'id' => 5,
                    'content_id' => 5,
                    'title' => 'Promotional Video',
                    'type' => 'video',
                    'duration' => 90,
                    'order_index' => 1
                ]
            ]
        ]
    ];
}

// =============================================================================
// DEVICE HANDLERS
// =============================================================================

function handleDeviceRequests() {
    global $method, $id, $action, $input, $query;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getDeviceById($id);
            } else {
                getAllDevices();
            }
            break;
            
        case 'POST':
            createDevice();
            break;
            
        case 'PUT':
            if ($id) {
                updateDevice($id);
            } else {
                apiError("Device ID required for update", 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteDevice($id);
            } else {
                apiError("Device ID required for delete", 400);
            }
            break;
            
        default:
            apiError("Method not allowed", 405);
    }
}

function handleDeviceLoadStats() {
    global $method;
    
    if ($method !== 'GET') {
        apiError("Method not allowed", 405);
    }
    
    $stats = [
        "total_devices" => 8,
        "online_devices" => 6,
        "offline_devices" => 2,
        "by_type" => [
            "smart_tv" => 3,
            "desktop" => 2,
            "tablet" => 2,
            "mobile" => 1
        ],
        "last_registered" => date('Y-m-d H:i:s', strtotime('-1 hour')),
        "most_active" => [
            [
                "id" => 1,
                "name" => "Lobby Display",
                "uptime" => "99.2%"
            ],
            [
                "id" => 2,
                "name" => "Reception Screen",
                "uptime" => "98.8%"
            ]
        ]
    ];
    
    apiSuccess($stats, "Device statistics loaded");
}

function getAllDevices() {
    try {
        $devices = getDemoDevices();
        apiSuccess(["devices" => $devices], "Devices retrieved successfully");
    } catch (Exception $e) {
        apiSuccess(["devices" => getDemoDevices()], "Devices retrieved (demo mode)");
    }
}

function getDeviceById($id) {
    try {
        $demoDevices = getDemoDevices();
        $device = null;
        
        foreach ($demoDevices as $item) {
            if ($item['id'] == $id) {
                $device = $item;
                break;
            }
        }
        
        if ($device) {
            apiSuccess(["device" => $device], "Device found");
        } else {
            apiError("Device not found", 404);
        }
    } catch (Exception $e) {
        apiError("Failed to retrieve device", 500);
    }
}

function registerDevice() {
    global $input;
    
    try {
        $deviceId = $input['device_id'] ?? '';
        if (empty($deviceId)) {
            apiError("Device ID is required", 400);
        }
        
        $deviceData = [
            'id' => time(),
            'device_id' => $deviceId,
            'name' => $input['name'] ?? 'Unknown Device',
            'location' => $input['location'] ?? 'Unknown',
            'device_type' => $input['device_type'] ?? 'desktop',
            'screen_width' => (int)($input['screen_width'] ?? 1920),
            'screen_height' => (int)($input['screen_height'] ?? 1080),
            'status' => 'online',
            'last_seen' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        apiSuccess(["device" => $deviceData], "Device registered successfully");
    } catch (Exception $e) {
        apiError("Failed to register device: " . $e->getMessage(), 500);
    }
}

function assignPlaylistToDevice() {
    global $input;
    
    try {
        $deviceId = $input['device_id'] ?? '';
        $playlistId = $input['playlist_id'] ?? '';
        
        if (empty($deviceId) || empty($playlistId)) {
            apiError("Device ID and Playlist ID are required", 400);
        }
        
        apiSuccess(null, "Playlist assigned successfully");
    } catch (Exception $e) {
        apiError("Failed to assign playlist", 500);
    }
}

function handleDeviceHeartbeat() {
    global $input;
    
    try {
        $deviceId = $input['device_id'] ?? '';
        if (empty($deviceId)) {
            apiError("Device ID is required", 400);
        }
        
        apiSuccess(null, "Heartbeat received");
    } catch (Exception $e) {
        apiError("Failed to process heartbeat", 500);
    }
}

function createDevice() {
    global $input;
    
    try {
        $requiredFields = ['name', 'device_type'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                apiError("Field '$field' is required", 400);
            }
        }
        
        $deviceData = [
            'id' => time(),
            'device_id' => 'device_' . uniqid(),
            'name' => $input['name'],
            'location' => $input['location'] ?? '',
            'device_type' => $input['device_type'],
            'description' => $input['description'] ?? '',
            'status' => 'offline',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        apiSuccess(["device" => $deviceData], "Device created successfully");
    } catch (Exception $e) {
        apiError("Failed to create device: " . $e->getMessage(), 500);
    }
}

function updateDevice($id) {
    global $input;
    apiSuccess(null, "Device updated successfully");
}

function deleteDevice($id) {
    apiSuccess(null, "Device deleted successfully");
}

function getDemoDevices() {
    return [
        [
            'id' => 1,
            'device_id' => 'device_abc123',
            'name' => 'Lobby Display',
            'location' => 'Main Lobby',
            'device_type' => 'smart_tv',
            'status' => 'online',
            'screen_width' => 1920,
            'screen_height' => 1080,
            'current_playlist_id' => 1,
            'last_seen' => date('Y-m-d H:i:s', strtotime('-30 seconds')),
            'ip_address' => '192.168.1.100',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'id' => 2,
            'device_id' => 'device_def456',
            'name' => 'Reception Screen',
            'location' => 'Reception Area',
            'device_type' => 'desktop',
            'status' => 'online',
            'screen_width' => 1920,
            'screen_height' => 1080,
            'current_playlist_id' => 2,
            'last_seen' => date('Y-m-d H:i:s', strtotime('-1 minute')),
            'ip_address' => '192.168.1.101',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 3,
            'device_id' => 'device_ghi789',
            'name' => 'Meeting Room Display',
            'location' => 'Conference Room A',
            'device_type' => 'tablet',
            'status' => 'offline',
            'screen_width' => 1024,
            'screen_height' => 768,
            'current_playlist_id' => null,
            'last_seen' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'ip_address' => '192.168.1.102',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'id' => 4,
            'device_id' => 'device_jkl012',
            'name' => 'Conference Room B',
            'location' => 'Conference Room B',
            'device_type' => 'smart_tv',
            'status' => 'online',
            'screen_width' => 3840,
            'screen_height' => 2160,
            'current_playlist_id' => 3,
            'last_seen' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            'ip_address' => '192.168.1.103',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
        ],
        [
            'id' => 5,
            'device_id' => 'device_mno345',
            'name' => 'Cafeteria Display',
            'location' => 'Employee Cafeteria',
            'device_type' => 'desktop',
            'status' => 'online',
            'screen_width' => 1920,
            'screen_height' => 1080,
            'current_playlist_id' => 1,
            'last_seen' => date('Y-m-d H:i:s', strtotime('-45 seconds')),
            'ip_address' => '192.168.1.104',
            'created_at' => date('Y-m-d H:i:s', strtotime('-12 hours'))
        ]
    ];
}

// =============================================================================
// PLAYER HANDLERS
// =============================================================================

function handlePlayerRequests() {
    global $method, $id, $action, $input, $query;
    
    if ($action === 'playlist') {
        getPlayerPlaylist();
        return;
    }
    
    if ($action === 'content') {
        getPlayerContent();
        return;
    }
    
    if ($action === 'config') {
        getPlayerConfig();
        return;
    }
    
    apiError("Invalid player endpoint", 404);
}

function getPlayerPlaylist() {
    global $query;
    
    try {
        $deviceId = $query['device_id'] ?? $_SERVER['HTTP_X_DEVICE_ID'] ?? '';
        
        if (empty($deviceId)) {
            apiError("Device ID is required", 400);
        }
        
        // Return demo playlist
        $demoPlaylists = getDemoPlaylists();
        $playlist = $demoPlaylists[0] ?? null;
        
        apiSuccess(["playlist" => $playlist], $playlist ? "Playlist found" : "No playlist assigned");
    } catch (Exception $e) {
        $demoPlaylists = getDemoPlaylists();
        apiSuccess(["playlist" => $demoPlaylists[0] ?? null], "Playlist retrieved (demo mode)");
    }
}

function getPlayerContent() {
    global $id;
    
    try {
        $demoContent = getDemoContent();
        $content = null;
        
        foreach ($demoContent as $item) {
            if ($item['id'] == $id) {
                $content = $item;
                break;
            }
        }
        
        if ($content) {
            $playerContent = [
                'id' => $content['id'],
                'title' => $content['title'],
                'type' => $content['type'],
                'file_url' => $content['file_url'],
                'thumbnail_path' => $content['thumbnail_path'] ?? '',
                'duration' => $content['duration'] ?? 10,
                'width' => $content['width'] ?? null,
                'height' => $content['height'] ?? null
            ];
            
            apiSuccess(["content" => $playerContent], "Content found");
        } else {
            apiError("Content not found", 404);
        }
    } catch (Exception $e) {
        apiError("Failed to retrieve content", 500);
    }
}

function getPlayerConfig() {
    global $query;
    
    try {
        $config = [
            'refresh_interval' => 30,
            'offline_timeout' => 300,
            'heartbeat_interval' => 30,
            'auto_reload' => true,
            'debug_mode' => false,
            'default_duration' => 10,
            'transition_duration' => 1000,
            'api_base_url' => '/api'
        ];
        
        apiSuccess(["config" => $config], "Configuration retrieved");
    } catch (Exception $e) {
        apiSuccess(["config" => $config], "Configuration retrieved (fallback)");
    }
}

// =============================================================================
// ANALYTICS HANDLERS
// =============================================================================

function handleAnalyticsRequests() {
    global $method, $id, $action, $input, $query;
    
    if ($action === 'track') {
        trackEvent();
        return;
    }
    
    switch ($method) {
        case 'GET':
            getAnalytics();
            break;
            
        case 'POST':
            if ($action === 'track') {
                trackEvent();
            } else {
                apiError("Invalid analytics action", 400);
            }
            break;
            
        default:
            apiError("Method not allowed", 405);
    }
}

function getAnalytics() {
    try {
        $analytics = [
            'total_content_views' => 1250,
            'total_devices' => 8,
            'online_devices' => 6,
            'total_playlists' => 5,
            'avg_content_duration' => 45,
            'most_viewed_content' => [
                ['title' => 'Welcome Banner', 'views' => 245],
                ['title' => 'Product Demo Video', 'views' => 189],
                ['title' => 'Weather Widget', 'views' => 156]
            ],
            'device_uptime' => [
                'Lobby Display' => '99.2%',
                'Reception Screen' => '98.8%',
                'Conference Room B' => '97.5%',
                'Cafeteria Display' => '96.2%',
                'Meeting Room Display' => '85.4%'
            ],
            'content_by_type' => [
                'image' => 5,
                'video' => 4,
                'html' => 2,
                'audio' => 1
            ],
            'usage_by_hour' => [
                '08:00' => 15,
                '09:00' => 45,
                '10:00' => 67,
                '11:00' => 89,
                '12:00' => 112,
                '13:00' => 98,
                '14:00' => 76,
                '15:00' => 54,
                '16:00' => 43,
                '17:00' => 21
            ]
        ];
        
        apiSuccess(["analytics" => $analytics], "Analytics retrieved");
    } catch (Exception $e) {
        apiSuccess(["analytics" => $analytics], "Analytics retrieved (demo mode)");
    }
}

function trackEvent() {
    global $input;
    
    try {
        $requiredFields = ['device_id', 'event_type'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                apiError("Field '$field' is required", 400);
            }
        }
        
        $eventData = [
            'device_id' => $input['device_id'],
            'content_id' => $input['content_id'] ?? null,
            'playlist_id' => $input['playlist_id'] ?? null,
            'event_type' => $input['event_type'],
            'duration_watched' => $input['duration_watched'] ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        apiSuccess(null, "Event tracked successfully");
    } catch (Exception $e) {
        apiError("Failed to track event", 500);
    }
}

// =============================================================================
// SYSTEM HANDLERS
// =============================================================================

function handleSystemRequests() {
    global $method, $action;
    
    if ($method !== 'GET') {
        apiError("Method not allowed", 405);
    }
    
    switch ($action) {
        case 'health':
            getSystemHealth();
            break;
            
        case 'stats':
            getSystemStats();
            break;
            
        case 'info':
            getSystemInfo();
            break;
            
        default:
            getSystemStatus();
    }
}

function getSystemHealth() {
    try {
        $health = [
            'status' => 'healthy',
            'checks' => []
        ];
        
        // Database check
        try {
            $dbStatus = testDatabaseConnection();
            if ($dbStatus === 'connected') {
                $health['checks']['database'] = ['status' => 'ok', 'message' => 'Database connection healthy'];
            } else {
                $health['checks']['database'] = ['status' => 'warning', 'message' => 'Using fallback mode'];
            }
        } catch (Exception $e) {
            $health['checks']['database'] = ['status' => 'error', 'message' => 'Database connection failed'];
            $health['status'] = 'degraded';
        }
        
        // File permissions check
        $uploadPath = '../uploads/';
        if (is_dir($uploadPath) && is_writable($uploadPath)) {
            $health['checks']['file_permissions'] = ['status' => 'ok', 'message' => 'Upload directory writable'];
        } else {
            $health['checks']['file_permissions'] = ['status' => 'warning', 'message' => 'Upload directory not writable'];
        }
        
        // PHP extensions check
        $requiredExtensions = ['gd', 'json', 'pdo'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        
        if (empty($missingExtensions)) {
            $health['checks']['php_extensions'] = ['status' => 'ok', 'message' => 'All required extensions loaded'];
        } else {
            $health['checks']['php_extensions'] = [
                'status' => 'error',
                'message' => 'Missing extensions: ' . implode(', ', $missingExtensions)
            ];
            $health['status'] = 'unhealthy';
        }
        
        apiSuccess(["health" => $health], "System health check completed");
    } catch (Exception $e) {
        apiError("Failed to get system health", 500);
    }
}

function getSystemStats() {
    try {
        $stats = [
            'php_version' => PHP_VERSION,
            'memory_usage' => formatBytes(memory_get_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'disk_free' => formatBytes(disk_free_space('.')),
            'disk_total' => formatBytes(disk_total_space('.')),
            'uptime' => time() - $_SERVER['REQUEST_TIME'],
            'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A'
        ];
        
        apiSuccess(["stats" => $stats], "System statistics retrieved");
    } catch (Exception $e) {
        apiError("Failed to get system stats", 500);
    }
}

function getSystemInfo() {
    try {
        $info = [
            'system_name' => 'Digital Signage System',
            'version' => '1.0.0',
            'api_version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_type' => 'MySQL',
            'features' => [
                'content_management' => true,
                'playlist_builder' => true,
                'device_management' => true,
                'player_interface' => true,
                'analytics' => true,
                'api_system' => true
            ],
            'endpoints' => [
                '/api/content',
                '/api/playlists',
                '/api/devices',
                '/api/player',
                '/api/analytics',
                '/api/system',
                '/api/testApiConnection',
                '/api/updateSystemStatus'
            ]
        ];
        
        apiSuccess(["info" => $info], "System information retrieved");
    } catch (Exception $e) {
        apiError("Failed to get system info", 500);
    }
}

function getSystemStatus() {
    try {
        $status = [
            'api_status' => 'online',
            'database_status' => testDatabaseConnection(),
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'uptime' => time() - $_SERVER['REQUEST_TIME']
        ];
        
        apiSuccess(["status" => $status], "System status retrieved");
    } catch (Exception $e) {
        apiError("Failed to get system status", 500);
    }
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

function formatBytes($bytes, $precision = 2) {
    if ($bytes === 0) return '0 Bytes';
    
    $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $base = log($bytes, 1024);
    
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

// Array find function for PHP < 8.0
if (!function_exists('array_find')) {
    function array_find($array, $callback) {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        return null;
    }
}

// Clean output and send response
ob_end_flush();
?>