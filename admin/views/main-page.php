<?php
/**
 * Main admin page view for Poxica Image Uploader
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Poxica Image Uploader - Panel Principal', 'poxica-image-uploader'); ?></h1>
    
    <div class="poxica-admin-dashboard">
        <div class="poxica-stats-grid">
            <!-- Orders Statistics -->
            <div class="poxica-stat-card">
                <div class="poxica-stat-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div class="poxica-stat-content">
                    <h3><?php echo number_format($stats['total_orders']); ?></h3>
                    <p><?php _e('Total Pedidos', 'poxica-image-uploader'); ?></p>
                </div>
            </div>
            
            <!-- Pending Orders -->
            <div class="poxica-stat-card">
                <div class="poxica-stat-icon pending">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="poxica-stat-content">
                    <h3><?php echo number_format($stats['pending_orders']); ?></h3>
                    <p><?php _e('Pedidos Pendientes', 'poxica-image-uploader'); ?></p>
                </div>
            </div>
            
            <!-- Completed Orders -->
            <div class="poxica-stat-card">
                <div class="poxica-stat-icon completed">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="poxica-stat-content">
                    <h3><?php echo number_format($stats['completed_orders']); ?></h3>
                    <p><?php _e('Pedidos Completados', 'poxica-image-uploader'); ?></p>
                </div>
            </div>
            
            <!-- Total Images -->
            <div class="poxica-stat-card">
                <div class="poxica-stat-icon images">
                    <span class="dashicons dashicons-format-gallery"></span>
                </div>
                <div class="poxica-stat-content">
                    <h3><?php echo number_format($stats['total_images']); ?></h3>
                    <p><?php _e('Imágenes Subidas', 'poxica-image-uploader'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Configuration Status -->
        <div class="poxica-config-status">
            <h2><?php _e('Estado de Configuración', 'poxica-image-uploader'); ?></h2>
            
            <div class="poxica-status-items">
                <div class="poxica-status-item <?php echo $stats['drive_configured'] ? 'configured' : 'not-configured'; ?>">
                    <span class="dashicons <?php echo $stats['drive_configured'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <span class="status-text">
                        <?php echo $stats['drive_configured'] ? 
                            __('Google Drive configurado', 'poxica-image-uploader') : 
                            __('Google Drive no configurado', 'poxica-image-uploader'); ?>
                    </span>
                    <?php if (!$stats['drive_configured']): ?>
                        <a href="<?php echo admin_url('admin.php?page=poxica-settings&tab=google_drive'); ?>" class="button button-primary">
                            <?php _e('Configurar', 'poxica-image-uploader'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="poxica-status-item configured">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="status-text"><?php _e('Base de datos inicializada', 'poxica-image-uploader'); ?></span>
                </div>
                
                <div class="poxica-status-item configured">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="status-text"><?php _e('Hooks de WooCommerce activos', 'poxica-image-uploader'); ?></span>
                </div>
                
                <div class="poxica-status-item configured">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="status-text">
                        <?php 
                        $hpos_enabled = false;
                        if (function_exists('wc_get_container')) {
                            try {
                                $hpos_enabled = wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled();
                            } catch (Exception $e) {
                                $hpos_enabled = false;
                            }
                        }
                        echo $hpos_enabled ? 
                            __('Compatible con HPOS (activo)', 'poxica-image-uploader') : 
                            __('Compatible con HPOS (modo legacy)', 'poxica-image-uploader'); 
                        ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="poxica-quick-actions">
            <h2><?php _e('Acciones Rápidas', 'poxica-image-uploader'); ?></h2>
            
            <div class="poxica-action-buttons">
                <a href="<?php echo admin_url('admin.php?page=poxica-settings'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Configuración', 'poxica-image-uploader'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=poxica-logs'); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('Ver Logs', 'poxica-image-uploader'); ?>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=poxica-cleanup'); ?>" class="button">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Limpieza', 'poxica-image-uploader'); ?>
                </a>
                
                <button type="button" id="poxica-test-connection" class="button">
                    <span class="dashicons dashicons-cloud"></span>
                    <?php _e('Probar Conexión Google Drive', 'poxica-image-uploader'); ?>
                </button>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="poxica-recent-activity">
            <h2><?php _e('Actividad Reciente', 'poxica-image-uploader'); ?></h2>
            
            <?php if (!empty($stats['recent_logs'])): ?>
                <div class="poxica-logs-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Fecha', 'poxica-image-uploader'); ?></th>
                                <th><?php _e('Acción', 'poxica-image-uploader'); ?></th>
                                <th><?php _e('Mensaje', 'poxica-image-uploader'); ?></th>
                                <th><?php _e('Pedido', 'poxica-image-uploader'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_logs'] as $log): ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                                    <td>
                                        <span class="poxica-action-badge poxica-action-<?php echo esc_attr($log->action); ?>">
                                            <?php echo esc_html($log->action); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log->message); ?></td>
                                    <td>
                                        <?php if ($log->order_id): ?>
                                            <a href="<?php echo admin_url('post.php?post=' . $log->order_id . '&action=edit'); ?>">
                                                #<?php echo esc_html($log->order_id); ?>
                                            </a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="poxica-view-all">
                        <a href="<?php echo admin_url('admin.php?page=poxica-logs'); ?>" class="button">
                            <?php _e('Ver todos los logs', 'poxica-image-uploader'); ?>
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <p><?php _e('No hay actividad reciente.', 'poxica-image-uploader'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- System Info -->
    <div class="poxica-system-info">
        <h2><?php _e('Información del Sistema', 'poxica-image-uploader'); ?></h2>
        
        <div class="poxica-info-grid">
            <div class="poxica-info-item">
                <strong><?php _e('Versión del Plugin:', 'poxica-image-uploader'); ?></strong>
                <span><?php echo POXICA_PLUGIN_VERSION; ?></span>
            </div>
            
            <div class="poxica-info-item">
                <strong><?php _e('WordPress:', 'poxica-image-uploader'); ?></strong>
                <span><?php echo get_bloginfo('version'); ?></span>
            </div>
            
            <div class="poxica-info-item">
                <strong><?php _e('WooCommerce:', 'poxica-image-uploader'); ?></strong>
                <span><?php echo defined('WC_VERSION') ? WC_VERSION : __('No detectado', 'poxica-image-uploader'); ?></span>
            </div>
            
            <div class="poxica-info-item">
                <strong><?php _e('PHP:', 'poxica-image-uploader'); ?></strong>
                <span><?php echo PHP_VERSION; ?></span>
            </div>
            
            <div class="poxica-info-item">
                <strong><?php _e('Límite de memoria:', 'poxica-image-uploader'); ?></strong>
                <span><?php echo ini_get('memory_limit'); ?></span>
            </div>
            
            <div class="poxica-info-item">
                <strong><?php _e('Tamaño máximo de subida:', 'poxica-image-uploader'); ?></strong>
                <span><?php echo size_format(wp_max_upload_size()); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.poxica-admin-dashboard {
    max-width: 1200px;
    margin: 20px 0;
}

.poxica-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.poxica-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.poxica-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #0073aa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.poxica-stat-icon.pending {
    background: #f39c12;
}

.poxica-stat-icon.completed {
    background: #27ae60;
}

.poxica-stat-icon.images {
    background: #9b59b6;
}

.poxica-stat-content h3 {
    margin: 0;
    font-size: 28px;
    color: #2c3e50;
}

.poxica-stat-content p {
    margin: 5px 0 0 0;
    color: #7f8c8d;
    font-size: 14px;
}

.poxica-config-status, 
.poxica-quick-actions, 
.poxica-recent-activity,
.poxica-system-info {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
}

.poxica-config-status h2,
.poxica-quick-actions h2,
.poxica-recent-activity h2,
.poxica-system-info h2 {
    margin-top: 0;
    color: #2c3e50;
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
}

.poxica-status-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.poxica-status-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
}

.poxica-status-item.configured {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.poxica-status-item.not-configured {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.poxica-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.poxica-action-buttons .button {
    display: flex;
    align-items: center;
    gap: 8px;
}

.poxica-logs-table {
    margin-top: 15px;
}

.poxica-action-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.poxica-action-order_created,
.poxica-action-email_sent {
    background: #d1ecf1;
    color: #0c5460;
}

.poxica-action-folder_created,
.poxica-action-image_uploaded {
    background: #d4edda;
    color: #155724;
}

.poxica-action-error,
.poxica-action-failed {
    background: #f8d7da;
    color: #721c24;
}

.poxica-view-all {
    text-align: center;
    margin-top: 15px;
}

.poxica-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.poxica-info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .poxica-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .poxica-action-buttons {
        flex-direction: column;
    }
    
    .poxica-action-buttons .button {
        justify-content: center;
    }
    
    .poxica-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#poxica-test-connection').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        
        button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e("Probando...", "poxica-image-uploader"); ?>');
        
        $.post(ajaxurl, {
            action: 'poxica_test_drive_connection',
            nonce: '<?php echo wp_create_nonce("poxica_admin_nonce"); ?>'
        })
        .done(function(response) {
            if (response.success) {
                button.html('<span class="dashicons dashicons-yes-alt"></span> <?php _e("Conexión exitosa", "poxica-image-uploader"); ?>');
                setTimeout(() => button.html(originalText), 3000);
            } else {
                button.html('<span class="dashicons dashicons-dismiss"></span> <?php _e("Error de conexión", "poxica-image-uploader"); ?>');
                setTimeout(() => button.html(originalText), 3000);
                alert('Error: ' + (response.data ? response.data.message : 'Conexión fallida'));
            }
        })
        .fail(function() {
            button.html('<span class="dashicons dashicons-dismiss"></span> <?php _e("Error de conexión", "poxica-image-uploader"); ?>');
            setTimeout(() => button.html(originalText), 3000);
            alert('<?php _e("Error de comunicación con el servidor", "poxica-image-uploader"); ?>');
        })
        .always(function() {
            button.prop('disabled', false);
        });
    });
});

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</script>