<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - API RESPONSE CLASS
=============================================================================
*/

class ApiResponse {
    
    /**
     * Send successful response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
            'status_code' => $statusCode
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'Error', $statusCode = 400, $details = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'status_code' => $statusCode
        ];
        
        if ($details) {
            $response['details'] = $details;
        }
        
        error_log("API Error [{$statusCode}]: {$message}");
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send unauthorized response (401)
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response (403)
     */
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send not found response (404)
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }
    
    /**
     * Send validation error response (422)
     */
    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, ['validation_errors' => $errors]);
    }
    
    /**
     * Send server error response (500)
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }
    
    /**
     * Send created response (201)
     */
    public static function created($data = null, $message = 'Resource created successfully') {
        self::success($data, $message, 201);
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $pagination, $message = 'Success') {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination,
            'timestamp' => date('c'),
            'status_code' => 200
        ];
        
        http_response_code(200);
        header('Content-Type: application/json');
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send file download response
     */
    public static function download($filePath, $fileName = null, $mimeType = null) {
        if (!file_exists($filePath)) {
            self::notFound('File not found');
        }
        
        if (!$fileName) {
            $fileName = basename($filePath);
        }
        
        if (!$mimeType) {
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        }
        
        $fileSize = filesize($filePath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($filePath);
        exit;
    }
    
    /**
     * Send streaming response for large files
     */
    public static function stream($filePath, $mimeType = null) {
        if (!file_exists($filePath)) {
            self::notFound('File not found');
        }
        
        if (!$mimeType) {
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        }
        
        $fileSize = filesize($filePath);
        
        // Handle range requests for video streaming
        $start = 0;
        $end = $fileSize - 1;
        
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
        }
        
        $length = $end - $start + 1;
        
        http_response_code(206); // Partial Content
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $length);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        header('Accept-Ranges: bytes');
        
        $file = fopen($filePath, 'rb');
        fseek($file, $start);
        
        $buffer = 8192;
        $bytesRemaining = $length;
        
        while ($bytesRemaining > 0 && !feof($file)) {
            $bytesToRead = min($buffer, $bytesRemaining);
            echo fread($file, $bytesToRead);
            $bytesRemaining -= $bytesToRead;
            
            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }
        
        fclose($file);
        exit;
    }
    
    /**
     * Send response with CORS headers
     */
    public static function cors($data = null, $message = 'Success', $statusCode = 200) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Device-ID');
        header('Access-Control-Max-Age: 86400');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        self::success($data, $message, $statusCode);
    }
}

// Custom exception classes
class UnauthorizedException extends Exception {}
class ForbiddenException extends Exception {}
class NotFoundException extends Exception {}
?>