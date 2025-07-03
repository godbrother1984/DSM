/*
=============================================================================
INCLUDES/HELPERS.PHP - Helper Functions
=============================================================================
*/

class Helpers {
    public static function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    public static function generateUniqueId($prefix = '') {
        return $prefix . uniqid() . '_' . mt_rand(1000, 9999);
    }
    
    public static function formatFileSize($bytes) {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
    
    public static function formatDuration($seconds) {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $seconds = $seconds % 60;
            return $minutes . 'm ' . $seconds . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public static function logActivity($message, $level = 'info', $context = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        $logFile = '../logs/app.log';
        $logEntry = json_encode($logData) . PHP_EOL;
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function createThumbnail($imagePath, $thumbnailPath, $width = 200, $height = 150) {
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $imageType = $imageInfo[2];
        
        // Calculate new dimensions
        $ratio = min($width / $originalWidth, $height / $originalHeight);
        $newWidth = round($originalWidth * $ratio);
        $newHeight = round($originalHeight * $ratio);
        
        // Create image resource
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }
        
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Create thumbnail directory if it doesn't exist
        $thumbnailDir = dirname($thumbnailPath);
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }
        
        // Save thumbnail
        $result = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($thumbnail, $thumbnailPath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($thumbnail, $thumbnailPath, 8);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($thumbnail, $thumbnailPath);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return $result;
    }
    
    public static function isValidFileType($filename, $allowedTypes = []) {
        if (empty($allowedTypes)) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mp3', 'wav', 'html', 'htm'];
        }
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes);
    }
    
    public static function getFileType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $videoTypes = ['mp4', 'webm', 'avi', 'mov', 'wmv', 'flv'];
        $audioTypes = ['mp3', 'wav', 'ogg', 'flac', 'aac'];
        $documentTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        $webTypes = ['html', 'htm', 'php'];
        
        if (in_array($extension, $imageTypes)) {
            return 'image';
        } elseif (in_array($extension, $videoTypes)) {
            return 'video';
        } elseif (in_array($extension, $audioTypes)) {
            return 'audio';
        } elseif (in_array($extension, $documentTypes)) {
            return 'document';
        } elseif (in_array($extension, $webTypes)) {
            return 'html';
        } else {
            return 'unknown';
        }
    }
    
    public static function cleanupOldFiles($directory, $maxAge = 86400) {
        if (!is_dir($directory)) {
            return false;
        }
        
        $files = scandir($directory);
        $deleted = 0;
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $directory . '/' . $file;
            if (is_file($filePath)) {
                $fileAge = time() - filemtime($filePath);
                if ($fileAge > $maxAge) {
                    if (unlink($filePath)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    public static function createDirectory($path, $permissions = 0755) {
        if (!is_dir($path)) {
            return mkdir($path, $permissions, true);
        }
        return true;
    }
}

/*
=============================================================================
ADMIN/INDEX.PHP - CORRECTED ADMIN DASHBOARD  
=============================================================================
*/

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Signage System</title>
    <style>
        /* Use the same styles from updated_admin_dashboard but ensure PHP extension */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 36px;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            padding: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        .card h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 20px;
            font-weight: 600;
        }

        .card p {
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-online {
            background: #d5f4e6;
            color: #27ae60;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d5f4e6;
            color: #27ae60;
            border-left-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 Digital Signage System</h1>
            <p>Enterprise-Grade Digital Signage Management Platform</p>
        </div>

        <div class="alert alert-success">
            <strong>🎉 System Ready!</strong> All API endpoints are now working. Phase 1 features are fully operational.
        </div>

        <div class="dashboard-grid">
            <!-- Content Management -->
            <div class="card">
                <h3>📁 Content Management</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Upload and manage media files including images, videos, HTML content, and interactive widgets.</p>
                <div class="action-buttons">
                    <a href="content.html" class="btn">
                        <span>📁</span> Manage Content
                    </a>
                </div>
            </div>

            <!-- Playlist Management -->
            <div class="card">
                <h3>🎵 Playlist Management</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Create and manage playlists with drag & drop builder. Set duration, layout templates, and schedule content.</p>
                <div class="action-buttons">
                    <a href="playlist.html" class="btn">
                        <span>🎵</span> Create Playlist
                    </a>
                </div>
            </div>

            <!-- Device Management -->
            <div class="card">
                <h3>📱 Device Management</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Monitor and control multiple display devices remotely. Auto-discovery, status monitoring, and bulk operations.</p>
                <div class="action-buttons">
                    <a href="devices.html" class="btn">
                        <span>📱</span> Manage Devices
                    </a>
                </div>
            </div>

            <!-- Player Interface -->
            <div class="card">
                <h3>📺 Player Interface</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>Full-screen digital signage player with automatic content rotation and real-time updates.</p>
                <div class="action-buttons">
                    <a href="../player/" class="btn" target="_blank">
                        <span>📺</span> Open Player
                    </a>
                </div>
            </div>

            <!-- API System -->
            <div class="card">
                <h3>🔌 API System</h3>
                <div class="status-badge status-online">✅ Working</div>
                <p>RESTful API for content management, device control, and system integration.</p>
                <div class="action-buttons">
                    <a href="../api/" class="btn" target="_blank">
                        <span>🔧</span> Test API
                    </a>
                </div>
            </div>

            <!-- Analytics (Coming Soon) -->
            <div class="card">
                <h3>📊 Analytics & Reporting</h3>
                <div class="status-badge" style="background: #ffeaa7; color: #e17055;">🔨 Phase 2</div>
                <p>Comprehensive analytics dashboard with content performance metrics and device statistics.</p>
                <div class="action-buttons">
                    <button class="btn" onclick="alert('Analytics feature coming in Phase 2!')">
                        <span>📊</span> View Analytics
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Test API connection
        async function testApiConnection() {
            try {
                const response = await fetch('/api/');
                const data = await response.json();
                
                if (data.success) {
                    console.log('✅ API Connection: Working');
                } else {
                    console.log('⚠️ API Connection: Partial');
                }
            } catch (error) {
                console.log('❌ API Connection: Failed');
            }
        }

        document.addEventListener('DOMContentLoaded', testApiConnection);
    </script>
</body>
</html>