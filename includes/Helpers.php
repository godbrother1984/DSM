<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - HELPERS CLASS
=============================================================================
*/

class Helpers {
    
    /**
     * Sanitize input data recursively
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        if (is_string($input)) {
            return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
        
        return $input;
    }
    
    /**
     * Validate data against rules
     */
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $ruleArray = explode('|', $rule);
            
            foreach ($ruleArray as $singleRule) {
                $ruleParts = explode(':', $singleRule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;
                
                $error = self::validateSingleRule($field, $value, $ruleName, $ruleValue);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate single rule
     */
    private static function validateSingleRule($field, $value, $ruleName, $ruleValue = null) {
        $fieldName = ucfirst(str_replace('_', ' ', $field));
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return "{$fieldName} is required";
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$fieldName} must be a valid email address";
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $ruleValue) {
                    return "{$fieldName} must be at least {$ruleValue} characters";
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $ruleValue) {
                    return "{$fieldName} must not exceed {$ruleValue} characters";
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return "{$fieldName} must be numeric";
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$fieldName} must be a valid URL";
                }
                break;
                
            case 'in':
                $allowedValues = explode(',', $ruleValue);
                if (!empty($value) && !in_array($value, $allowedValues)) {
                    return "{$fieldName} must be one of: " . implode(', ', $allowedValues);
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Format file size to human readable format
     */
    public static function formatFileSize($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Format duration in seconds to human readable format
     */
    public static function formatDuration($seconds) {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm' . ($remainingSeconds > 0 ? ' ' . $remainingSeconds . 's' : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
        }
    }
    
    /**
     * Generate random string
     */
    public static function generateRandomString($length = 32, $characters = null) {
        if ($characters === null) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
    
    /**
     * Generate UUID v4
     */
    public static function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Log activity with different levels
     */
    public static function logActivity($message, $level = 'info', $context = []) {
        $logFile = __DIR__ . '/../logs/app.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message} {$contextStr}" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        if ($level === 'error') {
            error_log($message);
        }
    }
    
    /**
     * Set CORS headers
     */
    public static function corsHeaders($allowedOrigins = ['*'], $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        if ($allowedOrigins[0] === '*' || in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Device-ID, X-API-Key');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Detect device type from user agent
     */
    public static function detectDeviceType($userAgent = null) {
        if (!$userAgent) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        $userAgent = strtolower($userAgent);
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }
        
        if (preg_match('/(mobile|iphone|ipod|blackberry|android.*mobile|windows.*phone)/i', $userAgent)) {
            return 'mobile';
        }
        
        if (preg_match('/(smart.*tv|tv|googletv|appletv|hbbtv|roku)/i', $userAgent)) {
            return 'smart_tv';
        }
        
        return 'desktop';
    }
    
    /**
     * Generate slug from string
     */
    public static function slug($string, $separator = '-') {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\s]/', '', $string);
        $string = preg_replace('/\s+/', $separator, trim($string));
        $string = preg_replace('/' . preg_quote($separator) . '+/', $separator, $string);
        
        return trim($string, $separator);
    }
    
    /**
     * Truncate string with ellipsis
     */
    public static function truncate($string, $length = 100, $ellipsis = '...') {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - strlen($ellipsis)) . $ellipsis;
    }
    
    /**
     * Time ago format
     */
    public static function timeAgo($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        
        $string = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $maxRequests = 100, $timeWindow = 3600) {
        $cacheFile = __DIR__ . '/../cache/rate_limit_' . md5($key) . '.json';
        $now = time();
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            });
        } else {
            $data = ['requests' => []];
        }
        
        if (count($data['requests']) >= $maxRequests) {
            return false;
        }
        
        $data['requests'][] = $now;
        
        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }
        file_put_contents($cacheFile, json_encode($data));
        
        return true;
    }
    
    /**
     * Encrypt string
     */
    public static function encrypt($data, $key = null) {
        if (!$key) {
            $config = include __DIR__ . '/../config/config.php';
            $key = $config['jwt_secret'] ?? 'default_key';
        }
        
        $cipher = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt string
     */
    public static function decrypt($data, $key = null) {
        if (!$key) {
            $config = include __DIR__ . '/../config/config.php';
            $key = $config['jwt_secret'] ?? 'default_key';
        }
        
        $data = base64_decode($data);
        $cipher = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($cipher);
        
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }
}
?>