<?php
/**
 * Plugin Name: Poxica Image Uploader
 * Plugin URI: https://yourwebsite.com/poxica-image-uploader
 * Description: Automatiza la gestión de imágenes de pedidos WooCommerce con almacenamiento en Google Drive y limpieza automática.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: poxica-image-uploader
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 * Woo: HPOS compatible
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('POXICA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('POXICA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('POXICA_PLUGIN_VERSION', '1.0.0');
define('POXICA_DB_VERSION', '1.0');

/**
 * Check if WooCommerce is active
 */
function poxica_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo __('Poxica Image Uploader requiere WooCommerce para funcionar.', 'poxica-image-uploader');
            echo '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Plugin activation hook
 */
function poxica_activate() {
    if (!poxica_check_woocommerce()) {
        wp_die(__('Por favor instala y activa WooCommerce antes de activar este plugin.', 'poxica-image-uploader'));
    }
    
    // Declare HPOS compatibility
    add_action('before_woocommerce_init', function() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    });
    
    // Create database tables
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-database.php';
    Poxica_Database::create_tables();
    
    // Schedule cron event
    if (!wp_next_scheduled('poxica_daily_cleanup')) {
        wp_schedule_event(time(), 'daily', 'poxica_daily_cleanup');
    }
    
    // Set default options
    add_option('poxica_unpaid_order_cleanup_days', 3);
    add_option('poxica_upload_link_expiry_days', 7);
    add_option('poxica_max_file_size', 10485760); // 10MB
    add_option('poxica_allowed_file_types', 'jpg,jpeg,png');
}
register_activation_hook(__FILE__, 'poxica_activate');

/**
 * Plugin deactivation hook
 */
function poxica_deactivate() {
    wp_clear_scheduled_hook('poxica_daily_cleanup');
}
register_deactivation_hook(__FILE__, 'poxica_deactivate');

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Initialize the plugin
 */
function poxica_init() {
    if (!poxica_check_woocommerce()) {
        return;
    }
    
    // Load text domain
    load_plugin_textdomain('poxica-image-uploader', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Include required files
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-core.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-database.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-google-drive.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-order-handler.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-upload-handler.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-email-notifications.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-cron.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-admin.php';
    require_once POXICA_PLUGIN_PATH . 'includes/class-poxica-security.php';
    
    // Initialize the core plugin
    new Poxica_Core();
}
add_action('plugins_loaded', 'poxica_init');

/**
 * Add custom endpoint for image uploads
 */
function poxica_add_endpoints() {
    add_rewrite_rule('^poxica-upload/([^/]*)/([^/]*)/?', 'index.php?poxica_upload=1&order_id=$matches[1]&token=$matches[2]', 'top');
}
add_action('init', 'poxica_add_endpoints');

/**
 * Add query vars
 */
function poxica_query_vars($vars) {
    $vars[] = 'poxica_upload';
    $vars[] = 'order_id';
    $vars[] = 'token';
    return $vars;
}
add_filter('query_vars', 'poxica_query_vars');

/**
 * Handle template redirect
 */
function poxica_template_redirect() {
    if (get_query_var('poxica_upload')) {
        require_once POXICA_PLUGIN_PATH . 'templates/upload-page.php';
        exit;
    }
}
add_action('template_redirect', 'poxica_template_redirect');