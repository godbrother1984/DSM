<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - XAMPP INSTALLER
=============================================================================
File: install.php
Description: One-click installer for XAMPP environment
Usage: Place in htdocs/digital-signage/ and run http://localhost/digital-signage/install.php
=============================================================================
*/

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Installation steps
$steps = [
    'welcome' => 'Welcome',
    'requirements' => 'System Requirements',
    'database' => 'Database Setup',
    'admin' => 'Admin Account',
    'complete' => 'Installation Complete'
];

$currentStep = $_GET['step'] ?? 'welcome';

// Handle form submissions
if ($_POST) {
    switch ($currentStep) {
        case 'database':
            handleDatabaseSetup();
            break;
        case 'admin':
            handleAdminSetup();
            break;
    }
}

function handleDatabaseSetup() {
    $host = $_POST['db_host'] ?? 'localhost';
    $username = $_POST['db_username'] ?? 'root';
    $password = $_POST['db_password'] ?? '';
    $database = $_POST['db_database'] ?? 'digital_signage';

    try {
        // Test connection
        $pdo = new PDO("mysql:host={$host}", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$database}`");

        // Create config files
        createConfigFiles($host, $username, $password, $database);

        // Import schema
        importDatabaseSchema($pdo);

        $_SESSION['install_step'] = 'admin';
        header('Location: install.php?step=admin');
        exit;

    } catch (Exception $e) {
        $GLOBALS['error'] = 'Database connection failed: ' . $e->getMessage();
    }
}

function handleAdminSetup() {
    $name = $_POST['admin_name'] ?? '';
    $email = $_POST['admin_email'] ?? '';
    $password = $_POST['admin_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $GLOBALS['error'] = 'All fields are required';
        return;
    }

    try {
        // Include database connection
        require_once 'config/database.php';
        $config = include 'config/database.php';
        
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
            $config['username'],
            $config['password'],
            $config['options']
        );

        // Create admin user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
        $stmt->execute([$name, $email, $hashedPassword]);

        // Create default content and settings
        createDefaultData($pdo);

        $_SESSION['install_step'] = 'complete';
        header('Location: install.php?step=complete');
        exit;

    } catch (Exception $e) {
        $GLOBALS['error'] = 'Admin creation failed: ' . $e->getMessage();
    }
}

function createConfigFiles($host, $username, $password, $database) {
    // Create config directory
    if (!is_dir('config')) {
        mkdir('config', 0755, true);
    }

    // Database config
    $dbConfig = "<?php
return [
    'host' => '{$host}',
    'database' => '{$database}',
    'username' => '{$username}',
    'password' => '{$password}',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
?>";
    file_put_contents('config/database.php', $dbConfig);

    // Main config
    $mainConfig = "<?php
return [
    'app_name' => 'Digital Signage System',
    'timezone' => 'Asia/Bangkok',
    'max_upload_size' => 104857600,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mp3', 'wav', 'html'],
    'upload_path' => 'uploads/',
    'jwt_secret' => '" . bin2hex(random_bytes(32)) . "',
    'session_timeout' => 3600,
    'debug' => false,
    'log_level' => 'info',
    'enable_analytics' => true,
    'heartbeat_interval' => 30,
    'default_content_duration' => 10,
];
?>";
    file_put_contents('config/config.php', $mainConfig);
}

function importDatabaseSchema($pdo) {
    $schemaFile = 'sql/schema.sql';
    
    if (!file_exists($schemaFile)) {
        // Create embedded schema if file doesn't exist
        $schema = getEmbeddedSchema();
        
        // Split and execute statements
        $statements = explode(';', $schema);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
    } else {
        $schema = file_get_contents($schemaFile);
        $statements = explode(';', $schema);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
    }
}

function createDefaultData($pdo) {
    // Create default layout
    $pdo->exec("INSERT INTO layouts (name, description, type, template, zones, is_default) VALUES 
        ('Fullscreen Layout', 'Default fullscreen layout', 'fullscreen', 'fullscreen', '{\"main\": {\"x\": 0, \"y\": 0, \"width\": 100, \"height\": 100}}', 1)");

    // Create uploads directories
    $dirs = ['uploads', 'uploads/content', 'uploads/thumbnails', 'uploads/temp', 'logs', 'cache'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Create .htaccess for uploads
    $htaccess = "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>";
    file_put_contents('uploads/.htaccess', $htaccess);
}

function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'MySQL/MariaDB' => extension_loaded('pdo_mysql'),
        'GD Extension' => extension_loaded('gd'),
        'JSON Extension' => extension_loaded('json'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'Uploads Directory Writable' => is_writable('.') || is_writable('uploads'),
        'Config Directory Writable' => is_writable('.') || is_writable('config')
    ];

    return $requirements;
}

function getEmbeddedSchema() {
    return "
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `users` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `role` enum('admin','manager','editor','viewer') DEFAULT 'viewer',
    `avatar` varchar(500) NULL,
    `is_active` boolean DEFAULT TRUE,
    `last_login_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `content` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text NULL,
    `type` enum('video','image','audio','html','widget','dashboard','text') NOT NULL,
    `file_path` varchar(500) NULL,
    `file_url` varchar(500) NULL,
    `thumbnail_path` varchar(500) NULL,
    `file_size` bigint NULL,
    `mime_type` varchar(100) NULL,
    `duration` int NULL,
    `width` int NULL,
    `height` int NULL,
    `metadata` json NULL,
    `tags` json NULL,
    `status` enum('active','inactive','processing','error') DEFAULT 'active',
    `expires_at` timestamp NULL,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `layouts` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `type` enum('grid','rotation','multi-zone','interactive','fullscreen') NOT NULL,
    `template` varchar(100) NOT NULL,
    `orientation` enum('landscape','portrait','auto') DEFAULT 'landscape',
    `resolution` varchar(20) DEFAULT '1920x1080',
    `zones` json NOT NULL,
    `settings` json NULL,
    `is_default` boolean DEFAULT FALSE,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `playlists` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `layout_id` bigint unsigned NULL,
    `total_duration` int DEFAULT 0,
    `loop_count` int DEFAULT 0,
    `shuffle` boolean DEFAULT FALSE,
    `settings` json NULL,
    `is_active` boolean DEFAULT TRUE,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`layout_id`) REFERENCES `layouts` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `playlist_items` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `playlist_id` bigint unsigned NOT NULL,
    `content_id` bigint unsigned NOT NULL,
    `zone_id` varchar(50) DEFAULT 'main',
    `order_index` int DEFAULT 0,
    `duration` int NULL,
    `transition_type` enum('none','fade','slide','zoom','flip') DEFAULT 'fade',
    `transition_duration` int DEFAULT 1000,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `devices` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `device_id` varchar(255) NOT NULL UNIQUE,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `location` varchar(255) NULL,
    `device_type` enum('desktop','tablet','mobile','smart_tv','display','kiosk') DEFAULT 'desktop',
    `os` varchar(100) NULL,
    `browser` varchar(100) NULL,
    `screen_width` int NULL,
    `screen_height` int NULL,
    `orientation` enum('landscape','portrait','auto') DEFAULT 'landscape',
    `current_playlist_id` bigint unsigned NULL,
    `current_layout_id` bigint unsigned NULL,
    `status` enum('online','offline','error','maintenance') DEFAULT 'offline',
    `last_seen` timestamp NULL,
    `last_heartbeat` timestamp NULL,
    `ip_address` varchar(45) NULL,
    `settings` json NULL,
    `api_key` varchar(64) NOT NULL,
    `is_active` boolean DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`current_playlist_id`) REFERENCES `playlists` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`current_layout_id`) REFERENCES `layouts` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `device_logs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `device_id` bigint unsigned NOT NULL,
    `level` enum('info','warning','error','debug') NOT NULL,
    `message` text NOT NULL,
    `context` json NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `content_analytics` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `device_id` bigint unsigned NOT NULL,
    `content_id` bigint unsigned NOT NULL,
    `playlist_id` bigint unsigned NULL,
    `event_type` enum('start','end','skip','error','interaction') NOT NULL,
    `duration_watched` int NULL,
    `interaction_data` json NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `token` varchar(64) NOT NULL UNIQUE,
    `name` varchar(255) NOT NULL,
    `abilities` json NULL,
    `last_used_at` timestamp NULL,
    `expires_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `user_activities` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NULL,
    `action` varchar(255) NOT NULL,
    `description` text NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

SET FOREIGN_KEY_CHECKS = 1;
    ";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .installer { background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2rem; margin-bottom: 10px; }
        .steps { display: flex; justify-content: center; margin: 20px 0; }
        .step { padding: 10px 20px; margin: 0 5px; border-radius: 20px; background: rgba(255,255,255,0.2); font-size: 14px; }
        .step.active { background: rgba(255,255,255,0.9); color: #333; }
        .content { padding: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 16px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-secondary { background: #6c757d; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .requirements { margin: 20px 0; }
        .requirement { display: flex; align-items: center; padding: 10px; margin: 5px 0; border-radius: 6px; }
        .requirement.pass { background: #d4edda; color: #155724; }
        .requirement.fail { background: #f8d7da; color: #721c24; }
        .requirement .icon { margin-right: 10px; font-weight: bold; }
        .text-center { text-align: center; }
        .mb-3 { margin-bottom: 1rem; }
        .welcome-content { text-align: center; padding: 20px 0; }
        .welcome-content h2 { color: #333; margin-bottom: 20px; }
        .welcome-content p { color: #666; line-height: 1.6; margin-bottom: 30px; }
        .feature-list { text-align: left; max-width: 500px; margin: 0 auto; }
        .feature-list li { padding: 8px 0; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <div class="installer">
            <div class="header">
                <h1>🎬 Digital Signage System</h1>
                <div class="steps">
                    <?php foreach ($steps as $key => $name): ?>
                        <div class="step <?= $currentStep === $key ? 'active' : '' ?>"><?= $name ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="content">
                <?php if (isset($GLOBALS['error'])): ?>
                    <div class="error"><?= htmlspecialchars($GLOBALS['error']) ?></div>
                <?php endif; ?>

                <?php if ($currentStep === 'welcome'): ?>
                    <div class="welcome-content">
                        <h2>Welcome to Digital Signage System</h2>
                        <p>Transform your displays into dynamic digital signage with our powerful, easy-to-use system.</p>
                        
                        <div class="feature-list">
                            <h3>Features:</h3>
                            <ul>
                                <li>✅ Content Management (Images, Videos, HTML)</li>
                                <li>✅ Playlist Scheduling</li>
                                <li>✅ Device Management</li>
                                <li>✅ Real-time Analytics</li>
                                <li>✅ Multi-layout Support</li>
                                <li>✅ Responsive Web Interface</li>
                            </ul>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <a href="?step=requirements" class="btn">Start Installation</a>
                        </div>
                    </div>

                <?php elseif ($currentStep === 'requirements'): ?>
                    <h2>System Requirements Check</h2>
                    <div class="requirements">
                        <?php 
                        $requirements = checkRequirements();
                        $allPassed = true;
                        foreach ($requirements as $name => $passed): 
                            if (!$passed) $allPassed = false;
                        ?>
                            <div class="requirement <?= $passed ? 'pass' : 'fail' ?>">
                                <span class="icon"><?= $passed ? '✅' : '❌' ?></span>
                                <?= $name ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($allPassed): ?>
                        <div class="success">All requirements passed! You can proceed with the installation.</div>
                        <a href="?step=database" class="btn">Continue to Database Setup</a>
                    <?php else: ?>
                        <div class="error">Please fix the failed requirements before continuing.</div>
                        <button onclick="location.reload()" class="btn btn-secondary">Recheck Requirements</button>
                    <?php endif; ?>

                <?php elseif ($currentStep === 'database'): ?>
                    <h2>Database Configuration</h2>
                    <p class="mb-3">Configure your MySQL/MariaDB database connection:</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Database Host:</label>
                            <input type="text" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label>Database Username:</label>
                            <input type="text" name="db_username" value="root" required>
                        </div>
                        <div class="form-group">
                            <label>Database Password:</label>
                            <input type="password" name="db_password" value="">
                        </div>
                        <div class="form-group">
                            <label>Database Name:</label>
                            <input type="text" name="db_database" value="digital_signage" required>
                        </div>
                        <button type="submit" class="btn">Create Database & Continue</button>
                    </form>

                <?php elseif ($currentStep === 'admin'): ?>
                    <h2>Create Admin Account</h2>
                    <p class="mb-3">Create your administrator account:</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name:</label>
                            <input type="text" name="admin_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address:</label>
                            <input type="email" name="admin_email" required>
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="admin_password" required>
                        </div>
                        <button type="submit" class="btn">Create Admin & Complete Installation</button>
                    </form>

                <?php elseif ($currentStep === 'complete'): ?>
                    <div class="text-center">
                        <h2 style="color: #28a745; margin-bottom: 20px;">🎉 Installation Complete!</h2>
                        <div class="success">Your Digital Signage System has been successfully installed.</div>
                        
                        <div style="margin: 30px 0; text-align: left; max-width: 500px; margin-left: auto; margin-right: auto;">
                            <h3>Next Steps:</h3>
                            <ol>
                                <li style="padding: 5px 0;">Access the <strong>Admin Panel</strong> to manage your system</li>
                                <li style="padding: 5px 0;">Upload your first content (images, videos)</li>
                                <li style="padding: 5px 0;">Create playlists and assign to devices</li>
                                <li style="padding: 5px 0;">Open the <strong>Player</strong> on your display devices</li>
                            </ol>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <a href="admin/" class="btn" style="margin-right: 10px;">🔧 Admin Panel</a>
                            <a href="player/" class="btn btn-secondary">📺 Player Interface</a>
                        </div>
                        
                        <div style="margin-top: 20px; font-size: 14px; color: #666;">
                            <p>Delete <code>install.php</code> file for security.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>