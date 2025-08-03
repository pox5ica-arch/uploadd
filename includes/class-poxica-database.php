<?php
/**
 * Database operations for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Database {
    
    /**
     * Check if HPOS (High-Performance Order Storage) is enabled
     */
    private static function is_hpos_enabled() {
        if (function_exists('wc_get_container')) {
            try {
                return wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled();
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
    
    /**
     * Create plugin tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Orders table - stores order information for image uploads
        $orders_table = $wpdb->prefix . 'poxica_orders';
        $orders_sql = "CREATE TABLE $orders_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NOT NULL,
            upload_token varchar(255) NOT NULL,
            drive_folder_id varchar(255) DEFAULT NULL,
            status enum('pending', 'uploading', 'completed', 'cancelled', 'expired') DEFAULT 'pending',
            token_expires datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_id (order_id),
            UNIQUE KEY upload_token (upload_token),
            KEY status (status),
            KEY token_expires (token_expires)
        ) $charset_collate;";
        
        // Products table - stores product information for each order
        $products_table = $wpdb->prefix . 'poxica_order_products';
        $products_sql = "CREATE TABLE $products_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            poxica_order_id int(11) NOT NULL,
            product_id int(11) NOT NULL,
            variation_id int(11) DEFAULT NULL,
            product_name varchar(255) NOT NULL,
            variation_details text DEFAULT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            drive_folder_id varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY poxica_order_id (poxica_order_id),
            KEY product_id (product_id),
            FOREIGN KEY (poxica_order_id) REFERENCES $orders_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Images table - stores uploaded image information
        $images_table = $wpdb->prefix . 'poxica_uploaded_images';
        $images_sql = "CREATE TABLE $images_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            poxica_product_id int(11) NOT NULL,
            unit_number int(11) NOT NULL DEFAULT 1,
            original_filename varchar(255) NOT NULL,
            drive_file_id varchar(255) NOT NULL,
            drive_folder_id varchar(255) NOT NULL,
            file_size int(11) NOT NULL,
            mime_type varchar(100) NOT NULL,
            upload_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY poxica_product_id (poxica_product_id),
            KEY unit_number (unit_number),
            UNIQUE KEY unique_product_unit (poxica_product_id, unit_number),
            FOREIGN KEY (poxica_product_id) REFERENCES $products_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Logs table - stores activity logs
        $logs_table = $wpdb->prefix . 'poxica_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) DEFAULT NULL,
            action varchar(100) NOT NULL,
            message text NOT NULL,
            data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($orders_sql);
        dbDelta($products_sql);
        dbDelta($images_sql);
        dbDelta($logs_sql);
        
        // Update database version
        update_option('poxica_db_version', POXICA_DB_VERSION);
    }
    
    /**
     * Get order data by order ID
     */
    public static function get_order_by_id($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_orders';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d",
            $order_id
        ));
    }
    
    /**
     * Get order data by upload token
     */
    public static function get_order_by_token($token) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_orders';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE upload_token = %s AND (token_expires IS NULL OR token_expires > NOW())",
            $token
        ));
    }
    
    /**
     * Create new order record
     */
    public static function create_order($order_id, $token, $expires = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_orders';
        
        if ($expires === null) {
            $expiry_days = get_option('poxica_upload_link_expiry_days', 7);
            $expires = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        }
        
        return $wpdb->insert($table, [
            'order_id' => $order_id,
            'upload_token' => $token,
            'token_expires' => $expires,
            'status' => 'pending'
        ]);
    }
    
    /**
     * Update order status
     */
    public static function update_order_status($order_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_orders';
        
        return $wpdb->update(
            $table,
            ['status' => $status],
            ['order_id' => $order_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Set order Drive folder ID
     */
    public static function set_order_drive_folder($order_id, $folder_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_orders';
        
        return $wpdb->update(
            $table,
            ['drive_folder_id' => $folder_id],
            ['order_id' => $order_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Add product to order
     */
    public static function add_order_product($poxica_order_id, $product_id, $variation_id, $product_name, $variation_details, $quantity) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_order_products';
        
        return $wpdb->insert($table, [
            'poxica_order_id' => $poxica_order_id,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'product_name' => $product_name,
            'variation_details' => $variation_details,
            'quantity' => $quantity
        ]);
    }
    
    /**
     * Get products for an order
     */
    public static function get_order_products($poxica_order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_order_products';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE poxica_order_id = %d ORDER BY id",
            $poxica_order_id
        ));
    }
    
    /**
     * Set product Drive folder ID
     */
    public static function set_product_drive_folder($product_record_id, $folder_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_order_products';
        
        return $wpdb->update(
            $table,
            ['drive_folder_id' => $folder_id],
            ['id' => $product_record_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Add uploaded image record
     */
    public static function add_uploaded_image($product_record_id, $unit_number, $filename, $drive_file_id, $drive_folder_id, $file_size, $mime_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_uploaded_images';
        
        return $wpdb->insert($table, [
            'poxica_product_id' => $product_record_id,
            'unit_number' => $unit_number,
            'original_filename' => $filename,
            'drive_file_id' => $drive_file_id,
            'drive_folder_id' => $drive_folder_id,
            'file_size' => $file_size,
            'mime_type' => $mime_type
        ]);
    }
    
    /**
     * Get uploaded images for a product
     */
    public static function get_product_images($product_record_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_uploaded_images';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE poxica_product_id = %d ORDER BY unit_number",
            $product_record_id
        ));
    }
    
    /**
     * Check if unit has uploaded image
     */
    public static function unit_has_image($product_record_id, $unit_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_uploaded_images';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE poxica_product_id = %d AND unit_number = %d",
            $product_record_id,
            $unit_number
        ));
        
        return $count > 0;
    }
    
    /**
     * Get orders for cleanup (unpaid orders older than specified days)
     */
    public static function get_orders_for_cleanup($days = 3) {
        global $wpdb;
        $orders_table = $wpdb->prefix . 'poxica_orders';
        
        // Check if HPOS is enabled
        if (self::is_hpos_enabled()) {
            // HPOS is enabled, use the new order tables
            $hpos_orders_table = $wpdb->prefix . 'wc_orders';
            return $wpdb->get_results($wpdb->prepare(
                "SELECT po.*, wo.status as order_status 
                 FROM $orders_table po 
                 LEFT JOIN $hpos_orders_table wo ON po.order_id = wo.id 
                 WHERE po.created_at < DATE_SUB(NOW(), INTERVAL %d DAY) 
                 AND po.status != 'completed' 
                 AND po.drive_folder_id IS NOT NULL 
                 AND (wo.status NOT IN ('wc-completed', 'wc-processing') OR wo.status IS NULL)",
                $days
            ));
        } else {
            // HPOS is disabled, use traditional posts table
            return $wpdb->get_results($wpdb->prepare(
                "SELECT po.*, p.post_status as order_status
                 FROM $orders_table po 
                 LEFT JOIN {$wpdb->posts} p ON po.order_id = p.ID 
                 WHERE po.created_at < DATE_SUB(NOW(), INTERVAL %d DAY) 
                 AND po.status != 'completed' 
                 AND po.drive_folder_id IS NOT NULL 
                 AND (p.post_status NOT IN ('wc-completed', 'wc-processing') OR p.post_status IS NULL)",
                $days
            ));
        }
    }
    
    /**
     * Get cancelled orders for immediate cleanup
     */
    public static function get_cancelled_orders() {
        global $wpdb;
        $orders_table = $wpdb->prefix . 'poxica_orders';
        
        // Check if HPOS is enabled
        if (self::is_hpos_enabled()) {
            // HPOS is enabled, use the new order tables
            $hpos_orders_table = $wpdb->prefix . 'wc_orders';
            return $wpdb->get_results(
                "SELECT po.* 
                 FROM $orders_table po 
                 LEFT JOIN $hpos_orders_table wo ON po.order_id = wo.id 
                 WHERE wo.status = 'wc-cancelled' 
                 AND po.drive_folder_id IS NOT NULL 
                 AND po.status != 'cancelled'"
            );
        } else {
            // HPOS is disabled, use traditional posts table
            return $wpdb->get_results(
                "SELECT po.* 
                 FROM $orders_table po 
                 LEFT JOIN {$wpdb->posts} p ON po.order_id = p.ID 
                 WHERE p.post_status = 'wc-cancelled' 
                 AND po.drive_folder_id IS NOT NULL 
                 AND po.status != 'cancelled'"
            );
        }
    }
    
    /**
     * Add log entry
     */
    public static function add_log($order_id, $action, $message, $data = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_logs';
        
        return $wpdb->insert($table, [
            'order_id' => $order_id,
            'action' => $action,
            'message' => $message,
            'data' => $data ? wp_json_encode($data) : null
        ]);
    }
    
    /**
     * Get logs for admin panel
     */
    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }
    
    /**
     * Check if all images are uploaded for an order
     */
    public static function order_images_complete($poxica_order_id) {
        global $wpdb;
        $products_table = $wpdb->prefix . 'poxica_order_products';
        $images_table = $wpdb->prefix . 'poxica_uploaded_images';
        
        // Get total quantity needed
        $total_needed = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(quantity) FROM $products_table WHERE poxica_order_id = %d",
            $poxica_order_id
        ));
        
        // Get total images uploaded
        $total_uploaded = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(i.id) 
             FROM $images_table i 
             INNER JOIN $products_table p ON i.poxica_product_id = p.id 
             WHERE p.poxica_order_id = %d",
            $poxica_order_id
        ));
        
        return $total_needed == $total_uploaded;
    }
}