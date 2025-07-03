<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - CONTENT MANAGER
=============================================================================
*/

require_once 'Database.php';
require_once 'FileUpload.php';
require_once 'Helpers.php';

class ContentManager {
    private $db;
    private $fileUpload;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->fileUpload = new FileUpload();
    }
    
    /**
     * Get all content with pagination and filters
     */
    public function getContent($filters = [], $page = 1, $limit = 20) {
        $where = ['deleted_at IS NULL'];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = 'type = ?';
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(title LIKE ? OR description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['created_by'])) {
            $where[] = 'created_by = ?';
            $params[] = $filters['created_by'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT c.*, u.name as creator_name 
                FROM content c 
                LEFT JOIN users u ON c.created_by = u.id 
                WHERE {$whereClause} 
                ORDER BY c.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $limit);
    }
    
    /**
     * Get single content by ID
     */
    public function getContentById($id) {
        return $this->db->fetchOne(
            "SELECT c.*, u.name as creator_name 
             FROM content c 
             LEFT JOIN users u ON c.created_by = u.id 
             WHERE c.id = ? AND c.deleted_at IS NULL",
            [$id]
        );
    }
    
    /**
     * Create new content
     */
    public function createContent($data, $file = null) {
        // Validate required fields
        $errors = Helpers::validate($data, [
            'title' => 'required|max:255',
            'type' => 'required|in:video,image,audio,html,widget,dashboard,text'
        ]);
        
        if (!empty($errors)) {
            throw new Exception('Validation failed: ' . json_encode($errors));
        }
        
        $contentData = [
            'title' => Helpers::sanitize($data['title']),
            'description' => Helpers::sanitize($data['description'] ?? ''),
            'type' => $data['type'],
            'status' => $data['status'] ?? 'active',
            'created_by' => $data['created_by'] ?? null
        ];
        
        // Handle file upload
        if ($file && isset($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadResult = $this->fileUpload->upload($file, 'content');
                
                $contentData['file_path'] = $uploadResult['path'];
                $contentData['file_url'] = $uploadResult['url'];
                $contentData['thumbnail_path'] = $uploadResult['thumbnail'];
                $contentData['file_size'] = $uploadResult['size'];
                $contentData['mime_type'] = $uploadResult['mime_type'];
                $contentData['width'] = $uploadResult['dimensions']['width'] ?? null;
                $contentData['height'] = $uploadResult['dimensions']['height'] ?? null;
                $contentData['duration'] = $uploadResult['duration'] ?? null;
                
            } catch (Exception $e) {
                throw new Exception('File upload failed: ' . $e->getMessage());
            }
        } elseif ($data['type'] === 'html' && !empty($data['html_content'])) {
            // Handle HTML content
            $contentData['file_url'] = $data['html_content'];
        } elseif ($data['type'] === 'text' && !empty($data['text_content'])) {
            // Handle text content
            $contentData['file_url'] = $data['text_content'];
        } elseif ($data['type'] === 'widget' && !empty($data['widget_config'])) {
            // Handle widget configuration
            $contentData['metadata'] = json_encode($data['widget_config']);
        }
        
        // Handle tags
        if (!empty($data['tags'])) {
            $tags = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
            $contentData['tags'] = json_encode(array_map('trim', $tags));
        }
        
        // Handle expiration
        if (!empty($data['expires_at'])) {
            $contentData['expires_at'] = $data['expires_at'];
        }
        
        $contentId = $this->db->insert('content', $contentData);
        
        Helpers::logActivity("Content created: {$contentData['title']}", 'info', ['content_id' => $contentId]);
        
        return $this->getContentById($contentId);
    }
    
    /**
     * Update content
     */
    public function updateContent($id, $data, $file = null) {
        $existingContent = $this->getContentById($id);
        if (!$existingContent) {
            throw new Exception('Content not found');
        }
        
        // Validate data
        $errors = Helpers::validate($data, [
            'title' => 'max:255',
            'type' => 'in:video,image,audio,html,widget,dashboard,text'
        ]);
        
        if (!empty($errors)) {
            throw new Exception('Validation failed: ' . json_encode($errors));
        }
        
        $updateData = [];
        
        if (isset($data['title'])) {
            $updateData['title'] = Helpers::sanitize($data['title']);
        }
        
        if (isset($data['description'])) {
            $updateData['description'] = Helpers::sanitize($data['description']);
        }
        
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        
        // Handle new file upload
        if ($file && isset($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK) {
            try {
                // Delete old file
                if ($existingContent['file_path']) {
                    $this->fileUpload->deleteFile($existingContent['file_path']);
                }
                
                $uploadResult = $this->fileUpload->upload($file, 'content');
                
                $updateData['file_path'] = $uploadResult['path'];
                $updateData['file_url'] = $uploadResult['url'];
                $updateData['thumbnail_path'] = $uploadResult['thumbnail'];
                $updateData['file_size'] = $uploadResult['size'];
                $updateData['mime_type'] = $uploadResult['mime_type'];
                $updateData['width'] = $uploadResult['dimensions']['width'] ?? null;
                $updateData['height'] = $uploadResult['dimensions']['height'] ?? null;
                $updateData['duration'] = $uploadResult['duration'] ?? null;
                
            } catch (Exception $e) {
                throw new Exception('File upload failed: ' . $e->getMessage());
            }
        }
        
        // Handle content updates for different types
        if (isset($data['html_content']) && $existingContent['type'] === 'html') {
            $updateData['file_url'] = $data['html_content'];
        }
        
        if (isset($data['text_content']) && $existingContent['type'] === 'text') {
            $updateData['file_url'] = $data['text_content'];
        }
        
        if (isset($data['widget_config']) && $existingContent['type'] === 'widget') {
            $updateData['metadata'] = json_encode($data['widget_config']);
        }
        
        // Handle tags
        if (isset($data['tags'])) {
            $tags = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
            $updateData['tags'] = json_encode(array_map('trim', $tags));
        }
        
        // Handle expiration
        if (isset($data['expires_at'])) {
            $updateData['expires_at'] = $data['expires_at'];
        }
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('content', $updateData, 'id = ?', [$id]);
            
            Helpers::logActivity("Content updated: {$updateData['title']}", 'info', ['content_id' => $id]);
        }
        
        return $this->getContentById($id);
    }
    
    /**
     * Delete content (soft delete)
     */
    public function deleteContent($id) {
        $content = $this->getContentById($id);
        if (!$content) {
            throw new Exception('Content not found');
        }
        
        // Soft delete
        $result = $this->db->update('content', 
            ['deleted_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$id]
        );
        
        if ($result) {
            Helpers::logActivity("Content deleted: {$content['title']}", 'info', ['content_id' => $id]);
        }
        
        return $result;
    }
    
    /**
     * Permanently delete content and files
     */
    public function permanentlyDeleteContent($id) {
        $content = $this->db->fetchOne(
            "SELECT * FROM content WHERE id = ?",
            [$id]
        );
        
        if (!$content) {
            throw new Exception('Content not found');
        }
        
        // Delete physical files
        if ($content['file_path']) {
            $this->fileUpload->deleteFile($content['file_path']);
        }
        
        // Delete from database
        $result = $this->db->delete('content', 'id = ?', [$id]);
        
        if ($result) {
            Helpers::logActivity("Content permanently deleted: {$content['title']}", 'warning', ['content_id' => $id]);
        }
        
        return $result;
    }
    
    /**
     * Get content by type
     */
    public function getContentByType($type, $limit = null) {
        $sql = "SELECT * FROM content WHERE type = ? AND status = 'active' AND deleted_at IS NULL ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql, [$type]);
    }
    
    /**
     * Search content
     */
    public function searchContent($query, $filters = []) {
        $where = ['deleted_at IS NULL'];
        $params = [];
        
        // Search in title and description
        $where[] = '(title LIKE ? OR description LIKE ?)';
        $params[] = '%' . $query . '%';
        $params[] = '%' . $query . '%';
        
        // Apply filters
        if (!empty($filters['type'])) {
            $where[] = 'type = ?';
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        return $this->db->fetchAll(
            "SELECT * FROM content WHERE {$whereClause} ORDER BY created_at DESC",
            $params
        );
    }
    
    /**
     * Get content statistics
     */
    public function getContentStats() {
        $stats = [];
        
        // Total content count
        $stats['total'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM content WHERE deleted_at IS NULL"
        )['count'];
        
        // Content by type
        $typeStats = $this->db->fetchAll(
            "SELECT type, COUNT(*) as count FROM content WHERE deleted_at IS NULL GROUP BY type"
        );
        
        $stats['by_type'] = [];
        foreach ($typeStats as $stat) {
            $stats['by_type'][$stat['type']] = $stat['count'];
        }
        
        // Content by status
        $statusStats = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM content WHERE deleted_at IS NULL GROUP BY status"
        );
        
        $stats['by_status'] = [];
        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = $stat['count'];
        }
        
        // Total file size
        $sizeResult = $this->db->fetchOne(
            "SELECT SUM(file_size) as total_size FROM content WHERE deleted_at IS NULL AND file_size IS NOT NULL"
        );
        
        $stats['total_size'] = $sizeResult['total_size'] ?? 0;
        $stats['total_size_formatted'] = Helpers::formatFileSize($stats['total_size']);
        
        return $stats;
    }
    
    /**
     * Clean up expired content
     */
    public function cleanupExpiredContent() {
        $expiredContent = $this->db->fetchAll(
            "SELECT * FROM content WHERE expires_at < NOW() AND deleted_at IS NULL"
        );
        
        $deletedCount = 0;
        foreach ($expiredContent as $content) {
            $this->deleteContent($content['id']);
            $deletedCount++;
        }
        
        Helpers::logActivity("Cleaned up {$deletedCount} expired content items", 'info');
        
        return $deletedCount;
    }
    
    /**
     * Duplicate content
     */
    public function duplicateContent($id) {
        $content = $this->getContentById($id);
        if (!$content) {
            throw new Exception('Content not found');
        }
        
        $newContentData = [
            'title' => $content['title'] . ' (Copy)',
            'description' => $content['description'],
            'type' => $content['type'],
            'file_url' => $content['file_url'],
            'thumbnail_path' => $content['thumbnail_path'],
            'mime_type' => $content['mime_type'],
            'duration' => $content['duration'],
            'width' => $content['width'],
            'height' => $content['height'],
            'metadata' => $content['metadata'],
            'tags' => $content['tags'],
            'status' => 'inactive', // Set as inactive by default
            'created_by' => $content['created_by']
        ];
        
        // Copy file if exists
        if ($content['file_path'] && file_exists($content['file_path'])) {
            $pathInfo = pathinfo($content['file_path']);
            $newFileName = $pathInfo['filename'] . '_copy_' . time() . '.' . $pathInfo['extension'];
            $newFilePath = $pathInfo['dirname'] . '/' . $newFileName;
            
            if (copy($content['file_path'], $newFilePath)) {
                $newContentData['file_path'] = $newFilePath;
            }
        }
        
        $newContentId = $this->db->insert('content', $newContentData);
        
        Helpers::logActivity("Content duplicated: {$content['title']}", 'info', ['original_id' => $id, 'new_id' => $newContentId]);
        
        return $this->getContentById($newContentId);
    }
    
    /**
     * Get content usage analytics
     */
    public function getContentAnalytics($contentId, $days = 30) {
        $analytics = [];
        
        // Play count
        $playCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM content_analytics 
             WHERE content_id = ? AND event_type = 'start' 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$contentId, $days]
        );
        
        $analytics['play_count'] = $playCount['count'];
        
        // Average watch duration
        $avgDuration = $this->db->fetchOne(
            "SELECT AVG(duration_watched) as avg_duration FROM content_analytics 
             WHERE content_id = ? AND event_type = 'end' 
             AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$contentId, $days]
        );
        
        $analytics['avg_duration'] = round($avgDuration['avg_duration'] ?? 0, 2);
        
        // Device breakdown
        $deviceStats = $this->db->fetchAll(
            "SELECT d.device_type, COUNT(*) as count 
             FROM content_analytics ca 
             JOIN devices d ON ca.device_id = d.id 
             WHERE ca.content_id = ? AND ca.event_type = 'start'
             AND ca.timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY d.device_type",
            [$contentId, $days]
        );
        
        $analytics['by_device'] = [];
        foreach ($deviceStats as $stat) {
            $analytics['by_device'][$stat['device_type']] = $stat['count'];
        }
        
        return $analytics;
    }
}
?>