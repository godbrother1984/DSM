/*
=============================================================================
ทางเลือกที่ 3: Quick Debug Script
=============================================================================
สร้างไฟล์ debug-api.php เพื่อตรวจสอบปัญหา
*/

// ไฟล์: debug-api.php
<?php
echo "<h1>🔍 API Debug Tool</h1>";

// ทดสอบ playlists API
echo "<h2>🎵 Testing Playlists API</h2>";

try {
    ob_start();
    $originalOutput = '';
    
    // Capture original output
    include 'api/playlists.php';
    $originalOutput = ob_get_clean();
    
    echo "<h3>Original Output:</h3>";
    echo "<textarea style='width:100%;height:200px;font-family:monospace;'>";
    echo htmlspecialchars($originalOutput);
    echo "</textarea>";
    
    // Test JSON parsing
    $decoded = json_decode($originalOutput, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<h3>✅ JSON is valid!</h3>";
        echo "<pre>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<h3>❌ JSON Error: " . json_last_error_msg() . "</h3>";
        echo "<p>First 500 characters of output:</p>";
        echo "<pre>" . htmlspecialchars(substr($originalOutput, 0, 500)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Exception: " . $e->getMessage() . "</h3>";
}

// ทดสอบ content API
echo "<h2>📁 Testing Content API</h2>";

try {
    ob_start();
    include 'api/content.php';
    $contentOutput = ob_get_clean();
    
    echo "<h3>Content API Output:</h3>";
    echo "<textarea style='width:100%;height:200px;font-family:monospace;'>";
    echo htmlspecialchars($contentOutput);
    echo "</textarea>";
    
} catch (Exception $e) {
    echo "<h3>❌ Content API Exception: " . $e->getMessage() . "</h3>";
}

echo "<h2>💡 Recommendations:</h2>";
echo "<ul>";
echo "<li>If you see PHP warnings/errors mixed with JSON, add error suppression at the top of API files</li>";
echo "<li>If output is empty, check if files exist and are readable</li>";
echo "<li>If JSON is invalid, check for extra characters before/after JSON</li>";
echo "</ul>";
?>

/*