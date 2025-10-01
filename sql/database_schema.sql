-- TikTok Live Host Agency Database Schema
-- Compatible with Laragon/HeidiSQL

-- Create database
CREATE DATABASE IF NOT EXISTS `tiktok_live_host` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tiktok_live_host`;

-- Users table
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `role` enum('admin', 'live_seller') NOT NULL DEFAULT 'live_seller',
    `full_name` varchar(100) NOT NULL,
    `profile_image` varchar(255) DEFAULT NULL,
    `status` enum('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_username` (`username`),
    KEY `idx_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table for security
CREATE TABLE `user_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `session_token` varchar(255) NOT NULL UNIQUE,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `expires_at` timestamp NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_token` (`session_token`),
    KEY `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Live streams table (for future use)
CREATE TABLE `live_streams` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `seller_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `stream_key` varchar(255) NOT NULL UNIQUE,
    `status` enum('scheduled', 'live', 'ended', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `scheduled_at` timestamp NULL DEFAULT NULL,
    `started_at` timestamp NULL DEFAULT NULL,
    `ended_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_status` (`status`),
    KEY `idx_scheduled_at` (`scheduled_at`),
    FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE `activity_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance time slots table
CREATE TABLE `attendance_time_slots` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `duration_hours` decimal(3,1) NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_duration` (`duration_hours`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seller attendance records table
CREATE TABLE `seller_attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `seller_id` int(11) NOT NULL,
    `attendance_date` date NOT NULL,
    `time_slot_id` int(11) NOT NULL,
    `check_in_time` time DEFAULT NULL,
    `check_out_time` time DEFAULT NULL,
    `actual_hours` decimal(3,1) DEFAULT NULL,
    `status` enum('scheduled', 'checked_in', 'completed', 'missed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_seller_date_slot` (`seller_id`, `attendance_date`, `time_slot_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_attendance_date` (`attendance_date`),
    KEY `idx_time_slot_id` (`time_slot_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`time_slot_id`) REFERENCES `attendance_time_slots`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table for live host sales
CREATE TABLE `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `category` varchar(100) DEFAULT NULL,
    `sku` varchar(100) UNIQUE DEFAULT NULL,
    `stock_quantity` int(11) DEFAULT 0,
    `status` enum('active', 'inactive', 'discontinued') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Live host sales table
CREATE TABLE `live_host_sales` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `seller_id` int(11) NOT NULL,
    `stream_id` int(11) DEFAULT NULL,
    `product_id` int(11) DEFAULT NULL,
    `product_name` varchar(255) NOT NULL,
    `quantity` int(11) NOT NULL DEFAULT 1,
    `unit_price` decimal(10,2) NOT NULL,
    `total_amount` decimal(10,2) NOT NULL,
    `commission_rate` decimal(5,2) DEFAULT 10.00,
    `commission_amount` decimal(10,2) DEFAULT NULL,
    `sale_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` enum('pending', 'confirmed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `customer_info` json DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_stream_id` (`stream_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_sale_date` (`sale_date`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`stream_id`) REFERENCES `live_streams`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Live host daily summaries table
CREATE TABLE `live_host_daily_summary` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `seller_id` int(11) NOT NULL,
    `summary_date` date NOT NULL,
    `total_sales` decimal(10,2) DEFAULT 0.00,
    `total_items_sold` int(11) DEFAULT 0,
    `total_commission` decimal(10,2) DEFAULT 0.00,
    `hours_worked` decimal(3,1) DEFAULT 0.0,
    `streams_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_seller_date` (`seller_id`, `summary_date`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_summary_date` (`summary_date`),
    FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `full_name`, `status`) VALUES
('admin', 'admin@tiktok-live-host.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'active');

-- Insert sample live sellers (password: seller123)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `full_name`, `status`) VALUES
('seller1', 'seller1@tiktok-live-host.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'live_seller', 'Jane Doe', 'active'),
('seller2', 'seller2@tiktok-live-host.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'live_seller', 'John Smith', 'active'),
('demo_seller', 'demo@tiktok-live-host.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'live_seller', 'Demo Seller', 'active');

-- Insert default attendance time slots
INSERT INTO `attendance_time_slots` (`name`, `duration_hours`, `start_time`, `end_time`, `is_active`) VALUES
('Morning Shift (3hrs)', 3.0, '07:00:00', '10:00:00', 1),
('Late Morning (4hrs)', 4.0, '08:00:00', '12:00:00', 1),
('Afternoon Shift (3hrs)', 3.0, '13:00:00', '16:00:00', 1),
('Evening Shift (4hrs)', 4.0, '16:00:00', '20:00:00', 1),
('Night Shift (3hrs)', 3.0, '20:00:00', '23:00:00', 1),
('Extended Day (6hrs)', 6.0, '10:00:00', '16:00:00', 1),
('Split Shift AM (2hrs)', 2.0, '07:00:00', '09:00:00', 1),
('Split Shift PM (2hrs)', 2.0, '18:00:00', '20:00:00', 1);

-- Insert sample products
INSERT INTO `products` (`name`, `description`, `price`, `category`, `sku`, `stock_quantity`, `status`) VALUES
('Wireless Bluetooth Headphones', 'High-quality wireless headphones with noise cancellation', 89.99, 'Electronics', 'WBH001', 50, 'active'),
('Smartphone Case', 'Protective case for smartphones with multiple colors', 19.99, 'Accessories', 'SPC001', 100, 'active'),
('LED Desk Lamp', 'Adjustable LED desk lamp with USB charging port', 45.99, 'Home & Office', 'LDL001', 30, 'active'),
('Fitness Tracker', 'Smart fitness tracker with heart rate monitor', 129.99, 'Health & Fitness', 'FT001', 25, 'active'),
('Portable Power Bank', '10000mAh portable charger with fast charging', 34.99, 'Electronics', 'PPB001', 75, 'active'),
('Skincare Set', 'Complete skincare routine set for all skin types', 79.99, 'Beauty', 'SKS001', 40, 'active'),
('Coffee Tumbler', 'Insulated travel coffee tumbler 16oz', 24.99, 'Kitchen', 'CT001', 60, 'active'),
('Yoga Mat', 'Non-slip exercise yoga mat with carrying strap', 39.99, 'Health & Fitness', 'YM001', 35, 'active');

-- Insert sample sales data for live hosts
INSERT INTO `live_host_sales` (`seller_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_amount`, `commission_rate`, `commission_amount`, `sale_date`, `status`) VALUES
(2, 1, 'Wireless Bluetooth Headphones', 2, 89.99, 179.98, 15.00, 26.99, '2025-10-01 14:30:00', 'confirmed'),
(2, 2, 'Smartphone Case', 5, 19.99, 99.95, 10.00, 9.99, '2025-10-01 15:15:00', 'confirmed'),
(2, 3, 'LED Desk Lamp', 1, 45.99, 45.99, 12.00, 5.52, '2025-10-01 16:45:00', 'confirmed'),
(3, 4, 'Fitness Tracker', 3, 129.99, 389.97, 20.00, 77.99, '2025-10-01 19:20:00', 'confirmed'),
(3, 5, 'Portable Power Bank', 4, 34.99, 139.96, 10.00, 13.99, '2025-10-01 20:10:00', 'confirmed'),
(2, 6, 'Skincare Set', 2, 79.99, 159.98, 15.00, 23.99, '2025-10-02 10:30:00', 'confirmed'),
(3, 7, 'Coffee Tumbler', 6, 24.99, 149.94, 8.00, 11.99, '2025-10-02 11:45:00', 'confirmed'),
(2, 8, 'Yoga Mat', 1, 39.99, 39.99, 10.00, 3.99, '2025-10-02 13:20:00', 'pending');

-- Insert sample daily summaries
INSERT INTO `live_host_daily_summary` (`seller_id`, `summary_date`, `total_sales`, `total_items_sold`, `total_commission`, `hours_worked`, `streams_count`) VALUES
(2, '2025-10-01', 325.92, 8, 40.50, 6.0, 2),
(3, '2025-10-01', 529.93, 7, 91.98, 4.0, 1),
(2, '2025-10-02', 199.97, 3, 27.98, 3.0, 1),
(3, '2025-10-02', 149.94, 6, 11.99, 2.0, 1);