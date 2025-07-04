<?php
/*
=============================================================================
DEBUG SCRIPT - ตรวจสอบปัญหา Playlist ไม่บันทึก
=============================================================================
ไฟล์: debug_playlist.php (วางไว้ใน root directory)
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Digital Signage Debug Tool</h1>";

function debugSection($title, $content) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px;'>";
    echo "<h3 style='color: #333; margin-top: 0;'>$title</h3>";
    echo "<div style='background: #f9f9f9; padding: 10px; font-family: monospace;'>$content</div>";
    echo "</div>";
}

// 1. ตรวจสอบไฟล์และโฟลเดอร์
echo "<h2>📁 File Structure Check</h2>";

$requiredFiles = [
    'config/database.php',
    'includes/Database.php', 
    'api/index.php',
    'admin/playlist.html'
];

$fileStatus = [];
foreach ($requiredFiles as $file) {
    $exists = file_exists($file);
    $fileStatus[] = ($exists ? "✅" : "❌") . " $file";
    
    if (!$exists && dirname($file) !== '.') {
        // สร้างโฟลเดอร์ถ้าไม่มี
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $fileStatus[] = "📁 Created directory: $dir";
        }
    }
}

debugSection("Required Files", implode("<br>", $fileStatus));

// 2. ตรวจสอบ Database Connection
echo "<h2>🗄️ Database Connection</h2>";

try {
    // สร้าง config ถ้าไม่มี
    if (!file_exists('config/database.php')) {
        $configContent = '<?php
return [
    "host" => "localhost",
    "database" => "digital_signage",
    "username" => "root", 
    "password" => "",
    "charset" => "utf8mb4",
    "options" => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
?>';
        file_put_contents('config/database.php', $configContent);
        debugSection("Config Created", "Created config/database.php with default settings");
    }
    
    // ทดสอบการเชื่อมต่อ
    $config = include 'config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    $dbInfo = [
        "✅ Connection: SUCCESS",
        "🏠 Host: " . $config['host'],
        "🗃️ Database: " . $config['database'], 
        "👤 Username: " . $config['username'],
        "📊 Server: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
    ];
    
    debugSection("Database Connection", implode("<br>", $dbInfo));
    
    // ตรวจสอบตาราง
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $tableList = empty($tables) ? "❌ No tables found" : "✅ Tables: " . implode(", ", $tables);
    debugSection("Database Tables", $tableList);
    
    // ตรวจสอบ playlists table
    if (in_array('playlists', $tables)) {
        $playlistCount = $pdo->query("SELECT COUNT(*) FROM playlists")->fetchColumn();
        $playlistInfo = "📊 Total playlists: $playlistCount";
        
        // แสดง 5 playlists ล่าสุด
        $recentPlaylists = $pdo->query("SELECT id, name, created_at FROM playlists ORDER BY created_at DESC LIMIT 5")->fetchAll();
        if ($recentPlaylists) {
            $playlistInfo .= "<br><br>🔖 Recent playlists:<br>";
            foreach ($recentPlaylists as $playlist) {
                $playlistInfo .= "• ID: {$playlist['id']}, Name: {$playlist['name']}, Created: {$playlist['created_at']}<br>";
            }
        }
        
        debugSection("Playlists Table", $playlistInfo);
    } else {
        debugSection("Playlists Table", "❌ Table 'playlists' not found! Please run installation.");
    }
    
} catch (Exception $e) {
    debugSection("Database Error", "❌ " . $e->getMessage());
}

// 3. ทดสอบ API
echo "<h2>🔌 API Test</h2>";

// ทดสอบ API endpoint
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/playlists';
$apiTest = [
    "🌐 API URL: $apiUrl",
    "📡 Testing API connection..."
];

// ทดสอบ GET request
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5
    ]
]);

try {
    $apiResponse = @file_get_contents($apiUrl, false, $context);
    if ($apiResponse) {
        $apiData = json_decode($apiResponse, true);
        if ($apiData) {
            $apiTest[] = "✅ API Response: " . $apiData['message'];
            if (isset($apiData['data']['playlists'])) {
                $apiTest[] = "📊 Playlists returned: " . count($apiData['data']['playlists']);
            }
        } else {
            $apiTest[] = "⚠️ API returned invalid JSON";
        }
    } else {
        $apiTest[] = "❌ API not responding";
    }
} catch (Exception $e) {
    $apiTest[] = "❌ API Error: " . $e->getMessage();
}

debugSection("API Test", implode("<br>", $apiTest));

// 4. ทดสอบการสร้าง Playlist
echo "<h2>🎵 Test Playlist Creation</h2>";

if (isset($_POST['test_create'])) {
    try {
        $testData = [
            'name' => 'Debug Test Playlist',
            'description' => 'Created by debug script at ' . date('Y-m-d H:i:s'),
            'layout_id' => 1,
            'shuffle' => false,
            'is_active' => 1,
            'created_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $columns = implode(',', array_keys($testData));
        $placeholders = ':' . implode(', :', array_keys($testData));
        $sql = "INSERT INTO playlists ($columns) VALUES ($placeholders)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($testData);
        
        if ($result) {
            $newId = $pdo->lastInsertId();
            $success = "✅ Test playlist created successfully!<br>🆔 New ID: $newId<br>📝 Name: {$testData['name']}";
            debugSection("Create Test Result", $success);
        } else {
            $error = $stmt->errorInfo();
            debugSection("Create Test Result", "❌ Failed: " . $error[2]);
        }
        
    } catch (Exception $e) {
        debugSection("Create Test Result", "❌ Exception: " . $e->getMessage());
    }
}

// ฟอร์มทดสอบ
echo "<form method='POST' style='margin: 20px 0;'>";
echo "<button type='submit' name='test_create' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>🧪 Test Create Playlist</button>";
echo "</form>";

// 5. การแก้ไขปัญหา
echo "<h2>🔧 Quick Fixes</h2>";

$fixes = [
    "1. ✅ Check database connection",
    "2. ✅ Verify table structure", 
    "3. ✅ Test API endpoints",
    "4. 📋 <strong>Next steps if issues persist:</strong>",
    "   • Run install.php to create tables",
    "   • Check PHP error logs",
    "   • Verify file permissions",
    "   • Update config/database.php with correct credentials"
];

debugSection("Troubleshooting Checklist", implode("<br>", $fixes));

// 6. SQL สำหรับสร้างตาราง (ถ้าจำเป็น)
if (isset($_POST['create_tables'])) {
    try {
        $tableSQL = "
        CREATE TABLE IF NOT EXISTS `playlists` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text NULL,
            `layout_id` bigint unsigned NULL DEFAULT 1,
            `shuffle` boolean DEFAULT FALSE,
            `is_active` boolean DEFAULT TRUE,
            `created_by` bigint unsigned NULL DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        );
        
        CREATE TABLE IF NOT EXISTS `playlist_items` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `playlist_id` bigint unsigned NOT NULL,
            `content_id` bigint unsigned NOT NULL,
            `order_index` int NOT NULL DEFAULT 0,
            `duration` int NULL DEFAULT 10,
            `zone_id` varchar(50) DEFAULT 'main',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS `content` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `description` text NULL,
            `type` enum('video','image','audio','html','widget','text') NOT NULL,
            `file_path` varchar(500) NULL,
            `file_url` varchar(500) NULL,
            `thumbnail_path` varchar(500) NULL,
            `duration` int NULL DEFAULT 10,
            `file_size` bigint NULL,
            `mime_type` varchar(100) NULL,
            `status` enum('active','inactive','processing','error') DEFAULT 'active',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        );";
        
        $pdo->exec($tableSQL);
        debugSection("Table Creation", "✅ Tables created successfully!");
        
        // Insert sample content
        $sampleContent = [
            ['title' => 'Sample Image 1', 'type' => 'image', 'file_url' => '/uploads/sample1.jpg', 'duration' => 10],
            ['title' => 'Sample Video 1', 'type' => 'video', 'file_url' => '/uploads/sample1.mp4', 'duration' => 30],
            ['title' => 'Welcome Message', 'type' => 'text', 'file_url' => 'Welcome to Digital Signage', 'duration' => 15]
        ];
        
        $contentSQL = "INSERT IGNORE INTO content (title, type, file_url, duration, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())";
        $contentStmt = $pdo->prepare($contentSQL);
        
        foreach ($sampleContent as $content) {
            $contentStmt->execute([$content['title'], $content['type'], $content['file_url'], $content['duration']]);
        }
        
        debugSection("Sample Data", "✅ Sample content added!");
        
    } catch (Exception $e) {
        debugSection("Table Creation Error", "❌ " . $e->getMessage());
    }
}

echo "<form method='POST' style='margin: 20px 0;'>";
echo "<button type='submit' name='create_tables' style='background: #2196F3; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>🗃️ Create Missing Tables</button>";
echo "</form>";

// 7. Debug Information
echo "<h2>🔬 System Information</h2>";

$sysInfo = [
    "🐘 PHP Version: " . PHP_VERSION,
    "🌐 Web Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'),
    "📁 Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'),
    "🔗 Current URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}",
    "⏰ Server Time: " . date('Y-m-d H:i:s'),
    "💾 Memory Limit: " . ini_get('memory_limit'),
    "📤 Upload Max Size: " . ini_get('upload_max_filesize'),
    "⏱️ Max Execution Time: " . ini_get('max_execution_time') . " seconds"
];

debugSection("System Info", implode("<br>", $sysInfo));

// 8. Log recent errors
echo "<h2>📋 Recent PHP Errors</h2>";

$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $errors = array_slice(file($errorLog), -10); // Last 10 lines
    $errorDisplay = empty($errors) ? "✅ No recent errors" : "📄 Last 10 error log entries:<br>" . implode("<br>", array_map('htmlspecialchars', $errors));
} else {
    $errorDisplay = "ℹ️ Error log not found or not configured";
}

debugSection("Error Log", $errorDisplay);

// 9. Final recommendations
echo "<h2>💡 Recommendations</h2>";

$recommendations = [
    "<strong>If playlists are not saving:</strong>",
    "1. 🔧 Run this debug script and check for red ❌ marks",
    "2. 🗃️ Click 'Create Missing Tables' if database tables are missing", 
    "3. 🔍 Check browser Developer Tools → Network tab when saving playlist",
    "4. 📝 Check if API returns success or error messages",
    "5. 🔗 Verify API URL is accessible: <code>/api/playlists</code>",
    "",
    "<strong>Database issues:</strong>",
    "• Make sure MySQL/MariaDB server is running",
    "• Verify database 'digital_signage' exists",
    "• Check username/password in config/database.php",
    "• Ensure user has INSERT/UPDATE permissions",
    "",
    "<strong>File permission issues:</strong>",
    "• Config folder should be writable (755)",
    "• Uploads folder should be writable (755)", 
    "• Check PHP has permission to read includes/ folder"
];

debugSection("Troubleshooting Guide", implode("<br>", $recommendations));

// CSS สำหรับการแสดงผล
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
h1 { color: #333; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin: 0 0 20px 0; }
h2 { color: #444; border-bottom: 2px solid #ddd; padding-bottom: 5px; }
h3 { margin-top: 0; }
code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
button:hover { opacity: 0.9; }
</style>";

echo "<div style='background: #e8f5e8; border: 1px solid #4CAF50; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Quick Test:</strong> Try creating a playlist in the admin panel after running this debug script. If it still doesn't work, check the browser console for JavaScript errors and the Network tab for API response details.";
echo "</div>";

?>