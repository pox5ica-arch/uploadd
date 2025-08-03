<?php
/**
 * Email notifications for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}

class Poxica_Email_Notifications {
    
    /**
     * Send upload link email to customer
     */
    public function send_upload_link_email($order, $upload_url) {
        $to = $order->get_billing_email();
        $subject = $this->get_email_subject('upload_link', $order);
        $message = $this->get_email_message('upload_link', $order, ['upload_url' => $upload_url]);
        $headers = $this->get_email_headers();
        
        $sent = wp_mail($to, $subject, $message, $headers);
        
        if ($sent) {
            Poxica_Database::add_log($order->get_id(), 'email_sent', "Email de enlace de subida enviado a: $to");
        } else {
            Poxica_Database::add_log($order->get_id(), 'email_failed', "Error enviando email de enlace de subida a: $to");
        }
        
        return $sent;
    }
    
    /**
     * Send completion notification to admin
     */
    public function send_completion_notification($order, $upload_status) {
        $admin_email = get_option('admin_email');
        $additional_emails = get_option('poxica_admin_notification_emails', '');
        
        $recipients = [$admin_email];
        
        if (!empty($additional_emails)) {
            $additional = array_map('trim', explode(',', $additional_emails));
            $recipients = array_merge($recipients, $additional);
        }
        
        $subject = $this->get_email_subject('completion_notification', $order);
        $message = $this->get_email_message('completion_notification', $order, [
            'upload_status' => $upload_status,
            'drive_links' => $this->get_drive_links($upload_status)
        ]);
        $headers = $this->get_email_headers();
        
        $sent_count = 0;
        
        foreach ($recipients as $email) {
            if (is_email($email)) {
                $sent = wp_mail($email, $subject, $message, $headers);
                if ($sent) {
                    $sent_count++;
                }
            }
        }
        
        if ($sent_count > 0) {
            Poxica_Database::add_log($order->get_id(), 'admin_notification_sent', "Notificación de completado enviada a $sent_count destinatarios");
        } else {
            Poxica_Database::add_log($order->get_id(), 'admin_notification_failed', "Error enviando notificación de completado");
        }
        
        return $sent_count > 0;
    }
    
    /**
     * Get email subject
     */
    private function get_email_subject($template, $order) {
        $placeholders = [
            '{order_number}' => $order->get_order_number(),
            '{order_id}' => $order->get_id(),
            '{customer_name}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '{site_name}' => get_bloginfo('name'),
            '{date}' => date_i18n(get_option('date_format'))
        ];
        
        switch ($template) {
            case 'upload_link':
                $subject = get_option('poxica_email_upload_link_subject', 
                    __('Sube las imágenes para tu pedido #{order_number}', 'poxica-image-uploader'));
                break;
                
            case 'completion_notification':
                $subject = get_option('poxica_email_completion_subject', 
                    __('[{site_name}] Pedido #{order_number} - Todas las imágenes subidas', 'poxica-image-uploader'));
                break;
                
            default:
                $subject = __('Notificación de Poxica Image Uploader', 'poxica-image-uploader');
        }
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $subject);
    }
    
    /**
     * Get email message content
     */
    private function get_email_message($template, $order, $extra_data = []) {
        $placeholders = [
            '{order_number}' => $order->get_order_number(),
            '{order_id}' => $order->get_id(),
            '{customer_name}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
            '{date}' => date_i18n(get_option('date_format')),
            '{order_date}' => $order->get_date_created()->date_i18n(get_option('date_format')),
            '{order_total}' => $order->get_formatted_order_total()
        ];
        
        // Add extra placeholders
        if (isset($extra_data['upload_url'])) {
            $placeholders['{upload_url}'] = $extra_data['upload_url'];
        }
        
        if (isset($extra_data['drive_links'])) {
            $placeholders['{drive_links}'] = $extra_data['drive_links'];
        }
        
        if (isset($extra_data['upload_status'])) {
            $placeholders['{order_products}'] = $this->format_order_products($extra_data['upload_status']);
        }
        
        switch ($template) {
            case 'upload_link':
                $message = get_option('poxica_email_upload_link_message', $this->get_default_upload_link_message());
                break;
                
            case 'completion_notification':
                $message = get_option('poxica_email_completion_message', $this->get_default_completion_message());
                break;
                
            default:
                $message = __('Notificación de Poxica Image Uploader', 'poxica-image-uploader');
        }
        
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
        
        // Convert to HTML if not already
        if (strpos($message, '<html>') === false) {
            $message = $this->text_to_html($message);
        }
        
        return $message;
    }
    
    /**
     * Get default upload link email message
     */
    private function get_default_upload_link_message() {
        return __('Hola {customer_name},

¡Gracias por tu pedido #{order_number}!

Para completar tu pedido, necesitamos que subas las imágenes correspondientes a los productos que has comprado.

**Enlace para subir imágenes:**
{upload_url}

**Detalles del pedido:**
- Número de pedido: #{order_number}
- Fecha: {order_date}
- Total: {order_total}

**Instrucciones importantes:**
1. Haz clic en el enlace de arriba para acceder a la página de subida
2. Sube una imagen para cada producto/unidad que has comprado
3. Solo se aceptan archivos JPG y PNG
4. El enlace expirará en 7 días

Si tienes alguna pregunta, no dudes en contactarnos.

Saludos,
Equipo de {site_name}
{site_url}', 'poxica-image-uploader');
    }
    
    /**
     * Get default completion notification message
     */
    private function get_default_completion_message() {
        return __('Hola,

El cliente {customer_name} ha completado la subida de todas las imágenes para el pedido #{order_number}.

**Detalles del pedido:**
- Número de pedido: #{order_number}
- Cliente: {customer_name}
- Fecha del pedido: {order_date}
- Total: {order_total}

**Productos y archivos subidos:**
{order_products}

**Enlaces a Google Drive:**
{drive_links}

Ya puedes proceder con la producción de este pedido.

Saludos,
Sistema Poxica Image Uploader', 'poxica-image-uploader');
    }
    
    /**
     * Format order products for email
     */
    private function format_order_products($upload_status) {
        $output = '';
        
        foreach ($upload_status['products'] as $product_status) {
            $product = $product_status['product'];
            $output .= "- {$product->product_name}";
            
            if ($product->variation_details) {
                $output .= " ({$product->variation_details})";
            }
            
            $output .= " - Cantidad: {$product->quantity} - Subidas: {$product_status['uploaded']}\n";
        }
        
        return $output;
    }
    
    /**
     * Get Google Drive links for email
     */
    private function get_drive_links($upload_status) {
        $google_drive = new Poxica_Google_Drive();
        $links = '';
        
        // Main order folder link
        if ($upload_status['order']->drive_folder_id) {
            $order_link = $google_drive->get_folder_link($upload_status['order']->drive_folder_id);
            $links .= "Carpeta principal del pedido: $order_link\n\n";
        }
        
        // Individual product folder links
        foreach ($upload_status['products'] as $product_status) {
            $product = $product_status['product'];
            if ($product->drive_folder_id) {
                $product_link = $google_drive->get_folder_link($product->drive_folder_id);
                $links .= "- {$product->product_name}: $product_link\n";
            }
        }
        
        return $links;
    }
    
    /**
     * Get email headers
     */
    private function get_email_headers() {
        $from_name = get_option('poxica_email_from_name', get_bloginfo('name'));
        $from_email = get_option('poxica_email_from_email', get_option('admin_email'));
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: $from_name <$from_email>"
        ];
        
        return $headers;
    }
    
    /**
     * Convert plain text to HTML
     */
    private function text_to_html($text) {
        $text = nl2br(esc_html($text));
        
        // Convert URLs to links
        $text = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1">$1</a>', $text);
        
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; }
        .content { padding: 20px 0; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 14px; color: #666; }
        a { color: #007cba; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>" . get_bloginfo('name') . "</h2>
        </div>
        <div class='content'>
            $text
        </div>
        <div class='footer'>
            <p>Este email fue enviado automáticamente por el sistema Poxica Image Uploader.</p>
        </div>
    </div>
</body>
</html>";
    }
    
    /**
     * Send test email (for admin testing)
     */
    public function send_test_email($to_email, $template = 'upload_link') {
        // Create a fake order for testing
        $test_data = [
            'order_number' => '12345',
            'order_id' => '12345',
            'customer_name' => 'Cliente de Prueba',
            'order_date' => date_i18n(get_option('date_format')),
            'order_total' => '$100.00',
            'upload_url' => home_url('poxica-upload/12345/test-token/')
        ];
        
        $subject = $this->get_test_email_subject($template);
        $message = $this->get_test_email_message($template, $test_data);
        $headers = $this->get_email_headers();
        
        return wp_mail($to_email, $subject, $message, $headers);
    }
    
    /**
     * Get test email subject
     */
    private function get_test_email_subject($template) {
        switch ($template) {
            case 'upload_link':
                return '[PRUEBA] ' . __('Sube las imágenes para tu pedido #12345', 'poxica-image-uploader');
            case 'completion_notification':
                return '[PRUEBA] ' . __('Pedido #12345 - Todas las imágenes subidas', 'poxica-image-uploader');
            default:
                return '[PRUEBA] ' . __('Email de Poxica Image Uploader', 'poxica-image-uploader');
        }
    }
    
    /**
     * Get test email message
     */
    private function get_test_email_message($template, $test_data) {
        $placeholders = [
            '{order_number}' => $test_data['order_number'],
            '{order_id}' => $test_data['order_id'],
            '{customer_name}' => $test_data['customer_name'],
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
            '{date}' => $test_data['order_date'],
            '{order_date}' => $test_data['order_date'],
            '{order_total}' => $test_data['order_total'],
            '{upload_url}' => $test_data['upload_url'],
            '{order_products}' => '- Producto de Prueba (20x30 horizontal) - Cantidad: 2 - Subidas: 2',
            '{drive_links}' => 'Carpeta principal: https://drive.google.com/drive/folders/test-folder-id'
        ];
        
        switch ($template) {
            case 'upload_link':
                $message = get_option('poxica_email_upload_link_message', $this->get_default_upload_link_message());
                break;
            case 'completion_notification':
                $message = get_option('poxica_email_completion_message', $this->get_default_completion_message());
                break;
            default:
                $message = 'Este es un email de prueba de Poxica Image Uploader.';
        }
        
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $message);
        $message = "**ESTE ES UN EMAIL DE PRUEBA**\n\n" . $message;
        
        return $this->text_to_html($message);
    }
}