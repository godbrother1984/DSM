<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - DEVICE API
=============================================================================
*/

require_once '../includes/DeviceManager.php';

// Get variables from main router
global $method, $id, $action, $input, $query, $user;

$deviceManager = new DeviceManager();

// Check permissions
function checkDevicePermission($action) {
    global $user;
    
    $auth = new Auth();
    if (!$auth->hasPermission("device.{$action}")) {
        ApiResponse::forbidden("Insufficient permissions for device.{$action}");
    }
}

switch ($method) {
    case 'GET':
        if ($id) {
            switch ($action) {
                case 'playlist':
                    handleGetDevicePlaylist();
                    break;
                case 'logs':
                    handleGetDeviceLogs();
                    break;
                case 'analytics':
                    handleGetDeviceAnalytics();
                    break;
                default:
                    handleGetDevice();
            }
        } else {
            if ($action === 'stats') {
                handleGetDeviceStats();
            } else {
                handleGetDeviceList();
            }
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'register':
                handleRegisterDevice();
                break;
            case 'heartbeat':
                handleDeviceHeartbeat();
                break;
            case 'assign-playlist':
                handleAssignPlaylist();
                break;
            case 'bulk-assign':
                handleBulkAssignPlaylist();
                break;
            case 'command':
                handleSendCommand();
                break;
            default:
                ApiResponse::notFound('Device action not found');
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        if ($action === 'status') {
            handleSetDeviceStatus();
        } else {
            handleUpdateDevice();
        }
        break;
        
    case 'DELETE':
        if ($action === 'playlist') {
            handleRemovePlaylist();
        } else {
            handleDeleteDevice();
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handleGetDeviceList() {
    global $query, $deviceManager;
    
    checkDevicePermission('view');
    
    try {
        $page = (int)($query['page'] ?? 1);
        $limit = min((int)($query['limit'] ?? 20), 100);
        
        $filters = [];
        if (!empty($query['status'])) $filters['status'] = $query['status'];
        if (!empty($query['device_type'])) $filters['device_type'] = $query['device_type'];
        if (!empty($query['location'])) $filters['location'] = $query['location'];
        if (!empty($query['search'])) $filters['search'] = $query['search'];
        if (isset($query['is_active'])) $filters['is_active'] = (bool)$query['is_active'];
        
        $result = $deviceManager->getDevices($filters, $page, $limit);
        
        ApiResponse::paginated($result['data'], $result['pagination']);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get devices: ' . $e->getMessage());
    }
}

function handleGetDevice() {
    global $id, $deviceManager;
    
    checkDevicePermission('view');
    
    try {
        $device = $deviceManager->getDeviceById($id);
        
        if (!$device) {
            ApiResponse::notFound('Device not found');
        }
        
        ApiResponse::success(['device' => $device]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get device: ' . $e->getMessage());
    }
}

function handleRegisterDevice() {
    global $input, $deviceManager;
    
    // Device registration is public endpoint
    
    try {
        $device = $deviceManager->registerDevice($input);
        
        ApiResponse::created(['device' => $device], 'Device registered successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to register device: ' . $e->getMessage());
    }
}

function handleDeviceHeartbeat() {
    global $input, $deviceManager;
    
    // Heartbeat is public endpoint
    
    try {
        if (empty($input['device_id'])) {
            ApiResponse::validationError(['device_id' => ['Device ID is required']]);
        }
        
        $deviceManager->updateHeartbeat($input['device_id'], $input);
        
        ApiResponse::success(null, 'Heartbeat updated');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update heartbeat: ' . $e->getMessage());
    }
}

function handleUpdateDevice() {
    global $id, $input, $deviceManager;
    
    checkDevicePermission('edit');
    
    try {
        $result = $deviceManager->updateDeviceInfo($id, $input);
        
        if ($result) {
            $device = $deviceManager->getDeviceById($id);
            ApiResponse::success(['device' => $device], 'Device updated successfully');
        } else {
            ApiResponse::notFound('Device not found');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update device: ' . $e->getMessage());
    }
}

function handleAssignPlaylist() {
    global $input, $deviceManager;
    
    checkDevicePermission('assign');
    
    try {
        if (empty($input['device_id']) || empty($input['playlist_id'])) {
            ApiResponse::validationError([
                'device_id' => ['Device ID is required'],
                'playlist_id' => ['Playlist ID is required']
            ]);
        }
        
        $layoutId = $input['layout_id'] ?? null;
        
        $result = $deviceManager->assignPlaylist($input['device_id'], $input['playlist_id'], $layoutId);
        
        if ($result) {
            ApiResponse::success(null, 'Playlist assigned successfully');
        } else {
            ApiResponse::serverError('Failed to assign playlist');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to assign playlist: ' . $e->getMessage());
    }
}

function handleBulkAssignPlaylist() {
    global $input, $deviceManager;
    
    checkDevicePermission('assign');
    
    try {
        if (empty($input['device_ids']) || empty($input['playlist_id'])) {
            ApiResponse::validationError([
                'device_ids' => ['Device IDs are required'],
                'playlist_id' => ['Playlist ID is required']
            ]);
        }
        
        $layoutId = $input['layout_id'] ?? null;
        
        $result = $deviceManager->bulkAssignPlaylist($input['device_ids'], $input['playlist_id'], $layoutId);
        
        ApiResponse::success(['result' => $result], 'Bulk playlist assignment completed');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to bulk assign playlist: ' . $e->getMessage());
    }
}

function handleRemovePlaylist() {
global $id, $deviceManager;
checkDevicePermission('assign');

try {
    $result = $deviceManager->removePlaylist($id);
    
    if ($result) {
        ApiResponse::success(null, 'Playlist removed successfully');
    } else {
        ApiResponse::notFound('Device not found');
    }
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to remove playlist: ' . $e->getMessage());
}
}
function handleSetDeviceStatus() {
global $input, $deviceManager;
checkDevicePermission('edit');

try {
    if (empty($input['device_id']) || empty($input['status'])) {
        ApiResponse::validationError([
            'device_id' => ['Device ID is required'],
            'status' => ['Status is required']
        ]);
    }
    
    $result = $deviceManager->setDeviceStatus($input['device_id'], $input['status']);
    
    if ($result) {
        ApiResponse::success(null, 'Device status updated successfully');
    } else {
        ApiResponse::notFound('Device not found');
    }
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to update device status: ' . $e->getMessage());
}
}
function handleDeleteDevice() {
global $id, $deviceManager;
checkDevicePermission('delete');

try {
    $result = $deviceManager->deleteDevice($id);
    
    if ($result) {
        ApiResponse::success(null, 'Device deleted successfully');
    } else {
        ApiResponse::notFound('Device not found');
    }
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to delete device: ' . $e->getMessage());
}
}
function handleGetDevicePlaylist() {
global $id, $deviceManager;
// Public endpoint for players

try {
    $device = $deviceManager->getDeviceById($id);
    if (!$device) {
        ApiResponse::notFound('Device not found');
    }
    
    $playlist = $deviceManager->getDevicePlaylist($device['device_id']);
    
    ApiResponse::success(['playlist' => $playlist]);
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to get device playlist: ' . $e->getMessage());
}
}
function handleGetDeviceLogs() {
global $id, $query, $deviceManager;
checkDevicePermission('view');

try {
    $limit = min((int)($query['limit'] ?? 100), 1000);
    
    $logs = $deviceManager->getDeviceLogs($id, $limit);
    
    ApiResponse::success(['logs' => $logs]);
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to get device logs: ' . $e->getMessage());
}
}
function handleGetDeviceAnalytics() {
global $id, $query, $deviceManager;
checkDevicePermission('view');

try {
    $days = (int)($query['days'] ?? 30);
    $analytics = $deviceManager->getDeviceAnalytics($id, $days);
    
    ApiResponse::success(['analytics' => $analytics]);
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to get device analytics: ' . $e->getMessage());
}
}
function handleGetDeviceStats() {
global $deviceManager;
checkDevicePermission('view');

try {
    $stats = $deviceManager->getDeviceStats();
    
    ApiResponse::success(['stats' => $stats]);
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to get device stats: ' . $e->getMessage());
}
}
function handleSendCommand() {
global $input, $deviceManager;
checkDevicePermission('edit');

try {
    if (empty($input['device_id']) || empty($input['command'])) {
        ApiResponse::validationError([
            'device_id' => ['Device ID is required'],
            'command' => ['Command is required']
        ]);
    }
    
    $parameters = $input['parameters'] ?? [];
    
    $result = $deviceManager->sendCommand($input['device_id'], $input['command'], $parameters);
    
    ApiResponse::success(null, 'Command sent successfully');
    
} catch (Exception $e) {
    ApiResponse::serverError('Failed to send command: ' . $e->getMessage());
}
}
?>