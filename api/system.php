<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - SYSTEM API
=============================================================================
*/

// Get variables from main router
global $method, $action, $input, $query, $user;

// Check admin permissions
function checkAdminPermission() {
    global $user;
    
    if (!$user || $user['role'] !== 'admin') {
        ApiResponse::forbidden('Admin access required');
    }
}

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'info':
                handleGetSystemInfo();
                break;
            case 'settings':
                handleGetSettings();
                break;
            case 'stats':
                handleGetSystemStats();
                break;
            case 'health':
                handleGetSystemHealth();
                break;
            case 'logs':
                handleGetSystemLogs();
                break;
            default:
                ApiResponse::notFound('System action not found');
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'settings':
                handleUpdateSettings();
                break;
            case 'cleanup':
                handleSystemCleanup();
                break;
            case 'backup':
                handleSystemBackup();
                break;
            case 'optimize':
                handleSystemOptimize();
                break;
            default:
                ApiResponse::notFound('System action not found');
        }
        break;
        
    default:
        ApiResponse::methodNotAllowed();
}

function handleGetSystemInfo() {
    try {
        $info = [
            'name' => 'Digital Signage System',
            'version' => '1.0.0',
            'php_version' => PHP_VERSION,
            'server_time' => date('c'),
            'timezone' => date_default_timezone_get(),
            'memory_usage' => Helpers::getMemoryUsage(),
            'peak_memory' => Helpers::getMemoryUsage(true),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
        ];
        
        ApiResponse::success(['info' => $info]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get system info: ' . $e->getMessage());
    }
}

function handleGetSettings() {
    checkAdminPermission();
    
    try {
        $db = Database::getInstance();
        $settings = $db->fetchAll(
            "SELECT `key`, `value`, `type`, description, is_public FROM system_settings ORDER BY `key`"
        );
        
        $formattedSettings = [];
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
            
            $formattedSettings[$setting['key']] = [
                'value' => $value,
                'type' => $setting['type'],
                'description' => $setting['description'],
                'is_public' => (bool)$setting['is_public']
            ];
        }
        
        ApiResponse::success(['settings' => $formattedSettings]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get settings: ' . $e->getMessage());
    }
}

function handleUpdateSettings() {
    global $input, $user;
    
    checkAdminPermission();
    
    try {
        if (empty($input['settings'])) {
            ApiResponse::validationError(['settings' => ['Settings data is required']]);
        }
        
        $db = Database::getInstance();
        $updatedCount = 0;
        
        foreach ($input['settings'] as $key => $data) {
            $value = $data['value'];
            $type = $data['type'] ?? 'string';
            
            // Convert value to string for storage
            if ($type === 'json') {
                $value = json_encode($value);
            } elseif ($type === 'boolean') {
                $value = $value ? '1' : '0';
            }
            
            $result = $db->update('system_settings', 
                [
                    'value' => $value,
                    'updated_by' => $user['id'],
                    'updated_at' => date('Y-m-d H:i:s')
                ], 
                '`key` = ?', 
                [$key]
            );
            
            if ($result) {
                $updatedCount++;
            }
        }
        
        Helpers::logActivity("System settings updated: {$updatedCount} settings", 'info', [
            'user_id' => $user['id'],
            'settings_count' => $updatedCount
        ]);
        
        ApiResponse::success([
            'updated_count' => $updatedCount
        ], 'Settings updated successfully');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to update settings: ' . $e->getMessage());
    }
}

function handleGetSystemStats() {
    checkAdminPermission();
    
    try {
        $db = Database::getInstance();
        
        $stats = [];
        
        // Database size
        $stats['database_size'] = $db->getDatabaseSize();
        
        // Table counts
        $tables = [
            'users' => 'Total users',
            'content' => 'Total content items',
            'playlists' => 'Total playlists',
            'devices' => 'Total devices',
            'content_analytics' => 'Analytics records'
        ];
        
        foreach ($tables as $table => $description) {
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM {$table}");
            $stats['table_counts'][$table] = [
                'count' => $count['count'],
                'description' => $description
            ];
        }
        
        // File storage usage
        $uploadPath = '../uploads/';
        if (is_dir($uploadPath)) {
            $size = 0;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadPath));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
            $stats['storage_usage'] = Helpers::formatFileSize($size);
        }
        
        // System load (if available)
        if (function_exists('sys_getloadavg')) {
            $stats['system_load'] = sys_getloadavg();
        }
        
        // Disk usage
        $stats['disk_free'] = Helpers::formatFileSize(disk_free_space('.'));
        $stats['disk_total'] = Helpers::formatFileSize(disk_total_space('.'));
        
        ApiResponse::success(['stats' => $stats]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get system stats: ' . $e->getMessage());
    }
}

function handleGetSystemHealth() {
    try {
        $health = [
            'status' => 'healthy',
            'checks' => []
        ];
        
        // Database connection
        try {
            $db = Database::getInstance();
            $db->fetchOne("SELECT 1");
            $health['checks']['database'] = ['status' => 'ok', 'message' => 'Database connection healthy'];
        } catch (Exception $e) {
            $health['checks']['database'] = ['status' => 'error', 'message' => 'Database connection failed'];
            $health['status'] = 'unhealthy';
        }
        
        // File permissions
        $uploadPath = '../uploads/';
        if (is_writable($uploadPath)) {
            $health['checks']['file_permissions'] = ['status' => 'ok', 'message' => 'Upload directory writable'];
        } else {
            $health['checks']['file_permissions'] = ['status' => 'warning', 'message' => 'Upload directory not writable'];
        }
        
        // PHP extensions
        $requiredExtensions = ['gd', 'json', 'pdo', 'pdo_mysql'];
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
        
        // Memory usage
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPercent = ($memoryUsage / $this->parseMemoryLimit($memoryLimit)) * 100;
        
        if ($memoryPercent < 80) {
            $health['checks']['memory'] = ['status' => 'ok', 'message' => "Memory usage: {$memoryPercent}%"];
        } else {
            $health['checks']['memory'] = ['status' => 'warning', 'message' => "High memory usage: {$memoryPercent}%"];
        }
        
        ApiResponse::success(['health' => $health]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get system health: ' . $e->getMessage());
    }
}

function handleGetSystemLogs() {
    global $query;
    
    checkAdminPermission();
    
    try {
        $logFile = '../logs/app.log';
        $lines = (int)($query['lines'] ?? 100);
        
        if (!file_exists($logFile)) {
            ApiResponse::success(['logs' => []]);
        }
        
        // Read last N lines from log file
        $logs = [];
        $file = new SplFileObject($logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $line = trim($file->current());
            if (!empty($line)) {
                $logs[] = $line;
            }
            $file->next();
        }
        
        ApiResponse::success(['logs' => $logs]);
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to get system logs: ' . $e->getMessage());
    }
}

function handleSystemCleanup() {
    global $user;
    
    checkAdminPermission();
    
    try {
        $results = [];
        
        // Clean expired content
        require_once '../includes/ContentManager.php';
        $contentManager = new ContentManager();
        $expiredContent = $contentManager->cleanupExpiredContent();
        $results['expired_content'] = $expiredContent;
        
        // Clean expired API tokens
        $auth = new Auth();
        $expiredTokens = $auth->cleanupExpiredTokens();
        $results['expired_tokens'] = $expiredTokens;
        
        // Clean old logs (older than 30 days)
        $logFiles = glob('../logs/*.log');
        $cleanedLogs = 0;
        foreach ($logFiles as $logFile) {
            if (filemtime($logFile) < strtotime('-30 days')) {
                if (unlink($logFile)) {
                    $cleanedLogs++;
                }
            }
        }
        $results['old_logs'] = $cleanedLogs;
        
        // Clean cache files
        Helpers::cleanupCache();
        $results['cache_cleaned'] = true;
        
        Helpers::logActivity('System cleanup performed', 'info', [
            'user_id' => $user['id'],
            'results' => $results
        ]);
        
        ApiResponse::success(['results' => $results], 'System cleanup completed');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to perform system cleanup: ' . $e->getMessage());
    }
}

function handleSystemBackup() {
    checkAdminPermission();
    
    try {
        $db = Database::getInstance();
        $backupFile = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Create backups directory if it doesn't exist
        $backupDir = dirname($backupFile);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $result = $db->backup($backupFile);
        
        if ($result) {
            ApiResponse::success([
                'backup_file' => basename($backupFile),
                'file_size' => Helpers::formatFileSize(filesize($backupFile))
            ], 'Database backup created successfully');
        } else {
            ApiResponse::serverError('Failed to create database backup');
        }
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to create backup: ' . $e->getMessage());
    }
}

function handleSystemOptimize() {
    checkAdminPermission();
    
    try {
        $db = Database::getInstance();
        $result = $db->optimizeDatabase();
        
        Helpers::logActivity('Database optimization performed', 'info');
        
        ApiResponse::success(null, 'Database optimization completed');
        
    } catch (Exception $e) {
        ApiResponse::serverError('Failed to optimize database: ' . $e->getMessage());
    }
}

// Helper function to parse memory limit
function parseMemoryLimit($limit) {
    $value = (int) $limit;
    $unit = strtolower(substr($limit, -1));
    
    switch ($unit) {
        case 'g':
            $value *= 1024 * 1024 * 1024;
            break;
        case 'm':
            $value *= 1024 * 1024;
            break;
        case 'k':
            $value *= 1024;
            break;
    }
    
    return $value;
}
?>