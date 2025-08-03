<?php
/**
 * Google Drive API integration for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Google_Drive {
    
    private $access_token;
    private $service_account_key;
    private $root_folder_id;
    
    public function __construct() {
        $this->service_account_key = get_option('poxica_google_drive_credentials');
        $this->root_folder_id = get_option('poxica_google_drive_root_folder');
    }
    
    /**
     * Authenticate with Google Drive using Service Account
     */
    private function authenticate() {
        if (empty($this->service_account_key)) {
            throw new Exception(__('Credenciales de Google Drive no configuradas', 'poxica-image-uploader'));
        }
        
        $credentials = json_decode($this->service_account_key, true);
        if (!$credentials) {
            throw new Exception(__('Credenciales de Google Drive inválidas', 'poxica-image-uploader'));
        }
        
        // Create JWT for service account
        $jwt = $this->create_jwt($credentials);
        
        // Request access token
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception(__('Error de conexión con Google Drive: ', 'poxica-image-uploader') . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['access_token'])) {
            throw new Exception(__('Error de autenticación con Google Drive', 'poxica-image-uploader'));
        }
        
        $this->access_token = $body['access_token'];
        
        // Cache token for 50 minutes (expires in 1 hour)
        set_transient('poxica_drive_token', $this->access_token, 3000);
        
        return $this->access_token;
    }
    
    /**
     * Create JWT for service account authentication
     */
    private function create_jwt($credentials) {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        $now = time();
        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];
        
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        
        $signature_input = $header_encoded . '.' . $payload_encoded;
        
        // Sign with private key
        $private_key = openssl_pkey_get_private($credentials['private_key']);
        if (!$private_key) {
            throw new Exception(__('Clave privada de Google Drive inválida', 'poxica-image-uploader'));
        }
        
        openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $signature_encoded = $this->base64url_encode($signature);
        
        return $signature_input . '.' . $signature_encoded;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Get access token (with caching)
     */
    private function get_access_token() {
        if ($this->access_token) {
            return $this->access_token;
        }
        
        // Check cache
        $cached_token = get_transient('poxica_drive_token');
        if ($cached_token) {
            $this->access_token = $cached_token;
            return $this->access_token;
        }
        
        return $this->authenticate();
    }
    
    /**
     * Make API request to Google Drive
     */
    private function make_request($endpoint, $method = 'GET', $body = null, $headers = []) {
        $token = $this->get_access_token();
        
        $default_headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];
        
        $headers = array_merge($default_headers, $headers);
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => 60
        ];
        
        if ($body && $method !== 'GET') {
            $args['body'] = is_array($body) ? json_encode($body) : $body;
        }
        
        $response = wp_remote_request('https://www.googleapis.com/drive/v3/' . $endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Google Drive API Error: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 400) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Unknown error';
            throw new Exception("Google Drive API Error ($response_code): $error_message");
        }
        
        return json_decode($response_body, true);
    }
    
    /**
     * Create folder in Google Drive
     */
    public function create_folder($name, $parent_id = null) {
        if (!$parent_id) {
            $parent_id = $this->root_folder_id;
        }
        
        $metadata = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ];
        
        if ($parent_id) {
            $metadata['parents'] = [$parent_id];
        }
        
        try {
            $result = $this->make_request('files', 'POST', $metadata);
            
            Poxica_Database::add_log(null, 'folder_created', "Carpeta creada: $name", [
                'folder_id' => $result['id'],
                'parent_id' => $parent_id
            ]);
            
            return $result['id'];
        } catch (Exception $e) {
            Poxica_Database::add_log(null, 'folder_creation_failed', "Error creando carpeta: $name - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Upload file to Google Drive
     */
    public function upload_file($file_path, $filename, $parent_folder_id) {
        if (!file_exists($file_path)) {
            throw new Exception(__('Archivo no encontrado: ', 'poxica-image-uploader') . $file_path);
        }
        
        // Get file info
        $file_size = filesize($file_path);
        $mime_type = mime_content_type($file_path);
        
        // Create file metadata
        $metadata = [
            'name' => $filename,
            'parents' => [$parent_folder_id]
        ];
        
        try {
            // First, create the file metadata
            $file_metadata = $this->make_request('files?uploadType=resumable', 'POST', $metadata, [
                'X-Upload-Content-Type' => $mime_type,
                'X-Upload-Content-Length' => $file_size
            ]);
            
            // Get upload URL from response headers
            $upload_url = null;
            $response = wp_remote_post('https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->get_access_token(),
                    'Content-Type' => 'application/json',
                    'X-Upload-Content-Type' => $mime_type,
                    'X-Upload-Content-Length' => $file_size
                ],
                'body' => json_encode($metadata)
            ]);
            
            if (is_wp_error($response)) {
                throw new Exception('Upload initiation failed: ' . $response->get_error_message());
            }
            
            $upload_url = wp_remote_retrieve_header($response, 'location');
            
            if (!$upload_url) {
                throw new Exception('No upload URL received from Google Drive');
            }
            
            // Upload file content
            $file_content = file_get_contents($file_path);
            $upload_response = wp_remote_request($upload_url, [
                'method' => 'PUT',
                'headers' => [
                    'Content-Type' => $mime_type,
                    'Content-Length' => $file_size
                ],
                'body' => $file_content,
                'timeout' => 300 // 5 minutes for large files
            ]);
            
            if (is_wp_error($upload_response)) {
                throw new Exception('File upload failed: ' . $upload_response->get_error_message());
            }
            
            $upload_result = json_decode(wp_remote_retrieve_body($upload_response), true);
            
            if (!isset($upload_result['id'])) {
                throw new Exception('File upload completed but no file ID returned');
            }
            
            Poxica_Database::add_log(null, 'file_uploaded', "Archivo subido: $filename", [
                'file_id' => $upload_result['id'],
                'parent_folder_id' => $parent_folder_id,
                'file_size' => $file_size
            ]);
            
            return $upload_result['id'];
            
        } catch (Exception $e) {
            Poxica_Database::add_log(null, 'file_upload_failed', "Error subiendo archivo: $filename - " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete folder and all its contents
     */
    public function delete_folder($folder_id) {
        try {
            $this->make_request("files/$folder_id", 'DELETE');
            
            Poxica_Database::add_log(null, 'folder_deleted', "Carpeta eliminada", [
                'folder_id' => $folder_id
            ]);
            
            return true;
        } catch (Exception $e) {
            Poxica_Database::add_log(null, 'folder_deletion_failed', "Error eliminando carpeta: " . $e->getMessage(), [
                'folder_id' => $folder_id
            ]);
            throw $e;
        }
    }
    
    /**
     * Get folder contents
     */
    public function get_folder_contents($folder_id) {
        try {
            return $this->make_request("files?q='{$folder_id}'+in+parents&fields=files(id,name,mimeType,size,createdTime)");
        } catch (Exception $e) {
            Poxica_Database::add_log(null, 'folder_access_failed', "Error accediendo a carpeta: " . $e->getMessage(), [
                'folder_id' => $folder_id
            ]);
            throw $e;
        }
    }
    
    /**
     * Get folder web view link
     */
    public function get_folder_link($folder_id) {
        return "https://drive.google.com/drive/folders/$folder_id";
    }
    
    /**
     * Test connection to Google Drive
     */
    public function test_connection() {
        try {
            $token = $this->get_access_token();
            
            // Try to access the root folder or create a test folder
            if ($this->root_folder_id) {
                $this->get_folder_contents($this->root_folder_id);
                return [
                    'success' => true,
                    'message' => __('Conexión exitosa con Google Drive', 'poxica-image-uploader')
                ];
            } else {
                // Try to create a test folder in root
                $test_folder = $this->create_folder('Poxica_Test_' . time());
                $this->delete_folder($test_folder);
                
                return [
                    'success' => true,
                    'message' => __('Conexión exitosa con Google Drive', 'poxica-image-uploader')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => __('Error de conexión: ', 'poxica-image-uploader') . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create order folder structure
     */
    public function create_order_structure($order_id, $products) {
        try {
            // Create main order folder
            $order_folder_name = "Pedido-$order_id";
            $order_folder_id = $this->create_folder($order_folder_name, $this->root_folder_id);
            
            // Create product subfolders
            $product_folders = [];
            
            foreach ($products as $product) {
                for ($unit = 1; $unit <= $product->quantity; $unit++) {
                    $folder_name = $this->generate_product_folder_name($product, $unit);
                    $product_folder_id = $this->create_folder($folder_name, $order_folder_id);
                    
                    $product_folders[] = [
                        'product_record_id' => $product->id,
                        'unit_number' => $unit,
                        'folder_id' => $product_folder_id,
                        'folder_name' => $folder_name
                    ];
                }
            }
            
            return [
                'order_folder_id' => $order_folder_id,
                'product_folders' => $product_folders
            ];
            
        } catch (Exception $e) {
            Poxica_Database::add_log($order_id, 'folder_structure_failed', "Error creando estructura de carpetas: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate product folder name based on product details and unit
     */
    private function generate_product_folder_name($product, $unit) {
        $name = sanitize_file_name($product->product_name);
        
        if ($product->variation_details) {
            $variation = sanitize_file_name($product->variation_details);
            $name .= "-$variation";
        }
        
        if ($product->quantity > 1) {
            $name .= "-$unit";
        }
        
        return $name;
    }
    
    /**
     * Check if credentials are configured
     */
    public function is_configured() {
        return !empty($this->service_account_key);
    }
    
    /**
     * Get root folder ID
     */
    public function get_root_folder_id() {
        return $this->root_folder_id;
    }
}