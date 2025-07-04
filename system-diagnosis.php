<?php
/*
=============================================================================
COMPLETE SYSTEM DIAGNOSIS - ตรวจสอบปัญหาทุกส่วน
=============================================================================
ไฟล์: system-diagnosis.php
รันไฟล์นี้เพื่อตรวจสอบปัญหาทั้งหมดในระบบ
=============================================================================
*/

echo "<h1>🔍 Complete System Diagnosis</h1>";
echo "<style>
body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
.container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; }
.header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
.content { padding: 30px; }
.section { margin-bottom: 30px; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; }
.section h3 { color: #333; margin-bottom: 15px; }
.status-ok { color: #28a745; font-weight: bold; }
.status-error { color: #dc3545; font-weight: bold; }
.status-warning { color: #ffc107; font-weight: bold; }
.file-list { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; font-size: 12px; }
.error-details { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
.recommendation { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 10px 0; }
</style>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 Digital Signage System Diagnosis</h1>";
echo "<p>Complete analysis of all system components</p>";
echo "</div>";
echo "<div class='content'>";

$issues = [];
$warnings = [];
$summary = [];

// ===============================================================
// 1. ตรวจสอบ File Structure
// ===============================================================

echo "<div class='section'>";
echo "<h3>📁 File Structure Analysis</h3>";

$requiredFiles = [
    'api/index.php' => 'Main API router',
    'api/playlists.php' => 'Playlists API endpoint',
    'api/content.php' => 'Content API endpoint',
    'api/devices.php' => 'Devices API endpoint',
    'admin/index.html' => 'Admin dashboard',
    'admin/playlist.html' => 'Playlist manager',
    'admin/content.html' => 'Content manager',
    'admin/devices.html' => 'Device manager',
    'includes/Database.php' => 'Database connection class',
    'includes/ContentManager.php' => 'Content management class',
    'includes/PlaylistManager.php' => 'Playlist management class',
    'config/database.php' => 'Database configuration'
];

$fileStatus = [];
$missingFiles = [];
$corruptedFiles = [];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        $content = file_get_contents($file);
        
        // Check for common issues
        $hasPhpSyntaxError = false;
        $hasJsonError = false;
        $hasHtmlMixed = false;
        
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            // Check for PHP syntax errors
            if (strpos($content, '<br />') !== false || strpos($content, '<b>') !== false) {
                $hasHtmlMixed = true;
                $corruptedFiles[] = "$file - Contains HTML mixed with PHP";
            }
            
            // Check for unclosed braces
            $openBraces = substr_count($content, '{');
            $closeBraces = substr_count($content, '}');
            if ($openBraces !== $closeBraces) {
                $hasPhpSyntaxError = true;
                $corruptedFiles[] = "$file - Unmatched braces (Open: $openBraces, Close: $closeBraces)";
            }
        }
        
        if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
            // Check for JavaScript syntax errors
            if (preg_match('/Unexpected token|SyntaxError|is not valid JSON/', $content)) {
                $hasJsonError = true;
                $corruptedFiles[] = "$file - Contains JavaScript/JSON syntax errors";
            }
        }
        
        $status = "✅";
        if ($hasPhpSyntaxError || $hasJsonError || $hasHtmlMixed) {
            $status = "❌";
            $issues[] = "File corrupted: $file";
        } elseif ($size < 100) {
            $status = "⚠️";
            $warnings[] = "File too small: $file ($size bytes)";
        }
        
        $fileStatus[] = "$status $file ($size bytes) - $description";
    } else {
        $fileStatus[] = "❌ $file - MISSING - $description";
        $missingFiles[] = $file;
        $issues[] = "Missing file: $file";
    }
}

echo "<div class='file-list'>" . implode("\n", $fileStatus) . "</div>";

if (!empty($missingFiles)) {
    echo "<div class='error-details'>";
    echo "<strong>❌ Missing Files (" . count($missingFiles) . "):</strong><br>";
    echo implode('<br>', $missingFiles);
    echo "</div>";
}

if (!empty($corruptedFiles)) {
    echo "<div class='error-details'>";
    echo "<strong>❌ Corrupted Files (" . count($corruptedFiles) . "):</strong><br>";
    echo implode('<br>', $corruptedFiles);
    echo "</div>";
}

$summary['files'] = [
    'total' => count($requiredFiles),
    'found' => count($requiredFiles) - count($missingFiles),
    'missing' => count($missingFiles),
    'corrupted' => count($corruptedFiles)
];

echo "</div>";

// ===============================================================
// 2. ตรวจสอบ Directory Structure
// ===============================================================

echo "<div class='section'>";
echo "<h3>📂 Directory Structure</h3>";

$requiredDirs = [
    'api' => 'API endpoints',
    'admin' => 'Admin interface',
    'includes' => 'PHP classes',
    'config' => 'Configuration files',
    'uploads' => 'Uploaded content',
    'uploads/content' => 'Content files',
    'uploads/thumbnails' => 'Thumbnail images',
    'logs' => 'System logs',
    'cache' => 'Cache files'
];

$dirStatus = [];
$missingDirs = [];

foreach ($requiredDirs as $dir => $description) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? "✅ Writable" : "❌ Not writable";
        $fileCount = count(glob($dir . '/*'));
        $dirStatus[] = "✅ $dir ($fileCount files) - $writable - $description";
        
        if (!is_writable($dir) && in_array($dir, ['uploads', 'uploads/content', 'uploads/thumbnails', 'logs', 'cache'])) {
            $issues[] = "Directory not writable: $dir";
        }
    } else {
        $dirStatus[] = "❌ $dir - MISSING - $description";
        $missingDirs[] = $dir;
        $issues[] = "Missing directory: $dir";
    }
}

echo "<div class='file-list'>" . implode("\n", $dirStatus) . "</div>";

$summary['directories'] = [
    'total' => count($requiredDirs),
    'found' => count($requiredDirs) - count($missingDirs),
    'missing' => count($missingDirs)
];

echo "</div>";

// ===============================================================
// 3. ตรวจสอบ API Endpoints
// ===============================================================

echo "<div class='section'>";
echo "<h3>📡 API Endpoints Testing</h3>";

$apiEndpoints = [
    'api/' => 'Main API router',
    'api/playlists.php' => 'Playlists API',
    'api/content.php' => 'Content API',
    'api/devices.php' => 'Devices API',
    'api/simple-playlists.php' => 'Simple Playlists API',
    'api/simple-content.php' => 'Simple Content API',
    'api/simple-devices.php' => 'Simple Devices API'
];

$apiStatus = [];
$workingAPIs = 0;

foreach ($apiEndpoints as $endpoint => $description) {
    if (file_exists($endpoint)) {
        // Try to test the endpoint
        $testUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($testUrl, false, $context);
        $httpCode = isset($http_response_header[0]) ? $http_response_header[0] : 'No response';
        
        if ($response !== false) {
            $isJson = json_decode($response) !== null;
            $hasError = strpos($response, 'Fatal error') !== false || strpos($response, 'Parse error') !== false;
            
            if ($isJson && !$hasError) {
                $apiStatus[] = "✅ $endpoint - $description - Working";
                $workingAPIs++;
            } elseif ($hasError) {
                $apiStatus[] = "❌ $endpoint - $description - PHP Error detected";
                $issues[] = "API has PHP error: $endpoint";
            } else {
                $apiStatus[] = "⚠️ $endpoint - $description - Non-JSON response";
                $warnings[] = "API returns non-JSON: $endpoint";
            }
        } else {
            $apiStatus[] = "❌ $endpoint - $description - No response ($httpCode)";
            $issues[] = "API not responding: $endpoint";
        }
    } else {
        $apiStatus[] = "❌ $endpoint - $description - File not found";
        $issues[] = "API file missing: $endpoint";
    }
}

echo "<div class='file-list'>" . implode("\n", $apiStatus) . "</div>";

$summary['apis'] = [
    'total' => count($apiEndpoints),
    'working' => $workingAPIs,
    'failing' => count($apiEndpoints) - $workingAPIs
];

echo "</div>";

// ===============================================================
// 4. ตรวจสอบ Database Connection
// ===============================================================

echo "<div class='section'>";
echo "<h3>🗄️ Database Connection</h3>";

$dbStatus = "❌ Not tested";
$dbError = "";

if (file_exists('config/database.php')) {
    try {
        $config = include 'config/database.php';
        
        if (is_array($config) && isset($config['host'])) {
            $dsn = "mysql:host={$config['host']};charset=utf8mb4";
            if (isset($config['database'])) {
                $dsn .= ";dbname={$config['database']}";
            }
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            // Test basic query
            $result = $pdo->query("SELECT 1")->fetchColumn();
            
            if ($result == 1) {
                $dbStatus = "✅ Connected successfully";
                
                // Check if database exists
                if (isset($config['database'])) {
                    $dbExists = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['database']}'")->fetchColumn();
                    if ($dbExists) {
                        $dbStatus .= " - Database '{$config['database']}' exists";
                        
                        // Check tables
                        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                        $requiredTables = ['users', 'content', 'playlists', 'devices'];
                        $missingTables = array_diff($requiredTables, $tables);
                        
                        if (empty($missingTables)) {
                            $dbStatus .= " - All tables present";
                        } else {
                            $dbStatus .= " - Missing tables: " . implode(', ', $missingTables);
                            $warnings[] = "Database missing tables: " . implode(', ', $missingTables);
                        }
                    } else {
                        $dbStatus .= " - Database '{$config['database']}' does not exist";
                        $warnings[] = "Database does not exist: {$config['database']}";
                    }
                }
            }
        } else {
            $dbError = "Invalid configuration format";
        }
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
        $dbStatus = "❌ Connection failed";
    } catch (Exception $e) {
        $dbError = $e->getMessage();
        $dbStatus = "❌ Error";
    }
} else {
    $dbStatus = "❌ No database configuration";
    $dbError = "config/database.php not found";
}

echo "<div class='file-list'>$dbStatus</div>";

if ($dbError) {
    echo "<div class='error-details'><strong>Database Error:</strong> $dbError</div>";
    if (strpos($dbStatus, '❌') !== false) {
        $issues[] = "Database connection failed: $dbError";
    }
}

$summary['database'] = strpos($dbStatus, '✅') !== false ? 'working' : 'failed';

echo "</div>";

// ===============================================================
// 5. ตรวจสอบ PHP Environment
// ===============================================================

echo "<div class='section'>";
echo "<h3>🐘 PHP Environment</h3>";

$phpInfo = [
    "PHP Version: " . PHP_VERSION,
    "Memory Limit: " . ini_get('memory_limit'),
    "Max Upload Size: " . ini_get('upload_max_filesize'),
    "Max Execution Time: " . ini_get('max_execution_time') . "s",
    "Error Reporting: " . (error_reporting() ? 'Enabled' : 'Disabled'),
    "Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off')
];

$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'gd', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $phpInfo[] = "Extension $ext: ✅ Loaded";
    } else {
        $phpInfo[] = "Extension $ext: ❌ Missing";
        $missingExtensions[] = $ext;
        $issues[] = "Missing PHP extension: $ext";
    }
}

echo "<div class='file-list'>" . implode("\n", $phpInfo) . "</div>";

$summary['php'] = [
    'version' => PHP_VERSION,
    'extensions_missing' => count($missingExtensions)
];

echo "</div>";

// ===============================================================
// 6. ตรวจสอบ Frontend Files
// ===============================================================

echo "<div class='section'>";
echo "<h3>🌐 Frontend Analysis</h3>";

$frontendFiles = [
    'admin/index.html',
    'admin/playlist.html', 
    'admin/content.html',
    'admin/devices.html',
    'admin/playlist-fixed.html',
    'admin/content-fixed.html',
    'admin/devices-fixed.html'
];

$frontendStatus = [];
$jsErrors = [];

foreach ($frontendFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $size = filesize($file);
        
        // Check for JavaScript errors
        $errors = [];
        if (preg_match_all('/SyntaxError|Unexpected token|is not valid JSON/i', $content, $matches)) {
            $errors = array_merge($errors, $matches[0]);
        }
        
        // Check for incomplete HTML
        if (substr_count($content, '<html') !== substr_count($content, '</html>')) {
            $errors[] = "Incomplete HTML structure";
        }
        
        // Check for PHP errors mixed in
        if (strpos($content, '<?php') !== false && pathinfo($file, PATHINFO_EXTENSION) === 'html') {
            $errors[] = "PHP code in HTML file";
        }
        
        $status = empty($errors) ? "✅" : "❌";
        $errorText = empty($errors) ? "" : " - Errors: " . implode(", ", $errors);
        
        $frontendStatus[] = "$status $file ($size bytes)$errorText";
        
        if (!empty($errors)) {
            $jsErrors[] = "$file: " . implode(", ", $errors);
            $issues[] = "Frontend error in $file";
        }
    } else {
        $frontendStatus[] = "❌ $file - Missing";
        $issues[] = "Missing frontend file: $file";
    }
}

echo "<div class='file-list'>" . implode("\n", $frontendStatus) . "</div>";

if (!empty($jsErrors)) {
    echo "<div class='error-details'>";
    echo "<strong>❌ Frontend Errors:</strong><br>";
    echo implode('<br>', $jsErrors);
    echo "</div>";
}

$summary['frontend'] = [
    'total' => count($frontendFiles),
    'errors' => count($jsErrors)
];

echo "</div>";

// ===============================================================
// 7. Overall System Health Summary
// ===============================================================

echo "<div class='section'>";
echo "<h3>📊 System Health Summary</h3>";

$totalIssues = count($issues);
$totalWarnings = count($warnings);

if ($totalIssues === 0 && $totalWarnings === 0) {
    $healthStatus = "<span class='status-ok'>🟢 HEALTHY</span>";
} elseif ($totalIssues === 0) {
    $healthStatus = "<span class='status-warning'>🟡 MINOR ISSUES</span>";
} elseif ($totalIssues < 5) {
    $healthStatus = "<span class='status-error'>🟠 NEEDS ATTENTION</span>";
} else {
    $healthStatus = "<span class='status-error'>🔴 CRITICAL</span>";
}

echo "<div style='font-size: 1.2em; margin-bottom: 20px;'>";
echo "<strong>Overall Status: $healthStatus</strong>";
echo "</div>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;'>";
echo "<div><strong>Critical Issues:</strong> <span class='status-error'>$totalIssues</span></div>";
echo "<div><strong>Warnings:</strong> <span class='status-warning'>$totalWarnings</span></div>";
echo "<div><strong>Files Found:</strong> {$summary['files']['found']}/{$summary['files']['total']}</div>";
echo "<div><strong>APIs Working:</strong> {$summary['apis']['working']}/{$summary['apis']['total']}</div>";
echo "<div><strong>Database:</strong> " . ($summary['database'] === 'working' ? '<span class="status-ok">✅</span>' : '<span class="status-error">❌</span>') . "</div>";
echo "</div>";

if (!empty($issues)) {
    echo "<div class='error-details'>";
    echo "<strong>❌ Critical Issues to Fix:</strong><br>";
    echo "1. " . implode('<br>2. ', array_slice($issues, 0, 10));
    if (count($issues) > 10) {
        echo "<br>... and " . (count($issues) - 10) . " more issues";
    }
    echo "</div>";
}

if (!empty($warnings)) {
    echo "<div class='error-details' style='background: #fff3cd;'>";
    echo "<strong>⚠️ Warnings:</strong><br>";
    echo "1. " . implode('<br>2. ', array_slice($warnings, 0, 5));
    if (count($warnings) > 5) {
        echo "<br>... and " . (count($warnings) - 5) . " more warnings";
    }
    echo "</div>";
}

echo "</div>";

// ===============================================================
// 8. Recommendations
// ===============================================================

echo "<div class='section'>";
echo "<h3>💡 Recommended Actions</h3>";

$recommendations = [];

if ($summary['files']['missing'] > 0) {
    $recommendations[] = "🔧 Run the complete system setup script to create missing files";
}

if ($summary['apis']['failing'] > 0) {
    $recommendations[] = "📡 Fix API endpoints - check for PHP syntax errors and missing dependencies";
}

if ($summary['database'] !== 'working') {
    $recommendations[] = "🗄️ Set up database connection or use demo mode for testing";
}

if ($summary['frontend']['errors'] > 0) {
    $recommendations[] = "🌐 Fix JavaScript errors in admin frontend files";
}

if ($summary['php']['extensions_missing'] > 0) {
    $recommendations[] = "🐘 Install missing PHP extensions";
}

if (count($corruptedFiles) > 0) {
    $recommendations[] = "📝 Restore corrupted files from backup or regenerate them";
}

if (!empty($recommendations)) {
    echo "<div class='recommendation'>";
    echo "<strong>Priority Actions:</strong><br>";
    echo "1. " . implode('<br>2. ', $recommendations);
    echo "</div>";
} else {
    echo "<div class='recommendation'>";
    echo "<strong>✅ System is healthy!</strong> No immediate actions required.";
    echo "</div>";
}

echo "</div>";

echo "</div></div>";

// Generate quick fix suggestions
echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<h3>🚀 Quick Fix Options</h3>";
echo "<div style='display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;'>";
echo "<button onclick=\"window.location.href='complete-system-fix.php'\" style='padding: 15px 30px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;'>🔧 Auto Fix All Issues</button>";
echo "<button onclick=\"window.location.href='emergency-restore.php'\" style='padding: 15px 30px; background: #dc3545; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;'>🚨 Emergency Restore</button>";
echo "<button onclick=\"window.location.href='api-test-complete.html'\" style='padding: 15px 30px; background: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;'>🧪 Test APIs</button>";
echo "</div>";
echo "</div>";

echo "<script>
console.log('System Diagnosis Complete');
console.log('Critical Issues: $totalIssues');
console.log('Warnings: $totalWarnings');
console.log('Overall Health: " . strip_tags($healthStatus) . "');
</script>";
?>