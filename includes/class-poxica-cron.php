<?php
/**
 * Cron jobs for Poxica Image Uploader
 * Handles automated cleanup and maintenance tasks
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Cron {
    
    private $google_drive;
    
    public function __construct($google_drive) {
        $this->google_drive = $google_drive;
        $this->init_hooks();
    }
    
    /**
     * Initialize cron hooks
     */
    private function init_hooks() {
        // Hook for the daily cleanup
        add_action('poxica_daily_cleanup', [$this, 'run_cleanup']);
        
        // Hook for immediate cleanup of cancelled orders
        add_action('woocommerce_order_status_cancelled', [$this, 'cleanup_cancelled_order'], 20, 1);
    }
    
    /**
     * Run daily cleanup tasks
     */
    public function run_cleanup() {
        try {
            $this->cleanup_unpaid_orders();
            $this->cleanup_cancelled_orders();
            $this->cleanup_expired_tokens();
            $this->cleanup_temp_files();
            
            Poxica_Database::add_log(null, 'daily_cleanup_completed', 'Limpieza diaria completada exitosamente');
            
        } catch (Exception $e) {
            Poxica_Database::add_log(null, 'daily_cleanup_failed', 'Error en limpieza diaria: ' . $e->getMessage());
            error_log('Poxica Daily Cleanup Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up unpaid orders older than configured days
     */
    private function cleanup_unpaid_orders() {
        if (!$this->google_drive->is_configured()) {
            return;
        }
        
        $cleanup_days = get_option('poxica_unpaid_order_cleanup_days', 3);
        $orders_to_cleanup = Poxica_Database::get_orders_for_cleanup($cleanup_days);
        
        $cleaned_count = 0;
        
        foreach ($orders_to_cleanup as $poxica_order) {
            try {
                // Delete folder from Google Drive
                $this->google_drive->delete_folder($poxica_order->drive_folder_id);
                
                // Update order status
                Poxica_Database::update_order_status($poxica_order->order_id, 'expired');
                
                $cleaned_count++;
                
                Poxica_Database::add_log($poxica_order->order_id, 'unpaid_cleanup', "Pedido no pagado limpiado después de $cleanup_days días");
                
            } catch (Exception $e) {
                Poxica_Database::add_log($poxica_order->order_id, 'unpaid_cleanup_failed', 'Error limpiando pedido no pagado: ' . $e->getMessage());
                error_log("Poxica Unpaid Cleanup Error for order {$poxica_order->order_id}: " . $e->getMessage());
            }
        }
        
        if ($cleaned_count > 0) {
            Poxica_Database::add_log(null, 'unpaid_cleanup_summary', "Limpiados $cleaned_count pedidos no pagados");
        }
    }
    
    /**
     * Clean up cancelled orders
     */
    private function cleanup_cancelled_orders() {
        if (!$this->google_drive->is_configured()) {
            return;
        }
        
        $cancelled_orders = Poxica_Database::get_cancelled_orders();
        
        $cleaned_count = 0;
        
        foreach ($cancelled_orders as $poxica_order) {
            try {
                // Delete folder from Google Drive
                $this->google_drive->delete_folder($poxica_order->drive_folder_id);
                
                // Update order status
                Poxica_Database::update_order_status($poxica_order->order_id, 'cancelled');
                
                $cleaned_count++;
                
                Poxica_Database::add_log($poxica_order->order_id, 'cancelled_cleanup', "Pedido cancelado limpiado");
                
            } catch (Exception $e) {
                Poxica_Database::add_log($poxica_order->order_id, 'cancelled_cleanup_failed', 'Error limpiando pedido cancelado: ' . $e->getMessage());
                error_log("Poxica Cancelled Cleanup Error for order {$poxica_order->order_id}: " . $e->getMessage());
            }
        }
        
        if ($cleaned_count > 0) {
            Poxica_Database::add_log(null, 'cancelled_cleanup_summary', "Limpiados $cleaned_count pedidos cancelados");
        }
    }
    
    /**
     * Clean up expired upload tokens
     */
    private function cleanup_expired_tokens() {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_orders';
        
        // Update expired tokens
        $updated = $wpdb->query(
            "UPDATE $table 
             SET status = 'expired' 
             WHERE token_expires < NOW() 
             AND status NOT IN ('completed', 'cancelled', 'expired')"
        );
        
        if ($updated > 0) {
            Poxica_Database::add_log(null, 'token_cleanup', "Marcados como expirados $updated tokens de subida");
        }
    }
    
    /**
     * Clean up temporary files
     */
    private function cleanup_temp_files() {
        $upload_handler = new Poxica_Upload_Handler($this->google_drive);
        $upload_handler->cleanup_temp_files();
        
        // Also clean up old log entries (keep only last 1000)
        $this->cleanup_old_logs();
    }
    
    /**
     * Clean up old log entries
     */
    private function cleanup_old_logs() {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_logs';
        
        // Keep only the latest 1000 log entries
        $wpdb->query(
            "DELETE FROM $table 
             WHERE id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM $table 
                     ORDER BY created_at DESC 
                     LIMIT 1000
                 ) AS keep_logs
             )"
        );
    }
    
    /**
     * Immediate cleanup for cancelled order
     */
    public function cleanup_cancelled_order($order_id) {
        if (!$this->google_drive->is_configured()) {
            return;
        }
        
        try {
            $poxica_order = Poxica_Database::get_order_by_id($order_id);
            
            if ($poxica_order && $poxica_order->drive_folder_id) {
                // Delete folder from Google Drive immediately
                $this->google_drive->delete_folder($poxica_order->drive_folder_id);
                
                // Update order status
                Poxica_Database::update_order_status($order_id, 'cancelled');
                
                Poxica_Database::add_log($order_id, 'immediate_cancelled_cleanup', "Pedido cancelado, carpeta eliminada inmediatamente");
            }
            
        } catch (Exception $e) {
            Poxica_Database::add_log($order_id, 'immediate_cancelled_cleanup_failed', 'Error en limpieza inmediata de pedido cancelado: ' . $e->getMessage());
            error_log("Poxica Immediate Cancelled Cleanup Error for order $order_id: " . $e->getMessage());
        }
    }
    
    /**
     * Manual cleanup trigger (for admin interface)
     */
    public function manual_cleanup() {
        $results = [
            'success' => true,
            'message' => '',
            'cleaned' => [
                'unpaid' => 0,
                'cancelled' => 0,
                'expired_tokens' => 0
            ]
        ];
        
        try {
            // Count before cleanup
            $unpaid_count = count(Poxica_Database::get_orders_for_cleanup());
            $cancelled_count = count(Poxica_Database::get_cancelled_orders());
            
            // Run cleanup
            $this->cleanup_unpaid_orders();
            $this->cleanup_cancelled_orders();
            $this->cleanup_expired_tokens();
            $this->cleanup_temp_files();
            
            // Count after cleanup
            $unpaid_remaining = count(Poxica_Database::get_orders_for_cleanup());
            $cancelled_remaining = count(Poxica_Database::get_cancelled_orders());
            
            $results['cleaned']['unpaid'] = $unpaid_count - $unpaid_remaining;
            $results['cleaned']['cancelled'] = $cancelled_count - $cancelled_remaining;
            
            $total_cleaned = $results['cleaned']['unpaid'] + $results['cleaned']['cancelled'];
            
            $results['message'] = sprintf(
                __('Limpieza completada. Eliminados: %d pedidos no pagados, %d pedidos cancelados.', 'poxica-image-uploader'),
                $results['cleaned']['unpaid'],
                $results['cleaned']['cancelled']
            );
            
            Poxica_Database::add_log(null, 'manual_cleanup', $results['message']);
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['message'] = __('Error durante la limpieza: ', 'poxica-image-uploader') . $e->getMessage();
            
            Poxica_Database::add_log(null, 'manual_cleanup_failed', $results['message']);
        }
        
        return $results;
    }
    
    /**
     * Get cleanup statistics
     */
    public function get_cleanup_stats() {
        $unpaid_orders = Poxica_Database::get_orders_for_cleanup();
        $cancelled_orders = Poxica_Database::get_cancelled_orders();
        
        // Get expired tokens count
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_orders';
        $expired_tokens = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table 
             WHERE token_expires < NOW() 
             AND status NOT IN ('completed', 'cancelled', 'expired')"
        );
        
        return [
            'unpaid_orders' => count($unpaid_orders),
            'cancelled_orders' => count($cancelled_orders),
            'expired_tokens' => (int) $expired_tokens,
            'next_cleanup' => wp_next_scheduled('poxica_daily_cleanup')
        ];
    }
    
    /**
     * Check if cron is working properly
     */
    public function is_cron_working() {
        $last_run = get_option('poxica_last_cron_run', 0);
        $current_time = time();
        
        // If last run was more than 25 hours ago, cron might be broken
        return ($current_time - $last_run) < (25 * 3600);
    }
    
    /**
     * Force schedule next cron run
     */
    public function reschedule_cron() {
        wp_clear_scheduled_hook('poxica_daily_cleanup');
        wp_schedule_event(time(), 'daily', 'poxica_daily_cleanup');
        
        return wp_next_scheduled('poxica_daily_cleanup');
    }
}