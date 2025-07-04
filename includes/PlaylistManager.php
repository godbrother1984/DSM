<?php
/*
=============================================================================
PLAYLIST MANAGER - แก้ไข Missing File
=============================================================================
*/

class PlaylistManager {
    private $db;
    
    public function __construct() {
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            $this->db = null;
        }
    }
    
    public function getPlaylists($filters = [], $page = 1, $limit = 20) {
        if (!$this->db || !$this->db->isConnected()) {
            return [
                "data" => $this->getDemoPlaylists(),
                "pagination" => [
                    "current_page" => 1,
                    "total_pages" => 1,
                    "total_items" => 2
                ]
            ];
        }
        
        try {
            $playlists = $this->db->fetchAll("SELECT * FROM playlists WHERE is_active = 1 ORDER BY created_at DESC");
            return [
                "data" => $playlists,
                "pagination" => [
                    "current_page" => $page,
                    "total_pages" => 1,
                    "total_items" => count($playlists)
                ]
            ];
        } catch (Exception $e) {
            return [
                "data" => $this->getDemoPlaylists(),
                "pagination" => [
                    "current_page" => 1,
                    "total_pages" => 1,
                    "total_items" => 2
                ]
            ];
        }
    }
    
    public function getPlaylistById($id) {
        if (!$this->db || !$this->db->isConnected()) {
            $demo = $this->getDemoPlaylists();
            foreach ($demo as $playlist) {
                if ($playlist["id"] == $id) {
                    $playlist["items"] = $this->getDemoPlaylistItems();
                    return $playlist;
                }
            }
            return null;
        }
        
        try {
            $playlist = $this->db->fetchOne("SELECT * FROM playlists WHERE id = ?", [$id]);
            if ($playlist) {
                $playlist["items"] = $this->getPlaylistItems($id);
            }
            return $playlist;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createPlaylist($data) {
        if (!$this->db || !$this->db->isConnected()) {
            return [
                "id" => time(),
                "name" => $data["name"],
                "description" => $data["description"] ?? "",
                "is_active" => true,
                "created_at" => date("Y-m-d H:i:s")
            ];
        }
        
        try {
            $id = $this->db->insert("playlists", $data);
            return $this->getPlaylistById($id);
        } catch (Exception $e) {
            throw new Exception("Failed to create playlist: " . $e->getMessage());
        }
    }
    
    public function updatePlaylist($id, $data) {
        if (!$this->db || !$this->db->isConnected()) {
            return true;
        }
        
        try {
            $this->db->update("playlists", $data, "id = ?", [$id]);
            return $this->getPlaylistById($id);
        } catch (Exception $e) {
            throw new Exception("Failed to update playlist: " . $e->getMessage());
        }
    }
    
    public function deletePlaylist($id) {
        if (!$this->db || !$this->db->isConnected()) {
            return true;
        }
        
        try {
            return $this->db->update("playlists", ["is_active" => false], "id = ?", [$id]);
        } catch (Exception $e) {
            throw new Exception("Failed to delete playlist: " . $e->getMessage());
        }
    }
    
    public function getPlaylistItems($playlistId) {
        if (!$this->db || !$this->db->isConnected()) {
            return $this->getDemoPlaylistItems();
        }
        
        try {
            return $this->db->fetchAll(
                "SELECT pi.*, c.title, c.type, c.file_url, c.duration 
                 FROM playlist_items pi 
                 JOIN content c ON pi.content_id = c.id 
                 WHERE pi.playlist_id = ? 
                 ORDER BY pi.order_index",
                [$playlistId]
            );
        } catch (Exception $e) {
            return $this->getDemoPlaylistItems();
        }
    }
    
    private function getDemoPlaylists() {
        return [
            [
                "id" => 1,
                "name" => "Demo Playlist 1",
                "description" => "Sample playlist for testing",
                "is_active" => true,
                "item_count" => 3,
                "total_duration" => 55,
                "created_at" => date("Y-m-d H:i:s")
            ],
            [
                "id" => 2,
                "name" => "Demo Playlist 2",
                "description" => "Another sample playlist",
                "is_active" => true,
                "item_count" => 2,
                "total_duration" => 40,
                "created_at" => date("Y-m-d H:i:s", strtotime("-1 hour"))
            ]
        ];
    }
    
    private function getDemoPlaylistItems() {
        return [
            [
                "id" => 1,
                "playlist_id" => 1,
                "content_id" => 1,
                "order_index" => 0,
                "duration" => 10,
                "title" => "Welcome Banner",
                "type" => "image",
                "file_url" => "/demo/welcome.jpg"
            ],
            [
                "id" => 2,
                "playlist_id" => 1,
                "content_id" => 2,
                "order_index" => 1,
                "duration" => 30,
                "title" => "Product Demo",
                "type" => "video",
                "file_url" => "/demo/product.mp4"
            ],
            [
                "id" => 3,
                "playlist_id" => 1,
                "content_id" => 3,
                "order_index" => 2,
                "duration" => 15,
                "title" => "News Widget",
                "type" => "html",
                "file_url" => "/demo/news.html"
            ]
        ];
    }
}
?>