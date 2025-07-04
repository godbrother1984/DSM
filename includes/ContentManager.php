<?php
/*
=============================================================================
CONTENT MANAGER - แก้ไข Missing File
=============================================================================
*/

class ContentManager {
    private $db;
    
    public function __construct() {
        // Simple database connection
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            $this->db = null;
        }
    }
    
    public function getAllContent($filters = []) {
        // If no database, return demo content
        if (!$this->db || !$this->db->isConnected()) {
            return $this->getDemoContent();
        }
        
        try {
            return $this->db->fetchAll("SELECT * FROM content WHERE status = ?", ["active"]);
        } catch (Exception $e) {
            return $this->getDemoContent();
        }
    }
    
    public function getContentById($id) {
        if (!$this->db || !$this->db->isConnected()) {
            $demo = $this->getDemoContent();
            foreach ($demo as $item) {
                if ($item["id"] == $id) return $item;
            }
            return null;
        }
        
        try {
            return $this->db->fetchOne("SELECT * FROM content WHERE id = ?", [$id]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function createContent($data) {
        if (!$this->db || !$this->db->isConnected()) {
            // Demo mode - simulate creation
            return [
                "id" => time(),
                "title" => $data["title"],
                "type" => $data["type"] ?? "text",
                "status" => "active",
                "created_at" => date("Y-m-d H:i:s")
            ];
        }
        
        try {
            $id = $this->db->insert("content", $data);
            return $this->getContentById($id);
        } catch (Exception $e) {
            throw new Exception("Failed to create content: " . $e->getMessage());
        }
    }
    
    public function updateContent($id, $data) {
        if (!$this->db || !$this->db->isConnected()) {
            return true; // Demo mode
        }
        
        try {
            return $this->db->update("content", $data, "id = ?", [$id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update content: " . $e->getMessage());
        }
    }
    
    public function deleteContent($id) {
        if (!$this->db || !$this->db->isConnected()) {
            return true; // Demo mode
        }
        
        try {
            return $this->db->update("content", ["status" => "deleted"], "id = ?", [$id]);
        } catch (Exception $e) {
            throw new Exception("Failed to delete content: " . $e->getMessage());
        }
    }
    
    private function getDemoContent() {
        return [
            [
                "id" => 1,
                "title" => "Welcome Banner",
                "type" => "image",
                "duration" => 10,
                "file_url" => "/demo/welcome.jpg",
                "status" => "active",
                "created_at" => date("Y-m-d H:i:s")
            ],
            [
                "id" => 2,
                "title" => "Product Demo",
                "type" => "video",
                "duration" => 30,
                "file_url" => "/demo/product.mp4",
                "status" => "active",
                "created_at" => date("Y-m-d H:i:s")
            ],
            [
                "id" => 3,
                "title" => "News Widget",
                "type" => "html",
                "duration" => 15,
                "file_url" => "/demo/news.html",
                "status" => "active",
                "created_at" => date("Y-m-d H:i:s")
            ]
        ];
    }
}
?>