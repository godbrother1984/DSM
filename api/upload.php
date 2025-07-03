<?php
/*
File Upload Handler for DSM
URL: http://localhost/DSM/api/upload.php
*/

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    echo "{}";
    exit;
}

try {
    if (!isset($_FILES["file"])) {
        throw new Exception("No file uploaded");
    }

    $file = $_FILES["file"];
    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload error: " . $file["error"]);
    }

    // Create directories
    $uploadDir = "../uploads/content/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate filename
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = uniqid() . "_" . time() . "." . $extension;
    $filepath = $uploadDir . $filename;

    // Move file
    if (!move_uploaded_file($file["tmp_name"], $filepath)) {
        throw new Exception("Failed to move uploaded file");
    }

    // Create content entry
    $contentData = [
        "id" => rand(1000, 9999),
        "title" => $_POST["title"] ?? pathinfo($file["name"], PATHINFO_FILENAME),
        "type" => getFileType($file["type"]),
        "file_url" => "/DSM/uploads/content/" . $filename,
        "file_size" => $file["size"],
        "mime_type" => $file["type"],
        "status" => "active",
        "created_at" => date("Y-m-d H:i:s")
    ];

    // Save to content list
    $contentFile = "../uploads/content_list.json";
    $contentList = [];
    if (file_exists($contentFile)) {
        $contentList = json_decode(file_get_contents($contentFile), true) ?? [];
    }
    $contentList[] = $contentData;
    file_put_contents($contentFile, json_encode($contentList, JSON_PRETTY_PRINT));

    echo json_encode([
        "success" => true,
        "message" => "File uploaded successfully",
        "data" => $contentData
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

function getFileType($mimeType) {
    if (strpos($mimeType, "image/") === 0) return "image";
    if (strpos($mimeType, "video/") === 0) return "video";
    if (strpos($mimeType, "audio/") === 0) return "audio";
    return "other";
}
?>