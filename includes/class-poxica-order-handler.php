<?php
/**
 * Order handler for Poxica Image Uploader
 * Handles WooCommerce order events and creates upload structures
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Order_Handler {
    
    private $google_drive;
    
    public function __construct($google_drive) {
        $this->google_drive = $google_drive;
        $this->init_hooks();
    }
    
    /**
     * Initialize WooCommerce hooks
     */
    private function init_hooks() {
        // Order creation and payment hooks
        add_action('woocommerce_new_order', [$this, 'handle_new_order'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'handle_order_completed'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'handle_order_processing'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'handle_order_cancelled'], 10, 1);
        
        // Payment hooks
        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete'], 10, 1);
    }
    
    /**
     * Handle new order creation
     */
    public function handle_new_order($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        try {
            // Check if order already exists in our system
            $existing_order = Poxica_Database::get_order_by_id($order_id);
            if ($existing_order) {
                return; // Already processed
            }
            
            // Generate unique upload token
            $upload_token = $this->generate_upload_token($order_id);
            
            // Create order record
            $poxica_order_created = Poxica_Database::create_order($order_id, $upload_token);
            
            if (!$poxica_order_created) {
                throw new Exception('Failed to create order record in database');
            }
            
            // Get the created order record
            $poxica_order = Poxica_Database::get_order_by_id($order_id);
            
            // Process order items and create product records
            $this->process_order_items($order, $poxica_order->id);
            
            // Send upload link email to customer
            $email_notifications = new Poxica_Email_Notifications();
            $upload_url = $this->get_upload_url($order_id, $upload_token);
            $email_notifications->send_upload_link_email($order, $upload_url);
            
            Poxica_Database::add_log($order_id, 'order_created', "Pedido creado y email enviado", [
                'upload_token' => $upload_token,
                'upload_url' => $upload_url
            ]);
            
        } catch (Exception $e) {
            Poxica_Database::add_log($order_id, 'order_creation_failed', "Error procesando nuevo pedido: " . $e->getMessage());
            error_log('Poxica Order Handler Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle order completion
     */
    public function handle_order_completed($order_id) {
        $this->create_drive_folders($order_id);
    }
    
    /**
     * Handle order processing
     */
    public function handle_order_processing($order_id) {
        $this->create_drive_folders($order_id);
    }
    
    /**
     * Handle payment completion
     */
    public function handle_payment_complete($order_id) {
        $this->create_drive_folders($order_id);
    }
    
    /**
     * Handle order cancellation
     */
    public function handle_order_cancelled($order_id) {
        try {
            $poxica_order = Poxica_Database::get_order_by_id($order_id);
            
            if ($poxica_order && $poxica_order->drive_folder_id) {
                // Delete folder from Google Drive immediately
                $this->google_drive->delete_folder($poxica_order->drive_folder_id);
                
                // Update order status
                Poxica_Database::update_order_status($order_id, 'cancelled');
                
                Poxica_Database::add_log($order_id, 'order_cancelled', "Pedido cancelado, carpeta eliminada de Google Drive");
            }
            
        } catch (Exception $e) {
            Poxica_Database::add_log($order_id, 'cancellation_cleanup_failed', "Error eliminando carpeta al cancelar pedido: " . $e->getMessage());
            error_log('Poxica Order Cancellation Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Create Google Drive folders for paid orders
     */
    private function create_drive_folders($order_id) {
        if (!$this->google_drive->is_configured()) {
            Poxica_Database::add_log($order_id, 'drive_not_configured', "Google Drive no configurado, saltando creación de carpetas");
            return;
        }
        
        try {
            $poxica_order = Poxica_Database::get_order_by_id($order_id);
            
            if (!$poxica_order) {
                // Order not in our system yet, create it
                $this->handle_new_order($order_id);
                $poxica_order = Poxica_Database::get_order_by_id($order_id);
            }
            
            if ($poxica_order->drive_folder_id) {
                return; // Folders already created
            }
            
            // Get order products
            $products = Poxica_Database::get_order_products($poxica_order->id);
            
            if (empty($products)) {
                // Process order items if not done yet
                $order = wc_get_order($order_id);
                $this->process_order_items($order, $poxica_order->id);
                $products = Poxica_Database::get_order_products($poxica_order->id);
            }
            
            // Create folder structure in Google Drive
            $folder_structure = $this->google_drive->create_order_structure($order_id, $products);
            
            // Update order with main folder ID
            Poxica_Database::set_order_drive_folder($order_id, $folder_structure['order_folder_id']);
            
            // Update product records with their folder IDs
            foreach ($folder_structure['product_folders'] as $product_folder) {
                Poxica_Database::set_product_drive_folder(
                    $product_folder['product_record_id'],
                    $product_folder['folder_id']
                );
            }
            
            Poxica_Database::add_log($order_id, 'folders_created', "Estructura de carpetas creada en Google Drive", [
                'order_folder_id' => $folder_structure['order_folder_id'],
                'product_folders_count' => count($folder_structure['product_folders'])
            ]);
            
        } catch (Exception $e) {
            Poxica_Database::add_log($order_id, 'folder_creation_failed', "Error creando carpetas: " . $e->getMessage());
            error_log('Poxica Folder Creation Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Process order items and create product records
     */
    private function process_order_items($order, $poxica_order_id) {
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $product_id = $product->get_id();
            $variation_id = $item->get_variation_id();
            $quantity = $item->get_quantity();
            
            // Get product name
            $product_name = $product->get_name();
            
            // Get variation details
            $variation_details = '';
            if ($variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $attributes = $variation->get_variation_attributes();
                    $variation_parts = [];
                    
                    foreach ($attributes as $attribute_name => $attribute_value) {
                        $attribute_label = wc_attribute_label($attribute_name);
                        $variation_parts[] = "$attribute_label: $attribute_value";
                    }
                    
                    $variation_details = implode(', ', $variation_parts);
                }
            }
            
            // Add product to database
            Poxica_Database::add_order_product(
                $poxica_order_id,
                $product_id,
                $variation_id,
                $product_name,
                $variation_details,
                $quantity
            );
        }
    }
    
    /**
     * Generate unique upload token
     */
    private function generate_upload_token($order_id) {
        $token_data = $order_id . time() . wp_generate_password(20, false);
        return hash('sha256', $token_data);
    }
    
    /**
     * Get upload URL for order
     */
    private function get_upload_url($order_id, $token) {
        return home_url("poxica-upload/$order_id/$token/");
    }
    
    /**
     * Check if order is ready for image uploads
     */
    public function is_order_ready_for_uploads($order_id) {
        $poxica_order = Poxica_Database::get_order_by_id($order_id);
        
        if (!$poxica_order) {
            return false;
        }
        
        // Check if token is valid
        if ($poxica_order->token_expires && strtotime($poxica_order->token_expires) < time()) {
            return false;
        }
        
        // Check if order has folders created (meaning it's paid)
        return !empty($poxica_order->drive_folder_id);
    }
    
    /**
     * Get order upload status
     */
    public function get_order_upload_status($order_id) {
        $poxica_order = Poxica_Database::get_order_by_id($order_id);
        
        if (!$poxica_order) {
            return null;
        }
        
        $products = Poxica_Database::get_order_products($poxica_order->id);
        $total_needed = 0;
        $total_uploaded = 0;
        $product_status = [];
        
        foreach ($products as $product) {
            $total_needed += $product->quantity;
            $uploaded_images = Poxica_Database::get_product_images($product->id);
            $product_uploaded = count($uploaded_images);
            $total_uploaded += $product_uploaded;
            
            $product_status[] = [
                'product' => $product,
                'needed' => $product->quantity,
                'uploaded' => $product_uploaded,
                'complete' => $product_uploaded >= $product->quantity,
                'units' => $this->get_product_units_status($product)
            ];
        }
        
        return [
            'order' => $poxica_order,
            'total_needed' => $total_needed,
            'total_uploaded' => $total_uploaded,
            'complete' => $total_uploaded >= $total_needed,
            'products' => $product_status
        ];
    }
    
    /**
     * Get upload status for each unit of a product
     */
    private function get_product_units_status($product) {
        $units = [];
        
        for ($unit = 1; $unit <= $product->quantity; $unit++) {
            $has_image = Poxica_Database::unit_has_image($product->id, $unit);
            $units[] = [
                'unit_number' => $unit,
                'has_image' => $has_image
            ];
        }
        
        return $units;
    }
    
    /**
     * Mark order as complete when all images are uploaded
     */
    public function check_and_complete_order($order_id) {
        $poxica_order = Poxica_Database::get_order_by_id($order_id);
        
        if (!$poxica_order) {
            return false;
        }
        
        if (Poxica_Database::order_images_complete($poxica_order->id)) {
            // Update order status
            Poxica_Database::update_order_status($order_id, 'completed');
            
            // Send completion email to admin
            $email_notifications = new Poxica_Email_Notifications();
            $order = wc_get_order($order_id);
            $status = $this->get_order_upload_status($order_id);
            $email_notifications->send_completion_notification($order, $status);
            
            Poxica_Database::add_log($order_id, 'upload_completed', "Todas las imágenes subidas, notificación enviada al administrador");
            
            return true;
        }
        
        return false;
    }
}