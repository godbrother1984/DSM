-- =============================================================================
-- DIGITAL SIGNAGE SYSTEM - DATABASE SCHEMA (FIXED VERSION)
-- =============================================================================
-- File: sql/schema.sql
-- Description: Complete MySQL database schema for Digital Signage System
-- Author: Digital Signage Team
-- Version: 1.0.1 (Fixed for MariaDB compatibility)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `role` enum('admin','manager','editor','viewer') DEFAULT 'viewer',
    `avatar` varchar(500) NULL,
    `is_active` boolean DEFAULT TRUE,
    `last_login_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
);

-- Content table
CREATE TABLE IF NOT EXISTS `content` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text NULL,
    `type` enum('video','image','audio','html','widget','dashboard','text') NOT NULL,
    `file_path` varchar(500) NULL,
    `file_url` varchar(500) NULL,
    `thumbnail_path` varchar(500) NULL,
    `file_size` bigint NULL COMMENT 'File size in bytes',
    `mime_type` varchar(100) NULL,
    `duration` int NULL COMMENT 'Duration in seconds',
    `width` int NULL,
    `height` int NULL,
    `metadata` json NULL,
    `tags` json NULL,
    `status` enum('active','inactive','processing','error') DEFAULT 'active',
    `expires_at` timestamp NULL,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_content_type` (`type`),
    INDEX `idx_content_status` (`status`),
    INDEX `idx_content_created_by` (`created_by`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Layouts table
CREATE TABLE IF NOT EXISTS `layouts` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `type` enum('grid','rotation','multi-zone','interactive','fullscreen') NOT NULL,
    `template` varchar(100) NOT NULL,
    `orientation` enum('landscape','portrait','auto') DEFAULT 'landscape',
    `resolution` varchar(20) DEFAULT '1920x1080',
    `zones` json NOT NULL,
    `settings` json NULL,
    `is_default` boolean DEFAULT FALSE,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_layouts_type` (`type`),
    INDEX `idx_layouts_is_default` (`is_default`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Playlists table
CREATE TABLE IF NOT EXISTS `playlists` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `layout_id` bigint unsigned NULL,
    `total_duration` int DEFAULT 0,
    `loop_count` int DEFAULT 0,
    `shuffle` boolean DEFAULT FALSE,
    `settings` json NULL,
    `is_active` boolean DEFAULT TRUE,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_playlists_layout_id` (`layout_id`),
    INDEX `idx_playlists_is_active` (`is_active`),
    FOREIGN KEY (`layout_id`) REFERENCES `layouts` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Playlist items table
CREATE TABLE IF NOT EXISTS `playlist_items` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `playlist_id` bigint unsigned NOT NULL,
    `content_id` bigint unsigned NOT NULL,
    `zone_id` varchar(50) DEFAULT 'main',
    `order_index` int DEFAULT 0,
    `duration` int NULL,
    `transition_type` enum('none','fade','slide','zoom','flip') DEFAULT 'fade',
    `transition_duration` int DEFAULT 1000,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_playlist_items_playlist_id` (`playlist_id`),
    INDEX `idx_playlist_items_order` (`playlist_id`, `order_index`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE
);

-- Devices table
CREATE TABLE IF NOT EXISTS `devices` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `device_id` varchar(255) NOT NULL UNIQUE,
    `name` varchar(255) NOT NULL,
    `description` text NULL,
    `location` varchar(255) NULL,
    `device_type` enum('desktop','tablet','mobile','smart_tv','display','kiosk') DEFAULT 'desktop',
    `os` varchar(100) NULL,
    `browser` varchar(100) NULL,
    `screen_width` int NULL,
    `screen_height` int NULL,
    `orientation` enum('landscape','portrait','auto') DEFAULT 'landscape',
    `current_playlist_id` bigint unsigned NULL,
    `current_layout_id` bigint unsigned NULL,
    `status` enum('online','offline','error','maintenance') DEFAULT 'offline',
    `last_seen` timestamp NULL,
    `last_heartbeat` timestamp NULL,
    `ip_address` varchar(45) NULL,
    `settings` json NULL,
    `api_key` varchar(64) NOT NULL UNIQUE,
    `is_active` boolean DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_devices_device_id` (`device_id`),
    INDEX `idx_devices_status` (`status`),
    INDEX `idx_devices_last_seen` (`last_seen`),
    FOREIGN KEY (`current_playlist_id`) REFERENCES `playlists` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`current_layout_id`) REFERENCES `layouts` (`id`) ON DELETE SET NULL
);

-- Device logs table
CREATE TABLE IF NOT EXISTS `device_logs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `device_id` bigint unsigned NOT NULL,
    `level` enum('info','warning','error','debug') NOT NULL,
    `message` text NOT NULL,
    `context` json NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_device_logs_device_id` (`device_id`),
    INDEX `idx_device_logs_level` (`level`),
    INDEX `idx_device_logs_created_at` (`created_at`),
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
);

-- Content analytics table
CREATE TABLE IF NOT EXISTS `content_analytics` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `device_id` bigint unsigned NOT NULL,
    `content_id` bigint unsigned NOT NULL,
    `playlist_id` bigint unsigned NULL,
    `event_type` enum('start','end','skip','error','interaction') NOT NULL,
    `duration_watched` int NULL,
    `interaction_data` json NULL,
    `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_content_analytics_device_id` (`device_id`),
    INDEX `idx_content_analytics_content_id` (`content_id`),
    INDEX `idx_content_analytics_timestamp` (`timestamp`),
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`content_id`) REFERENCES `content` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE SET NULL
);

-- System settings table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `key` varchar(255) NOT NULL UNIQUE,
    `value` text NULL,
    `type` enum('string','integer','boolean','json') DEFAULT 'string',
    `description` text NULL,
    `is_public` boolean DEFAULT FALSE,
    `updated_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_system_settings_key` (`key`),
    FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Schedules table
CREATE TABLE IF NOT EXISTS `schedules` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `playlist_id` bigint unsigned NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `days_of_week` json NOT NULL,
    `timezone` varchar(50) DEFAULT 'Asia/Bangkok',
    `priority` int DEFAULT 1,
    `is_active` boolean DEFAULT TRUE,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_schedules_playlist_id` (`playlist_id`),
    INDEX `idx_schedules_is_active` (`is_active`),
    FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- API tokens table
CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NOT NULL,
    `token` varchar(64) NOT NULL UNIQUE,
    `name` varchar(255) NOT NULL,
    `abilities` json NULL,
    `last_used_at` timestamp NULL,
    `expires_at` timestamp NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_api_tokens_token` (`token`),
    INDEX `idx_api_tokens_user_id` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

-- Dashboard integrations table
CREATE TABLE IF NOT EXISTS `dashboard_integrations` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `dashboard_url` varchar(500) NOT NULL,
    `integration_method` enum('iframe','api','screenshot') NOT NULL,
    `refresh_interval` int DEFAULT 60,
    `auth_required` boolean DEFAULT FALSE,
    `auth_token` text NULL,
    `settings` json NULL,
    `last_screenshot_path` varchar(500) NULL,
    `last_updated` timestamp NULL,
    `is_active` boolean DEFAULT TRUE,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_dashboard_integrations_method` (`integration_method`),
    INDEX `idx_dashboard_integrations_is_active` (`is_active`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- Widgets table
CREATE TABLE IF NOT EXISTS `widgets` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `type` varchar(100) NOT NULL,
    `component` varchar(100) NOT NULL,
    `config` json NOT NULL,
    `refresh_interval` int DEFAULT 300,
    `is_active` boolean DEFAULT TRUE,
    `created_by` bigint unsigned NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_widgets_type` (`type`),
    INDEX `idx_widgets_is_active` (`is_active`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

-- User activities table (for logging)
CREATE TABLE IF NOT EXISTS `user_activities` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint unsigned NULL,
    `action` varchar(255) NOT NULL,
    `description` text NULL,
    `ip_address` varchar(45) NULL,
    `user_agent` text NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_activities_user_id` (`user_id`),
    INDEX `idx_user_activities_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
);

SET FOREIGN_KEY_CHECKS = 1;