<?php
/*
=============================================================================
API RESPONSE - แก้ไข Missing File
=============================================================================
*/

class ApiResponse {
    public static function success($data = null, $message = "Success") {
        self::sendResponse([
            "success" => true,
            "message" => $message,
            "data" => $data
        ], 200);
    }
    
    public static function error($message = "Error", $code = 400) {
        self::sendResponse([
            "success" => false,
            "message" => $message,
            "error_code" => $code
        ], $code);
    }
    
    public static function notFound($message = "Not found") {
        self::sendResponse([
            "success" => false,
            "message" => $message,
            "error_code" => 404
        ], 404);
    }
    
    public static function serverError($message = "Internal server error") {
        self::sendResponse([
            "success" => false,
            "message" => $message,
            "error_code" => 500
        ], 500);
    }
    
    public static function created($data = null, $message = "Created successfully") {
        self::sendResponse([
            "success" => true,
            "message" => $message,
            "data" => $data
        ], 201);
    }
    
    public static function validationError($errors) {
        self::sendResponse([
            "success" => false,
            "message" => "Validation failed",
            "errors" => $errors,
            "error_code" => 422
        ], 422);
    }
    
    public static function paginated($data, $pagination) {
        self::sendResponse([
            "success" => true,
            "message" => "Data retrieved successfully",
            "data" => $data,
            "pagination" => $pagination
        ], 200);
    }
    
    private static function sendResponse($data, $statusCode) {
        // Clear any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code($statusCode);
        header("Content-Type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        if ($json === false) {
            echo json_encode([
                "success" => false,
                "message" => "JSON encoding error",
                "error_code" => 500
            ]);
        } else {
            echo $json;
        }
        
        exit;
    }
}
?>