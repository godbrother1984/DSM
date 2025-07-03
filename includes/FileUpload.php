<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - FILE UPLOAD CLASS
=============================================================================
*/

class FileUpload {
    private $uploadPath;
    private $allowedExtensions;
    private $maxFileSize;
    private $thumbnailSizes;
    
    public function __construct() {
        $config = include __DIR__ . '/../config/config.php';
        $this->uploadPath = $config['upload_path'];
        $this->allowedExtensions = $config['allowed_extensions'];
        $this->maxFileSize = $config['max_upload_size'];
        $this->thumbnailSizes = [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600]
        ];
    }
    
    /**
     * Upload file with validation and processing
     */
    public function upload($file, $subfolder = 'content') {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $this->getUploadError($file['error']));
        }
        
        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File too large. Maximum size: ' . $this->formatBytes($this->maxFileSize));
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate extension
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $this->allowedExtensions));
        }
        
        // Validate file content (security check)
        if (!$this->validateFileContent($file['tmp_name'], $extension)) {
            throw new Exception('File content validation failed');
        }
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file['name'], $extension);
        $targetDir = $this->uploadPath . $subfolder . '/';
        $targetPath = $targetDir . $filename;
        
        // Create directory if not exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Get file info
        $fileInfo = $this->getFileInfo($targetPath, $extension);
        
        // Generate thumbnail for images
        $thumbnailPath = null;
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $thumbnailPath = $this->generateThumbnail($targetPath, $subfolder);
        }
        
        return [
            'filename' => $filename,
            'original_name' => $file['name'],
            'path' => $targetPath,
            'url' => $this->getFileUrl($targetPath),
            'size' => $file['size'],
            'mime_type' => $file['type'],
            'extension' => $extension,
            'thumbnail' => $thumbnailPath ? $this->getFileUrl($thumbnailPath) : null,
            'dimensions' => $fileInfo['dimensions'] ?? null,
            'duration' => $fileInfo['duration'] ?? null
        ];
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($originalName, $extension) {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '', $baseName);
        $safeName = substr($safeName, 0, 50);
        
        return uniqid() . '_' . time() . '_' . $safeName . '.' . $extension;
    }
    
    /**
     * Validate file content for security
     */
    private function validateFileContent($filePath, $extension) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $imageInfo = getimagesize($filePath);
                return $imageInfo !== false && $imageInfo['mime'] === 'image/jpeg';
                
            case 'png':
                $imageInfo = getimagesize($filePath);
                return $imageInfo !== false && $imageInfo['mime'] === 'image/png';
                
            case 'gif':
                $imageInfo = getimagesize($filePath);
                return $imageInfo !== false && $imageInfo['mime'] === 'image/gif';
                
            case 'mp4':
                $handle = fopen($filePath, 'rb');
                $header = fread($handle, 8);
                fclose($handle);
                return strpos($header, 'ftyp') !== false;
                
            case 'html':
                $content = file_get_contents($filePath);
                return !preg_match('/<script|javascript:|vbscript:|onload|onerror/i', $content);
                
            default:
                return true;
        }
    }
    
    /**
     * Get file information
     */
    private function getFileInfo($filePath, $extension) {
        $info = [];
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                $imageInfo = getimagesize($filePath);
                if ($imageInfo) {
                    $info['dimensions'] = [
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1]
                    ];
                }
                break;
                
            case 'mp4':
            case 'webm':
                if (function_exists('shell_exec')) {
                    $duration = $this->getVideoDuration($filePath);
                    if ($duration) {
                        $info['duration'] = $duration;
                    }
                }
                break;
        }
        
        return $info;
    }
    
    /**
     * Generate thumbnail for images
     */
    private function generateThumbnail($imagePath, $subfolder) {
        $thumbnailDir = $this->uploadPath . 'thumbnails/';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }
        
        $filename = basename($imagePath);
        $thumbnailPath = $thumbnailDir . 'thumb_' . $filename;
        
        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return null;
            }
            
            $srcWidth = $imageInfo[0];
            $srcHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            
            $maxWidth = $this->thumbnailSizes['medium'][0];
            $maxHeight = $this->thumbnailSizes['medium'][1];
            $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
            $thumbWidth = round($srcWidth * $ratio);
            $thumbHeight = round($srcHeight * $ratio);
            
            // Create source image
            switch ($mimeType) {
                case 'image/jpeg':
                    $srcImage = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $srcImage = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $srcImage = imagecreatefromgif($imagePath);
                    break;
                default:
                    return null;
            }
            
            if (!$srcImage) {
                return null;
            }
            
            // Create thumbnail
            $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
            
            // Preserve transparency
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($thumbImage, false);
                imagesavealpha($thumbImage, true);
                $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
                imagefill($thumbImage, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, 
                             $thumbWidth, $thumbHeight, $srcWidth, $srcHeight);
            
            // Save thumbnail
            $success = false;
            switch ($mimeType) {
                case 'image/jpeg':
                    $success = imagejpeg($thumbImage, $thumbnailPath, 85);
                    break;
                case 'image/png':
                    $success = imagepng($thumbImage, $thumbnailPath, 8);
                    break;
                case 'image/gif':
                    $success = imagegif($thumbImage, $thumbnailPath);
                    break;
            }
            
            imagedestroy($srcImage);
            imagedestroy($thumbImage);
            
            return $success ? $thumbnailPath : null;
            
        } catch (Exception $e) {
            error_log('Thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get video duration using ffmpeg
     */
    private function getVideoDuration($filePath) {
        $command = "ffprobe -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($filePath);
        $output = shell_exec($command);
        
        if ($output !== null) {
            return round(floatval(trim($output)));
        }
        
        return null;
    }
    
    /**
     * Get file URL
     */
    private function getFileUrl($path) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        
        $path = ltrim($path, '/');
        
        return $protocol . '://' . $host . $scriptPath . '/' . $path;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Delete file and its thumbnail
     */
    public function deleteFile($path) {
        $deleted = false;
        
        if (file_exists($path)) {
            $deleted = unlink($path);
            
            $filename = basename($path);
            $thumbnailPath = $this->uploadPath . 'thumbnails/thumb_' . $filename;
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }
        
        return $deleted;
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultiple($files, $subfolder = 'content') {
        $uploadedFiles = [];
        $fileCount = count($files['tmp_name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            try {
                $uploadedFiles[] = $this->upload($file, $subfolder);
            } catch (Exception $e) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $this->deleteFile($uploadedFile['path']);
                }
                throw new Exception("Failed to upload file {$i}: " . $e->getMessage());
            }
        }
        
        return $uploadedFiles;
    }
}
?>