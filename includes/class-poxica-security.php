<?php
/**
 * Security and validation for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Security {
    
    /**
     * Validate uploaded file for security
     */
    public function validate_file($file_path, $declared_mime_type) {
        // Check if file exists
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Get actual file info
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $actual_mime_type = finfo_file($file_info, $file_path);
        finfo_close($file_info);
        
        // Validate MIME type
        if (!$this->is_allowed_mime_type($actual_mime_type)) {
            return false;
        }
        
        // Check if declared MIME type matches actual
        if (!$this->mime_types_match($declared_mime_type, $actual_mime_type)) {
            return false;
        }
        
        // Check for malicious content
        if (!$this->scan_for_malicious_content($file_path)) {
            return false;
        }
        
        // Validate image file
        if ($this->is_image_mime_type($actual_mime_type)) {
            return $this->validate_image_file($file_path);
        }
        
        return true;
    }
    
    /**
     * Check if MIME type is allowed
     */
    private function is_allowed_mime_type($mime_type) {
        $allowed_types = [
            'image/jpeg',
            'image/jpg',
            'image/png'
        ];
        
        return in_array($mime_type, $allowed_types);
    }
    
    /**
     * Check if MIME types match (with some tolerance)
     */
    private function mime_types_match($declared, $actual) {
        // Normalize MIME types
        $declared = strtolower(trim($declared));
        $actual = strtolower(trim($actual));
        
        // Direct match
        if ($declared === $actual) {
            return true;
        }
        
        // Handle jpeg/jpg variations
        $jpeg_types = ['image/jpeg', 'image/jpg'];
        if (in_array($declared, $jpeg_types) && in_array($actual, $jpeg_types)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if MIME type is an image
     */
    private function is_image_mime_type($mime_type) {
        return strpos($mime_type, 'image/') === 0;
    }
    
    /**
     * Validate image file structure
     */
    private function validate_image_file($file_path) {
        // Try to get image info
        $image_info = getimagesize($file_path);
        
        if ($image_info === false) {
            return false;
        }
        
        // Check image dimensions (minimum and maximum)
        $min_width = 100;
        $min_height = 100;
        $max_width = 10000;
        $max_height = 10000;
        
        if ($image_info[0] < $min_width || $image_info[0] > $max_width ||
            $image_info[1] < $min_height || $image_info[1] > $max_height) {
            return false;
        }
        
        // Check if it's a valid image type
        $allowed_image_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if (!in_array($image_info[2], $allowed_image_types)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Scan file for malicious content
     */
    private function scan_for_malicious_content($file_path) {
        // Read file content
        $content = file_get_contents($file_path);
        
        if ($content === false) {
            return false;
        }
        
        // Check for PHP tags
        if (strpos($content, '<?php') !== false ||
            strpos($content, '<?=') !== false ||
            strpos($content, '<%') !== false) {
            return false;
        }
        
        // Check for script tags
        if (stripos($content, '<script') !== false) {
            return false;
        }
        
        // Check for suspicious strings
        $suspicious_patterns = [
            'eval\s*\(',
            'exec\s*\(',
            'system\s*\(',
            'shell_exec\s*\(',
            'passthru\s*\(',
            'file_get_contents\s*\(',
            'file_put_contents\s*\(',
            'fopen\s*\(',
            'fwrite\s*\(',
            'base64_decode\s*\(',
            'gzinflate\s*\(',
            'str_rot13\s*\(',
            '__halt_compiler\s*\(',
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate secure token
     */
    public function generate_secure_token($data = null) {
        $random_data = wp_generate_password(32, true, true);
        $timestamp = microtime(true);
        $additional_data = $data ?: '';
        
        $token_source = $random_data . $timestamp . $additional_data . wp_salt();
        
        return hash('sha256', $token_source);
    }
    
    /**
     * Verify token with expiration
     */
    public function verify_token_with_expiration($token, $expires_timestamp) {
        if (empty($token)) {
            return false;
        }
        
        if ($expires_timestamp && time() > $expires_timestamp) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize filename for safe storage
     */
    public function sanitize_filename($filename) {
        // Remove path information
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $filename);
        
        // Remove multiple consecutive dots or underscores
        $filename = preg_replace('/[\.\_\-]{2,}/', '_', $filename);
        
        // Ensure it doesn't start with a dot
        $filename = ltrim($filename, '.');
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - strlen($ext) - 1);
            $filename = $name . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Check if HTTPS is being used
     */
    public function is_https() {
        return is_ssl();
    }
    
    /**
     * Validate user permissions for admin actions
     */
    public function can_manage_plugin() {
        return current_user_can('manage_options');
    }
    
    /**
     * Rate limit check for uploads
     */
    public function check_upload_rate_limit($identifier, $max_uploads = 10, $time_window = 3600) {
        $transient_key = 'poxica_upload_rate_' . md5($identifier);
        $uploads = get_transient($transient_key);
        
        if ($uploads === false) {
            $uploads = 0;
        }
        
        if ($uploads >= $max_uploads) {
            return false;
        }
        
        set_transient($transient_key, $uploads + 1, $time_window);
        
        return true;
    }
    
    /**
     * Log security events
     */
    public function log_security_event($event, $details = []) {
        $log_data = [
            'event' => $event,
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql'),
            'details' => $details
        ];
        
        Poxica_Database::add_log(null, 'security_event', $event, $log_data);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Validate nonce with custom action
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $action);
    }
    
    /**
     * Clean and validate input data
     */
    public function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return esc_url_raw($input);
            case 'int':
                return intval($input);
            case 'float':
                return floatval($input);
            case 'filename':
                return $this->sanitize_filename($input);
            case 'text':
            default:
                return sanitize_text_field($input);
        }
    }
}