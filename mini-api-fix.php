<?php
/*
=============================================================================
ENTERPRISE SYSTEM UPGRADE PLAN
=============================================================================
Migration from JSON to MySQL + Authentication + Multi-Tenant
=============================================================================
*/

echo "<h1>🚀 Enterprise System Upgrade Plan</h1>";
echo "<h3>Migration: JSON → MySQL + Authentication + Multi-Tenant</h3>";

// ===============================================================
// Phase 1: Database Setup & Migration (Day 1-2)
// ===============================================================

echo "<h2>📅 Phase 1: Database Setup & Migration (วันที่ 1-2)</h2>";

$databaseSchema = '
-- =============================================================================
-- ENTERPRISE DATABASE SCHEMA WITH MULTI-TENANT SUPPORT
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Tenants table (Multi-tenant support)
CREATE TABLE IF NOT EXISTS `tenants` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(100) NOT NULL UNIQUE,
    `domain` varchar(255) NULL,
    `database_name` varchar(100) NULL,
    `settings` json NULL,
    `subscription_plan` enum("free","basic","pro","enterprise") DEFAULT "free",
    `max_devices` int DEFAULT 5,
    `max_storage_gb` int DEFAULT 10,
    `max_users` int DEFAULT 10,
    `is_active` boolean DEFAULT TRUE,
    `trial_ends_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tenants_slug` (`slug`),
    INDEX `idx_tenants_domain` (`domain`),
    INDEX `idx_tenants_is_active` (`is_active`)
);

-- Users table (with tenant isolation)
CREATE TABLE IF NOT EXISTS `users` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint unsigned NOT NULL,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum("super_admin","admin","manager","editor","viewer") DEFAULT "viewer",
    `avatar` varchar(500) NULL,
    `is_active` boolean DEFAULT TRUE,
    `email_verified_at` timestamp NULL,
    `last_login_at` timestamp NULL,
    `last_login_ip` varchar(45) NULL,
    `failed_login_attempts` int DEFAULT 0,
    `locked_until` timestamp NULL,
    `password_changed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_tenant_email` (`tenant_id`, `email`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_tenant_id` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- Content table (with tenant isolation)
CREATE TABLE IF NOT EXISTS `content` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint unsigned NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text NULL,
    `type` enum("video","image","audio","html","widget","dashboard","text") NOT NULL,
    `file_path` varchar(500) NULL,
    `file_url` varchar(500) NULL,
    `thumbnail_path` varchar(500) NULL,
    `file_size` bigint NULL COMMENT "File size in bytes",
    `mime_type` varchar(100) NULL,
    `duration` int NULL COMMENT "Duration in seconds",
    `width` int NULL,
    `height` int NULL,
    `metadata` json NULL,
    `tags` json NULL,
    `status` enum("active","inactive","processing","error") DEFAULT "active",
    `expires_at` timestamp NULL,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_content_tenant_id` (`tenant_id`),
    INDEX `idx_content_type` (`type`),
    INDEX `idx_content_status` (`status`),
    INDEX `idx_content_created_by` (`created_by`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Playlists table (with tenant isolation)
CREATE TABLE IF NOT EXISTS `playlists` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint unsigned NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `total_duration` int DEFAULT 0 COMMENT "Total duration in seconds",
    `is_default` boolean DEFAULT FALSE,
    `is_active` boolean DEFAULT TRUE,
    `settings` json NULL,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_playlists_tenant_id` (`tenant_id`),
    INDEX `idx_playlists_is_default` (`is_default`),
    INDEX `idx_playlists_is_active` (`is_active`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Playlist items (junction table)
CREATE TABLE IF NOT EXISTS `playlist_items` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `playlist_id` bigint unsigned NOT NULL,
    `content_id` bigint unsigned NOT NULL,
    `order_index` int NOT NULL DEFAULT 0,
    `duration_override` int NULL COMMENT "Override content duration",
    `start_date` date NULL,
    `end_date` date NULL,
    `start_time` time NULL,
    `end_time` time NULL,
    `days_of_week` json NULL COMMENT "Array of weekday numbers 0-6",
    `is_active` boolean DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_playlist_items_playlist_id` (`playlist_id`),
    INDEX `idx_playlist_items_content_id` (`content_id`),
    INDEX `idx_playlist_items_order` (`playlist_id`, `order_index`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
);

-- Devices table (with tenant isolation)
CREATE TABLE IF NOT EXISTS `devices` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint unsigned NOT NULL,
    `device_id` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `location` varchar(255) NULL,
    `screen_width` int NULL,
    `screen_height` int NULL,
    `orientation` enum("landscape","portrait") DEFAULT "landscape",
    `current_playlist_id` bigint unsigned NULL,
    `status` enum("online","offline","error","maintenance") DEFAULT "offline",
    `last_seen` timestamp NULL,
    `last_heartbeat` timestamp NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `settings` json NULL,
    `api_key` varchar(64) NOT NULL,
    `is_active` boolean DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_devices_tenant_device_id` (`tenant_id`, `device_id`),
    UNIQUE KEY `uk_devices_api_key` (`api_key`),
    INDEX `idx_devices_tenant_id` (`tenant_id`),
    INDEX `idx_devices_status` (`status`),
    INDEX `idx_devices_last_seen` (`last_seen`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`current_playlist_id`) REFERENCES `playlists` (`id`) ON DELETE SET NULL
);

-- API tokens table (for API authentication)
CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `name` varchar(255) NOT NULL,
    `token` varchar(64) NOT NULL UNIQUE,
    `abilities` json NULL,
    `last_used_at` timestamp NULL,
    `expires_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_api_tokens_token` (`token`),
    INDEX `idx_api_tokens_user_id` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

-- System settings table (tenant-specific)
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint unsigned NULL COMMENT "NULL for global settings",
    `key` varchar(255) NOT NULL,
    `value` text NULL,
    `type` enum("string","integer","boolean","json") DEFAULT "string",
    `description` text NULL,
    `is_public` boolean DEFAULT FALSE,
    `updated_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_system_settings_tenant_key` (`tenant_id`, `key`),
    INDEX `idx_system_settings_tenant_id` (`tenant_id`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Content analytics table
CREATE TABLE IF NOT EXISTS `content_analytics` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint unsigned NOT NULL,
    `device_id` bigint unsigned NOT NULL,
    `content_id` bigint unsigned NOT NULL,
    `playlist_id` bigint unsigned NULL,
    `event_type` enum("start","end","skip","error","interaction") NOT NULL,
    `duration_watched` int NULL,
    `interaction_data` json NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_content_analytics_tenant_id` (`tenant_id`),
    INDEX `idx_content_analytics_device_id` (`device_id`),
    INDEX `idx_content_analytics_content_id` (`content_id`),
    INDEX `idx_content_analytics_timestamp` (`timestamp`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE SET NULL
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tenant_id` bigint unsigned NULL,
    `user_id` bigint unsigned NULL,
    `action` varchar(255) NOT NULL,
    `subject_type` varchar(255) NULL,
    `subject_id` bigint unsigned NULL,
    `description` text NULL,
    `properties` json NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_activity_logs_tenant_id` (`tenant_id`),
    INDEX `idx_activity_logs_user_id` (`user_id`),
    INDEX `idx_activity_logs_action` (`action`),
    INDEX `idx_activity_logs_created_at` (`created_at`),
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DEFAULT DATA INSERTION
-- =============================================================================

-- Insert default tenant
INSERT IGNORE INTO `tenants` (`id`, `name`, `slug`, `subscription_plan`, `max_devices`, `max_storage_gb`, `max_users`) 
VALUES (1, "Default Organization", "default", "enterprise", 100, 100, 50);

-- Insert super admin user (password: admin123)
INSERT IGNORE INTO `users` (`id`, `tenant_id`, `name`, `email`, `password`, `role`, `is_active`, `email_verified_at`) 
VALUES (1, 1, "Super Admin", "admin@dsm.local", "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi", "super_admin", 1, NOW());

-- Insert default playlist
INSERT IGNORE INTO `playlists` (`id`, `tenant_id`, `name`, `description`, `is_default`, `created_by`) 
VALUES (1, 1, "Default Playlist", "Default playlist for new devices", 1, 1);

-- Insert system settings
INSERT IGNORE INTO `system_settings` (`tenant_id`, `key`, `value`, `type`, `description`, `is_public`) VALUES
(NULL, "system_name", "Digital Signage Management", "string", "System name", 1),
(NULL, "system_version", "2.0.0", "string", "System version", 1),
(NULL, "max_upload_size", "104857600", "integer", "Maximum upload size in bytes", 0),
(NULL, "session_timeout", "3600", "integer", "Session timeout in seconds", 0),
(NULL, "enable_registration", "0", "boolean", "Allow user registration", 0),
(1, "company_name", "Default Company", "string", "Company name", 1),
(1, "company_logo", "", "string", "Company logo URL", 1),
(1, "default_content_duration", "10", "integer", "Default content duration in seconds", 1);
';

echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 11px; max-height: 300px; overflow-y: auto;'>";
echo htmlspecialchars($databaseSchema);
echo "</pre>";

// ===============================================================
// Phase 2: Enhanced Database Class
// ===============================================================

echo "<h2>📅 Phase 2: Enhanced Database Class (วันที่ 2)</h2>";

$enhancedDatabase = '<?php
/*
=============================================================================
ENHANCED DATABASE CLASS WITH MULTI-TENANT SUPPORT
=============================================================================
*/

class Database {
    private static $instance = null;
    private $pdo = null;
    private $currentTenantId = null;
    
    private function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $config = include __DIR__ . "/../config/database.php";
            
            $dsn = "mysql:host={$config[\"host\"]};dbname={$config[\"database\"]};charset={$config[\"charset\"]}";
            
            $this->pdo = new PDO($dsn, $config["username"], $config["password"], $config["options"]);
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setTenantId($tenantId) {
        $this->currentTenantId = $tenantId;
    }
    
    public function getCurrentTenantId() {
        return $this->currentTenantId;
    }
    
    // Enhanced query methods with tenant isolation
    
    public function fetchAll($sql, $params = [], $tenantIsolation = true) {
        if ($tenantIsolation && $this->currentTenantId && $this->needsTenantFilter($sql)) {
            $sql = $this->addTenantFilter($sql);
            $params[] = $this->currentTenantId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = [], $tenantIsolation = true) {
        if ($tenantIsolation && $this->currentTenantId && $this->needsTenantFilter($sql)) {
            $sql = $this->addTenantFilter($sql);
            $params[] = $this->currentTenantId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data) {
        // Auto-add tenant_id if table supports it
        if ($this->currentTenantId && $this->tableHasTenantId($table)) {
            $data["tenant_id"] = $this->currentTenantId;
        }
        
        $fields = implode(",", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        // Auto-add tenant_id filter if table supports it
        if ($this->currentTenantId && $this->tableHasTenantId($table)) {
            $where .= " AND tenant_id = ?";
            $whereParams[] = $this->currentTenantId;
        }
        
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }
        $fields = implode(", ", $fields);
        
        $sql = "UPDATE {$table} SET {$fields} WHERE {$where}";
        $allParams = array_merge($data, $whereParams);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($allParams);
        
        return $stmt->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        // Auto-add tenant_id filter if table supports it
        if ($this->currentTenantId && $this->tableHasTenantId($table)) {
            $where .= " AND tenant_id = ?";
            $params[] = $this->currentTenantId;
        }
        
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    public function paginate($sql, $params = [], $page = 1, $limit = 20) {
        // Add tenant filter if needed
        if ($this->currentTenantId && $this->needsTenantFilter($sql)) {
            $sql = $this->addTenantFilter($sql);
            $params[] = $this->currentTenantId;
        }
        
        // Count total records
        $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as count_query";
        $totalRecords = $this->fetchOne($countSql, $params, false)["total"];
        
        // Calculate pagination
        $offset = ($page - 1) * $limit;
        $totalPages = ceil($totalRecords / $limit);
        
        // Add LIMIT and OFFSET
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        
        $records = $this->fetchAll($sql, $params, false);
        
        return [
            "data" => $records,
            "pagination" => [
                "current_page" => (int)$page,
                "per_page" => (int)$limit,
                "total" => (int)$totalRecords,
                "total_pages" => (int)$totalPages,
                "has_more" => $page < $totalPages
            ]
        ];
    }
    
    // Transaction support
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    // Helper methods
    private function needsTenantFilter($sql) {
        $tenantTables = ["users", "content", "playlists", "devices", "content_analytics", "activity_logs"];
        
        foreach ($tenantTables as $table) {
            if (stripos($sql, $table) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function addTenantFilter($sql) {
        // Simple tenant filter addition (can be enhanced)
        if (stripos($sql, "WHERE") !== false) {
            return str_ireplace("WHERE", "WHERE tenant_id = ? AND", $sql);
        } else {
            return $sql . " WHERE tenant_id = ?";
        }
    }
    
    private function tableHasTenantId($table) {
        $tenantTables = ["users", "content", "playlists", "devices", "content_analytics", "activity_logs", "system_settings"];
        return in_array($table, $tenantTables);
    }
    
    // Schema management
    public function createSchema() {
        $schemaFile = __DIR__ . "/../sql/schema.sql";
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $this->pdo->exec($sql);
            return true;
        }
        return false;
    }
    
    // Health check
    public function isHealthy() {
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>';

echo "<h4>📝 Enhanced Database Features:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Automatic Tenant Isolation</strong> - แยกข้อมูลตาม tenant อัตโนมัติ</li>";
echo "<li>✅ <strong>Pagination Support</strong> - รองรับการแบ่งหน้าข้อมูล</li>";
echo "<li>✅ <strong>Transaction Support</strong> - รองรับ database transactions</li>";
echo "<li>✅ <strong>Query Builder</strong> - สร้าง query อัตโนมัติ</li>";
echo "<li>✅ <strong>Security</strong> - ป้องกัน SQL injection</li>";
echo "</ul>";

// ===============================================================
// Phase 3: Authentication System
// ===============================================================

echo "<h2>📅 Phase 3: Authentication System (วันที่ 3)</h2>";

$authenticationSystem = '<?php
/*
=============================================================================
ENTERPRISE AUTHENTICATION SYSTEM
=============================================================================
*/

class AuthManager {
    private $db;
    private $jwtSecret;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $config = include __DIR__ . "/../config/config.php";
        $this->jwtSecret = $config["jwt_secret"];
    }
    
    // User Authentication
    public function login($email, $password, $tenantSlug = null) {
        try {
            // Find user by email and tenant
            $sql = "SELECT u.*, t.slug as tenant_slug, t.name as tenant_name 
                    FROM users u 
                    JOIN tenants t ON u.tenant_id = t.id 
                    WHERE u.email = ?";
            
            $params = [$email];
            
            if ($tenantSlug) {
                $sql .= " AND t.slug = ?";
                $params[] = $tenantSlug;
            }
            
            $sql .= " AND u.is_active = 1 AND t.is_active = 1";
            
            $user = $this->db->fetchOne($sql, $params, false);
            
            if (!$user) {
                throw new Exception("Invalid credentials");
            }
            
            // Check if account is locked
            if ($user["locked_until"] && strtotime($user["locked_until"]) > time()) {
                throw new Exception("Account is temporarily locked");
            }
            
            // Verify password
            if (!password_verify($password, $user["password"])) {
                $this->handleFailedLogin($user["id"]);
                throw new Exception("Invalid credentials");
            }
            
            // Reset failed attempts
            $this->resetFailedAttempts($user["id"]);
            
            // Update last login
            $this->updateLastLogin($user["id"]);
            
            // Set tenant context
            $this->db->setTenantId($user["tenant_id"]);
            
            // Generate JWT token
            $token = $this->generateJWT($user);
            
            // Log activity
            $this->logActivity($user["id"], "user_login", "User logged in");
            
            return [
                "user" => $this->sanitizeUser($user),
                "token" => $token,
                "tenant" => [
                    "id" => $user["tenant_id"],
                    "name" => $user["tenant_name"],
                    "slug" => $user["tenant_slug"]
                ]
            ];
            
        } catch (Exception $e) {
            throw new Exception("Login failed: " . $e->getMessage());
        }
    }
    
    public function logout($token) {
        try {
            $payload = $this->verifyJWT($token);
            $this->logActivity($payload["user_id"], "user_logout", "User logged out");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function register($userData, $tenantSlug) {
        try {
            // Check if registration is enabled
            if (!$this->isRegistrationEnabled()) {
                throw new Exception("Registration is not enabled");
            }
            
            // Find tenant
            $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE slug = ? AND is_active = 1", [$tenantSlug], false);
            if (!$tenant) {
                throw new Exception("Invalid organization");
            }
            
            // Check tenant user limits
            $userCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE tenant_id = ?", [$tenant["id"]], false);
            if ($userCount["count"] >= $tenant["max_users"]) {
                throw new Exception("User limit reached for this organization");
            }
            
            // Validate email uniqueness within tenant
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ? AND tenant_id = ?", 
                [$userData["email"], $tenant["id"]], 
                false
            );
            
            if ($existingUser) {
                throw new Exception("Email already exists in this organization");
            }
            
            // Create user
            $userId = $this->db->insert("users", [
                "tenant_id" => $tenant["id"],
                "name" => $userData["name"],
                "email" => $userData["email"],
                "password" => password_hash($userData["password"], PASSWORD_DEFAULT),
                "role" => $userData["role"] ?? "viewer",
                "is_active" => 1
            ]);
            
            $this->logActivity($userId, "user_register", "User registered");
            
            return $userId;
            
        } catch (Exception $e) {
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }
    
    // JWT Token Management
    public function generateJWT($user) {
        $header = json_encode(["typ" => "JWT", "alg" => "HS256"]);
        
        $payload = json_encode([
            "user_id" => $user["id"],
            "tenant_id" => $user["tenant_id"],
            "email" => $user["email"],
            "role" => $user["role"],
            "iat" => time(),
            "exp" => time() + (24 * 60 * 60) // 24 hours
        ]);
        
        $base64Header = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($header));
        $base64Payload = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($payload));
        
        $signature = hash_hmac("sha256", $base64Header . "." . $base64Payload, $this->jwtSecret, true);
        $base64Signature = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    public function verifyJWT($token) {
        try {
            $parts = explode(".", $token);
            if (count($parts) !== 3) {
                throw new Exception("Invalid token format");
            }
            
            [$header, $payload, $signature] = $parts;
            
            // Verify signature
            $expectedSignature = hash_hmac("sha256", $header . "." . $payload, $this->jwtSecret, true);
            $expectedBase64Signature = str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($expectedSignature));
            
            if (!hash_equals($expectedBase64Signature, $signature)) {
                throw new Exception("Invalid token signature");
            }
            
            // Decode payload
            $payloadData = json_decode(base64_decode(str_replace(["-", "_"], ["+", "/"], $payload)), true);
            
            // Check expiration
            if ($payloadData["exp"] < time()) {
                throw new Exception("Token expired");
            }
            
            // Verify user still exists and is active
            $user = $this->db->fetchOne(
                "SELECT u.*, t.is_active as tenant_active FROM users u JOIN tenants t ON u.tenant_id = t.id WHERE u.id = ?", 
                [$payloadData["user_id"]], 
                false
            );
            
            if (!$user || !$user["is_active"] || !$user["tenant_active"]) {
                throw new Exception("Invalid user");
            }
            
            // Set tenant context
            $this->db->setTenantId($user["tenant_id"]);
            
            return $payloadData;
            
        } catch (Exception $e) {
            throw new Exception("Token verification failed: " . $e->getMessage());
        }
    }
    
    // Permission Management
    public function hasPermission($userId, $permission) {
        $user = $this->db->fetchOne("SELECT role FROM users WHERE id = ?", [$userId]);
        if (!$user) return false;
        
        $rolePermissions = [
            "super_admin" => ["*"],
            "admin" => ["content.*", "playlist.*", "device.*", "user.view", "user.create", "user.edit", "analytics.*"],
            "manager" => ["content.*", "playlist.*", "device.*", "analytics.view"],
            "editor" => ["content.*", "playlist.view"],
            "viewer" => ["content.view", "playlist.view", "device.view"]
        ];
        
        $userPermissions = $rolePermissions[$user["role"]] ?? [];
        
        // Check for wildcard permission
        if (in_array("*", $userPermissions)) {
            return true;
        }
        
        // Check exact permission
        if (in_array($permission, $userPermissions)) {
            return true;
        }
        
        // Check wildcard patterns
        foreach ($userPermissions as $userPerm) {
            if (str_ends_with($userPerm, ".*")) {
                $prefix = substr($userPerm, 0, -2);
                if (str_starts_with($permission, $prefix . ".")) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    // Helper Methods
    private function handleFailedLogin($userId) {
        $this->db->update("users", [
            "failed_login_attempts" => "failed_login_attempts + 1",
            "locked_until" => "IF(failed_login_attempts >= 4, DATE_ADD(NOW(), INTERVAL 30 MINUTE), locked_until)"
        ], "id = ?", [$userId]);
    }
    
    private function resetFailedAttempts($userId) {
        $this->db->update("users", [
            "failed_login_attempts" => 0,
            "locked_until" => null
        ], "id = ?", [$userId]);
    }
    
    private function updateLastLogin($userId) {
        $this->db->update("users", [
            "last_login_at" => date("Y-m-d H:i:s"),
            "last_login_ip" => $_SERVER["REMOTE_ADDR"] ?? null
        ], "id = ?", [$userId]);
    }
    
    private function sanitizeUser($user) {
        unset($user["password"]);
        unset($user["failed_login_attempts"]);
        unset($user["locked_until"]);
        return $user;
    }
    
    private function isRegistrationEnabled() {
        $setting = $this->db->fetchOne(
            "SELECT value FROM system_settings WHERE `key` = ? AND tenant_id IS NULL", 
            ["enable_registration"], 
            false
        );
        return $setting && $setting["value"] === "1";
    }
    
    private function logActivity($userId, $action, $description) {
        $this->db->insert("activity_logs", [
            "tenant_id" => $this->db->getCurrentTenantId(),
            "user_id" => $userId,
            "action" => $action,
            "description" => $description,
            "ip_address" => $_SERVER["REMOTE_ADDR"] ?? null,
            "user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? null
        ]);
    }
}

// =============================================================================
// AUTHENTICATION MIDDLEWARE
// =============================================================================

class AuthMiddleware {
    private $auth;
    
    public function __construct() {
        $this->auth = new AuthManager();
    }
    
    public function authenticate($requiredRole = null) {
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            $this->unauthorizedResponse("No token provided");
        }
        
        try {
            $payload = $this->auth->verifyJWT($token);
            
            // Check role if required
            if ($requiredRole && !$this->checkRole($payload["role"], $requiredRole)) {
                $this->forbiddenResponse("Insufficient permissions");
            }
            
            return $payload;
            
        } catch (Exception $e) {
            $this->unauthorizedResponse($e->getMessage());
        }
    }
    
    public function checkPermission($permission) {
        $payload = $this->authenticate();
        
        if (!$this->auth->hasPermission($payload["user_id"], $permission)) {
            $this->forbiddenResponse("Permission denied: " . $permission);
        }
        
        return $payload;
    }
    
    private function getTokenFromRequest() {
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
        
        if (preg_match("/Bearer\s+(.*)$/i", $authHeader, $matches)) {
            return $matches[1];
        }
        
        return $_GET["token"] ?? $_POST["token"] ?? null;
    }
    
    private function checkRole($userRole, $requiredRole) {
        $roleHierarchy = [
            "super_admin" => 5,
            "admin" => 4,
            "manager" => 3,
            "editor" => 2,
            "viewer" => 1
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    private function unauthorizedResponse($message) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => $message]);
        exit;
    }
    
    private function forbiddenResponse($message) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => $message]);
        exit;
    }
}
?>';

// ===============================================================
// Phase 4: Multi-Tenant Manager
// ===============================================================

echo "<h2>📅 Phase 4: Multi-Tenant Manager (วันที่ 4)</h2>";

$multiTenantManager = '<?php
/*
=============================================================================
MULTI-TENANT MANAGER
=============================================================================
*/

class TenantManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Tenant Management
    public function createTenant($data) {
        try {
            $this->db->beginTransaction();
            
            // Validate tenant data
            $errors = $this->validateTenantData($data);
            if (!empty($errors)) {
                throw new Exception("Validation failed: " . implode(", ", $errors));
            }
            
            // Create tenant
            $tenantId = $this->db->insert("tenants", [
                "name" => $data["name"],
                "slug" => $this->generateSlug($data["name"]),
                "domain" => $data["domain"] ?? null,
                "subscription_plan" => $data["subscription_plan"] ?? "free",
                "max_devices" => $data["max_devices"] ?? 5,
                "max_storage_gb" => $data["max_storage_gb"] ?? 10,
                "max_users" => $data["max_users"] ?? 10,
                "trial_ends_at" => $data["trial_ends_at"] ?? null
            ]);
            
            // Create admin user for tenant
            $adminUserId = $this->createTenantAdmin($tenantId, $data["admin"]);
            
            // Create default playlist
            $this->createDefaultPlaylist($tenantId, $adminUserId);
            
            // Set up default settings
            $this->setupDefaultSettings($tenantId, $data);
            
            $this->db->commit();
            
            return [
                "tenant_id" => $tenantId,
                "admin_user_id" => $adminUserId,
                "slug" => $this->db->fetchOne("SELECT slug FROM tenants WHERE id = ?", [$tenantId], false)["slug"]
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to create tenant: " . $e->getMessage());
        }
    }
    
    public function getTenant($identifier, $by = "id") {
        $field = ($by === "slug") ? "slug" : "id";
        
        return $this->db->fetchOne(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) as user_count,
                    (SELECT COUNT(*) FROM devices WHERE tenant_id = t.id) as device_count,
                    (SELECT COUNT(*) FROM content WHERE tenant_id = t.id) as content_count
             FROM tenants t 
             WHERE t.{$field} = ?", 
            [$identifier], 
            false
        );
    }
    
    public function updateTenant($tenantId, $data) {
        try {
            $allowedFields = ["name", "domain", "subscription_plan", "max_devices", "max_storage_gb", "max_users", "is_active"];
            $updateData = array_intersect_key($data, array_flip($allowedFields));
            
            if (!empty($updateData)) {
                $this->db->update("tenants", $updateData, "id = ?", [$tenantId]);
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Failed to update tenant: " . $e->getMessage());
        }
    }
    
    public function deleteTenant($tenantId) {
        try {
            $this->db->beginTransaction();
            
            // Prevent deletion of default tenant
            if ($tenantId == 1) {
                throw new Exception("Cannot delete default tenant");
            }
            
            // Check if tenant has active subscriptions or devices
            $tenant = $this->getTenant($tenantId);
            if ($tenant["device_count"] > 0) {
                throw new Exception("Cannot delete tenant with active devices");
            }
            
            // Delete tenant (cascading will handle related records)
            $this->db->delete("tenants", "id = ?", [$tenantId]);
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Failed to delete tenant: " . $e->getMessage());
        }
    }
    
    // Resource Management
    public function checkResourceLimits($tenantId, $resourceType) {
        $tenant = $this->getTenant($tenantId);
        if (!$tenant) {
            throw new Exception("Tenant not found");
        }
        
        switch ($resourceType) {
            case "users":
                $current = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE tenant_id = ?", [$tenantId])["count"];
                return [
                    "current" => $current,
                    "limit" => $tenant["max_users"],
                    "available" => max(0, $tenant["max_users"] - $current),
                    "exceeded" => $current >= $tenant["max_users"]
                ];
                
            case "devices":
                $current = $this->db->fetchOne("SELECT COUNT(*) as count FROM devices WHERE tenant_id = ?", [$tenantId])["count"];
                return [
                    "current" => $current,
                    "limit" => $tenant["max_devices"],
                    "available" => max(0, $tenant["max_devices"] - $current),
                    "exceeded" => $current >= $tenant["max_devices"]
                ];
                
            case "storage":
                $current = $this->getStorageUsage($tenantId);
                $limit = $tenant["max_storage_gb"] * 1024 * 1024 * 1024; // Convert GB to bytes
                return [
                    "current" => $current,
                    "limit" => $limit,
                    "available" => max(0, $limit - $current),
                    "exceeded" => $current >= $limit
                ];
                
            default:
                throw new Exception("Unknown resource type: " . $resourceType);
        }
    }
    
    public function getStorageUsage($tenantId) {
        $result = $this->db->fetchOne(
            "SELECT SUM(file_size) as total_size FROM content WHERE tenant_id = ? AND file_size IS NOT NULL", 
            [$tenantId]
        );
        
        return (int)($result["total_size"] ?? 0);
    }
    
    // Tenant Context Management
    public function resolveTenant($request) {
        // Try to resolve tenant from domain
        $host = $_SERVER["HTTP_HOST"] ?? "";
        
        $tenant = $this->db->fetchOne(
            "SELECT * FROM tenants WHERE domain = ? AND is_active = 1", 
            [$host], 
            false
        );
        
        if ($tenant) {
            return $tenant;
        }
        
        // Try to resolve from subdomain
        if (preg_match("/^([^.]+)\.(.+)$/", $host, $matches)) {
            $subdomain = $matches[1];
            
            $tenant = $this->db->fetchOne(
                "SELECT * FROM tenants WHERE slug = ? AND is_active = 1", 
                [$subdomain], 
                false
            );
            
            if ($tenant) {
                return $tenant;
            }
        }
        
        // Try to resolve from URL path
        $path = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH);
        if (preg_match("/^\/([^\/]+)/", $path, $matches)) {
            $slug = $matches[1];
            
            $tenant = $this->db->fetchOne(
                "SELECT * FROM tenants WHERE slug = ? AND is_active = 1", 
                [$slug], 
                false
            );
            
            if ($tenant) {
                return $tenant;
            }
        }
        
        // Default to first tenant if no specific tenant found
        return $this->db->fetchOne(
            "SELECT * FROM tenants WHERE is_active = 1 ORDER BY id LIMIT 1", 
            [], 
            false
        );
    }
    
    // Helper Methods
    private function validateTenantData($data) {
        $errors = [];
        
        if (empty($data["name"])) {
            $errors[] = "Name is required";
        }
        
        if (empty($data["admin"]["name"])) {
            $errors[] = "Admin name is required";
        }
        
        if (empty($data["admin"]["email"]) || !filter_var($data["admin"]["email"], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid admin email is required";
        }
        
        if (empty($data["admin"]["password"]) || strlen($data["admin"]["password"]) < 8) {
            $errors[] = "Admin password must be at least 8 characters";
        }
        
        return $errors;
    }
    
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace("/[^A-Za-z0-9-]+/", "-", $name)));
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->db->fetchOne("SELECT id FROM tenants WHERE slug = ?", [$slug], false)) {
            $slug = $originalSlug . "-" . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    private function createTenantAdmin($tenantId, $adminData) {
        return $this->db->insert("users", [
            "tenant_id" => $tenantId,
            "name" => $adminData["name"],
            "email" => $adminData["email"],
            "password" => password_hash($adminData["password"], PASSWORD_DEFAULT),
            "role" => "admin",
            "is_active" => 1,
            "email_verified_at" => date("Y-m-d H:i:s")
        ]);
    }
    
    private function createDefaultPlaylist($tenantId, $userId) {
        return $this->db->insert("playlists", [
            "tenant_id" => $tenantId,
            "name" => "Default Playlist",
            "description" => "Default playlist for new devices",
            "is_default" => 1,
            "created_by" => $userId
        ]);
    }
    
    private function setupDefaultSettings($tenantId, $data) {
        $defaultSettings = [
            "company_name" => $data["name"],
            "company_logo" => "",
            "default_content_duration" => "10",
            "timezone" => "Asia/Bangkok",
            "theme_color" => "#667eea"
        ];
        
        foreach ($defaultSettings as $key => $value) {
            $this->db->insert("system_settings", [
                "tenant_id" => $tenantId,
                "key" => $key,
                "value" => $value,
                "type" => "string",
                "is_public" => 1
            ]);
        }
    }
}
?>';

// ===============================================================
// Phase 5: Migration Script
// ===============================================================

echo "<h2>📅 Phase 5: Migration Script (วันที่ 4)</h2>";

$migrationScript = '<?php
/*
=============================================================================
DATA MIGRATION SCRIPT: JSON → MySQL
=============================================================================
*/

class DataMigration {
    private $db;
    private $tenantId;
    
    public function __construct($tenantId = 1) {
        $this->db = Database::getInstance();
        $this->tenantId = $tenantId;
        $this->db->setTenantId($tenantId);
    }
    
    public function migrateFromJSON() {
        try {
            $this->db->beginTransaction();
            
            echo "🔄 Starting migration from JSON to MySQL...\n";
            
            // Migrate content
            $contentMigrated = $this->migrateContent();
            echo "✅ Migrated {$contentMigrated} content items\n";
            
            // Create default playlist with migrated content
            $playlistId = $this->createDefaultPlaylist();
            $this->addContentToPlaylist($playlistId);
            echo "✅ Created default playlist with content\n";
            
            // Backup JSON file
            $this->backupJSONFile();
            echo "✅ Backed up JSON file\n";
            
            $this->db->commit();
            
            echo "🎉 Migration completed successfully!\n";
            
            return [
                "content_migrated" => $contentMigrated,
                "playlist_created" => $playlistId
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Migration failed: " . $e->getMessage());
        }
    }
    
    private function migrateContent() {
        $jsonFile = "../uploads/content_list.json";
        
        if (!file_exists($jsonFile)) {
            echo "⚠️ No JSON file found, skipping content migration\n";
            return 0;
        }
        
        $jsonData = json_decode(file_get_contents($jsonFile), true);
        
        if (!is_array($jsonData)) {
            echo "⚠️ Invalid JSON data, skipping content migration\n";
            return 0;
        }
        
        $migratedCount = 0;
        
        foreach ($jsonData as $item) {
            try {
                // Check if content already exists
                $existing = $this->db->fetchOne(
                    "SELECT id FROM content WHERE title = ? AND type = ? AND tenant_id = ?",
                    [$item["title"] ?? "Untitled", $item["type"] ?? "text", $this->tenantId]
                );
                
                if ($existing) {
                    echo "⏭️ Skipping duplicate: " . ($item["title"] ?? "Untitled") . "\n";
                    continue;
                }
                
                // Insert content
                $contentData = [
                    "tenant_id" => $this->tenantId,
                    "title" => $item["title"] ?? "Untitled",
                    "description" => $item["description"] ?? "",
                    "type" => $item["type"] ?? "text",
                    "file_path" => $item["file_path"] ?? null,
                    "file_url" => $item["file_url"] ?? null,
                    "thumbnail_path" => $item["thumbnail_path"] ?? null,
                    "file_size" => $item["file_size"] ?? null,
                    "mime_type" => $item["mime_type"] ?? null,
                    "duration" => $item["duration"] ?? 10,
                    "status" => $item["status"] ?? "active",
                    "created_by" => 1, // Default admin user
                    "created_at" => $item["created_at"] ?? date("Y-m-d H:i:s")
                ];
                
                $contentId = $this->db->insert("content", $contentData);
                
                echo "✅ Migrated: " . $contentData["title"] . " (ID: {$contentId})\n";
                $migratedCount++;
                
            } catch (Exception $e) {
                echo "❌ Failed to migrate item: " . ($item["title"] ?? "Unknown") . " - " . $e->getMessage() . "\n";
            }
        }
        
        return $migratedCount;
    }
    
    private function createDefaultPlaylist() {
        // Check if default playlist exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM playlists WHERE tenant_id = ? AND is_default = 1",
            [$this->tenantId]
        );
        
        if ($existing) {
            return $existing["id"];
        }
        
        return $this->db->insert("playlists", [
            "tenant_id" => $this->tenantId,
            "name" => "Migrated Content Playlist",
            "description" => "Playlist created from migrated JSON content",
            "is_default" => 1,
            "created_by" => 1
        ]);
    }
    
    private function addContentToPlaylist($playlistId) {
        $content = $this->db->fetchAll(
            "SELECT id, duration FROM content WHERE tenant_id = ? AND status = ? ORDER BY created_at",
            [$this->tenantId, "active"]
        );
        
        $order = 0;
        foreach ($content as $item) {
            $this->db->insert("playlist_items", [
                "playlist_id" => $playlistId,
                "content_id" => $item["id"],
                "order_index" => $order++,
                "duration_override" => $item["duration"]
            ]);
        }
    }
    
    private function backupJSONFile() {
        $jsonFile = "../uploads/content_list.json";
        
        if (file_exists($jsonFile)) {
            $backupFile = "../uploads/content_list_backup_" . date("Y-m-d_H-i-s") . ".json";
            
            if (copy($jsonFile, $backupFile)) {
                echo "📄 JSON file backed up to: " . basename($backupFile) . "\n";
            }
        }
    }
    
    public function verifyMigration() {
        echo "\n🔍 Verifying migration...\n";
        
        $stats = [
            "tenants" => $this->db->fetchOne("SELECT COUNT(*) as count FROM tenants", [], false)["count"],
            "users" => $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE tenant_id = ?", [$this->tenantId])["count"],
            "content" => $this->db->fetchOne("SELECT COUNT(*) as count FROM content WHERE tenant_id = ?", [$this->tenantId])["count"],
            "playlists" => $this->db->fetchOne("SELECT COUNT(*) as count FROM playlists WHERE tenant_id = ?", [$this->tenantId])["count"]
        ];
        
        echo "📊 Migration Statistics:\n";
        foreach ($stats as $table => $count) {
            echo "   - " . ucfirst($table) . ": {$count}\n";
        }
        
        return $stats;
    }
}

// Usage example:
/*
$migration = new DataMigration(1); // Use tenant ID 1
$result = $migration->migrateFromJSON();
$migration->verifyMigration();
*/
?>';

echo "<h4>🔄 Migration Features:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Safe Migration</strong> - Backup original JSON files</li>";
echo "<li>✅ <strong>Duplicate Prevention</strong> - Skip existing content</li>";
echo "<li>✅ <strong>Transaction Support</strong> - Rollback on errors</li>";
echo "<li>✅ <strong>Progress Tracking</strong> - Real-time migration status</li>";
echo "<li>✅ <strong>Verification</strong> - Verify migration success</li>";
echo "</ul>";

// ===============================================================
// Summary & Implementation Plan
// ===============================================================

echo "<h2>🎯 Implementation Plan Summary</h2>";

echo "<h3>📅 Timeline (4-5 วัน):</h3>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<strong>Day 1:</strong> Database schema setup + Enhanced Database class<br>";
echo "<strong>Day 2:</strong> Authentication system implementation<br>";
echo "<strong>Day 3:</strong> Multi-tenant manager + API updates<br>";
echo "<strong>Day 4:</strong> Data migration + Testing<br>";
echo "<strong>Day 5:</strong> Frontend updates + Final testing";
echo "</div>";

echo "<h3>🚀 Ready to Start?</h3>";
echo "<p>คุณต้องการให้ผมเริ่มพัฒนาส่วนไหนก่อน?</p>";

echo "<div style='margin: 20px 0;'>";
echo "<button onclick=\"alert('เริ่ม Day 1: Database Schema + Enhanced Database Class')\" style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 6px; margin: 5px; cursor: pointer;'>📅 Day 1: Database Setup</button><br>";
echo "<button onclick=\"alert('เริ่ม Day 2: Authentication System')\" style='background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 6px; margin: 5px; cursor: pointer;'>🔒 Day 2: Authentication</button><br>";
echo "<button onclick=\"alert('เริ่ม Day 3: Multi-Tenant Manager')\" style='background: #6f42c1; color: white; padding: 12px 24px; border: none; border-radius: 6px; margin: 5px; cursor: pointer;'>🏢 Day 3: Multi-Tenant</button><br>";
echo "<button onclick=\"alert('เริ่ม Day 4: Data Migration')\" style='background: #fd7e14; color: white; padding: 12px 24px; border: none; border-radius: 6px; margin: 5px; cursor: pointer;'>🔄 Day 4: Migration</button><br>";
echo "<button onclick=\"alert('สร้างทั้งหมดในครั้งเดียว!')\" style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 8px; margin: 10px; cursor: pointer; font-weight: bold;'>🚀 All-in-One Setup</button>";
echo "</div>";

echo "<h3>💡 คำแนะนำ:</h3>";
echo "<ul>";
echo "<li><strong>All-in-One Setup:</strong> ผมจะสร้างทุกอย่างในครั้งเดียว รวมถึง migration script</li>";
echo "<li><strong>Step-by-Step:</strong> พัฒนาทีละขั้นตอน testing ระหว่างทาง</li>";
echo "<li><strong>Custom:</strong> เลือกส่วนที่ต้องการพัฒนาเฉพาะ</li>";
echo "</ul>";

echo "<p><strong>แค่บอกว่าต้องการแบบไหน ผมจะเริ่มสร้างให้เลยครับ! 🚀</strong></p>";
?>