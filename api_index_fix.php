<?php
/*
=============================================================================
DIGITAL SIGNAGE API - MAIN ROUTER (FIXED VERSION)
=============================================================================
File: api/index.php
Description: Main API router that handles all API endpoints
Usage: All API calls go through this file
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

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON output

// Include necessary files with error handling
$requiredFiles = [
    '../includes/Database.php',
    '../includes/ApiResponse.php',
    '../includes/Helpers.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
    }
}

// Simple response functions
function apiSuccess($data = null, $message = "Success") {
    ob_clean();
    echo json_encode([
        "success" => true,
        "message" => $message,
        "data" => $data,
        "timestamp" => date("c")
    ]);
    exit;
}

function apiError($message = "Error", $code = 400) {
    ob_clean();
    http_response_code($code);
    echo json_encode([
        "success" => false,
        "message" => $message,
        "timestamp" => date("c")
    ]);
    exit;
}

// Parse request path
$requestUri = $_SERVER["REQUEST_URI"];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove script name from path if present
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
            // API status endpoint
            handleApiStatus();
            break;
            
        case 'content':
            handleContentRequests();
            break;
            
        case 'playlists':
            handlePlaylistRequests();
            break;
            
        case 'devices':
            handleDeviceRequests();
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
    apiError("Internal server error", 500);
}

// =============================================================================
// API STATUS HANDLER
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
            "system" => "/api/system"
        ],
        "database" => testDatabaseConnection(),
        "php_version" => PHP_VERSION,
        "server" => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];
    
    apiSuccess($status, "API is online and functional");
}

function testDatabaseConnection() {
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                return "Connected";
            }
        }
        return "Fallback mode";
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// =============================================================================
// CONTENT HANDLER
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

function getAllContent() {
    try {
        $content = [];
        
        // Try to get from database first
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $content = $db->fetchAll("SELECT * FROM content WHERE deleted_at IS NULL ORDER BY created_at DESC");
            }
        }
        
        // Fallback to demo data
        if (empty($content)) {
            $content = getDemoContent();
        }
        
        apiSuccess(["content" => $content], "Content retrieved successfully");
        
    } catch (Exception $e) {
        // Return demo data on error
        apiSuccess(["content" => getDemoContent()], "Content retrieved (demo mode)");
    }
}

function getContentById($id) {
    try {
        $content = null;
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $content = $db->fetchOne("SELECT * FROM content WHERE id = ? AND deleted_at IS NULL", [$id]);
            }
        }
        
        if (!$content) {
            // Fallback to demo data
            $demoContent = getDemoContent();
            $content = array_find($demoContent, function($item) use ($id) {
                return $item['id'] == $id;
            });
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
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $contentId = $db->insert('content', $contentData);
                $contentData['id'] = $contentId;
                apiSuccess(["content" => $contentData], "Content created successfully");
                return;
            }
        }
        
        // Fallback - simulate creation
        $contentData['id'] = time();
        apiSuccess(["content" => $contentData], "Content created (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to create content: " . $e->getMessage(), 500);
    }
}

function updateContent($id) {
    global $input;
    
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->update('content', $input, 'id = ?', [$id]);
                if ($result) {
                    apiSuccess(null, "Content updated successfully");
                } else {
                    apiError("Content not found", 404);
                }
                return;
            }
        }
        
        // Fallback - simulate update
        apiSuccess(null, "Content updated (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to update content", 500);
    }
}

function deleteContent($id) {
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->update('content', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
                if ($result) {
                    apiSuccess(null, "Content deleted successfully");
                } else {
                    apiError("Content not found", 404);
                }
                return;
            }
        }
        
        // Fallback - simulate deletion
        apiSuccess(null, "Content deleted (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to delete content", 500);
    }
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
        ]
    ];
}

// =============================================================================
// PLAYLIST HANDLER
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

function getAllPlaylists() {
    try {
        $playlists = [];
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $playlists = $db->fetchAll("SELECT * FROM playlists WHERE is_active = 1 ORDER BY created_at DESC");
            }
        }
        
        if (empty($playlists)) {
            $playlists = getDemoPlaylists();
        }
        
        apiSuccess(["playlists" => $playlists], "Playlists retrieved successfully");
        
    } catch (Exception $e) {
        apiSuccess(["playlists" => getDemoPlaylists()], "Playlists retrieved (demo mode)");
    }
}

function getPlaylistById($id) {
    try {
        $playlist = null;
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $playlist = $db->fetchOne("SELECT * FROM playlists WHERE id = ? AND is_active = 1", [$id]);
                if ($playlist) {
                    // Get playlist items
                    $items = $db->fetchAll(
                        "SELECT pi.*, c.title, c.type, c.file_url, c.thumbnail_path, c.duration as content_duration 
                         FROM playlist_items pi 
                         JOIN content c ON pi.content_id = c.id 
                         WHERE pi.playlist_id = ? AND c.deleted_at IS NULL 
                         ORDER BY pi.order_index ASC", 
                        [$id]
                    );
                    $playlist['items'] = $items;
                }
            }
        }
        
        if (!$playlist) {
            $demoPlaylists = getDemoPlaylists();
            $playlist = array_find($demoPlaylists, function($item) use ($id) {
                return $item['id'] == $id;
            });
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
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'layout_id' => $input['layout_id'] ?? 1,
            'shuffle' => (bool)($input['shuffle'] ?? false),
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $playlistId = null;
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $playlistId = $db->insert('playlists', $playlistData);
                
                // Add playlist items
                if (isset($input['items']) && is_array($input['items'])) {
                    foreach ($input['items'] as $index => $item) {
                        $itemData = [
                            'playlist_id' => $playlistId,
                            'content_id' => $item['content_id'],
                            'order_index' => $index,
                            'duration' => $item['duration'] ?? 10,
                            'zone_id' => $item['zone_id'] ?? 'main'
                        ];
                        $db->insert('playlist_items', $itemData);
                    }
                }
                
                $playlistData['id'] = $playlistId;
                apiSuccess(["playlist" => $playlistData], "Playlist created successfully");
                return;
            }
        }
        
        // Fallback - simulate creation
        $playlistData['id'] = time();
        apiSuccess(["playlist" => $playlistData], "Playlist created (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to create playlist: " . $e->getMessage(), 500);
    }
}

function updatePlaylist($id) {
    global $input;
    
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->update('playlists', $input, 'id = ?', [$id]);
                if ($result) {
                    apiSuccess(null, "Playlist updated successfully");
                } else {
                    apiError("Playlist not found", 404);
                }
                return;
            }
        }
        
        apiSuccess(null, "Playlist updated (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to update playlist", 500);
    }
}

function deletePlaylist($id) {
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->update('playlists', ['is_active' => false], 'id = ?', [$id]);
                if ($result) {
                    apiSuccess(null, "Playlist deleted successfully");
                } else {
                    apiError("Playlist not found", 404);
                }
                return;
            }
        }
        
        apiSuccess(null, "Playlist deleted (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to delete playlist", 500);
    }
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
        ]
    ];
}

// =============================================================================
// DEVICE HANDLER
// =============================================================================
function handleDeviceRequests() {
    global $method, $id, $action, $input, $query;
    
    if ($action === 'register') {
        registerDevice();
        return;
    }
    
    if ($action === 'assign') {
        assignPlaylistToDevice();
        return;
    }
    
    if ($action === 'heartbeat') {
        handleDeviceHeartbeat();
        return;
    }
    
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

function getAllDevices() {
    try {
        $devices = [];
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $devices = $db->fetchAll("SELECT * FROM devices ORDER BY created_at DESC");
            }
        }
        
        if (empty($devices)) {
            $devices = getDemoDevices();
        }
        
        apiSuccess(["devices" => $devices], "Devices retrieved successfully");
        
    } catch (Exception $e) {
        apiSuccess(["devices" => getDemoDevices()], "Devices retrieved (demo mode)");
    }
}

function getDeviceById($id) {
    try {
        $device = null;
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $device = $db->fetchOne("SELECT * FROM devices WHERE id = ?", [$id]);
            }
        }
        
        if (!$device) {
            $demoDevices = getDemoDevices();
            $device = array_find($demoDevices, function($item) use ($id) {
                return $item['id'] == $id;
            });
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
            'device_id' => $deviceId,
            'name' => $input['name'] ?? 'Unknown Device',
            'location' => $input['location'] ?? 'Unknown',
            'device_type' => $input['device_type'] ?? 'desktop',
            'screen_width' => (int)($input['screen_width'] ?? 1920),
            'screen_height' => (int)($input['screen_height'] ?? 1080),
            'status' => 'online',
            'last_seen' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                // Check if device already exists
                $existing = $db->fetchOne("SELECT id FROM devices WHERE device_id = ?", [$deviceId]);
                if ($existing) {
                    // Update existing device
                    $db->update('devices', [
                        'status' => 'online',
                        'last_seen' => date('Y-m-d H:i:s'),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                    ], 'device_id = ?', [$deviceId]);
                    
                    apiSuccess(["device_id" => $deviceId], "Device updated successfully");
                } else {
                    // Create new device
                    $id = $db->insert('devices', $deviceData);
                    $deviceData['id'] = $id;
                    apiSuccess(["device" => $deviceData], "Device registered successfully");
                }
                return;
            }
        }
        
        // Fallback - simulate registration
        $deviceData['id'] = time();
        apiSuccess(["device" => $deviceData], "Device registered (demo mode)");
        
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
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->update('devices', [
                    'current_playlist_id' => $playlistId,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$deviceId]);
                
                if ($result) {
                    apiSuccess(null, "Playlist assigned successfully");
                } else {
                    apiError("Device not found", 404);
                }
                return;
            }
        }
        
        apiSuccess(null, "Playlist assigned (demo mode)");
        
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
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->update('devices', [
                    'last_seen' => date('Y-m-d H:i:s'),
                    'status' => 'online'
                ], 'device_id = ?', [$deviceId]);
                
                apiSuccess(null, "Heartbeat received");
                return;
            }
        }
        
        apiSuccess(null, "Heartbeat received (demo mode)");
        
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
            'device_id' => 'device_' . uniqid(),
            'name' => $input['name'],
            'location' => $input['location'] ?? '',
            'device_type' => $input['device_type'],
            'description' => $input['description'] ?? '',
            'status' => 'offline',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $deviceId = $db->insert('devices', $deviceData);
                $deviceData['id'] = $deviceId;
                apiSuccess(["device" => $deviceData], "Device created successfully");
                return;
            }
        }
        
        $deviceData['id'] = time();
        apiSuccess(["device" => $deviceData], "Device created (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to create device: " . $e->getMessage(), 500);
    }
}

function updateDevice($id) {
    global $input;
    
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->update('devices', $input, 'id = ?', [$id]);
                if ($result) {
                    apiSuccess(null, "Device updated successfully");
                } else {
                    apiError("Device not found", 404);
                }
                return;
            }
        }
        
        apiSuccess(null, "Device updated (demo mode)");
        
    } catch (Exception $e) {
        apiError("Failed to update device", 500);
    }
}

function deleteDevice($id) {
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            if ($db->isConnected()) {
                $result = $db->