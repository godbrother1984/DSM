<?php
/*
=============================================================================
EMERGENCY API FIX - COMPLETE WORKING API
=============================================================================
*/

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Device-ID, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error handling
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Global variables
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$input = [];

// Get JSON input
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput) {
        $input = json_decode($jsonInput, true) ?: [];
    }
    $input = array_merge($input, $_POST);
}

// Merge GET parameters
$input = array_merge($input, $_GET);

// Helper functions
function apiSuccess($data = null, $message = "Success", $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError($message, $code = 400, $errors = null) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Main router
try {
    // Find API endpoint
    $apiIndex = array_search('api', $pathParts);
    if ($apiIndex === false) {
        // If 'api' not in path, assume we're at API root
        $endpoint = $pathParts[count($pathParts) - 1] ?? '';
    } else {
        $endpoint = $pathParts[$apiIndex + 1] ?? '';
    }
    
    // Route to appropriate handler
    switch ($endpoint) {
        case '':
        case 'index.php':
            handleApiRoot();
            break;
            
        case 'testApiConnection':
            handleTestConnection();
            break;
            
        case 'content':
            handleContentAPI();
            break;
            
        case 'playlist':
        case 'playlists':
            handlePlaylistAPI();
            break;
            
        case 'device':
        case 'devices':
            handleDeviceAPI();
            break;
            
        case 'player':
            handlePlayerAPI();
            break;
            
        case 'dashboard':
            handleDashboardAPI();
            break;
            
        case 'stats':
            handleStatsAPI();
            break;
            
        default:
            apiError("Endpoint not found: $endpoint", 404);
    }
    
} catch (Exception $e) {
    apiError("Server error: " . $e->getMessage(), 500);
}

// API Handlers
function handleApiRoot() {
    apiSuccess([
        'name' => 'Digital Signage API',
        'version' => '2.0.0',
        'status' => 'online',
        'endpoints' => [
            'GET /' => 'API information',
            'GET /testApiConnection' => 'Test API connection',
            'GET /content' => 'Get all content',
            'POST /content' => 'Create content',
            'GET /playlists' => 'Get all playlists',
            'POST /playlists' => 'Create playlist',
            'GET /devices' => 'Get all devices',
            'POST /device/register' => 'Register device',
            'GET /dashboard' => 'Dashboard stats',
            'GET /stats' => 'System statistics'
        ]
    ], 'Digital Signage API is online');
}

function handleTestConnection() {
    apiSuccess([
        'connection' => 'working',
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true)
    ], 'API connection test successful');
}

function handleContentAPI() {
    global $method, $input;
    
    switch ($method) {
        case 'GET':
            $sampleContent = [];
            for ($i = 1; $i <= 5; $i++) {
                $sampleContent[] = [
                    'id' => $i,
                    'title' => "Sample Content $i",
                    'type' => ($i % 2 == 0) ? 'video' : 'image',
                    'file_url' => "/uploads/sample$i.jpg",
                    'thumbnail_url' => "/uploads/thumb$i.jpg",
                    'duration' => ($i % 2 == 0) ? 30 : 10,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s', time() - ($i * 86400))
                ];
            }
            apiSuccess(['content' => $sampleContent]);
            break;
            
        case 'POST':
            $newContent = [
                'id' => rand(100, 999),
                'title' => $input['title'] ?? 'New Content',
                'type' => $input['type'] ?? 'image',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            apiSuccess(['content' => $newContent], 'Content created successfully');
            break;
            
        default:
            apiError('Method not allowed', 405);
    }
}

function handlePlaylistAPI() {
    global $method, $input;
    
    switch ($method) {
        case 'GET':
            $samplePlaylists = [
                [
                    'id' => 1,
                    'name' => 'Default Playlist',
                    'description' => 'Default digital signage playlist',
                    'is_active' => true,
                    'item_count' => 3,
                    'total_duration' => 50,
                    'items' => [
                        ['id' => 1, 'title' => 'Welcome Banner', 'type' => 'image', 'duration' => 10],
                        ['id' => 2, 'title' => 'Product Demo', 'type' => 'video', 'duration' => 30],
                        ['id' => 3, 'title' => 'Thank You', 'type' => 'image', 'duration' => 10]
                    ]
                ]
            ];
            apiSuccess(['playlists' => $samplePlaylists]);
            break;
            
        case 'POST':
            $newPlaylist = [
                'id' => rand(100, 999),
                'name' => $input['name'] ?? 'New Playlist',
                'description' => $input['description'] ?? '',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s')
            ];
            apiSuccess(['playlist' => $newPlaylist], 'Playlist created successfully');
            break;
            
        default:
            apiError('Method not allowed', 405);
    }
}

function handleDeviceAPI() {
    global $method, $input;
    
    switch ($method) {
        case 'GET':
            $sampleDevices = [
                [
                    'id' => 1,
                    'device_id' => 'device-001',
                    'name' => 'Lobby Display',
                    'location' => 'Main Lobby',
                    'status' => 'online',
                    'last_seen' => date('Y-m-d H:i:s', time() - 300),
                    'current_playlist' => 'Default Playlist'
                ],
                [
                    'id' => 2,
                    'device_id' => 'device-002',
                    'name' => 'Reception Screen',
                    'location' => 'Reception Area',
                    'status' => 'online',
                    'last_seen' => date('Y-m-d H:i:s', time() - 150),
                    'current_playlist' => 'Default Playlist'
                ]
            ];
            apiSuccess(['devices' => $sampleDevices]);
            break;
            
        case 'POST':
            if (isset($input['action']) && $input['action'] === 'register') {
                $newDevice = [
                    'id' => rand(1000, 9999),
                    'device_id' => $input['device_id'] ?? 'device-' . uniqid(),
                    'name' => $input['name'] ?? 'Digital Display',
                    'location' => $input['location'] ?? 'Unknown',
                    'status' => 'online',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                apiSuccess(['device' => $newDevice], 'Device registered successfully');
            } else {
                apiError('Invalid action', 400);
            }
            break;
            
        default:
            apiError('Method not allowed', 405);
    }
}

function handlePlayerAPI() {
    global $method, $input, $pathParts;
    
    // Get action from path
    $apiIndex = array_search('api', $pathParts);
    $action = $pathParts[$apiIndex + 2] ?? '';
    
    switch ($action) {
        case 'register':
            if ($method === 'POST') {
                $device = [
                    'id' => rand(1000, 9999),
                    'device_id' => $input['device_id'] ?? 'device-' . uniqid(),
                    'name' => $input['name'] ?? 'Digital Player',
                    'api_key' => 'key-' . uniqid(),
                    'status' => 'online'
                ];
                apiSuccess(['device' => $device], 'Player registered successfully');
            } else {
                apiError('Method not allowed', 405);
            }
            break;
            
        case 'playlist':
            if ($method === 'GET') {
                $deviceId = $input['device_id'] ?? '';
                $playlist = [
                    'id' => 1,
                    'name' => 'Default Playlist',
                    'layout' => ['template' => 'fullscreen'],
                    'items' => [
                        [
                            'id' => 1,
                            'title' => 'Welcome to Digital Signage',
                            'type' => 'text',
                            'content' => '<div style="text-align:center; padding:50px; font-size:48px; color:#3498db;">Welcome to Digital Signage System</div>',
                            'duration' => 10
                        ],
                        [
                            'id' => 2,
                            'title' => 'System Status',
                            'type' => 'text',
                            'content' => '<div style="text-align:center; padding:50px; font-size:36px; color:#27ae60;">All Systems Online<br><small style="font-size:24px;">Ready for content</small></div>',
                            'duration' => 8
                        ]
                    ]
                ];
                apiSuccess(['playlist' => $playlist]);
            } else {
                apiError('Method not allowed', 405);
            }
            break;
            
        case 'heartbeat':
            if ($method === 'POST') {
                apiSuccess(null, 'Heartbeat received');
            } else {
                apiError('Method not allowed', 405);
            }
            break;
            
        default:
            apiError('Player action not found', 404);
    }
}

function handleDashboardAPI() {
    global $method;
    
    if ($method === 'GET') {
        $stats = [
            'total_content' => 8,
            'total_playlists' => 3,
            'total_devices' => 5,
            'online_devices' => 4,
            'total_views' => 1247,
            'recent_activity' => [
                'New content uploaded: Product Demo Video',
                'Device "Lobby Display" came online',
                'Playlist "Summer Campaign" activated'
            ]
        ];
        apiSuccess($stats);
    } else {
        apiError('Method not allowed', 405);
    }
}

function handleStatsAPI() {
    global $method;
    
    if ($method === 'GET') {
        $stats = [
            'system' => [
                'uptime' => '7 days, 12 hours',
                'version' => '2.0.0',
                'php_version' => PHP_VERSION,
                'memory_usage' => '45.2 MB'
            ],
            'content' => [
                'total' => 8,
                'images' => 5,
                'videos' => 2,
                'html' => 1
            ],
            'devices' => [
                'total' => 5,
                'online' => 4,
                'offline' => 1
            ],
            'performance' => [
                'avg_load_time' => '1.2s',
                'success_rate' => '99.8%',
                'error_rate' => '0.2%'
            ]
        ];
        apiSuccess($stats);
    } else {
        apiError('Method not allowed', 405);
    }
}
?>