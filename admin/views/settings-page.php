<?php
/**
 * Admin Settings Page Template
 * 
 * @package Poxica_Image_Uploader
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enable error reporting for debugging (remove in production)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

$active_tab = $_GET['tab'] ?? 'google_drive';
?>

<div class="wrap">
    <h1><?php _e('Configuración de Poxica Image Uploader', 'poxica-image-uploader'); ?></h1>
    
    <!-- Debug info -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <div class="notice notice-info">
        <p><strong>Debug Info:</strong> Página cargada correctamente. Active tab: <?php echo esc_html($active_tab); ?></p>
    </div>
    <?php endif; ?>
    
    <?php settings_errors('poxica_settings'); ?>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=poxica-settings&tab=google_drive" class="nav-tab <?php echo $active_tab == 'google_drive' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-cloud"></span>
            <?php _e('Google Drive', 'poxica-image-uploader'); ?>
        </a>
        <a href="?page=poxica-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('General', 'poxica-image-uploader'); ?>
        </a>
        <a href="?page=poxica-settings&tab=email" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-email"></span>
            <?php _e('Emails', 'poxica-image-uploader'); ?>
        </a>
    </nav>

    <form method="post" action="">
        <?php wp_nonce_field('poxica_save_settings', 'poxica_settings_nonce'); ?>
        
        <div class="tab-content">
            <?php if ($active_tab == 'google_drive'): ?>
                <div class="tab-pane active" id="google_drive">
                    <h2><?php _e('Configuración de Google Drive', 'poxica-image-uploader'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="poxica_google_drive_credentials"><?php _e('Credenciales JSON', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <textarea name="poxica_google_drive_credentials" id="poxica_google_drive_credentials" class="large-text code" rows="10" placeholder='{"type": "service_account", "project_id": "...", ...}'><?php echo esc_textarea(get_option('poxica_google_drive_credentials', '')); ?></textarea>
                                <p class="description">
                                    <?php _e('Pega aquí el contenido completo del archivo JSON de credenciales de Google Cloud.', 'poxica-image-uploader'); ?>
                                    <br>
                                    <a href="<?php echo admin_url('admin.php?page=poxica-uploader'); ?>" target="_blank"><?php _e('Ver guía de configuración', 'poxica-image-uploader'); ?></a>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="poxica_google_drive_root_folder"><?php _e('Carpeta Raíz ID', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="poxica_google_drive_root_folder" id="poxica_google_drive_root_folder" value="<?php echo esc_attr(get_option('poxica_google_drive_root_folder', '')); ?>" class="regular-text" placeholder="1a2B3c4D5e6F7g8H9i0J..." />
                                <p class="description">
                                    <?php _e('ID de la carpeta donde se crearán las subcarpetas de pedidos. Déjalo vacío para usar la raíz de Google Drive.', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="poxica-test-connection">
                        <h3><?php _e('Probar Conexión', 'poxica-image-uploader'); ?></h3>
                        <p>
                            <button type="button" id="test-drive-connection" class="button button-secondary">
                                <span class="dashicons dashicons-cloud"></span>
                                <?php _e('Probar Conexión con Google Drive', 'poxica-image-uploader'); ?>
                            </button>
                            <button type="button" id="debug-connection" class="button button-secondary" style="margin-left: 10px;">
                                <span class="dashicons dashicons-info"></span>
                                <?php _e('Debug Conexión', 'poxica-image-uploader'); ?>
                            </button>
                            <button type="button" id="debug-system" class="button button-secondary" style="margin-left: 10px;">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php _e('Debug Sistema', 'poxica-image-uploader'); ?>
                            </button>
                            <br><br>
                            <button type="button" id="test-jwt-only" class="button button-secondary">
                                <span class="dashicons dashicons-lock"></span>
                                <?php _e('Test JWT Solo', 'poxica-image-uploader'); ?>
                            </button>
                            <span id="test-result" style="margin-left: 10px;"></span>
                        </p>
                        <div id="test-output" style="margin-top: 10px;"></div>
                    </div>
                </div>
                
            <?php elseif ($active_tab == 'general'): ?>
                <div class="tab-pane active" id="general">
                    <h2><?php _e('Configuración General', 'poxica-image-uploader'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="poxica_unpaid_order_cleanup_days"><?php _e('Días para limpieza de pedidos no pagados', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="poxica_unpaid_order_cleanup_days" id="poxica_unpaid_order_cleanup_days" value="<?php echo esc_attr(get_option('poxica_unpaid_order_cleanup_days', 3)); ?>" min="1" max="30" class="small-text" />
                                <p class="description">
                                    <?php _e('Después de cuántos días se eliminarán automáticamente las carpetas de pedidos no pagados.', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="poxica_upload_link_expiry_days"><?php _e('Días de expiración de enlaces de subida', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="poxica_upload_link_expiry_days" id="poxica_upload_link_expiry_days" value="<?php echo esc_attr(get_option('poxica_upload_link_expiry_days', 7)); ?>" min="1" max="365" class="small-text" />
                                <p class="description">
                                    <?php _e('Después de cuántos días expiran los enlaces de subida de imágenes.', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="poxica_max_file_size"><?php _e('Tamaño máximo de archivo (MB)', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="poxica_max_file_size" id="poxica_max_file_size" value="<?php echo esc_attr(get_option('poxica_max_file_size', 10485760) / 1048576); ?>" min="1" max="100" class="small-text" />
                                <p class="description">
                                    <?php _e('Tamaño máximo permitido para las imágenes subidas (en megabytes).', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="poxica_allowed_file_types"><?php _e('Tipos de archivo permitidos', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="poxica_allowed_file_types" id="poxica_allowed_file_types" value="<?php echo esc_attr(get_option('poxica_allowed_file_types', 'jpg,jpeg,png')); ?>" class="regular-text" />
                                <p class="description">
                                    <?php _e('Extensiones de archivo permitidas, separadas por comas (ej: jpg,jpeg,png).', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
            <?php elseif ($active_tab == 'email'): ?>
                <div class="tab-pane active" id="email">
                    <h2><?php _e('Configuración de Emails', 'poxica-image-uploader'); ?></h2>
                    
                    <h3><?php _e('Email de Enlace de Subida (para clientes)', 'poxica-image-uploader'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="poxica_upload_link_email_subject"><?php _e('Asunto del email', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="poxica_upload_link_email_subject" id="poxica_upload_link_email_subject" value="<?php echo esc_attr(get_option('poxica_upload_link_email_subject', __('Sube las imágenes de tu pedido #{order_number}', 'poxica-image-uploader'))); ?>" class="large-text" />
                                <p class="description">
                                    <?php _e('Placeholders disponibles: {order_number}, {customer_name}, {site_name}', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="poxica_upload_link_email_message"><?php _e('Mensaje del email', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <?php 
                                $default_message = __("Hola {customer_name},\n\nGracias por tu pedido #{order_number}.\n\nPara completar tu pedido, necesitamos que subas las imágenes correspondientes a los productos que compraste.\n\nProductos en tu pedido:\n{order_products}\n\nPuedes subir tus imágenes usando el siguiente enlace:\n{upload_link}\n\nEste enlace expira en {expiry_days} días.\n\n¡Gracias!\nEquipo de {site_name}", 'poxica-image-uploader');
                                ?>
                                <textarea name="poxica_upload_link_email_message" id="poxica_upload_link_email_message" class="large-text code" rows="10"><?php echo esc_textarea(get_option('poxica_upload_link_email_message', $default_message)); ?></textarea>
                                <p class="description">
                                    <?php _e('Placeholders disponibles: {customer_name}, {order_number}, {order_products}, {upload_link}, {expiry_days}, {site_name}', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Email de Notificación de Completado (para administradores)', 'poxica-image-uploader'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="poxica_completion_email_subject"><?php _e('Asunto del email', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="poxica_completion_email_subject" id="poxica_completion_email_subject" value="<?php echo esc_attr(get_option('poxica_completion_email_subject', __('Pedido #{order_number} - Todas las imágenes subidas', 'poxica-image-uploader'))); ?>" class="large-text" />
                                <p class="description">
                                    <?php _e('Placeholders disponibles: {order_number}, {customer_name}, {site_name}', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="poxica_completion_email_message"><?php _e('Mensaje del email', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <?php 
                                $default_completion_message = __("El pedido #{order_number} de {customer_name} tiene todas las imágenes subidas.\n\nProductos y enlaces a Google Drive:\n{drive_links}\n\nFecha de completado: {completion_date}\n\nPuedes proceder con la producción.", 'poxica-image-uploader');
                                ?>
                                <textarea name="poxica_completion_email_message" id="poxica_completion_email_message" class="large-text code" rows="8"><?php echo esc_textarea(get_option('poxica_completion_email_message', $default_completion_message)); ?></textarea>
                                <p class="description">
                                    <?php _e('Placeholders disponibles: {order_number}, {customer_name}, {drive_links}, {completion_date}, {site_name}', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="poxica_admin_email"><?php _e('Email del administrador', 'poxica-image-uploader'); ?></label>
                            </th>
                            <td>
                                <input type="email" name="poxica_admin_email" id="poxica_admin_email" value="<?php echo esc_attr(get_option('poxica_admin_email', get_option('admin_email'))); ?>" class="regular-text" />
                                <p class="description">
                                    <?php _e('Email donde se enviarán las notificaciones de pedidos completados.', 'poxica-image-uploader'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="poxica-test-email">
                        <h3><?php _e('Probar Emails', 'poxica-image-uploader'); ?></h3>
                        <p>
                            <button type="button" id="test-email" class="button button-secondary">
                                <span class="dashicons dashicons-email"></span>
                                <?php _e('Enviar Email de Prueba', 'poxica-image-uploader'); ?>
                            </button>
                            <span id="email-test-result" style="margin-left: 10px;"></span>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php submit_button(__('Guardar Configuración', 'poxica-image-uploader')); ?>
    </form>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.nav-tab .dashicons {
    margin-right: 5px;
}

.tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.poxica-test-connection,
.poxica-test-email {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 20px;
}

.poxica-test-connection h3,
.poxica-test-email h3 {
    margin-top: 0;
}

#test-result.success,
#email-test-result.success {
    color: #46b450;
    font-weight: bold;
}

#test-result.error,
#email-test-result.error {
    color: #dc3232;
    font-weight: bold;
}

#test-output {
    background: #fff;
    border: 1px solid #ddd;
    padding: 10px;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}

#test-output.show {
    display: block;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test Google Drive connection
    $('#test-drive-connection').on('click', function() {
        var button = $(this);
        var result = $('#test-result');
        var output = $('#test-output');
        
        button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e("Probando...", "poxica-image-uploader"); ?>');
        result.removeClass('success error').text('');
        output.removeClass('show').text('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'poxica_test_drive_connection',
                nonce: '<?php echo wp_create_nonce("poxica_test_drive"); ?>',
                credentials: $('#poxica_google_drive_credentials').val(),
                root_folder: $('#poxica_google_drive_root_folder').val()
            },
            timeout: 30000, // 30 seconds timeout
            success: function(response) {
                console.log('AJAX Success:', response);
                if (response.success) {
                    result.addClass('success').text('<?php _e("✓ Conexión exitosa", "poxica-image-uploader"); ?>');
                    if (response.data.details) {
                        output.text(response.data.details).addClass('show');
                    }
                } else {
                    result.addClass('error').text('✗ ' + (response.data ? response.data.message : 'Error desconocido'));
                    if (response.data && response.data.details) {
                        output.text(response.data.details).addClass('show');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error);
                console.log('Response Text:', xhr.responseText);
                console.log('Status Code:', xhr.status);
                
                var errorMsg = '✗ Error de conexión (' + xhr.status + '): ';
                if (xhr.status === 0) {
                    errorMsg += 'Sin respuesta del servidor';
                } else if (xhr.status === 403) {
                    errorMsg += 'Sin permisos';
                } else if (xhr.status === 404) {
                    errorMsg += 'Endpoint no encontrado';
                } else if (xhr.status === 500) {
                    errorMsg += 'Error interno del servidor';
                } else {
                    errorMsg += status + ' - ' + error;
                }
                
                result.addClass('error').text(errorMsg);
                
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.data && response.data.message) {
                            output.text('Error: ' + response.data.message).addClass('show');
                        } else {
                            output.text('Response: ' + xhr.responseText).addClass('show');
                        }
                    } catch (e) {
                        output.text('Raw response: ' + xhr.responseText).addClass('show');
                    }
                }
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-cloud"></span> <?php _e("Probar Conexión con Google Drive", "poxica-image-uploader"); ?>');
            }
        });
    });
    
    // Debug connection
    $('#debug-connection').on('click', function() {
        var credentials = $('#poxica_google_drive_credentials').val();
        var result = $('#test-result');
        var output = $('#test-output');
        
        result.removeClass('success error');
        output.removeClass('show');
        
        if (!credentials.trim()) {
            result.addClass('error').text('Por favor ingresa las credenciales JSON primero');
            return;
        }
        
        try {
            var parsed = JSON.parse(credentials);
            var debugInfo = 'Credenciales JSON válidas:\n';
            debugInfo += 'Tipo: ' + (parsed.type || 'N/A') + '\n';
            debugInfo += 'Project ID: ' + (parsed.project_id || 'N/A') + '\n';
            debugInfo += 'Client Email: ' + (parsed.client_email || 'N/A') + '\n';
            debugInfo += 'Private Key ID: ' + (parsed.private_key_id || 'N/A') + '\n';
            debugInfo += 'Private Key: ' + (parsed.private_key ? 'Presente' : 'Ausente') + '\n';
            
            result.addClass('success').text('✓ JSON válido');
            output.text(debugInfo).addClass('show');
        } catch (e) {
            result.addClass('error').text('✗ JSON inválido: ' + e.message);
            output.text('Error de parsing: ' + e.message).addClass('show');
        }
    });
    
    // Debug system
    $('#debug-system').on('click', function() {
        var result = $('#test-result');
        var output = $('#test-output');
        
        result.removeClass('success error').text('Obteniendo información del sistema...');
        output.removeClass('show');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'poxica_debug_system'
            },
            success: function(response) {
                console.log('System Debug Response:', response);
                if (response.success) {
                    result.addClass('success').text('✓ Información del sistema obtenida');
                    var debugInfo = 'Información del Sistema:\n';
                    debugInfo += 'PHP Version: ' + response.data.debug_info.php_version + '\n';
                    debugInfo += 'WordPress Version: ' + response.data.debug_info.wordpress_version + '\n';
                    debugInfo += 'OpenSSL: ' + (response.data.debug_info.openssl_available ? 'Disponible' : 'NO DISPONIBLE') + '\n';
                    debugInfo += 'cURL: ' + (response.data.debug_info.curl_available ? 'Disponible' : 'NO DISPONIBLE') + '\n';
                    debugInfo += 'JSON: ' + (response.data.debug_info.json_available ? 'Disponible' : 'NO DISPONIBLE') + '\n';
                    debugInfo += 'WP Remote: ' + (response.data.debug_info.wp_remote_available ? 'Disponible' : 'NO DISPONIBLE') + '\n';
                    debugInfo += 'Memory Limit: ' + response.data.debug_info.memory_limit + '\n';
                    debugInfo += 'Max Execution Time: ' + response.data.debug_info.max_execution_time + 's\n';
                    debugInfo += 'Server Time: ' + response.data.debug_info.server_time + '\n';
                    debugInfo += 'Timezone: ' + response.data.debug_info.timezone + '\n';
                    
                    output.text(debugInfo).addClass('show');
                } else {
                    result.addClass('error').text('✗ Error obteniendo información del sistema');
                }
            },
            error: function(xhr, status, error) {
                console.log('System Debug Error:', xhr, status, error);
                result.addClass('error').text('✗ Error en debug del sistema: ' + error);
            }
        });
    });
    
    // Test JWT only
    $('#test-jwt-only').on('click', function() {
        var credentials = $('#poxica_google_drive_credentials').val();
        var result = $('#test-result');
        var output = $('#test-output');
        
        result.removeClass('success error').text('Probando creación de JWT...');
        output.removeClass('show');
        
        if (!credentials.trim()) {
            result.addClass('error').text('Por favor ingresa las credenciales JSON primero');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'poxica_test_jwt_only',
                credentials: credentials
            },
            timeout: 15000,
            success: function(response) {
                console.log('JWT Test Response:', response);
                if (response.success) {
                    result.addClass('success').text('✓ ' + response.data.message);
                    if (response.data.details) {
                        output.text(response.data.details).addClass('show');
                    }
                } else {
                    result.addClass('error').text('✗ ' + response.data.message);
                    if (response.data.details) {
                        output.text(response.data.details).addClass('show');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('JWT Test Error:', xhr, status, error);
                result.addClass('error').text('✗ Error en test JWT: ' + status + ' - ' + error);
                if (xhr.responseText) {
                    output.text('Error details: ' + xhr.responseText).addClass('show');
                }
            }
        });
    });
    
    // Test email
    $('#test-email').on('click', function() {
        var button = $(this);
        var result = $('#email-test-result');
        
        button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e("Enviando...", "poxica-image-uploader"); ?>');
        result.removeClass('success error').text('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'poxica_test_email',
                nonce: '<?php echo wp_create_nonce("poxica_test_email"); ?>',
                admin_email: $('#poxica_admin_email').val()
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').text('<?php _e("✓ Email enviado correctamente", "poxica-image-uploader"); ?>');
                } else {
                    result.addClass('error').text('✗ ' + response.data.message);
                }
            },
            error: function() {
                result.addClass('error').text('<?php _e("✗ Error al enviar email", "poxica-image-uploader"); ?>');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-email"></span> <?php _e("Enviar Email de Prueba", "poxica-image-uploader"); ?>');
            }
        });
    });
});
</script>