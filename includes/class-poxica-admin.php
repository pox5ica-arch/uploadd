<?php
/**
 * Admin interface for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Admin {
    
    private $google_drive;
    
    public function __construct($google_drive) {
        $this->google_drive = $google_drive;
        $this->init_hooks();
    }
    
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_poxica_test_drive_connection', [$this, 'handle_test_drive_connection']);
        add_action('wp_ajax_poxica_manual_cleanup', [$this, 'handle_manual_cleanup']);
        add_action('wp_ajax_poxica_test_email', [$this, 'handle_test_email']);
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Poxica Image Uploader', 'poxica-image-uploader'),
            __('Poxica Uploader', 'poxica-image-uploader'),
            'manage_options',
            'poxica-uploader',
            [$this, 'display_main_page'],
            'dashicons-upload',
            30
        );
        
        add_submenu_page(
            'poxica-uploader',
            __('Configuración', 'poxica-image-uploader'),
            __('Configuración', 'poxica-image-uploader'),
            'manage_options',
            'poxica-settings',
            [$this, 'display_settings_page']
        );
        
        add_submenu_page(
            'poxica-uploader',
            __('Logs', 'poxica-image-uploader'),
            __('Logs', 'poxica-image-uploader'),
            'manage_options',
            'poxica-logs',
            [$this, 'display_logs_page']
        );
        
        add_submenu_page(
            'poxica-uploader',
            __('Limpieza', 'poxica-image-uploader'),
            __('Limpieza', 'poxica-image-uploader'),
            'manage_options',
            'poxica-cleanup',
            [$this, 'display_cleanup_page']
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Google Drive settings
        register_setting('poxica_google_drive', 'poxica_google_drive_credentials');
        register_setting('poxica_google_drive', 'poxica_google_drive_root_folder');
        
        // General settings
        register_setting('poxica_general', 'poxica_unpaid_order_cleanup_days');
        register_setting('poxica_general', 'poxica_upload_link_expiry_days');
        register_setting('poxica_general', 'poxica_max_file_size');
        register_setting('poxica_general', 'poxica_allowed_file_types');
        
        // Email settings
        register_setting('poxica_email', 'poxica_email_from_name');
        register_setting('poxica_email', 'poxica_email_from_email');
        register_setting('poxica_email', 'poxica_admin_notification_emails');
        register_setting('poxica_email', 'poxica_email_upload_link_subject');
        register_setting('poxica_email', 'poxica_email_upload_link_message');
        register_setting('poxica_email', 'poxica_email_completion_subject');
        register_setting('poxica_email', 'poxica_email_completion_message');
    }
    
    /**
     * Display main admin page
     */
    public function display_main_page() {
        $stats = $this->get_dashboard_stats();
        
        include POXICA_PLUGIN_PATH . 'admin/views/main-page.php';
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        $active_tab = $_GET['tab'] ?? 'google_drive';
        
        if ($_POST && wp_verify_nonce($_POST['poxica_settings_nonce'], 'poxica_save_settings')) {
            $this->save_settings($active_tab);
        }
        
        include POXICA_PLUGIN_PATH . 'admin/views/settings-page.php';
    }
    
    /**
     * Display logs page
     */
    public function display_logs_page() {
        $page = $_GET['paged'] ?? 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $logs = Poxica_Database::get_logs($limit, $offset);
        
        include POXICA_PLUGIN_PATH . 'admin/views/logs-page.php';
    }
    
    /**
     * Display cleanup page
     */
    public function display_cleanup_page() {
        $cron = new Poxica_Cron($this->google_drive);
        $stats = $cron->get_cleanup_stats();
        $is_cron_working = $cron->is_cron_working();
        
        include POXICA_PLUGIN_PATH . 'admin/views/cleanup-page.php';
    }
    
    /**
     * Save settings
     */
    private function save_settings($tab) {
        switch ($tab) {
            case 'google_drive':
                $this->save_google_drive_settings();
                break;
            case 'general':
                $this->save_general_settings();
                break;
            case 'email':
                $this->save_email_settings();
                break;
        }
        
        add_settings_error('poxica_settings', 'settings_saved', __('Configuración guardada correctamente.', 'poxica-image-uploader'), 'success');
    }
    
    /**
     * Save Google Drive settings
     */
    private function save_google_drive_settings() {
        if (isset($_POST['poxica_google_drive_credentials'])) {
            $credentials = sanitize_textarea_field($_POST['poxica_google_drive_credentials']);
            
            // Validate JSON
            if (!empty($credentials)) {
                $decoded = json_decode($credentials, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    add_settings_error('poxica_settings', 'invalid_json', __('Las credenciales JSON no son válidas.', 'poxica-image-uploader'), 'error');
                    return;
                }
                
                // Check required fields
                $required_fields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id', 'auth_uri', 'token_uri'];
                foreach ($required_fields as $field) {
                    if (!isset($decoded[$field])) {
                        add_settings_error('poxica_settings', 'missing_field', sprintf(__('Falta el campo requerido: %s', 'poxica-image-uploader'), $field), 'error');
                        return;
                    }
                }
            }
            
            update_option('poxica_google_drive_credentials', $credentials);
        }
        
        if (isset($_POST['poxica_google_drive_root_folder'])) {
            update_option('poxica_google_drive_root_folder', sanitize_text_field($_POST['poxica_google_drive_root_folder']));
        }
    }
    
    /**
     * Save general settings
     */
    private function save_general_settings() {
        if (isset($_POST['poxica_unpaid_order_cleanup_days'])) {
            $days = intval($_POST['poxica_unpaid_order_cleanup_days']);
            if ($days < 1) $days = 3;
            update_option('poxica_unpaid_order_cleanup_days', $days);
        }
        
        if (isset($_POST['poxica_upload_link_expiry_days'])) {
            $days = intval($_POST['poxica_upload_link_expiry_days']);
            if ($days < 1) $days = 7;
            update_option('poxica_upload_link_expiry_days', $days);
        }
        
        if (isset($_POST['poxica_max_file_size'])) {
            $size_mb = intval($_POST['poxica_max_file_size']);
            if ($size_mb < 1) $size_mb = 10; // Min 1MB, default 10MB
            $size_bytes = $size_mb * 1048576; // Convert to bytes
            update_option('poxica_max_file_size', $size_bytes);
        }
        
        if (isset($_POST['poxica_allowed_file_types'])) {
            $types = sanitize_text_field($_POST['poxica_allowed_file_types']);
            update_option('poxica_allowed_file_types', $types);
        }
    }
    
    /**
     * Save email settings
     */
    private function save_email_settings() {
        $email_fields = [
            'poxica_upload_link_email_subject',
            'poxica_completion_email_subject',
            'poxica_admin_email'
        ];
        
        foreach ($email_fields as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'poxica_admin_email') {
                    update_option($field, sanitize_email($_POST[$field]));
                } else {
                    update_option($field, sanitize_text_field($_POST[$field]));
                }
            }
        }
        
        // Handle textarea fields
        $textarea_fields = ['poxica_upload_link_email_message', 'poxica_completion_email_message'];
        foreach ($textarea_fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_textarea_field($_POST[$field]));
            }
        }
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'poxica_orders';
        $products_table = $wpdb->prefix . 'poxica_order_products';
        $images_table = $wpdb->prefix . 'poxica_uploaded_images';
        
        return [
            'total_orders' => $wpdb->get_var("SELECT COUNT(*) FROM $orders_table"),
            'pending_orders' => $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status = 'pending'"),
            'completed_orders' => $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status = 'completed'"),
            'total_images' => $wpdb->get_var("SELECT COUNT(*) FROM $images_table"),
            'drive_configured' => $this->google_drive->is_configured(),
            'recent_logs' => Poxica_Database::get_logs(5, 0)
        ];
    }
    
    /**
     * Handle test Google Drive connection AJAX
     */
    public function handle_test_drive_connection() {
        check_ajax_referer('poxica_test_drive', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos suficientes', 'poxica-image-uploader'));
        }
        
        $credentials = $_POST['credentials'] ?? '';
        $root_folder = $_POST['root_folder'] ?? '';
        
        if (empty($credentials)) {
            wp_send_json_error([
                'message' => __('Las credenciales son requeridas', 'poxica-image-uploader')
            ]);
        }
        
        // Temporarily save credentials for testing
        update_option('poxica_google_drive_credentials', $credentials);
        if (!empty($root_folder)) {
            update_option('poxica_google_drive_root_folder', $root_folder);
        }
        
        $google_drive = new Poxica_Google_Drive();
        $result = $google_drive->test_connection();
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => __('Conexión exitosa con Google Drive', 'poxica-image-uploader'),
                'details' => $result['details'] ?? ''
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
                'details' => $result['details'] ?? ''
            ]);
        }
    }

    /**
     * Handle manual cleanup AJAX
     */
    public function handle_manual_cleanup() {
        check_ajax_referer('poxica_manual_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Acceso denegado', 'poxica-image-uploader'));
        }
        
        $cleanup_type = $_POST['cleanup_type'] ?? 'full';
        $cron = new Poxica_Cron($this->google_drive);
        
        switch ($cleanup_type) {
            case 'full':
                $result = $cron->manual_cleanup();
                break;
            case 'unpaid':
                $result = $cron->cleanup_unpaid_orders();
                break;
            case 'cancelled':
                $result = $cron->cleanup_cancelled_orders();
                break;
            case 'tokens':
                $result = $cron->cleanup_expired_tokens();
                break;
            case 'temp_files':
                $result = $cron->cleanup_temp_files();
                break;
            default:
                wp_send_json_error(['message' => __('Tipo de limpieza no válido', 'poxica-image-uploader')]);
                return;
        }
        
        if ($result) {
            wp_send_json_success([
                'message' => sprintf(__('Limpieza %s completada exitosamente', 'poxica-image-uploader'), $cleanup_type)
            ]);
        } else {
            wp_send_json_error([
                'message' => sprintf(__('Error al ejecutar la limpieza %s', 'poxica-image-uploader'), $cleanup_type)
            ]);
        }
    }
    
    /**
     * Handle test email AJAX
     */
    public function handle_test_email() {
        check_ajax_referer('poxica_test_email', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos suficientes', 'poxica-image-uploader'));
        }
        
        $admin_email = $_POST['admin_email'] ?? get_option('admin_email');
        
        if (!is_email($admin_email)) {
            wp_send_json_error(['message' => __('Email inválido', 'poxica-image-uploader')]);
        }
        
        $email_notifications = new Poxica_Email_Notifications();
        $sent = $email_notifications->send_test_email($admin_email);
        
        if ($sent) {
            wp_send_json_success(['message' => __('Email de prueba enviado correctamente', 'poxica-image-uploader')]);
        } else {
            wp_send_json_error(['message' => __('Error enviando email de prueba', 'poxica-image-uploader')]);
        }
    }
}