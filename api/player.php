<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - PLAYER API
=============================================================================
*/

require_once '../includes/DeviceManager.php';
require_once '../includes/ContentManager.php';

// Get variables from main router
global $method, $id, $action, $input, $query;

$deviceManager = new DeviceManager();
$contentManager = new ContentManager();

// Player endpoints are mostly public for device access

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'playlist':
                handleGetPlayerPlaylist();
                break;
            case 'content':
                handleGetPlayerContent();
                break;
            case 'config':
                handleGetPlayerConfig();
                break;
            default:
                handlePlayerStatus();
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'register':
                handlePlayerRegister();
                break;
            case 'heartbeat':
                handlePlayerHeartbeat();
                break;
            case 'analytics':
                handlePlayerAnalytics();
                break;
            case 'log':
                handlePlayerLog();
                break;
            default:
                ApiResponse::notFound('Player action not found');
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handlePlayerStatus() {
    ApiResponse::success([
        'status' => 'online',
        'version' => '1.0.0',
        'timestamp' => date('c'),
        'endpoints' => [
            'register' => '/api/player/register',
            'heartbeat' => '/api/player/heartbeat', 
            'playlist' => '/api/player/playlist',
            'content' => '/api/player/content/{id}',
            'analytics' => '/api/player/analytics',
            'config' => '/api/player/config'
        ]
    ], 'Player API Ready');
}

function handlePlayerRegister() {
    global $input, $deviceManager;
    
    try {
        // Get device info from request
        $deviceData = [
            'device_id' => $input['device_id'] ?? null,
            'name' => $input['name'] ?? null,
            'screen_width' => $input['screen_width'] ?? null,
            'screen_height' => $input['screen_height'] ?? null,
            'orientation' => $input['orientation'] ?? 'landscape',
            'device_type' => $input['device_type'] ?? null,
            'os' => $input['os'] ?? null,
            'browser' => $input['browser'] ?? null,
            'location' => $input['location'] ?? null
        ];
        
        $device = $deviceManager->registerDevice($deviceData);
        
        ApiResponse::success([
            'device' => [
                'id' => $device['id'],
                'device_id' => $device['device_id'],
                'name' => $device['name'],
                'api_key' => $device['api_key']
            ]
        ], 'Device registered successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to register device: ' . $e->getMessage());
    }
}

function handlePlayerHeartbeat() {
    global $input, $deviceManager;
    
    try {
        $deviceId = $input['device_id'] ?? $_SERVER['HTTP_X_DEVICE_ID'] ?? null;
        
        if (!$deviceId) {
            ApiResponse::validationError(['device_id' => ['Device ID is required']]);
        }
        
        // Additional data from heartbeat
        $additionalData = [
            'screen_width' => $input['screen_width'] ?? null,
            'screen_height' => $input['screen_height'] ?? null,
            'orientation' => $input['orientation'] ?? null
        ];
        
        $deviceManager->updateHeartbeat($deviceId, $additionalData);
        
        // Get current playlist info for response
        $playlist = $deviceManager->getDevicePlaylist($deviceId);
        
        ApiResponse::success([
            'status' => 'received',
            'timestamp' => date('c'),
            'has_playlist' => $playlist !== null,
            'playlist_id' => $playlist['id'] ?? null
        ], 'Heartbeat received');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to process heartbeat: ' . $e->getMessage());
    }
}

function handleGetPlayerPlaylist() {
    global $query, $deviceManager;
    
    try {
        $deviceId = $query['device_id'] ?? $_SERVER['HTTP_X_DEVICE_ID'] ?? null;
        
        if (!$deviceId) {
            ApiResponse::validationError(['device_id' => ['Device ID is required']]);
        }
        
        $playlist = $deviceManager->getDevicePlaylist($deviceId);
        
        if (!$playlist) {
            ApiResponse::success([
                'playlist' => null,
                'message' => 'No playlist assigned'
            ]);
        } else {
            ApiResponse::success(['playlist' => $playlist]);
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get playlist: ' . $e->getMessage());
    }
}

function handleGetPlayerContent() {
    global $id, $contentManager;
    
    try {
        $content = $contentManager->getContentById($id);
        
        if (!$content) {
            ApiResponse::notFound('Content not found');
        }
        
        // Only return necessary fields for player
        $playerContent = [
            'id' => $content['id'],
            'title' => $content['title'],
            'type' => $content['type'],
            'file_url' => $content['file_url'],
            'thumbnail_path' => $content['thumbnail_path'],
            'duration' => $content['duration'],
            'width' => $content['width'],
            'height' => $content['height'],
            'metadata' => $content['metadata'] ? json_decode($content['metadata'], true) : null
        ];
        
        ApiResponse::success(['content' => $playerContent]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get content: ' . $e->getMessage());
    }
}

function handleGetPlayerConfig() {
    global $query;
    
    try {
        $deviceId = $query['device_id'] ?? $_SERVER['HTTP_X_DEVICE_ID'] ?? null;
        
        if (!$deviceId) {
            ApiResponse::validationError(['device_id' => ['Device ID is required']]);
        }
        
        // Get system configuration for player
        $db = Database::getInstance();
        $settings = $db->fetchAll(
            "SELECT `key`, `value`, `type` FROM system_settings WHERE is_public = 1"
        );
        
        $config = [];
        foreach ($settings as $setting) {
            $value = $setting['value'];
            
            // Convert value based on type
            switch ($setting['type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $config[$setting['key']] = $value;
        }
        
        ApiResponse::success(['config' => $config]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get player config: ' . $e->getMessage());
    }
}

function handlePlayerAnalytics() {
    global $input;
    
    try {
        // Validate required fields
        $errors = Helpers::validate($input, [
            'device_id' => 'required',
            'content_id' => 'required',
            'event_type' => 'required|in:start,end,skip,error,interaction'
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        // Get device by device_id
        $db = Database::getInstance();
        $device = $db->fetchOne(
            "SELECT id FROM devices WHERE device_id = ?",
            [$input['device_id']]
        );
        
        if (!$device) {
            ApiResponse::notFound('Device not found');
        }
        
        // Insert analytics data
        $analyticsData = [
            'device_id' => $device['id'],
            'content_id' => $input['content_id'],
            'playlist_id' => $input['playlist_id'] ?? null,
            'event_type' => $input['event_type'],
            'duration_watched' => $input['duration_watched'] ?? null,
            'interaction_data' => isset($input['interaction_data']) ? json_encode($input['interaction_data']) : null
        ];
        
        $db->insert('content_analytics', $analyticsData);
        
        ApiResponse::success(null, 'Analytics data recorded');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to record analytics: ' . $e->getMessage());
    }
}

function handlePlayerLog() {
    global $input, $deviceManager;
    
    try {
        // Validate required fields
        $errors = Helpers::validate($input, [
            'device_id' => 'required',
            'level' => 'required|in:info,warning,error,debug',
            'message' => 'required'
        ]);
        
        if (!empty($errors)) {
            ApiResponse::validationError($errors);
        }
        
        // Get device by device_id
        $db = Database::getInstance();
        $device = $db->fetchOne(
            "SELECT id FROM devices WHERE device_id = ?",
            [$input['device_id']]
        );
        
        if (!$device) {
            ApiResponse::notFound('Device not found');
        }
        
        // Log the message
        $context = $input['context'] ?? [];
        $deviceManager->logDeviceActivity(
            $device['id'], 
            $input['level'], 
            $input['message'], 
            $context
        );
        
        ApiResponse::success(null, 'Log recorded');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to record log: ' . $e->getMessage());
    }
}
?>