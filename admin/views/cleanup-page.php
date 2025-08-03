<?php
/**
 * Admin Cleanup Page Template
 * 
 * @package Poxica_Image_Uploader
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Limpieza y Mantenimiento - Poxica Image Uploader', 'poxica-image-uploader'); ?></h1>
    
    <div class="poxica-cleanup-dashboard">
        <div class="cleanup-stats">
            <h2><?php _e('Estadísticas de Limpieza', 'poxica-image-uploader'); ?></h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['unpaid_orders'] ?? 0); ?></h3>
                        <p><?php _e('Pedidos no pagados elegibles', 'poxica-image-uploader'); ?></p>
                        <small><?php printf(__('Más de %d días sin pagar', 'poxica-image-uploader'), get_option('poxica_unpaid_order_cleanup_days', 3)); ?></small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-dismiss"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['cancelled_orders'] ?? 0); ?></h3>
                        <p><?php _e('Pedidos cancelados', 'poxica-image-uploader'); ?></p>
                        <small><?php _e('Pendientes de limpieza', 'poxica-image-uploader'); ?></small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-admin-links"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['expired_tokens'] ?? 0); ?></h3>
                        <p><?php _e('Tokens expirados', 'poxica-image-uploader'); ?></p>
                        <small><?php printf(__('Más de %d días', 'poxica-image-uploader'), get_option('poxica_upload_link_expiry_days', 7)); ?></small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-media-document"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['temp_files'] ?? 0); ?></h3>
                        <p><?php _e('Archivos temporales', 'poxica-image-uploader'); ?></p>
                        <small><?php _e('Pendientes de eliminar', 'poxica-image-uploader'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="cron-status">
            <h2><?php _e('Estado del Cron', 'poxica-image-uploader'); ?></h2>
            
            <div class="status-indicator">
                <?php if ($is_cron_working): ?>
                    <span class="status-badge success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Cron funcionando correctamente', 'poxica-image-uploader'); ?>
                    </span>
                    <p class="description">
                        <?php _e('El sistema de limpieza automática está activo y funcionando. Se ejecuta diariamente para mantener el sistema optimizado.', 'poxica-image-uploader'); ?>
                    </p>
                <?php else: ?>
                    <span class="status-badge error">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Problema con el Cron', 'poxica-image-uploader'); ?>
                    </span>
                    <p class="description">
                        <?php _e('El sistema de limpieza automática no está funcionando correctamente. Considera ejecutar la limpieza manual regularmente.', 'poxica-image-uploader'); ?>
                    </p>
                <?php endif; ?>
                
                <p class="cron-schedule">
                    <strong><?php _e('Próxima ejecución programada:', 'poxica-image-uploader'); ?></strong>
                    <?php 
                    $next_cron = wp_next_scheduled('poxica_daily_cleanup');
                    if ($next_cron) {
                        echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_cron);
                    } else {
                        echo __('No programada', 'poxica-image-uploader');
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="manual-cleanup">
            <h2><?php _e('Limpieza Manual', 'poxica-image-uploader'); ?></h2>
            
            <div class="cleanup-actions">
                <div class="action-group">
                    <h3><?php _e('Ejecutar Limpieza Completa', 'poxica-image-uploader'); ?></h3>
                    <p><?php _e('Ejecuta todas las tareas de limpieza de una vez: pedidos no pagados, cancelados, tokens expirados y archivos temporales.', 'poxica-image-uploader'); ?></p>
                    <button type="button" id="run-full-cleanup" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Ejecutar Limpieza Completa', 'poxica-image-uploader'); ?>
                    </button>
                    <div id="cleanup-result" style="margin-top: 10px;"></div>
                </div>
                
                <div class="action-group">
                    <h3><?php _e('Acciones Específicas', 'poxica-image-uploader'); ?></h3>
                    <p><?php _e('Ejecuta tareas de limpieza específicas según sea necesario.', 'poxica-image-uploader'); ?></p>
                    
                    <div class="specific-actions">
                        <button type="button" class="button cleanup-action" data-action="unpaid">
                            <span class="dashicons dashicons-clock"></span>
                            <?php _e('Limpiar Pedidos No Pagados', 'poxica-image-uploader'); ?>
                        </button>
                        
                        <button type="button" class="button cleanup-action" data-action="cancelled">
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php _e('Limpiar Pedidos Cancelados', 'poxica-image-uploader'); ?>
                        </button>
                        
                        <button type="button" class="button cleanup-action" data-action="tokens">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php _e('Limpiar Tokens Expirados', 'poxica-image-uploader'); ?>
                        </button>
                        
                        <button type="button" class="button cleanup-action" data-action="temp_files">
                            <span class="dashicons dashicons-media-document"></span>
                            <?php _e('Limpiar Archivos Temporales', 'poxica-image-uploader'); ?>
                        </button>
                    </div>
                    
                    <div id="specific-cleanup-result" style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>
        
        <div class="cleanup-history">
            <h2><?php _e('Historial de Limpieza', 'poxica-image-uploader'); ?></h2>
            
            <p class="description">
                <?php _e('Para ver el historial detallado de las operaciones de limpieza, consulta la', 'poxica-image-uploader'); ?>
                <a href="<?php echo admin_url('admin.php?page=poxica-logs'); ?>"><?php _e('página de logs', 'poxica-image-uploader'); ?></a>.
            </p>
            
            <div class="recent-cleanup-summary">
                <h3><?php _e('Resumen de Últimas 24 Horas', 'poxica-image-uploader'); ?></h3>
                <!-- This would show recent cleanup activity -->
                <p><em><?php _e('Funcionalidad próximamente disponible', 'poxica-image-uploader'); ?></em></p>
            </div>
        </div>
    </div>
</div>

<style>
.poxica-cleanup-dashboard {
    display: grid;
    gap: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #f1f1f1;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stat-icon .dashicons {
    font-size: 24px;
    color: #555;
}

.stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: bold;
    color: #0073aa;
}

.stat-content p {
    margin: 0 0 5px 0;
    font-weight: 600;
    color: #333;
}

.stat-content small {
    color: #666;
    font-size: 12px;
}

.cron-status, .manual-cleanup, .cleanup-history {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.status-indicator {
    margin-top: 15px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 4px;
    font-weight: bold;
    margin-bottom: 10px;
}

.status-badge.success {
    background: #ecf7ed;
    color: #5b841b;
    border: 1px solid #c6e1c7;
}

.status-badge.error {
    background: #fbeaea;
    color: #d94f4f;
    border: 1px solid #f0b7b7;
}

.status-badge .dashicons {
    margin-right: 5px;
}

.cron-schedule {
    margin-top: 15px;
    padding: 10px;
    background: #f9f9f9;
    border-left: 4px solid #0073aa;
}

.cleanup-actions {
    margin-top: 15px;
}

.action-group {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.action-group:last-child {
    border-bottom: none;
}

.action-group h3 {
    margin-top: 0;
    color: #0073aa;
}

.specific-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.cleanup-action .dashicons {
    margin-right: 5px;
}

#cleanup-result.success,
#specific-cleanup-result.success {
    color: #46b450;
    font-weight: bold;
}

#cleanup-result.error,
#specific-cleanup-result.error {
    color: #dc3232;
    font-weight: bold;
}

.recent-cleanup-summary {
    margin-top: 15px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.recent-cleanup-summary h3 {
    margin-top: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Full cleanup
    $('#run-full-cleanup').on('click', function() {
        var button = $(this);
        var result = $('#cleanup-result');
        
        button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e("Ejecutando limpieza...", "poxica-image-uploader"); ?>');
        result.removeClass('success error').text('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'poxica_manual_cleanup',
                nonce: '<?php echo wp_create_nonce("poxica_manual_cleanup"); ?>',
                cleanup_type: 'full'
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').text('✓ ' + response.data.message);
                    // Refresh stats
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    result.addClass('error').text('✗ ' + response.data.message);
                }
            },
            error: function() {
                result.addClass('error').text('<?php _e("✗ Error al ejecutar la limpieza", "poxica-image-uploader"); ?>');
            },
            complete: function() {
                button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> <?php _e("Ejecutar Limpieza Completa", "poxica-image-uploader"); ?>');
            }
        });
    });
    
    // Specific cleanup actions
    $('.cleanup-action').on('click', function() {
        var button = $(this);
        var action = button.data('action');
        var result = $('#specific-cleanup-result');
        
        button.prop('disabled', true);
        result.removeClass('success error').text('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'poxica_manual_cleanup',
                nonce: '<?php echo wp_create_nonce("poxica_manual_cleanup"); ?>',
                cleanup_type: action
            },
            success: function(response) {
                if (response.success) {
                    result.addClass('success').text('✓ ' + response.data.message);
                    // Refresh stats after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    result.addClass('error').text('✗ ' + response.data.message);
                }
            },
            error: function() {
                result.addClass('error').text('<?php _e("✗ Error al ejecutar la limpieza específica", "poxica-image-uploader"); ?>');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script>