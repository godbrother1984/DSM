<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - API RESPONSE HELPER
=============================================================================
File: includes/ApiResponse.php
Description: Standardized API response handler for all endpoints
Author: Digital Signage Team
Version: 1.0.0
Usage: ApiResponse::success($data, $message) or ApiResponse::error($message, $code)
=============================================================================
*/

class ApiResponse {
    
    /**
     * Send a successful API response
     * 
     * @param mixed $data The data to return
     * @param string $message Success message
     * @param int $code HTTP status code (default: 200)
     * @param array $meta Additional metadata
     */
    public static function success($data = null, $message = "Success", $code = 200, $meta = []) {
        // Clean any existing output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set HTTP status code
        http_response_code($code);
        
        // Prepare response structure
        $response = [
            "success" => true,
            "message" => $message,
            "data" => $data,
            "meta" => array_merge([
                "timestamp" => date("c"),
                "timezone" => date_default_timezone_get(),
                "execution_time" => self::getExecutionTime(),
                "memory_usage" => self::getMemoryUsage()
            ], $meta),
            "api_version" => "1.0.0"
        ];
        
        // Send JSON response
        self::sendJsonResponse($response);
    }
    
    /**
     * Send an error API response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param mixed $data Additional error data
     * @param array $meta Additional metadata
     */
    public static function error($message = "Error", $code = 400, $data = null, $meta = []) {
        // Clean any existing output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set HTTP status code
        http_response_code($code);
        
        // Prepare response structure
        $response = [
            "success" => false,
            "message" => $message,
            "error" => [
                "code" => $code,
                "message" => $message,
                "data" => $data
            ],
            "meta" => array_merge([
                "timestamp" => date("c"),
                "timezone" => date_default_timezone_get(),
                "execution_time" => self::getExecutionTime(),
                "memory_usage" => self::getMemoryUsage(),
                "request_method" => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                "request_uri" => $_SERVER['REQUEST_URI'] ?? 'Unknown'
            ], $meta),
            "api_version" => "1.0.0"
        ];
        
        // Log error for debugging
        self::logError($code, $message, $data);
        
        // Send JSON response
        self::sendJsonResponse($response);
    }
    
    /**
     * Send a 404 Not Found response
     * 
     * @param string $message Custom not found message
     * @param mixed $data Additional data
     */
    public static function notFound($message = "Resource not found", $data = null) {
        self::error($message, 404, $data, [
            "suggestion" => "Please check the endpoint URL and try again"
        ]);
    }
    
    /**
     * Send a 401 Unauthorized response
     * 
     * @param string $message Custom unauthorized message
     * @param mixed $data Additional data
     */
    public static function unauthorized($message = "Unauthorized access", $data = null) {
        self::error($message, 401, $data, [
            "suggestion" => "Please provide valid authentication credentials"
        ]);
    }
    
    /**
     * Send a 403 Forbidden response
     * 
     * @param string $message Custom forbidden message
     * @param mixed $data Additional data
     */
    public static function forbidden($message = "Access forbidden", $data = null) {
        self::error($message, 403, $data, [
            "suggestion" => "You don't have permission to access this resource"
        ]);
    }
    
    /**
     * Send a 405 Method Not Allowed response
     * 
     * @param array $allowedMethods List of allowed HTTP methods
     * @param string $message Custom message
     */
    public static function methodNotAllowed($allowedMethods = [], $message = "Method not allowed") {
        header('Allow: ' . implode(', ', $allowedMethods));
        self::error($message, 405, null, [
            "allowed_methods" => $allowedMethods,
            "current_method" => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
        ]);
    }
    
    /**
     * Send a 422 Validation Error response
     * 
     * @param array $errors Validation errors
     * @param string $message Custom validation message
     */
    public static function validationError($errors = [], $message = "Validation failed") {
        self::error($message, 422, [
            "validation_errors" => $errors,
            "fields_with_errors" => array_keys($errors)
        ], [
            "suggestion" => "Please correct the validation errors and try again"
        ]);
    }
    
    /**
     * Send a 429 Too Many Requests response
     * 
     * @param int $retryAfter Seconds to wait before retry
     * @param string $message Custom rate limit message
     */
    public static function rateLimitExceeded($retryAfter = 60, $message = "Rate limit exceeded") {
        header("Retry-After: $retryAfter");
        self::error($message, 429, null, [
            "retry_after" => $retryAfter,
            "suggestion" => "Please wait before making another request"
        ]);
    }
    
    /**
     * Send a 500 Internal Server Error response
     * 
     * @param string $message Custom server error message
     * @param mixed $data Additional error data (only in debug mode)
     */
    public static function serverError($message = "Internal server error", $data = null) {
        // Only include error data in development mode
        $errorData = (defined('DEBUG_MODE') && DEBUG_MODE) ? $data : null;
        
        self::error($message, 500, $errorData, [
            "suggestion" => "Please try again later or contact support if the problem persists"
        ]);
    }
    
    /**
     * Send a 503 Service Unavailable response
     * 
     * @param int $retryAfter Seconds to wait before retry
     * @param string $message Custom service unavailable message
     */
    public static function serviceUnavailable($retryAfter = 300, $message = "Service temporarily unavailable") {
        header("Retry-After: $retryAfter");
        self::error($message, 503, null, [
            "retry_after" => $retryAfter,
            "suggestion" => "The service is temporarily down for maintenance"
        ]);
    }
    
    /**
     * Send a paginated response
     * 
     * @param array $data The paginated data
     * @param int $currentPage Current page number
     * @param int $totalPages Total number of pages
     * @param int $totalItems Total number of items
     * @param int $perPage Items per page
     * @param string $message Success message
     */
    public static function paginated($data, $currentPage, $totalPages, $totalItems, $perPage, $message = "Data retrieved successfully") {
        $paginationMeta = [
            "pagination" => [
                "current_page" => (int)$currentPage,
                "total_pages" => (int)$totalPages,
                "total_items" => (int)$totalItems,
                "per_page" => (int)$perPage,
                "has_next" => $currentPage < $totalPages,
                "has_previous" => $currentPage > 1,
                "next_page" => $currentPage < $totalPages ? $currentPage + 1 : null,
                "previous_page" => $currentPage > 1 ? $currentPage - 1 : null
            ]
        ];
        
        self::success($data, $message, 200, $paginationMeta);
    }
    
    /**
     * Send a response for created resource
     * 
     * @param mixed $data The created resource data
     * @param string $message Success message
     * @param string $location Optional location header for the created resource
     */
    public static function created($data = null, $message = "Resource created successfully", $location = null) {
        if ($location) {
            header("Location: $location");
        }
        
        self::success($data, $message, 201, [
            "created_at" => date("c")
        ]);
    }
    
    /**
     * Send a response for updated resource
     * 
     * @param mixed $data The updated resource data
     * @param string $message Success message
     */
    public static function updated($data = null, $message = "Resource updated successfully") {
        self::success($data, $message, 200, [
            "updated_at" => date("c")
        ]);
    }
    
    /**
     * Send a response for deleted resource
     * 
     * @param string $message Success message
     */
    public static function deleted($message = "Resource deleted successfully") {
        self::success(null, $message, 200, [
            "deleted_at" => date("c")
        ]);
    }
    
    /**
     * Send a no content response (204)
     */
    public static function noContent() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        http_response_code(204);
        exit;
    }
    
    /**
     * Send custom response with full control
     * 
     * @param bool $success Whether the request was successful
     * @param string $message Response message
     * @param mixed $data Response data
     * @param int $code HTTP status code
     * @param array $meta Additional metadata
     */
    public static function custom($success, $message, $data = null, $code = 200, $meta = []) {
        if (ob_get_level()) {
            ob_clean();
        }
        
        http_response_code($code);
        
        $response = [
            "success" => (bool)$success,
            "message" => $message,
            "data" => $data,
            "meta" => array_merge([
                "timestamp" => date("c"),
                "execution_time" => self::getExecutionTime(),
                "memory_usage" => self::getMemoryUsage()
            ], $meta),
            "api_version" => "1.0.0"
        ];
        
        self::sendJsonResponse($response);
    }
    
    /**
     * Get the execution time since script start
     * 
     * @return float Execution time in seconds
     */
    private static function getExecutionTime() {
        if (defined('API_START_TIME')) {
            return round(microtime(true) - API_START_TIME, 4);
        }
        return 0;
    }
    
    /**
     * Get current memory usage
     * 
     * @return string Formatted memory usage
     */
    private static function getMemoryUsage() {
        $bytes = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Send JSON response with proper headers
     * 
     * @param array $response Response data
     */
    private static function sendJsonResponse($response) {
        // Set JSON headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Enable CORS if needed
        if (defined('ENABLE_CORS') && ENABLE_CORS) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Device-ID');
        }
        
        // Encode and send response
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Log error for debugging purposes
     * 
     * @param int $code Error code
     * @param string $message Error message
     * @param mixed $data Additional error data
     */
    private static function logError($code, $message, $data = null) {
        // Only log errors in debug mode or for server errors
        if ((defined('DEBUG_MODE') && DEBUG_MODE) || $code >= 500) {
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => 'ERROR',
                'code' => $code,
                'message' => $message,
                'data' => $data,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip_address' => self::getClientIP()
            ];
            
            $logFile = dirname(__DIR__) . '/logs/api_errors.log';
            $logDir = dirname($logFile);
            
            // Create logs directory if it doesn't exist
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            // Write log entry
            file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private static function getClientIP() {
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
    
    /**
     * Validate JSON input and return decoded data
     * 
     * @return array Decoded JSON data
     * @throws Exception If JSON is invalid
     */
    public static function getJsonInput() {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::validationError([
                'json' => ['Invalid JSON format: ' . json_last_error_msg()]
            ], 'Invalid JSON input');
        }
        
        return $data ?? [];
    }
    
    /**
     * Validate required fields in input data
     * 
     * @param array $data Input data
     * @param array $requiredFields List of required field names
     * @param array $optionalFields List of optional fields with default values
     * @return array Validated data
     */
    public static function validateInput($data, $requiredFields = [], $optionalFields = []) {
        $errors = [];
        $validatedData = [];
        
        // Check required fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[$field] = ["The {$field} field is required"];
            } else {
                $validatedData[$field] = $data[$field];
            }
        }
        
        // Add optional fields with defaults
        foreach ($optionalFields as $field => $default) {
            $validatedData[$field] = $data[$field] ?? $default;
        }
        
        // If there are validation errors, send error response
        if (!empty($errors)) {
            self::validationError($errors);
        }
        
        return $validatedData;
    }
    
    /**
     * Handle API exceptions gracefully
     * 
     * @param Exception $exception The exception to handle
     */
    public static function handleException($exception) {
        $message = $exception->getMessage();
        $code = $exception->getCode() ?: 500;
        
        // Determine appropriate HTTP status code
        if ($code < 100 || $code > 599) {
            $code = 500;
        }
        
        // Log the exception
        error_log("API Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        // Send appropriate error response
        if ($code >= 500) {
            self::serverError($message);
        } else {
            self::error($message, $code);
        }
    }
}

// Define API start time for execution time tracking
if (!defined('API_START_TIME')) {
    define('API_START_TIME', microtime(true));
}

// Set up global exception handler for API
set_exception_handler(['ApiResponse', 'handleException']);

?>