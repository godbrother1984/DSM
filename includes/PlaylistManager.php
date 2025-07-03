<?php
/*
=============================================================================
DIGITAL SIGNAGE SYSTEM - PLAYLIST MANAGER
=============================================================================
*/

require_once 'Database.php';
require_once 'Helpers.php';

class PlaylistManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all playlists with pagination and filters
     */
    public function getPlaylists($filters = [], $page = 1, $limit = 20) {
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['is_active'])) {
            $where[] = 'p.is_active = ?';
            $params[] = $filters['is_active'];
        }
        
        if (!empty($filters['layout_id'])) {
            $where[] = 'p.layout_id = ?';
            $params[] = $filters['layout_id'];
        }
        
        if (!empty($filters['created_by'])) {
            $where[] = 'p.created_by = ?';
            $params[] = $filters['created_by'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT p.*, l.name as layout_name, u.name as creator_name,
                       (SELECT COUNT(*) FROM playlist_items pi WHERE pi.playlist_id = p.id) as item_count
                FROM playlists p 
                LEFT JOIN layouts l ON p.layout_id = l.id
                LEFT JOIN users u ON p.created_by = u.id 
                WHERE {$whereClause} 
                ORDER BY p.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $limit);
    }
    
    /**
     * Get single playlist by ID
     */
    public function getPlaylistById($id) {
        $playlist = $this->db->fetchOne(
            "SELECT p.*, l.name as layout_name, u.name as creator_name
             FROM playlists p 
             LEFT JOIN layouts l ON p.layout_id = l.id
             LEFT JOIN users u ON p.created_by = u.id 
             WHERE p.id = ?",
            [$id]
        );
        
        if ($playlist) {
            $playlist['items'] = $this->getPlaylistItems($id);
        }
        
        return $playlist;
    }
    
    /**
     * Create new playlist
     */
    public function createPlaylist($data) {
        // Validate required fields
        $errors = Helpers::validate($data, [
            'name' => 'required|max:255'
        ]);
        
        if (!empty($errors)) {
            throw new Exception('Validation failed: ' . json_encode($errors));
        }
        
        $playlistData = [
            'name' => Helpers::sanitize($data['name']),
            'description' => Helpers::sanitize($data['description'] ?? ''),
            'layout_id' => $data['layout_id'] ?? null,
            'loop_count' => $data['loop_count'] ?? 0,
            'shuffle' => $data['shuffle'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $data['created_by'] ?? null
        ];
        
        // Handle settings
        if (!empty($data['settings'])) {
            $playlistData['settings'] = json_encode($data['settings']);
        }
        
        $playlistId = $this->db->insert('playlists', $playlistData);
        
        Helpers::logActivity("Playlist created: {$playlistData['name']}", 'info', ['playlist_id' => $playlistId]);
        
        return $this->getPlaylistById($playlistId);
    }
    
    /**
     * Update playlist
     */
    public function updatePlaylist($id, $data) {
        $existingPlaylist = $this->getPlaylistById($id);
        if (!$existingPlaylist) {
            throw new Exception('Playlist not found');
        }
        
        // Validate data
        $errors = Helpers::validate($data, [
            'name' => 'max:255'
        ]);
        
        if (!empty($errors)) {
            throw new Exception('Validation failed: ' . json_encode($errors));
        }
        
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = Helpers::sanitize($data['name']);
        }
        
        if (isset($data['description'])) {
            $updateData['description'] = Helpers::sanitize($data['description']);
        }
        
        if (isset($data['layout_id'])) {
            $updateData['layout_id'] = $data['layout_id'];
        }
        
        if (isset($data['loop_count'])) {
            $updateData['loop_count'] = $data['loop_count'];
        }
        
        if (isset($data['shuffle'])) {
            $updateData['shuffle'] = $data['shuffle'];
        }
        
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }
        
        if (isset($data['settings'])) {
            $updateData['settings'] = json_encode($data['settings']);
        }
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('playlists', $updateData, 'id = ?', [$id]);
            
            Helpers::logActivity("Playlist updated: {$updateData['name']}", 'info', ['playlist_id' => $id]);
        }
        
        return $this->getPlaylistById($id);
    }
    
    /**
     * Delete playlist
     */
    public function deletePlaylist($id) {
        $playlist = $this->getPlaylistById($id);
        if (!$playlist) {
            throw new Exception('Playlist not found');
        }
        
        // Check if playlist is being used by any devices
        $devicesUsingPlaylist = $this->db->fetchAll(
            "SELECT name FROM devices WHERE current_playlist_id = ? AND is_active = 1",
            [$id]
        );
        
        if (!empty($devicesUsingPlaylist)) {
            $deviceNames = array_column($devicesUsingPlaylist, 'name');
            throw new Exception('Playlist is currently being used by devices: ' . implode(', ', $deviceNames));
        }
        
        // Delete playlist items first
        $this->db->delete('playlist_items', 'playlist_id = ?', [$id]);
        
        // Delete playlist
        $result = $this->db->delete('playlists', 'id = ?', [$id]);
        
        if ($result) {
            Helpers::logActivity("Playlist deleted: {$playlist['name']}", 'warning', ['playlist_id' => $id]);
        }
        
        return $result;
    }
    
    /**
     * Get playlist items
     */
    public function getPlaylistItems($playlistId) {
        return $this->db->fetchAll(
            "SELECT pi.*, c.title, c.type, c.file_url, c.thumbnail_path, c.duration as content_duration,
                    COALESCE(pi.duration, c.duration, 5) as effective_duration
             FROM playlist_items pi 
             JOIN content c ON pi.content_id = c.id 
             WHERE pi.playlist_id = ? AND c.deleted_at IS NULL
             ORDER BY pi.order_index ASC",
            [$playlistId]
        );
    }
    
    /**
     * Add content to playlist
     */
    public function addContentToPlaylist($playlistId, $contentId, $data = []) {
        // Check if playlist exists
        $playlist = $this->getPlaylistById($playlistId);
        if (!$playlist) {
            throw new Exception('Playlist not found');
        }
        
        // Check if content exists
        $content = $this->db->fetchOne(
            "SELECT * FROM content WHERE id = ? AND deleted_at IS NULL",
            [$contentId]
        );
        if (!$content) {
            throw new Exception('Content not found');
        }
        
        // Get next order index
        $maxOrder = $this->db->fetchOne(
            "SELECT MAX(order_index) as max_order FROM playlist_items WHERE playlist_id = ?",
            [$playlistId]
        );
        
        $itemData = [
            'playlist_id' => $playlistId,
            'content_id' => $contentId,
            'zone_id' => $data['zone_id'] ?? 'main',
            'order_index' => $data['order_index'] ?? (($maxOrder['max_order'] ?? -1) + 1),
            'duration' => $data['duration'] ?? null,
            'transition_type' => $data['transition_type'] ?? 'fade',
            'transition_duration' => $data['transition_duration'] ?? 1000
        ];
        
        $itemId = $this->db->insert('playlist_items', $itemData);
        
        Helpers::logActivity("Content added to playlist", 'info', [
            'playlist_id' => $playlistId,
            'content_id' => $contentId,
            'item_id' => $itemId
        ]);
        
        return $itemId;
    }
    
    /**
     * Remove content from playlist
     */
    public function removeContentFromPlaylist($playlistId, $contentId) {
        $result = $this->db->delete(
            'playlist_items', 
            'playlist_id = ? AND content_id = ?', 
            [$playlistId, $contentId]
        );
        
        if ($result) {
            // Reorder remaining items
            $this->reorderPlaylistItems($playlistId);
            
            Helpers::logActivity("Content removed from playlist", 'info', [
                'playlist_id' => $playlistId,
                'content_id' => $contentId
            ]);
        }
        
        return $result;
    }
    
    /**
     * Update playlist item
     */
    public function updatePlaylistItem($itemId, $data) {
        $updateData = [];
        
        if (isset($data['order_index'])) {
            $updateData['order_index'] = $data['order_index'];
        }
        
        if (isset($data['duration'])) {
            $updateData['duration'] = $data['duration'];
        }
        
        if (isset($data['zone_id'])) {
            $updateData['zone_id'] = $data['zone_id'];
        }
        
        if (isset($data['transition_type'])) {
            $updateData['transition_type'] = $data['transition_type'];
        }
        
        if (isset($data['transition_duration'])) {
            $updateData['transition_duration'] = $data['transition_duration'];
        }
        
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $result = $this->db->update('playlist_items', $updateData, 'id = ?', [$itemId]);
            
            if ($result) {
                Helpers::logActivity("Playlist item updated", 'info', ['item_id' => $itemId]);
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Reorder playlist items
     */
    public function reorderPlaylistItems($playlistId, $itemIds = null) {
        if ($itemIds) {
            // Update specific order
            foreach ($itemIds as $index => $itemId) {
                $this->db->update(
                    'playlist_items', 
                    ['order_index' => $index], 
                    'id = ? AND playlist_id = ?', 
                    [$itemId, $playlistId]
                );
            }
        } else {
            // Reset order for all items
            $items = $this->db->fetchAll(
                "SELECT id FROM playlist_items WHERE playlist_id = ? ORDER BY order_index ASC",
                [$playlistId]
            );
            
            foreach ($items as $index => $item) {
                $this->db->update(
                    'playlist_items', 
                    ['order_index' => $index], 
                    'id = ?', 
                    [$item['id']]
                );
            }
        }
        
        Helpers::logActivity("Playlist items reordered", 'info', ['playlist_id' => $playlistId]);
        
        return true;
    }
    
    /**
     * Duplicate playlist
     */
    public function duplicatePlaylist($id) {
        $playlist = $this->getPlaylistById($id);
        if (!$playlist) {
            throw new Exception('Playlist not found');
        }
        
        $newPlaylistData = [
            'name' => $playlist['name'] . ' (Copy)',
            'description' => $playlist['description'],
            'layout_id' => $playlist['layout_id'],
            'loop_count' => $playlist['loop_count'],
            'shuffle' => $playlist['shuffle'],
            'settings' => $playlist['settings'],
            'is_active' => false, // Set as inactive by default
            'created_by' => $playlist['created_by']
        ];
        
        $newPlaylistId = $this->db->insert('playlists', $newPlaylistData);
        
        // Copy playlist items
        $items = $this->getPlaylistItems($id);
        foreach ($items as $item) {
            $this->addContentToPlaylist($newPlaylistId, $item['content_id'], [
                'zone_id' => $item['zone_id'],
                'order_index' => $item['order_index'],
                'duration' => $item['duration'],
                'transition_type' => $item['transition_type'],
                'transition_duration' => $item['transition_duration']
            ]);
        }
        
        Helpers::logActivity("Playlist duplicated: {$playlist['name']}", 'info', [
            'original_id' => $id,
            'new_id' => $newPlaylistId
        ]);
        
        return $this->getPlaylistById($newPlaylistId);
    }
    
    /**
     * Get playlist statistics
     */
    public function getPlaylistStats() {
        $stats = [];
        
        // Total playlists
        $stats['total'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM playlists"
        )['count'];
        
        // Active playlists
        $stats['active'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM playlists WHERE is_active = 1"
        )['count'];
        
        // Average items per playlist
        $avgItems = $this->db->fetchOne(
            "SELECT AVG(item_count) as avg_items FROM (
                SELECT COUNT(*) as item_count FROM playlist_items GROUP BY playlist_id
             ) as playlist_counts"
        );
        
        $stats['avg_items'] = round($avgItems['avg_items'] ?? 0, 1);
        
        // Most used content
        $mostUsed = $this->db->fetchAll(
            "SELECT c.title, COUNT(*) as usage_count 
             FROM playlist_items pi 
             JOIN content c ON pi.content_id = c.id 
             WHERE c.deleted_at IS NULL
             GROUP BY pi.content_id, c.title 
             ORDER BY usage_count DESC 
             LIMIT 5"
        );
        
        $stats['most_used_content'] = $mostUsed;
        
        return $stats;
    }
}
?>