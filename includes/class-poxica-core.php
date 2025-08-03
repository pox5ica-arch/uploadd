<?php
/**
 * Core plugin class for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Core {
    
    private $google_drive;
    private $order_handler;
    private $upload_handler;
    private $email_notifications;
    private $admin;
    private $security;
    private $cron;
    
    public function __construct() {
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX hooks
        add_action('wp_ajax_poxica_upload_image', [$this, 'handle_ajax_upload']);
        add_action('wp_ajax_nopriv_poxica_upload_image', [$this, 'handle_ajax_upload']);
        add_action('wp_ajax_poxica_test_drive_connection', [$this, 'handle_test_drive_connection']);
        
        // Custom cron hook
        add_action('poxica_daily_cleanup', [$this, 'run_daily_cleanup']);
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->google_drive = new Poxica_Google_Drive();
        $this->order_handler = new Poxica_Order_Handler($this->google_drive);
        $this->upload_handler = new Poxica_Upload_Handler($this->google_drive);
        $this->email_notifications = new Poxica_Email_Notifications();
        $this->security = new Poxica_Security();
        $this->cron = new Poxica_Cron($this->google_drive);
        
        if (is_admin()) {
            $this->admin = new Poxica_Admin($this->google_drive);
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check database version and update if needed
        $this->check_database_version();
        
        // Flush rewrite rules if needed
        if (get_option('poxica_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('poxica_flush_rewrite_rules');
        }
    }
    
    /**
     * Check and update database version
     */
    private function check_database_version() {
        $current_version = get_option('poxica_db_version', '0');
        
        if (version_compare($current_version, POXICA_DB_VERSION, '<')) {
            Poxica_Database::create_tables();
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue on upload pages
        if (get_query_var('poxica_upload')) {
            wp_enqueue_script('poxica-dropzone', 'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js', [], '5.9.3', true);
            wp_enqueue_style('poxica-dropzone-css', 'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css', [], '5.9.3');
            
            wp_enqueue_script('poxica-upload', POXICA_PLUGIN_URL . 'assets/js/upload.js', ['jquery', 'poxica-dropzone'], POXICA_PLUGIN_VERSION, true);
            wp_enqueue_style('poxica-upload-css', POXICA_PLUGIN_URL . 'assets/css/upload.css', [], POXICA_PLUGIN_VERSION);
            
            // Localize script
            wp_localize_script('poxica-upload', 'poxica_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('poxica_upload_nonce'),
                'order_id' => get_query_var('order_id'),
                'token' => get_query_var('token'),
                'max_file_size' => get_option('poxica_max_file_size', 10485760),
                'allowed_types' => get_option('poxica_allowed_file_types', 'jpg,jpeg,png'),
                'strings' => [
                    'upload_success' => __('Imagen subida correctamente', 'poxica-image-uploader'),
                    'upload_error' => __('Error al subir la imagen', 'poxica-image-uploader'),
                    'invalid_file_type' => __('Tipo de archivo no permitido', 'poxica-image-uploader'),
                    'file_too_large' => __('El archivo es demasiado grande', 'poxica-image-uploader'),
                    'all_complete' => __('Todas las imágenes han sido subidas', 'poxica-image-uploader')
                ]
            ]);
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only on Poxica admin pages
        if (strpos($hook, 'poxica') !== false) {
            wp_enqueue_script('poxica-admin', POXICA_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], POXICA_PLUGIN_VERSION, true);
            wp_enqueue_style('poxica-admin-css', POXICA_PLUGIN_URL . 'assets/css/admin.css', [], POXICA_PLUGIN_VERSION);
            
            wp_localize_script('poxica-admin', 'poxica_admin_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('poxica_admin_nonce'),
                'strings' => [
                    'test_connection' => __('Probando conexión...', 'poxica-image-uploader'),
                    'connection_success' => __('Conexión exitosa', 'poxica-image-uploader'),
                    'connection_error' => __('Error de conexión', 'poxica-image-uploader')
                ]
            ]);
        }
    }
    
    /**
     * Handle AJAX image upload
     */
    public function handle_ajax_upload() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'poxica_upload_nonce')) {
            wp_die(__('Acceso denegado', 'poxica-image-uploader'));
        }
        
        try {
            $result = $this->upload_handler->process_upload();
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle AJAX test Drive connection
     */
    public function handle_test_drive_connection() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Acceso denegado', 'poxica-image-uploader'));
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'poxica_admin_nonce')) {
            wp_die(__('Acceso denegado', 'poxica-image-uploader'));
        }
        
        $result = $this->google_drive->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Run daily cleanup cron job
     */
    public function run_daily_cleanup() {
        $this->cron->run_cleanup();
    }
    
    /**
     * Get Google Drive instance
     */
    public function get_google_drive() {
        return $this->google_drive;
    }
    
    /**
     * Get order handler instance
     */
    public function get_order_handler() {
        return $this->order_handler;
    }
    
    /**
     * Get upload handler instance
     */
    public function get_upload_handler() {
        return $this->upload_handler;
    }
    
    /**
     * Get email notifications instance
     */
    public function get_email_notifications() {
        return $this->email_notifications;
    }
    
    /**
     * Get security instance
     */
    public function get_security() {
        return $this->security;
    }
}