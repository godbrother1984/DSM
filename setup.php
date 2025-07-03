<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - QUICK SETUP SCRIPT
=============================================================================
File: setup.php
Description: One-click setup for development environment
Usage: Run this file once to create all missing files and directories
=============================================================================
*/

echo "<h1>Digital Signage Quick Setup</h1>";
echo "<pre>";

$success = true;
$errors = [];

// Create directory structure
$directories = [
    'config',
    'includes',
    'api',
    'admin',
    'player',
    'uploads',
    'uploads/content',
    'uploads/thumbnails',
    'uploads/temp',
    'logs',
    'cache',
    'sql'
];

echo "Creating directory structure...\n";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created: $dir\n";
        } else {
            echo "❌ Failed to create: $dir\n";
            $errors[] = "Failed to create directory: $dir";
            $success = false;
        }
    } else {
        echo "✅ Exists: $dir\n";
    }
}

// Create .htaccess files for security
echo "\nCreating security files...\n";

$htaccessFiles = [
    'uploads/.htaccess' => "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>",
    'config/.htaccess' => "Deny from all",
    'logs/.htaccess' => "Deny from all",
    'includes/.htaccess' => "Deny from all"
];

foreach ($htaccessFiles as $file => $content) {
    if (file_put_contents($file, $content)) {
        echo "✅ Created: $file\n";
    } else {
        echo "❌ Failed to create: $file\n";
        $errors[] = "Failed to create: $file";
    }
}

// Create basic config files if they don't exist
echo "\nCreating configuration files...\n";

if (!file_exists('config/config.php')) {
    $configContent = "<?php
return [
    'app_name' => 'Digital Signage System',
    'timezone' => 'Asia/Bangkok',
    'max_upload_size' => 104857600,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mp3', 'wav', 'html'],
    'upload_path' => 'uploads/',
    'jwt_secret' => '" . bin2hex(random_bytes(32)) . "',
    'session_timeout' => 3600,
    'debug' => true,
    'log_level' => 'info',
    'enable_analytics' => true,
    'heartbeat_interval' => 30,
    'default_content_duration' => 10,
];
?>";
    
    if (file_put_contents('config/config.php', $configContent)) {
        echo "✅ Created: config/config.php\n";
    } else {
        echo "❌ Failed to create: config/config.php\n";
        $errors[] = "Failed to create config/config.php";
    }
} else {
    echo "✅ Exists: config/config.php\n";
}

// Create a simple API response class if missing
if (!file_exists('includes/ApiResponse.php')) {
    $apiResponseContent = '<?php
class ApiResponse {
    public static function success($data = null, $message = "Success", $statusCode = 200) {
        http_response_code($statusCode);
        header("Content-Type: application/json");
        echo json_encode([
            "success" => true,
            "message" => $message,
            "data" => $data,
            "timestamp" => date("c"),
            "status_code" => $statusCode
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function error($message = "Error", $statusCode = 400, $details = null) {
        http_response_code($statusCode);
        header("Content-Type: application/json");
        $response = [
            "success" => false,
            "message" => $message,
            "timestamp" => date("c"),
            "status_code" => $statusCode
        ];
        if ($details) $response["details"] = $details;
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function unauthorized($message = "Unauthorized") { self::error($message, 401); }
    public static function forbidden($message = "Forbidden") { self::error($message, 403); }
    public static function notFound($message = "Not found") { self::error($message, 404); }
    public static function methodNotAllowed($message = "Method not allowed") { self::error($message, 405); }
    public static function validationError($errors, $message = "Validation failed") { self::error($message, 422, ["validation_errors" => $errors]); }
    public static function serverError($message = "Internal server error") { self::error($message, 500); }
    public static function created($data = null, $message = "Created") { self::success($data, $message, 201); }
    
    public static function paginated($data, $pagination, $message = "Success") {
        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode([
            "success" => true,
            "message" => $message,
            "data" => $data,
            "pagination" => $pagination,
            "timestamp" => date("c"),
            "status_code" => 200
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>';
    
    if (file_put_contents('includes/ApiResponse.php', $apiResponseContent)) {
        echo "✅ Created: includes/ApiResponse.php\n";
    } else {
        echo "❌ Failed to create: includes/ApiResponse.php\n";
        $errors[] = "Failed to create includes/ApiResponse.php";
    }
} else {
    echo "✅ Exists: includes/ApiResponse.php\n";
}

// Create basic helpers if missing
if (!file_exists('includes/Helpers.php')) {
    $helpersContent = '<?php
class Helpers {
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, "sanitize"], $input);
        }
        return is_string($input) ? htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, "UTF-8") : $input;
    }
    
    public static function validate($data, $rules) {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleArray = explode("|", $rule);
            foreach ($ruleArray as $singleRule) {
                $ruleParts = explode(":", $singleRule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;
                $error = self::validateSingleRule($field, $value, $ruleName, $ruleValue);
                if ($error) $errors[$field][] = $error;
            }
        }
        return $errors;
    }
    
    private static function validateSingleRule($field, $value, $ruleName, $ruleValue = null) {
        $fieldName = ucfirst(str_replace("_", " ", $field));
        switch ($ruleName) {
            case "required":
                if (empty($value) && $value !== "0") return "{$fieldName} is required";
                break;
            case "email":
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) return "{$fieldName} must be a valid email";
                break;
            case "min":
                if (!empty($value) && strlen($value) < $ruleValue) return "{$fieldName} must be at least {$ruleValue} characters";
                break;
            case "max":
                if (!empty($value) && strlen($value) > $ruleValue) return "{$fieldName} must not exceed {$ruleValue} characters";
                break;
            case "in":
                $allowedValues = explode(",", $ruleValue);
                if (!empty($value) && !in_array($value, $allowedValues)) return "{$fieldName} must be one of: " . implode(", ", $allowedValues);
                break;
        }
        return null;
    }
    
    public static function logActivity($message, $level = "info", $context = []) {
        $logFile = __DIR__ . "/../logs/app.log";
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        $timestamp = date("Y-m-d H:i:s");
        $contextStr = !empty($context) ? json_encode($context) : "";
        $logEntry = "[{$timestamp}] {$level}: {$message} {$contextStr}" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function getClientIP() {
        $ipKeys = ["HTTP_CF_CONNECTING_IP", "HTTP_CLIENT_IP", "HTTP_X_FORWARDED_FOR", "REMOTE_ADDR"];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(",", $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER["REMOTE_ADDR"] ?? "unknown";
    }
    
    public static function formatFileSize($bytes, $precision = 2) {
        $units = ["B", "KB", "MB", "GB"];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) $bytes /= 1024;
        return round($bytes, $precision) . " " . $units[$i];
    }
    
    public static function generateUuid() {
        return sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}
?>';
    
    if (file_put_contents('includes/Helpers.php', $helpersContent)) {
        echo "✅ Created: includes/Helpers.php\n";
    } else {
        echo "❌ Failed to create: includes/Helpers.php\n";
        $errors[] = "Failed to create includes/Helpers.php";
    }
} else {
    echo "✅ Exists: includes/Helpers.php\n";
}

// Create basic Auth class if missing
if (!file_exists('includes/Auth.php')) {
    $authContent = '<?php
class Auth {
    private $db;
    
    public function __construct() {
        if (class_exists("Database")) {
            try {
                $this->db = Database::getInstance();
            } catch (Exception $e) {
                $this->db = null;
            }
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($email, $password) {
        if (!$this->db) return false;
        try {
            $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
            if ($user && password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];
                $_SESSION["login_time"] = time();
                return $user;
            }
        } catch (Exception $e) {
            error_log("Auth error: " . $e->getMessage());
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION["user_id"]) && 
               isset($_SESSION["login_time"]) && 
               (time() - $_SESSION["login_time"]) < 3600;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn() || !$this->db) return null;
        try {
            return $this->db->fetchOne("SELECT id, name, email, role FROM users WHERE id = ?", [$_SESSION["user_id"]]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        return $user && $user["role"] === "admin";
    }
    
    public function validateApiToken($token) {
        if (!$this->db) return false;
        try {
            return $this->db->fetchOne("SELECT u.* FROM api_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ? AND u.is_active = 1", [$token]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function generateApiToken($userId, $name = "API Token", $expiresInDays = 30) {
        $token = bin2hex(random_bytes(32));
        if ($this->db) {
            try {
                $this->db->insert("api_tokens", [
                    "user_id" => $userId,
                    "token" => $token,
                    "name" => $name,
                    "expires_at" => $expiresInDays ? date("Y-m-d H:i:s", time() + ($expiresInDays * 24 * 3600)) : null
                ]);
            } catch (Exception $e) {
                error_log("Token generation error: " . $e->getMessage());
            }
        }
        return $token;
    }
}
?>';
    
    if (file_put_contents('includes/Auth.php', $authContent)) {
        echo "✅ Created: includes/Auth.php\n";
    } else {
        echo "❌ Failed to create: includes/Auth.php\n";
        $errors[] = "Failed to create includes/Auth.php";
    }
} else {
    echo "✅ Exists: includes/Auth.php\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($success && empty($errors)) {
    echo "🎉 SETUP COMPLETE!\n\n";
    echo "Next steps:\n";
    echo "1. Go to: http://localhost/digital-signage/\n";
    echo "2. Run the installer if not already done\n";
    echo "3. Access Admin Panel: http://localhost/digital-signage/admin/\n";
    echo "4. Open Player: http://localhost/digital-signage/player/\n";
    echo "\nDelete this setup.php file after setup is complete.\n";
} else {
    echo "❌ SETUP COMPLETED WITH ERRORS:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    echo "\nPlease fix these errors and run setup again.\n";
}

echo str_repeat("=", 50) . "\n";
echo "</pre>";

// Add some styling and links
echo "<style>
body { font-family: monospace; background: #f5f5f5; padding: 20px; }
h1 { color: #333; text-align: center; margin-bottom: 30px; }
pre { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { color: #28a745; }
.error { color: #dc3545; }
.warning { color: #ffc107; }
.info { color: #17a2b8; }
.links { margin-top: 30px; text-align: center; }
.links a { 
    display: inline-block; 
    margin: 10px; 
    padding: 10px 20px; 
    background: #007bff; 
    color: white; 
    text-decoration: none; 
    border-radius: 5px; 
}
.links a:hover { background: #0056b3; }
</style>";

if ($success && empty($errors)) {
    echo "<div class='links'>
        <a href='./'>🏠 Home</a>
        <a href='install.php'>⚙️ Installer</a>
        <a href='admin/'>🔧 Admin Panel</a>
        <a href='player/'>📺 Player</a>
        <a href='api/'>🔌 API</a>
    </div>";
}

echo "<script>
// Auto refresh stats
setTimeout(() => {
    if (window.location.href.includes('setup.php')) {
        const link = document.createElement('a');
        link.href = './';
        link.textContent = 'Continue to Main Page';
        link.style.cssText = 'display:block;text-align:center;margin-top:20px;padding:15px;background:#28a745;color:white;text-decoration:none;border-radius:8px;font-size:18px;';
        document.body.appendChild(link);
    }
}, 2000);
</script>";
?>