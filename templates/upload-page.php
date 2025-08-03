<?php
/**
 * Upload page template for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}

try {
    $order_id = get_query_var('order_id');
    $token = get_query_var('token');
    
    if (!$order_id || !$token) {
        wp_die(__('Parámetros de subida inválidos', 'poxica-image-uploader'));
    }
    
    $upload_handler = new Poxica_Upload_Handler(new Poxica_Google_Drive());
    $data = $upload_handler->get_upload_page_data($order_id, $token);
    
} catch (Exception $e) {
    wp_die($e->getMessage());
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php printf(__('Subir imágenes - Pedido #%s', 'poxica-image-uploader'), $data['order']->get_order_number()); ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body class="poxica-upload-page">

<div class="poxica-container">
    <header class="poxica-header">
        <div class="poxica-logo">
            <h1><?php bloginfo('name'); ?></h1>
        </div>
        <div class="poxica-order-info">
            <h2><?php printf(__('Pedido #%s', 'poxica-image-uploader'), $data['order']->get_order_number()); ?></h2>
            <p><?php printf(__('Cliente: %s', 'poxica-image-uploader'), $data['order']->get_billing_first_name() . ' ' . $data['order']->get_billing_last_name()); ?></p>
        </div>
    </header>

    <main class="poxica-main">
        <?php if (!$data['is_ready']): ?>
            <div class="poxica-message poxica-warning">
                <h3><?php _e('Pedido no listo para subir imágenes', 'poxica-image-uploader'); ?></h3>
                <p><?php _e('Tu pedido debe estar pagado antes de poder subir imágenes. Por favor, completa el pago y vuelve a intentar.', 'poxica-image-uploader'); ?></p>
            </div>
        <?php elseif ($data['upload_status']['complete']): ?>
            <div class="poxica-message poxica-success">
                <h3><?php _e('¡Todas las imágenes han sido subidas!', 'poxica-image-uploader'); ?></h3>
                <p><?php _e('Hemos recibido todas las imágenes de tu pedido. Te contactaremos pronto con los detalles de producción.', 'poxica-image-uploader'); ?></p>
            </div>
        <?php else: ?>
            <div class="poxica-upload-section">
                <div class="poxica-progress-header">
                    <h3><?php _e('Sube las imágenes de tu pedido', 'poxica-image-uploader'); ?></h3>
                    <div class="poxica-progress-bar">
                        <div class="poxica-progress-fill" style="width: <?php echo ($data['upload_status']['total_uploaded'] / $data['upload_status']['total_needed']) * 100; ?>%"></div>
                    </div>
                    <p class="poxica-progress-text">
                        <?php printf(__('%d de %d imágenes subidas', 'poxica-image-uploader'), 
                                   $data['upload_status']['total_uploaded'], 
                                   $data['upload_status']['total_needed']); ?>
                    </p>
                </div>

                <div class="poxica-instructions">
                    <h4><?php _e('Instrucciones:', 'poxica-image-uploader'); ?></h4>
                    <ul>
                        <li><?php _e('Arrastra y suelta tus imágenes en las áreas correspondientes', 'poxica-image-uploader'); ?></li>
                        <li><?php printf(__('Solo se aceptan archivos: %s', 'poxica-image-uploader'), str_replace(',', ', ', $data['allowed_types'])); ?></li>
                        <li><?php printf(__('Tamaño máximo por archivo: %s', 'poxica-image-uploader'), size_format($data['max_file_size'])); ?></li>
                        <li><?php _e('Sube una imagen para cada unidad de producto que has comprado', 'poxica-image-uploader'); ?></li>
                    </ul>
                </div>

                <div class="poxica-products">
                    <?php foreach ($data['upload_status']['products'] as $product_status): ?>
                        <div class="poxica-product">
                            <div class="poxica-product-header">
                                <h4><?php echo esc_html($product_status['product']->product_name); ?></h4>
                                <?php if ($product_status['product']->variation_details): ?>
                                    <p class="poxica-variation"><?php echo esc_html($product_status['product']->variation_details); ?></p>
                                <?php endif; ?>
                                <div class="poxica-product-progress">
                                    <span class="poxica-uploaded-count"><?php echo $product_status['uploaded']; ?></span>
                                    <span class="poxica-separator">/</span>
                                    <span class="poxica-total-count"><?php echo $product_status['needed']; ?></span>
                                    <?php if ($product_status['complete']): ?>
                                        <span class="poxica-complete-badge"><?php _e('Completado', 'poxica-image-uploader'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="poxica-product-units">
                                <?php foreach ($product_status['units'] as $unit): ?>
                                    <div class="poxica-unit <?php echo $unit['has_image'] ? 'poxica-unit-completed' : ''; ?>">
                                        <div class="poxica-unit-header">
                                            <h5><?php printf(__('Unidad %d', 'poxica-image-uploader'), $unit['unit_number']); ?></h5>
                                            <?php if ($unit['has_image']): ?>
                                                <span class="poxica-status poxica-status-uploaded"><?php _e('Subida', 'poxica-image-uploader'); ?></span>
                                            <?php else: ?>
                                                <span class="poxica-status poxica-status-pending"><?php _e('Pendiente', 'poxica-image-uploader'); ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!$unit['has_image']): ?>
                                            <div class="poxica-dropzone" 
                                                 data-product-id="<?php echo $product_status['product']->id; ?>"
                                                 data-unit-number="<?php echo $unit['unit_number']; ?>">
                                                <div class="poxica-dropzone-content">
                                                    <div class="poxica-dropzone-icon">
                                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M14 2H6C4.9 2 4 2.9 4 4V20C4 21.1 4.89 22 5.99 22H18C19.1 22 20 21.1 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <polyline points="10,9 9,9 9,10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </svg>
                                                    </div>
                                                    <p class="poxica-dropzone-text"><?php _e('Arrastra tu imagen aquí o haz clic para seleccionar', 'poxica-image-uploader'); ?></p>
                                                    <button type="button" class="poxica-select-file-btn"><?php _e('Seleccionar archivo', 'poxica-image-uploader'); ?></button>
                                                </div>
                                                <div class="poxica-upload-progress" style="display: none;">
                                                    <div class="poxica-progress-bar">
                                                        <div class="poxica-progress-fill"></div>
                                                    </div>
                                                    <p class="poxica-upload-status"><?php _e('Subiendo...', 'poxica-image-uploader'); ?></p>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="poxica-uploaded-image">
                                                <div class="poxica-upload-success">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M22 11.08V12C21.9988 14.1564 21.3005 16.2547 20.0093 17.9818C18.7182 19.709 16.9033 20.9725 14.8354 21.5839C12.7674 22.1953 10.5573 22.1219 8.53447 21.3746C6.51168 20.6273 4.78465 19.2461 3.61096 17.4371C2.43727 15.628 1.87979 13.4906 2.02168 11.3412C2.16356 9.19173 2.99721 7.14459 4.39394 5.49695C5.79067 3.84930 7.67293 2.69547 9.75166 2.1917C11.8304 1.68794 14.0089 1.84809 15.9999 2.65"
                                                              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <polyline points="22,4 12,14.01 9,11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    <p><?php _e('Imagen subida correctamente', 'poxica-image-uploader'); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="poxica-footer">
        <p><?php printf(__('Powered by %s', 'poxica-image-uploader'), '<strong>Poxica Image Uploader</strong>'); ?></p>
        <?php if (!$data['upload_status']['complete']): ?>
            <p class="poxica-expiry-notice">
                <?php printf(__('Este enlace expira el: %s', 'poxica-image-uploader'), 
                           date_i18n(get_option('date_format') . ' ' . get_option('time_format'), 
                                   strtotime($data['poxica_order']->token_expires))); ?>
            </p>
        <?php endif; ?>
    </footer>
</div>

<!-- Hidden form for file uploads -->
<form id="poxica-upload-form" style="display: none;">
    <input type="file" id="poxica-file-input" accept="image/jpeg,image/jpg,image/png">
    <input type="hidden" id="poxica-order-id" value="<?php echo esc_attr($order_id); ?>">
    <input type="hidden" id="poxica-token" value="<?php echo esc_attr($token); ?>">
</form>

<!-- Success Modal -->
<div id="poxica-success-modal" class="poxica-modal" style="display: none;">
    <div class="poxica-modal-content">
        <div class="poxica-modal-header">
            <h3><?php _e('¡Éxito!', 'poxica-image-uploader'); ?></h3>
        </div>
        <div class="poxica-modal-body">
            <p id="poxica-success-message"></p>
        </div>
        <div class="poxica-modal-footer">
            <button type="button" class="poxica-btn poxica-btn-primary" onclick="poxicaCloseModal()"><?php _e('Continuar', 'poxica-image-uploader'); ?></button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="poxica-error-modal" class="poxica-modal" style="display: none;">
    <div class="poxica-modal-content">
        <div class="poxica-modal-header">
            <h3><?php _e('Error', 'poxica-image-uploader'); ?></h3>
        </div>
        <div class="poxica-modal-body">
            <p id="poxica-error-message"></p>
        </div>
        <div class="poxica-modal-footer">
            <button type="button" class="poxica-btn poxica-btn-secondary" onclick="poxicaCloseModal()"><?php _e('Cerrar', 'poxica-image-uploader'); ?></button>
        </div>
    </div>
</div>

<script>
function poxicaCloseModal() {
    document.getElementById('poxica-success-modal').style.display = 'none';
    document.getElementById('poxica-error-modal').style.display = 'none';
}

function poxicaShowModal(type, message) {
    if (type === 'success') {
        document.getElementById('poxica-success-message').textContent = message;
        document.getElementById('poxica-success-modal').style.display = 'flex';
    } else {
        document.getElementById('poxica-error-message').textContent = message;
        document.getElementById('poxica-error-modal').style.display = 'flex';
    }
}

// Check if all uploads are complete and show completion message
function poxicaCheckAllComplete() {
    const pendingUnits = document.querySelectorAll('.poxica-unit:not(.poxica-unit-completed)');
    if (pendingUnits.length === 0) {
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}
</script>

<?php wp_footer(); ?>
</body>
</html>