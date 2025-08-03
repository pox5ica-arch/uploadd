<?php
/**
 * Admin Logs Page Template
 * 
 * @package Poxica_Image_Uploader
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$page = $_GET['paged'] ?? 1;
$total_logs = count(Poxica_Database::get_logs(999999));
$per_page = 50;
$total_pages = ceil($total_logs / $per_page);
?>

<div class="wrap">
    <h1><?php _e('Logs de Actividad - Poxica Image Uploader', 'poxica-image-uploader'); ?></h1>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <p class="description">
                <?php printf(__('Mostrando los registros más recientes. Total: %d entradas', 'poxica-image-uploader'), $total_logs); ?>
            </p>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(__('%d elementos', 'poxica-image-uploader'), $total_logs); ?></span>
            <?php
            $page_links = paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page
            ]);
            echo $page_links;
            ?>
        </div>
        <?php endif; ?>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-date"><?php _e('Fecha', 'poxica-image-uploader'); ?></th>
                <th scope="col" class="manage-column column-type"><?php _e('Tipo', 'poxica-image-uploader'); ?></th>
                <th scope="col" class="manage-column column-message"><?php _e('Mensaje', 'poxica-image-uploader'); ?></th>
                <th scope="col" class="manage-column column-order"><?php _e('Pedido', 'poxica-image-uploader'); ?></th>
                <th scope="col" class="manage-column column-details"><?php _e('Detalles', 'poxica-image-uploader'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px;">
                    <em><?php _e('No hay logs registrados aún.', 'poxica-image-uploader'); ?></em>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="column-date">
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?>
                    </td>
                    <td class="column-type">
                        <?php
                        $type_classes = [
                            'info' => 'notice-info',
                            'success' => 'notice-success', 
                            'warning' => 'notice-warning',
                            'error' => 'notice-error'
                        ];
                        $class = $type_classes[$log->type] ?? 'notice-info';
                        ?>
                        <span class="log-type <?php echo esc_attr($class); ?>">
                            <?php echo esc_html(ucfirst($log->type)); ?>
                        </span>
                    </td>
                    <td class="column-message">
                        <strong><?php echo esc_html($log->message); ?></strong>
                    </td>
                    <td class="column-order">
                        <?php if ($log->order_id): ?>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $log->order_id . '&action=edit')); ?>" target="_blank">
                                #<?php echo esc_html($log->order_id); ?>
                            </a>
                        <?php else: ?>
                            <span class="na">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-details">
                        <?php if ($log->details): ?>
                            <details>
                                <summary style="cursor: pointer; color: #0073aa;"><?php _e('Ver detalles', 'poxica-image-uploader'); ?></summary>
                                <pre style="background: #f9f9f9; padding: 10px; margin-top: 5px; border: 1px solid #ddd; font-size: 11px; max-height: 200px; overflow-y: auto;"><?php echo esc_html($log->details); ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="na">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php printf(__('%d elementos', 'poxica-image-uploader'), $total_logs); ?></span>
            <?php echo $page_links; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.log-type {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.log-type.notice-info {
    background: #e5f5ff;
    color: #0073aa;
    border: 1px solid #b3d9ff;
}

.log-type.notice-success {
    background: #ecf7ed;
    color: #5b841b;
    border: 1px solid #c6e1c7;
}

.log-type.notice-warning {
    background: #fff8e5;
    color: #8a6914;
    border: 1px solid #f0e68c;
}

.log-type.notice-error {
    background: #fbeaea;
    color: #d94f4f;
    border: 1px solid #f0b7b7;
}

.column-date {
    width: 150px;
}

.column-type {
    width: 80px;
}

.column-order {
    width: 80px;
}

.column-details {
    width: 120px;
}

.na {
    color: #999;
    font-style: italic;
}

details summary {
    outline: none;
}

details[open] summary {
    margin-bottom: 5px;
}
</style>