-- Poxica Image Uploader - Installation SQL Script
-- Run this script only if automatic installation fails
-- Replace 'wp_' with your WordPress database prefix if different

-- Orders table - stores order information for image uploads
CREATE TABLE `wp_poxica_orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `upload_token` varchar(255) NOT NULL,
    `drive_folder_id` varchar(255) DEFAULT NULL,
    `status` enum('pending', 'uploading', 'completed', 'cancelled', 'expired') DEFAULT 'pending',
    `token_expires` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_id` (`order_id`),
    UNIQUE KEY `upload_token` (`upload_token`),
    KEY `status` (`status`),
    KEY `token_expires` (`token_expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table - stores product information for each order
CREATE TABLE `wp_poxica_order_products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `poxica_order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `variation_id` int(11) DEFAULT NULL,
    `product_name` varchar(255) NOT NULL,
    `variation_details` text DEFAULT NULL,
    `quantity` int(11) NOT NULL DEFAULT 1,
    `drive_folder_id` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `poxica_order_id` (`poxica_order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `fk_poxica_order_products_order` 
        FOREIGN KEY (`poxica_order_id`) 
        REFERENCES `wp_poxica_orders` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Images table - stores uploaded image information
CREATE TABLE `wp_poxica_uploaded_images` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `poxica_product_id` int(11) NOT NULL,
    `unit_number` int(11) NOT NULL DEFAULT 1,
    `original_filename` varchar(255) NOT NULL,
    `drive_file_id` varchar(255) NOT NULL,
    `drive_folder_id` varchar(255) NOT NULL,
    `file_size` int(11) NOT NULL,
    `mime_type` varchar(100) NOT NULL,
    `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `poxica_product_id` (`poxica_product_id`),
    KEY `unit_number` (`unit_number`),
    UNIQUE KEY `unique_product_unit` (`poxica_product_id`, `unit_number`),
    CONSTRAINT `fk_poxica_uploaded_images_product` 
        FOREIGN KEY (`poxica_product_id`) 
        REFERENCES `wp_poxica_order_products` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs table - stores activity logs
CREATE TABLE `wp_poxica_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `message` text NOT NULL,
    `data` longtext DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default options
INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('poxica_db_version', '1.0', 'yes'),
('poxica_unpaid_order_cleanup_days', '3', 'yes'),
('poxica_upload_link_expiry_days', '7', 'yes'),
('poxica_max_file_size', '10485760', 'yes'),
('poxica_allowed_file_types', 'jpg,jpeg,png', 'yes'),
('poxica_email_from_name', '', 'yes'),
('poxica_email_from_email', '', 'yes'),
('poxica_admin_notification_emails', '', 'yes'),
('poxica_email_upload_link_subject', 'Sube las imágenes para tu pedido #{order_number}', 'yes'),
('poxica_email_completion_subject', '[{site_name}] Pedido #{order_number} - Todas las imágenes subidas', 'yes'),
('poxica_email_upload_link_message', 'Hola {customer_name},\n\n¡Gracias por tu pedido #{order_number}!\n\nPara completar tu pedido, necesitamos que subas las imágenes correspondientes a los productos que has comprado.\n\n**Enlace para subir imágenes:**\n{upload_url}\n\n**Detalles del pedido:**\n- Número de pedido: #{order_number}\n- Fecha: {order_date}\n- Total: {order_total}\n\n**Instrucciones importantes:**\n1. Haz clic en el enlace de arriba para acceder a la página de subida\n2. Sube una imagen para cada producto/unidad que has comprado\n3. Solo se aceptan archivos JPG y PNG\n4. El enlace expirará en 7 días\n\nSi tienes alguna pregunta, no dudes en contactarnos.\n\nSaludos,\nEquipo de {site_name}\n{site_url}', 'no'),
('poxica_email_completion_message', 'Hola,\n\nEl cliente {customer_name} ha completado la subida de todas las imágenes para el pedido #{order_number}.\n\n**Detalles del pedido:**\n- Número de pedido: #{order_number}\n- Cliente: {customer_name}\n- Fecha del pedido: {order_date}\n- Total: {order_total}\n\n**Productos y archivos subidos:**\n{order_products}\n\n**Enlaces a Google Drive:**\n{drive_links}\n\nYa puedes proceder con la producción de este pedido.\n\nSaludos,\nSistema Poxica Image Uploader', 'no')
ON DUPLICATE KEY UPDATE option_value=VALUES(option_value);

-- Add WordPress rewrite rule flush option
INSERT INTO `wp_options` (`option_name`, `option_value`, `autoload`) VALUES
('poxica_flush_rewrite_rules', '1', 'no')
ON DUPLICATE KEY UPDATE option_value='1';

-- Create upload directory if it doesn't exist (requires appropriate permissions)
-- This would need to be done via PHP or manually:
-- mkdir -p wp-content/uploads/poxica-temp/
-- chmod 755 wp-content/uploads/poxica-temp/

-- Verification queries to check installation
-- Uncomment these to verify the installation:

/*
-- Check if tables were created successfully
SELECT 
    TABLE_NAME, 
    TABLE_ROWS, 
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE 'wp_poxica_%';

-- Check if options were inserted
SELECT option_name, option_value 
FROM wp_options 
WHERE option_name LIKE 'poxica_%' 
ORDER BY option_name;

-- Check foreign key constraints
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE CONSTRAINT_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'wp_poxica_%'
AND REFERENCED_TABLE_NAME IS NOT NULL;
*/