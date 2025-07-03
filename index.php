<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - MAIN INDEX
=============================================================================
File: index.php
Description: Main entry point with installation check
=============================================================================
*/

// Check if system is installed
if (!file_exists('config/database.php')) {
    header('Location: install.php');
    exit;
}

// Start session
session_start();

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Error reporting for development
if (file_exists('config/config.php')) {
    $config = include 'config/config.php';
    if ($config['debug'] ?? false) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .welcome-container { 
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.2); 
            padding: 60px; 
            text-align: center; 
            max-width: 600px; 
            margin: 20px; 
        }
        .logo { 
            font-size: 4rem; 
            margin-bottom: 20px; 
        }
        h1 { 
            color: #333; 
            font-size: 2.5rem; 
            margin-bottom: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        p { 
            color: #666; 
            font-size: 1.2rem; 
            line-height: 1.6; 
            margin-bottom: 40px; 
        }
        .buttons { 
            display: flex; 
            gap: 20px; 
            justify-content: center; 
            flex-wrap: wrap; 
        }
        .btn { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 15px 30px; 
            border: none; 
            border-radius: 10px; 
            font-size: 1.1rem; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            transition: all 0.3s ease;
            min-width: 160px;
        }
        .btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); 
        }
        .btn-secondary { 
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%); 
        }
        .features { 
            margin: 40px 0; 
            text-align: left; 
        }
        .features h3 { 
            color: #333; 
            margin-bottom: 20px; 
            text-align: center; 
        }
        .feature-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
        }
        .feature { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
        }
        .feature-icon { 
            font-size: 2rem; 
            margin-bottom: 10px; 
        }
        .feature h4 { 
            color: #333; 
            margin-bottom: 10px; 
        }
        .feature p { 
            color: #666; 
            font-size: 0.9rem; 
            margin: 0; 
        }
        .system-info {
            background: #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            font-size: 14px;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .welcome-container { padding: 40px 20px; }
            h1 { font-size: 2rem; }
            .buttons { flex-direction: column; align-items: center; }
            .btn { width: 100%; max-width: 300px; }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="logo">🎬</div>
        <h1>Digital Signage System</h1>
        <p>Transform your displays into dynamic digital signage with powerful content management, real-time device control, and analytics.</p>
        
        <div class="features">
            <h3>System Features</h3>
            <div class="feature-grid">
                <div class="feature">
                    <div class="feature-icon">📁</div>
                    <h4>Content Management</h4>
                    <p>Upload and organize videos, images, and HTML content with ease</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📋</div>
                    <h4>Playlist Control</h4>
                    <p>Create and schedule playlists with custom timing and transitions</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📱</div>
                    <h4>Device Management</h4>
                    <p>Monitor and control multiple display devices from one interface</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📊</div>
                    <h4>Real-time Analytics</h4>
                    <p>Track content performance and device status with detailed reports</p>
                </div>
            </div>
        </div>
        
        <div class="buttons">
            <a href="admin/" class="btn">
                🔧 Admin Panel
            </a>
            <a href="player/" class="btn btn-secondary">
                📺 Player Interface
            </a>
        </div>
        
        <div class="system-info">
            <strong>System Status:</strong> 
            <?php if (file_exists('config/database.php')): ?>
                ✅ Installed and Ready
            <?php else: ?>
                ⚠️ Not Installed - <a href="install.php">Run Installation</a>
            <?php endif; ?>
            <br>
            <strong>PHP Version:</strong> <?= PHP_VERSION ?>
            <br>
            <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>
        </div>
    </div>
</body>
</html>