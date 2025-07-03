<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - DEVICE MANAGER
=============================================================================
*/

require_once 'Database.php';
require_once 'Helpers.php';

class DeviceManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register new device
     */
    public function registerDevice($deviceData) {
        // Generate unique device ID if not provided
        if (empty($deviceData['device_id'])) {
            $deviceData['device_id'] = Helpers::generateUuid();
        }
        
        // Check if device already exists
        $existingDevice = $this->db->fetchOne(
            "SELECT id FROM devices WHERE device_id = ?",
            [$deviceData['device_id']]
        );
        
        if ($existingDevice) {
            // Update existing device
            return $this->updateDeviceInfo($existingDevice['id'], $deviceData);
        }
        
        // Detect device type from user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = Helpers::detectDeviceType($userAgent);
        
        $data = [
            'device_id' => $deviceData['device_id'],
            'name' => $deviceData['name'] ?? 'Device ' . substr($deviceData['device_id'], 0, 8),
            'description' => $deviceData['description'] ?? '',
            'location' => $deviceData['location'] ?? '',
            'device_type' => $deviceData['device_type'] ?? $deviceType,
            'os' => $deviceData['os'] ?? '',
            'browser' => $deviceData['browser'] ?? '',
            'screen_width' => $deviceData['screen_width'] ?? null,
            'screen_height' => $deviceData['screen_height'] ?? null,
            'orientation' => $deviceData['orientation'] ?? 'landscape',
            'ip_address' => Helpers::getClientIP(),
            'status' => 'online',
            'last_seen' => date('Y-m-d H:i:s'),
            'last_heartbeat' => date('Y-m-d H:i:s'),
            'is_active' => true
        ];
        
        // Handle settings
        if (!empty($deviceData['settings'])) {
            $data['settings'] = json_encode($deviceData['settings']);
        }
        
        $deviceId = $this->db->insert('devices', $data);
        
        // Log device registration
        $this->logDeviceActivity($deviceId, 'info', 'Device registered');
        
        Helpers::logActivity("Device registered: {$data['name']}", 'info', ['device_id' => $deviceId]);
        
        return $this->getDeviceById($deviceId);
    }
    
    /**
     * Get device by ID
     */
    public function getDeviceById($id) {
        return $this->db->fetchOne(
            "SELECT d.*, p.name as playlist_name, l.name as layout_name
             FROM devices d 
             LEFT JOIN playlists p ON d.current_playlist_id = p.id
             LEFT JOIN layouts l ON d.current_layout_id = l.id
             WHERE d.id = ?",
            [$id]
        );
    }
    
    /**
     * Get device by device_id
     */
    public function getDeviceByDeviceId($deviceId) {
        return $this->db->fetchOne(
            "SELECT d.*, p.name as playlist_name, l.name as layout_name
             FROM devices d 
             LEFT JOIN playlists p ON d.current_playlist_id = p.id
             LEFT JOIN layouts l ON d.current_layout_id = l.id
             WHERE d.device_id = ?",
            [$deviceId]
        );
    }
    
    /**
     * Get all devices with pagination and filters
     */
    public function getDevices($filters = [], $page = 1, $limit = 20) {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'd.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['device_type'])) {
            $where[] = 'd.device_type = ?';
            $params[] = $filters['device_type'];
        }
        
        if (!empty($filters['location'])) {
            $where[] = 'd.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(d.name LIKE ? OR d.description LIKE ? OR d.location LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['is_active'])) {
            $where[] = 'd.is_active = ?';
            $params[] = $filters['is_active'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT d.*, p.name as playlist_name, l.name as layout_name,
                       TIMESTAMPDIFF(MINUTE, d.last_heartbeat, NOW()) as minutes_since_heartbeat
                FROM devices d 
                LEFT JOIN playlists p ON d.current_playlist_id = p.id
                LEFT JOIN layouts l ON d.current_layout_id = l.id
                WHERE {$whereClause} 
                ORDER BY d.last_seen DESC";
        
        return $this->db->paginate($sql, $params, $page, $limit);
    }
    
    /**
     * Update device information
     */
    public function updateDeviceInfo($id, $data) {
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = Helpers::sanitize($data['name']);
        }
        
        if (isset($data['description'])) {
            $updateData['description'] = Helpers::sanitize($data['description']);
        }
        
        if (isset($data['location'])) {
            $updateData['location'] = Helpers::sanitize($data['location']);
        }
        
        if (isset($data['device_type'])) {
            $updateData['device_type'] = $data['device_type'];
        }
        
        if (isset($data['screen_width'])) {
            $updateData['screen_width'] = $data['screen_width'];
        }
        
        if (isset($data['screen_height'])) {
            $updateData['screen_height'] = $data['screen_height'];
        }
        
        if (isset($data['orientation'])) {
            $updateData['orientation'] = $data['orientation'];
        }
        
        if (isset($data['settings'])) {
            $updateData['settings'] = json_encode($data['settings']);
        }
        
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }
        
        // Always update last seen and IP
        $updateData['last_seen'] = date('Y-m-d H:i:s');
        $updateData['ip_address'] = Helpers::getClientIP();
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $result = $this->db->update('devices', $updateData, 'id = ?', [$id]);
            
            if ($result) {
                $this->logDeviceActivity($id, 'info', 'Device information updated');
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Update device heartbeat
     */
    public function updateHeartbeat($deviceId, $additionalData = []) {
        $device = $this->getDeviceByDeviceId($deviceId);
        if (!$device) {
            throw new Exception('Device not found');
        }
        
        $updateData = [
            'last_heartbeat' => date('Y-m-d H:i:s'),
            'last_seen' => date('Y-m-d H:i:s'),
            'ip_address' => Helpers::getClientIP(),
            'status' => 'online'
        ];
        
        // Update additional data if provided
        if (!empty($additionalData['screen_width'])) {
            $updateData['screen_width'] = $additionalData['screen_width'];
        }
        
        if (!empty($additionalData['screen_height'])) {
            $updateData['screen_height'] = $additionalData['screen_height'];
        }
        
        if (!empty($additionalData['orientation'])) {
            $updateData['orientation'] = $additionalData['orientation'];
        }
        
        $this->db->update('devices', $updateData, 'device_id = ?', [$deviceId]);
        
        return true;
    }
    
    /**
     * Assign playlist to device
     */
    public function assignPlaylist($deviceId, $playlistId, $layoutId = null) {
        $device = $this->getDeviceById($deviceId);
        if (!$device) {
            throw new Exception('Device not found');
        }
        
        // Verify playlist exists
        $playlist = $this->db->fetchOne(
            "SELECT id, name FROM playlists WHERE id = ? AND is_active = 1",
            [$playlistId]
        );
        
        if (!$playlist) {
            throw new Exception('Playlist not found or inactive');
        }
        
        $updateData = [
            'current_playlist_id' => $playlistId,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($layoutId) {
            // Verify layout exists
            $layout = $this->db->fetchOne(
                "SELECT id FROM layouts WHERE id = ?",
                [$layoutId]
            );
            
            if ($layout) {
                $updateData['current_layout_id'] = $layoutId;
            }
        }
        
        $result = $this->db->update('devices', $updateData, 'id = ?', [$deviceId]);
        
        if ($result) {
            $this->logDeviceActivity($deviceId, 'info', "Playlist assigned: {$playlist['name']}");
            
            Helpers::logActivity("Playlist assigned to device", 'info', [
                'device_id' => $deviceId,
                'playlist_id' => $playlistId,
                'playlist_name' => $playlist['name']
            ]);
        }
        
        return $result;
    }
    
    /**
     * Remove playlist from device
     */
    public function removePlaylist($deviceId) {
        $device = $this->getDeviceById($deviceId);
        if (!$device) {
            throw new Exception('Device not found');
        }
        
        $result = $this->db->update('devices', 
            [
                'current_playlist_id' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ], 
            'id = ?', 
            [$deviceId]
        );
        
        if ($result) {
            $this->logDeviceActivity($deviceId, 'info', 'Playlist removed from device');
        }
        
        return $result;
    }
    
    /**
     * Set device status
     */
    public function setDeviceStatus($deviceId, $status) {
        $allowedStatuses = ['online', 'offline', 'error', 'maintenance'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new Exception('Invalid status');
        }
        
        $result = $this->db->update('devices', 
            [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], 
            'device_id = ?', 
            [$deviceId]
        );
        
        if ($result) {
            $device = $this->getDeviceByDeviceId($deviceId);
            $this->logDeviceActivity($device['id'], 'info', "Status changed to: {$status}");
        }
        
        return $result;
    }
    
    /**
     * Delete device
     */
    public function deleteDevice($id) {
        $device = $this->getDeviceById($id);
        if (!$device) {
            throw new Exception('Device not found');
        }
        
        // Delete device logs first
        $this->db->delete('device_logs', 'device_id = ?', [$id]);
        
        // Delete device
        $result = $this->db->delete('devices', 'id = ?', [$id]);
        
        if ($result) {
            Helpers::logActivity("Device deleted: {$device['name']}", 'warning', ['device_id' => $id]);
        }
        
        return $result;
    }
    
    /**
     * Get device current playlist
     */
    public function getDevicePlaylist($deviceId) {
        $device = $this->getDeviceByDeviceId($deviceId);
        if (!$device || !$device['current_playlist_id']) {
            return null;
        }
        
        $playlist = $this->db->fetchOne(
            "SELECT * FROM playlists WHERE id = ? AND is_active = 1",
            [$device['current_playlist_id']]
        );
        
        if ($playlist) {
            // Get playlist items
            $items = $this->db->fetchAll(
                "SELECT pi.*, c.title, c.type, c.file_url, c.thumbnail_path, c.duration as content_duration,
                        COALESCE(pi.duration, c.duration, 5) as effective_duration
                 FROM playlist_items pi 
                 JOIN content c ON pi.content_id = c.id 
                 WHERE pi.playlist_id = ? AND c.deleted_at IS NULL AND c.status = 'active'
                 ORDER BY pi.order_index ASC",
                [$device['current_playlist_id']]
            );
            
            $playlist['items'] = $items;
            
            // Get layout if assigned
            if ($device['current_layout_id']) {
                $layout = $this->db->fetchOne(
                    "SELECT * FROM layouts WHERE id = ?",
                    [$device['current_layout_id']]
                );
                $playlist['layout'] = $layout;
            }
        }
        
        return $playlist;
    }
    
    /**
     * Log device activity
     */
    public function logDeviceActivity($deviceId, $level, $message, $context = []) {
        $logData = [
            'device_id' => $deviceId,
            'level' => $level,
            'message' => $message,
            'context' => !empty($context) ? json_encode($context) : null,
            'ip_address' => Helpers::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $this->db->insert('device_logs', $logData);
    }
    
    /**
     * Get device logs
     */
    public function getDeviceLogs($deviceId, $limit = 100) {
        return $this->db->fetchAll(
            "SELECT * FROM device_logs WHERE device_id = ? ORDER BY created_at DESC LIMIT ?",
            [$deviceId, $limit]
        );
    }
    
    /**
     * Get device statistics
     */
    public function getDeviceStats() {
        $stats = [];
        
        // Total devices
        $stats['total'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM devices WHERE is_active = 1"
        )['count'];
        
        // Devices by status
        $statusStats = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM devices WHERE is_active = 1 GROUP BY status"
        );
        
        $stats['by_status'] = [];
        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = $stat['count'];
        }
        
        // Devices by type
        $typeStats = $this->db->fetchAll(
            "SELECT device_type, COUNT(*) as count FROM devices WHERE is_active = 1 GROUP BY device_type"
        );
        
        $stats['by_type'] = [];
        foreach ($typeStats as $stat) {
            $stats['by_type'][$stat['device_type']] = $stat['count'];
        }
        
        // Online devices (heartbeat within last 5 minutes)
        $onlineCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM devices 
             WHERE is_active = 1 AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        );
        
        $stats['online'] = $onlineCount['count'];
        $stats['offline'] = $stats['total'] - $stats['online'];
        
        return $stats;
    }
    
    /**
     * Update device status based on heartbeat
     */
    public function updateDeviceStatuses() {
        // Mark devices as offline if no heartbeat for 5+ minutes
        $offlineDevices = $this->db->update('devices', 
            ['status' => 'offline'], 
            'last_heartbeat < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND status = "online"'
        );
        
        if ($offlineDevices > 0) {
            Helpers::logActivity("Marked {$offlineDevices} devices as offline due to missed heartbeat", 'info');
        }
        
        return $offlineDevices;
    }
    
    /**
     * Get device analytics
     */
    public function getDeviceAnalytics($deviceId, $days = 30) {
        $analytics = [];
        
        // Content play statistics
        $playStats = $this->db->fetchAll(
            "SELECT c.title, COUNT(*) as play_count, AVG(ca.duration_watched) as avg_duration
             FROM content_analytics ca
             JOIN content c ON ca.content_id = c.id
             WHERE ca.device_id = ? AND ca.event_type = 'start'
             AND ca.timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY ca.content_id, c.title
             ORDER BY play_count DESC
             LIMIT 10",
            [$deviceId, $days]
        );
        
        $analytics['top_content'] = $playStats;
        
        // Daily activity
        $dailyActivity = $this->db->fetchAll(
            "SELECT DATE(timestamp) as date, COUNT(*) as activity_count
             FROM content_analytics
             WHERE device_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(timestamp)
             ORDER BY date DESC",
            [$deviceId, $days]
        );
        
        $analytics['daily_activity'] = $dailyActivity;
        
        // Uptime calculation
        $uptimeData = $this->db->fetchOne(
            "SELECT 
                COUNT(CASE WHEN level = 'info' AND message LIKE '%online%' THEN 1 END) as online_events,
                COUNT(CASE WHEN level = 'warning' AND message LIKE '%offline%' THEN 1 END) as offline_events
             FROM device_logs 
             WHERE device_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$deviceId, $days]
        );
        
        $analytics['uptime_events'] = $uptimeData;
        
        return $analytics;
    }
    
    /**
     * Send command to device (for future implementation)
     */
    public function sendCommand($deviceId, $command, $parameters = []) {
        $device = $this->getDeviceById($deviceId);
        if (!$device) {
            throw new Exception('Device not found');
        }
        
        // For now, just log the command
        $this->logDeviceActivity($deviceId, 'info', "Command sent: {$command}", $parameters);
        
        // TODO: Implement real-time command sending via WebSocket or similar
        
        return true;
    }
    
    /**
     * Bulk assign playlist to multiple devices
     */
    public function bulkAssignPlaylist($deviceIds, $playlistId, $layoutId = null) {
        if (!is_array($deviceIds) || empty($deviceIds)) {
            throw new Exception('Device IDs must be a non-empty array');
        }
        
        // Verify playlist exists
        $playlist = $this->db->fetchOne(
            "SELECT id, name FROM playlists WHERE id = ? AND is_active = 1",
            [$playlistId]
        );
        
        if (!$playlist) {
            throw new Exception('Playlist not found or inactive');
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($deviceIds as $deviceId) {
            try {
                $this->assignPlaylist($deviceId, $playlistId, $layoutId);
                $successCount++;
            } catch (Exception $e) {
                $errors[$deviceId] = $e->getMessage();
            }
        }
        
        Helpers::logActivity("Bulk playlist assignment", 'info', [
            'playlist_id' => $playlistId,
            'playlist_name' => $playlist['name'],
            'success_count' => $successCount,
            'total_devices' => count($deviceIds),
            'errors' => $errors
        ]);
        
        return [
            'success_count' => $successCount,
            'total_devices' => count($deviceIds),
            'errors' => $errors
        ];
    }
}
?>