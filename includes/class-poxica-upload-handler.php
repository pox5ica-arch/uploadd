<?php
/**
 * Upload handler for Poxica Image Uploader
 * Handles file uploads and validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Upload_Handler {
    
    private $google_drive;
    private $security;
    
    public function __construct($google_drive) {
        $this->google_drive = $google_drive;
        $this->security = new Poxica_Security();
    }
    
    /**
     * Process file upload via AJAX
     */
    public function process_upload() {
        // Validate request
        $this->validate_upload_request();
        
        $order_id = intval($_POST['order_id']);
        $token = sanitize_text_field($_POST['token']);
        $product_record_id = intval($_POST['product_record_id']);
        $unit_number = intval($_POST['unit_number']);
        
        // Verify order and token
        $poxica_order = Poxica_Database::get_order_by_token($token);
        if (!$poxica_order || $poxica_order->order_id != $order_id) {
            throw new Exception(__('Token de subida inválido o expirado', 'poxica-image-uploader'));
        }
        
        // Check if order is ready for uploads
        $order_handler = new Poxica_Order_Handler($this->google_drive);
        if (!$order_handler->is_order_ready_for_uploads($order_id)) {
            throw new Exception(__('El pedido no está listo para subir imágenes. Debe estar pagado.', 'poxica-image-uploader'));
        }
        
        // Check if unit already has an image
        if (Poxica_Database::unit_has_image($product_record_id, $unit_number)) {
            throw new Exception(__('Esta unidad ya tiene una imagen subida', 'poxica-image-uploader'));
        }
        
        // Validate uploaded file
        $file = $this->validate_uploaded_file();
        
        // Get product info
        $product = $this->get_product_record($product_record_id, $poxica_order->id);
        
        // Create temporary file
        $temp_file = $this->create_temp_file($file);
        
        try {
            // Generate filename
            $filename = $this->generate_filename($product, $unit_number, $file['name']);
            
            // Get the product's folder ID
            $product_folder_id = $this->get_product_unit_folder($product, $unit_number);
            
            // Upload to Google Drive
            $drive_file_id = $this->google_drive->upload_file($temp_file, $filename, $product_folder_id);
            
            // Save to database
            Poxica_Database::add_uploaded_image(
                $product_record_id,
                $unit_number,
                $file['name'],
                $drive_file_id,
                $product_folder_id,
                $file['size'],
                $file['type']
            );
            
            // Log the upload
            Poxica_Database::add_log($order_id, 'image_uploaded', "Imagen subida: $filename", [
                'product_record_id' => $product_record_id,
                'unit_number' => $unit_number,
                'drive_file_id' => $drive_file_id,
                'original_filename' => $file['name']
            ]);
            
            // Check if order is now complete
            $order_handler->check_and_complete_order($order_id);
            
            return [
                'success' => true,
                'message' => __('Imagen subida correctamente', 'poxica-image-uploader'),
                'filename' => $filename,
                'drive_file_id' => $drive_file_id
            ];
            
        } finally {
            // Clean up temporary file
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
    }
    
    /**
     * Validate upload request
     */
    private function validate_upload_request() {
        if (!isset($_POST['order_id'], $_POST['token'], $_POST['product_record_id'], $_POST['unit_number'])) {
            throw new Exception(__('Datos de subida incompletos', 'poxica-image-uploader'));
        }
        
        if (!isset($_FILES['file'])) {
            throw new Exception(__('No se recibió ningún archivo', 'poxica-image-uploader'));
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validate_uploaded_file() {
        $file = $_FILES['file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => __('El archivo excede el tamaño máximo permitido', 'poxica-image-uploader'),
                UPLOAD_ERR_FORM_SIZE => __('El archivo excede el tamaño máximo del formulario', 'poxica-image-uploader'),
                UPLOAD_ERR_PARTIAL => __('El archivo se subió parcialmente', 'poxica-image-uploader'),
                UPLOAD_ERR_NO_FILE => __('No se subió ningún archivo', 'poxica-image-uploader'),
                UPLOAD_ERR_NO_TMP_DIR => __('Falta el directorio temporal', 'poxica-image-uploader'),
                UPLOAD_ERR_CANT_WRITE => __('Error de escritura en disco', 'poxica-image-uploader'),
                UPLOAD_ERR_EXTENSION => __('Subida detenida por extensión', 'poxica-image-uploader')
            ];
            
            $message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : __('Error desconocido en la subida', 'poxica-image-uploader');
            throw new Exception($message);
        }
        
        // Check file size
        $max_size = get_option('poxica_max_file_size', 10485760); // 10MB default
        if ($file['size'] > $max_size) {
            throw new Exception(sprintf(__('El archivo es demasiado grande. Máximo permitido: %s', 'poxica-image-uploader'), size_format($max_size)));
        }
        
        // Check file type
        $allowed_types = explode(',', get_option('poxica_allowed_file_types', 'jpg,jpeg,png'));
        $allowed_types = array_map('trim', $allowed_types);
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception(sprintf(__('Tipo de archivo no permitido. Permitidos: %s', 'poxica-image-uploader'), implode(', ', $allowed_types)));
        }
        
        // Validate file content using security class
        if (!$this->security->validate_file($file['tmp_name'], $file['type'])) {
            throw new Exception(__('El archivo no pasó las validaciones de seguridad', 'poxica-image-uploader'));
        }
        
        return $file;
    }
    
    /**
     * Get product record from database
     */
    private function get_product_record($product_record_id, $poxica_order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'poxica_order_products';
        
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND poxica_order_id = %d",
            $product_record_id,
            $poxica_order_id
        ));
        
        if (!$product) {
            throw new Exception(__('Producto no encontrado', 'poxica-image-uploader'));
        }
        
        return $product;
    }
    
    /**
     * Create temporary file for processing
     */
    private function create_temp_file($file) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/poxica-temp/';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        $temp_filename = 'poxica_' . uniqid() . '_' . sanitize_file_name($file['name']);
        $temp_file = $temp_dir . $temp_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            throw new Exception(__('Error moviendo archivo temporal', 'poxica-image-uploader'));
        }
        
        return $temp_file;
    }
    
    /**
     * Generate filename for Drive upload
     */
    private function generate_filename($product, $unit_number, $original_filename) {
        $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
        $base_name = sanitize_file_name($product->product_name);
        
        if ($product->variation_details) {
            $variation = sanitize_file_name($product->variation_details);
            $base_name .= "_$variation";
        }
        
        if ($product->quantity > 1) {
            $base_name .= "_unidad_$unit_number";
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        
        return "{$base_name}_{$timestamp}.{$extension}";
    }
    
    /**
     * Get the Google Drive folder ID for a specific product unit
     */
    private function get_product_unit_folder($product, $unit_number) {
        // For now, all units of a product share the same folder
        // This could be modified to create separate folders per unit if needed
        
        if (empty($product->drive_folder_id)) {
            throw new Exception(__('Carpeta del producto no encontrada en Google Drive', 'poxica-image-uploader'));
        }
        
        return $product->drive_folder_id;
    }
    
    /**
     * Get upload page data
     */
    public function get_upload_page_data($order_id, $token) {
        // Verify token
        $poxica_order = Poxica_Database::get_order_by_token($token);
        if (!$poxica_order || $poxica_order->order_id != $order_id) {
            throw new Exception(__('Token de subida inválido o expirado', 'poxica-image-uploader'));
        }
        
        // Get order handler for status
        $order_handler = new Poxica_Order_Handler($this->google_drive);
        $upload_status = $order_handler->get_order_upload_status($order_id);
        
        if (!$upload_status) {
            throw new Exception(__('Pedido no encontrado', 'poxica-image-uploader'));
        }
        
        // Check if order is ready for uploads
        $is_ready = $order_handler->is_order_ready_for_uploads($order_id);
        
        // Get WooCommerce order
        $wc_order = wc_get_order($order_id);
        
        return [
            'order' => $wc_order,
            'poxica_order' => $poxica_order,
            'upload_status' => $upload_status,
            'is_ready' => $is_ready,
            'token' => $token,
            'max_file_size' => get_option('poxica_max_file_size', 10485760),
            'allowed_types' => get_option('poxica_allowed_file_types', 'jpg,jpeg,png')
        ];
    }
    
    /**
     * Clean up temporary files older than 1 hour
     */
    public function cleanup_temp_files() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/poxica-temp/';
        
        if (!file_exists($temp_dir)) {
            return;
        }
        
        $files = glob($temp_dir . 'poxica_*');
        $one_hour_ago = time() - 3600;
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $one_hour_ago) {
                unlink($file);
            }
        }
    }
}