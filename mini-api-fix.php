<?php
/*
=============================================================================
PATH FIX API - แก้ปัญหา Path Parsing
=============================================================================
*/

echo "<h1>🔧 Path Fix API</h1>";
echo "<h3>แก้ปัญหา DSM ปนใน path</h3>";

// ===============================================================
// สร้าง API ใหม่ที่แก้ไข path parsing
// ===============================================================

$fixedAPI = '<?php
// Ultra simple API with fixed path parsing
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Clean any previous output
if (ob_get_level()) ob_end_clean();

// Turn off error display
error_reporting(0);
ini_set("display_errors", 0);

// Handle OPTIONS
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo "{}";
    exit;
}

// Get request info
$method = $_SERVER["REQUEST_METHOD"];
$uri = $_SERVER["REQUEST_URI"];

// Debug: log the original URI
// error_log("Original URI: " . $uri);

// Better path parsing - remove all possible prefixes
$path = $uri;

// Remove common prefixes
$prefixes = [
    "/dsm/api/",
    "/DSM/api/", 
    "/api/",
    "/dsm/",
    "/DSM/"
];

foreach ($prefixes as $prefix) {
    if (strpos($path, $prefix) === 0) {
        $path = substr($path, strlen($prefix));
        break;
    }
}

// Remove index.php if present
$path = str_replace("index.php", "", $path);
$path = str_replace("index.php/", "", $path);

// Remove leading/trailing slashes
$path = trim($path, "/");

// Remove query string
if (strpos($path, "?") !== false) {
    $path = substr($path, 0, strpos($path, "?"));
}

// Debug: log the processed path
// error_log("Processed path: " . $path);

// Route logic
if (empty($path)) {
    // API root
    $data = [
        "success" => true,
        "message" => "Digital Signage API is working!",
        "data" => [
            "name" => "Digital Signage API",
            "version" => "1.0.0",
            "timestamp" => date("c"),
            "debug_info" => [
                "original_uri" => $uri,
                "processed_path" => $path,
                "method" => $method
            ],
            "endpoints" => [
                "/api/" => "API info",
                "/api/content" => "Content list", 
                "/api/player/playlist" => "Playlist",
                "/api/player/register" => "Register device"
            ]
        ]
    ];
} elseif ($path === "content") {
    // Content endpoint
    $data = [
        "success" => true,
        "message" => "Content retrieved successfully",
        "data" => [
            [
                "id" => 1,
                "title" => "Welcome to Digital Signage",
                "type" => "image",
                "file_url" => "https://picsum.photos/1920/1080?text=Welcome+Digital+Signage",
                "thumbnail_path" => "https://picsum.photos/300/200?text=Welcome",
                "duration" => 10,
                "status" => "active",
                "created_at" => "2024-01-01 12:00:00"
            ],
            [
                "id" => 2,
                "title" => "Sample Video Content",
                "type" => "video",
                "file_url" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                "thumbnail_path" => "https://picsum.photos/300/200?text=Video",
                "duration" => 30,
                "status" => "active",
                "created_at" => "2024-01-01 12:00:00"
            ],
            [
                "id" => 3,
                "title" => "Information Display",
                "type" => "text",
                "file_url" => "Your Digital Signage System is Ready!",
                "duration" => 8,
                "status" => "active",
                "created_at" => "2024-01-01 12:00:00"
            ]
        ]
    ];
} elseif ($path === "player/playlist" || strpos($path, "playlist") !== false) {
    // Playlist endpoint
    $data = [
        "success" => true,
        "message" => "Playlist retrieved successfully",
        "data" => [
            "playlist" => [
                "id" => 1,
                "name" => "Default Playlist",
                "items" => [
                    [
                        "content_id" => 1,
                        "title" => "Welcome Message",
                        "type" => "image",
                        "file_url" => "https://picsum.photos/1920/1080?text=Welcome+Digital+Signage",
                        "duration" => 10
                    ],
                    [
                        "content_id" => 2,
                        "title" => "Demo Video",
                        "type" => "video",
                        "file_url" => "https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4",
                        "duration" => 20
                    ],
                    [
                        "content_id" => 3,
                        "title" => "Ready for Content",
                        "type" => "text",
                        "file_url" => "Digital Signage System Ready!",
                        "duration" => 8
                    ]
                ]
            ]
        ]
    ];
} elseif ($path === "player/register" || strpos($path, "register") !== false) {
    // Register endpoint
    $data = [
        "success" => true,
        "message" => "Device registered successfully",
        "data" => [
            "device" => [
                "id" => rand(1000, 9999),
                "device_id" => "device-" . time(),
                "name" => "Digital Display",
                "api_key" => "key-" . bin2hex(random_bytes(8)),
                "status" => "registered"
            ]
        ]
    ];
} else {
    // Unknown endpoint
    $data = [
        "success" => false,
        "message" => "Endpoint not found: " . $path,
        "data" => [
            "debug_info" => [
                "original_uri" => $uri,
                "processed_path" => $path,
                "method" => $method,
                "available_endpoints" => [
                    "content",
                    "player/playlist", 
                    "player/register"
                ]
            ]
        ]
    ];
}

// Output JSON
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
?>';

// Replace the API file
if (file_put_contents('api/index.php', $fixedAPI)) {
    echo "✅ Updated api/index.php with fixed path parsing<br>";
} else {
    echo "❌ Failed to update api/index.php<br>";
    die("Cannot update API file");
}

// ===============================================================
// สร้างหน้าทดสอบใหม่ที่แสดง debug info
// ===============================================================

$debugTestPage = '<!DOCTYPE html>
<html>
<head>
    <title>Debug API Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .btn { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; margin: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn.success { background: #28a745; }
        .result { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 4px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; background: #f8fff8; }
        .error { border-left-color: #dc3545; background: #fff8f8; }
        .debug { background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .url-info { background: #e9ecef; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Debug API Test - Path Fix Version</h1>
        <p>This version shows debug info to see exactly how paths are processed</p>
        
        <div class="url-info">
            <strong>Base URL:</strong> <span id="baseUrl"></span><br>
            <strong>API URL:</strong> <span id="apiUrl"></span><br>
            <strong>Current Page:</strong> <span id="currentPage"></span>
        </div>
        
        <button class="btn" onclick="testEndpoint(\'\')">🏠 Test API Root</button>
        <button class="btn" onclick="testEndpoint(\'content\')">📁 Test Content</button>
        <button class="btn" onclick="testEndpoint(\'player/playlist\')">📋 Test Playlist</button>
        <button class="btn" onclick="testEndpoint(\'player/register\', \'POST\')">📱 Test Register</button>
        <button class="btn success" onclick="runAllTests()">🚀 Run All Tests</button>
        <button class="btn" onclick="clearResults()">🗑️ Clear</button>
        
        <div id="results"></div>
    </div>

    <script>
        const currentUrl = window.location.href;
        const baseUrl = window.location.origin + window.location.pathname.replace("path-fix-api.php", "").replace("debug-test.html", "");
        const apiUrl = baseUrl + "api/";
        
        document.getElementById("baseUrl").textContent = baseUrl;
        document.getElementById("apiUrl").textContent = apiUrl;
        document.getElementById("currentPage").textContent = currentUrl;
        
        function clearResults() {
            document.getElementById("results").innerHTML = "";
        }
        
        async function testEndpoint(endpoint, method = "GET") {
            const url = apiUrl + endpoint;
            const resultsDiv = document.getElementById("results");
            
            // Show loading
            const loadingDiv = document.createElement("div");
            loadingDiv.className = "result";
            loadingDiv.innerHTML = `
                <h3>Testing: ${method} /${endpoint}</h3>
                <div class="debug">
                    <strong>Full URL:</strong> ${url}<br>
                    <strong>Method:</strong> ${method}<br>
                    <strong>Time:</strong> ${new Date().toLocaleTimeString()}
                </div>
                <p>⏳ Loading...</p>
            `;
            resultsDiv.appendChild(loadingDiv);
            
            try {
                const options = { method };
                if (method === "POST") {
                    options.headers = { "Content-Type": "application/json" };
                    options.body = JSON.stringify({ 
                        device_id: "test-" + Date.now(),
                        name: "Test Device"
                    });
                }
                
                const response = await fetch(url, options);
                const responseText = await response.text();
                
                let resultHtml = `<h3>${response.ok ? "✅" : "❌"} ${method} /${endpoint}</h3>`;
                
                resultHtml += `<div class="debug">
                    <strong>Full URL:</strong> ${url}<br>
                    <strong>Status:</strong> ${response.status} ${response.statusText}<br>
                    <strong>Content-Type:</strong> ${response.headers.get("content-type")}<br>
                    <strong>Response Size:</strong> ${responseText.length} bytes
                </div>`;
                
                try {
                    const data = JSON.parse(responseText);
                    
                    // Show debug info if available
                    if (data.data && data.data.debug_info) {
                        resultHtml += `<div class="debug">
                            <strong>Debug Info:</strong><br>
                            Original URI: ${data.data.debug_info.original_uri}<br>
                            Processed Path: ${data.data.debug_info.processed_path}<br>
                            Method: ${data.data.debug_info.method}
                        </div>`;
                    }
                    
                    resultHtml += `<h4>JSON Response:</h4><pre>${JSON.stringify(data, null, 2)}</pre>`;
                    loadingDiv.className = data.success ? "result success" : "result error";
                } catch (jsonError) {
                    resultHtml += `<h4>❌ JSON Parse Error:</h4><p>${jsonError.message}</p>`;
                    resultHtml += `<h4>Raw Response:</h4><pre>${responseText.substring(0, 1000)}</pre>`;
                    loadingDiv.className = "result error";
                }
                
                loadingDiv.innerHTML = resultHtml;
                
            } catch (fetchError) {
                loadingDiv.innerHTML = `
                    <h3>❌ Network Error: ${method} /${endpoint}</h3>
                    <div class="debug">
                        <strong>URL:</strong> ${url}<br>
                        <strong>Error:</strong> ${fetchError.message}
                    </div>
                `;
                loadingDiv.className = "result error";
            }
            
            // Scroll to result
            loadingDiv.scrollIntoView({ behavior: "smooth", block: "center" });
        }
        
        async function runAllTests() {
            clearResults();
            const resultsDiv = document.getElementById("results");
            resultsDiv.innerHTML = "<h3>🚀 Running all tests with debug info...</h3>";
            
            await testEndpoint("");
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            await testEndpoint("content");
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            await testEndpoint("player/playlist");
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            await testEndpoint("player/register", "POST");
            
            // Add summary
            setTimeout(() => {
                const summaryDiv = document.createElement("div");
                summaryDiv.className = "result success";
                summaryDiv.innerHTML = `
                    <h3>✅ All Tests Completed</h3>
                    <p>Check the debug info above to see how paths are processed.</p>
                    <p>If endpoints show "not found", check the processed path in debug info.</p>
                `;
                resultsDiv.appendChild(summaryDiv);
            }, 2000);
        }
    </script>
</body>
</html>';

if (file_put_contents('debug-test.html', $debugTestPage)) {
    echo "✅ Created debug-test.html<br>";
} else {
    echo "❌ Failed to create test page<br>";
}

echo "<h3>🎯 What Changed</h3>";
echo "<ul>";
echo "<li>✅ Fixed path parsing to handle /DSM/ prefix properly</li>";
echo "<li>✅ Added debug info to see original URI vs processed path</li>";
echo "<li>✅ Better endpoint matching with fallback options</li>";
echo "<li>✅ Created debug test page to show detailed info</li>";
echo "</ul>";

echo "<h3>🔗 Test Now</h3>";
echo "<p><a href='api/' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px;'>🔌 Direct API Test</a></p>";
echo "<p><a href='debug-test.html' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px;'>🔧 Debug Test Page</a></p>";

echo "<script>";
echo "setTimeout(() => {";
echo "  if (confirm('Path fix applied! Test the API now?')) {";
echo "    window.open('debug-test.html', '_blank');";
echo "  }";
echo "}, 1500);";
echo "</script>";
?>