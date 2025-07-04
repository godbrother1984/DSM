<?php
/*
=============================================================================
แก้ไขปัญหา JSON Error - Unexpected end of JSON input
=============================================================================
*/

// ไฟล์: api/playlists.php (สร้างใหม่เป็น endpoint แยก)

<?php
// ป้องกัน PHP warnings/notices ที่อาจทำให้ JSON เสีย
error_reporting(0);
ini_set('display_errors', 0);
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');

// เคลียร์ output buffer ก่อนส่ง JSON
if (ob_get_length()) ob_clean();

// ตั้งค่า headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ฟังก์ชันส่ง JSON response ที่ปลอดภัย
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    
    // ล้าง output buffer ให้แน่ใจ
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if ($json === false) {
        // JSON encode ล้มเหลว
        echo json_encode([
            'success' => false,
            'message' => 'JSON encode error: ' . json_last_error_msg()
        ]);
    } else {
        echo $json;
    }
    exit;
}

// ฟังก์ชันเชื่อมต่อ database แบบง่าย
function getDbConnection() {
    try {
        // ลองหา config ในหลายที่
        $configPaths = [
            '../config/database.php',
            './config/database.php',
            dirname(__DIR__) . '/config/database.php'
        ];
        
        $config = null;
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $config = include $path;
                break;
            }
        }
        
        // ถ้าไม่เจอ config ใช้ค่า default
        if (!$config) {
            $config = [
                'host' => 'localhost',
                'database' => 'digital_signage',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4'
            ];
        }
        
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        return $pdo;
        
    } catch (Exception $e) {
        return null;
    }
}

// Main logic
try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // เชื่อมต่อ database
    $pdo = getDbConnection();
    
    switch ($method) {
        case 'GET':
            // ดึงรายการ playlists
            if ($pdo) {
                try {
                    $stmt = $pdo->query("
                        SELECT p.*, 
                               COUNT(pi.id) as item_count,
                               COALESCE(SUM(pi.duration), 0) as total_duration
                        FROM playlists p 
                        LEFT JOIN playlist_items pi ON p.id = pi.playlist_id 
                        WHERE p.is_active = 1 
                        GROUP BY p.id 
                        ORDER BY p.created_at DESC
                    ");
                    $playlists = $stmt->fetchAll();
                    
                    sendJSON([
                        'success' => true,
                        'message' => 'Playlists retrieved successfully',
                        'data' => ['playlists' => $playlists],
                        'count' => count($playlists)
                    ]);
                    
                } catch (Exception $e) {
                    // ถ้า query ผิดพลาด อาจจะไม่มีตาราง
                    sendJSON([
                        'success' => false,
                        'message' => 'Database error: ' . $e->getMessage(),
                        'data' => ['playlists' => []],
                        'note' => 'Please run installation or create tables'
                    ], 500);
                }
            } else {
                // ไม่สามารถเชื่อมต่อ database - ส่ง demo data
                $demoPlaylists = [
                    [
                        'id' => 1,
                        'name' => 'Demo Playlist 1',
                        'description' => 'Demo playlist - database not connected',
                        'is_active' => 1,
                        'item_count' => 0,
                        'total_duration' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
                
                sendJSON([
                    'success' => true,
                    'message' => 'Demo playlists (database not connected)',
                    'data' => ['playlists' => $demoPlaylists],
                    'count' => count($demoPlaylists),
                    'note' => 'Please check database connection'
                ]);
            }
            break;
            
        case 'POST':
            // สร้าง playlist ใหม่
            if (empty($input['name'])) {
                sendJSON([
                    'success' => false,
                    'message' => 'Playlist name is required'
                ], 400);
            }
            
            if ($pdo) {
                try {
                    // เตรียมข้อมูล
                    $data = [
                        'name' => trim($input['name']),
                        'description' => trim($input['description'] ?? ''),
                        'layout_id' => intval($input['layout_id'] ?? 1),
                        'shuffle' => !empty($input['shuffle']),
                        'is_active' => 1,
                        'created_by' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Insert playlist
                    $sql = "INSERT INTO playlists (name, description, layout_id, shuffle, is_active, created_by, created_at, updated_at) 
                            VALUES (:name, :description, :layout_id, :shuffle, :is_active, :created_by, :created_at, :updated_at)";
                    
                    $stmt = $pdo->prepare($sql);
                    $result = $stmt->execute($data);
                    
                    if ($result) {
                        $playlistId = $pdo->lastInsertId();
                        
                        // เพิ่ม playlist items ถ้ามี
                        if (!empty($input['items']) && is_array($input['items'])) {
                            $itemSql = "INSERT INTO playlist_items (playlist_id, content_id, order_index, duration, zone_id, created_at) 
                                       VALUES (?, ?, ?, ?, ?, ?)";
                            $itemStmt = $pdo->prepare($itemSql);
                            
                            foreach ($input['items'] as $index => $item) {
                                $itemStmt->execute([
                                    $playlistId,
                                    intval($item['content_id'] ?? 0),
                                    $index,
                                    intval($item['duration'] ?? 10),
                                    $item['zone_id'] ?? 'main',
                                    date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                        
                        // ดึงข้อมูล playlist ที่สร้างแล้ว
                        $createdPlaylist = $pdo->query("SELECT * FROM playlists WHERE id = $playlistId")->fetch();
                        
                        sendJSON([
                            'success' => true,
                            'message' => 'Playlist created successfully',
                            'data' => [
                                'playlist' => $createdPlaylist,
                                'id' => $playlistId
                            ]
                        ]);
                        
                    } else {
                        sendJSON([
                            'success' => false,
                            'message' => 'Failed to create playlist'
                        ], 500);
                    }
                    
                } catch (Exception $e) {
                    sendJSON([
                        'success' => false,
                        'message' => 'Database error: ' . $e->getMessage()
                    ], 500);
                }
            } else {
                // Demo mode - simulate creation
                $newPlaylist = [
                    'id' => time(),
                    'name' => $input['name'],
                    'description' => $input['description'] ?? '',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                sendJSON([
                    'success' => true,
                    'message' => 'Playlist created (demo mode - database not connected)',
                    'data' => ['playlist' => $newPlaylist],
                    'note' => 'This is demo mode. Please check database connection.'
                ]);
            }
            break;
            
        default:
            sendJSON([
                'success' => false,
                'message' => 'Method not allowed'
            ], 405);
    }
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}
?>