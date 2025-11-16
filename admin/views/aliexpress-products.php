<?php
/**
 * Vista de Productos AliExpress
 */

if (!defined('ABSPATH')) exit;

require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-aliexpress.php';
$aliexpress = new Ad_Platform_AliExpress();

$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
?>

<div class="wrap">
    <h1><?php _e('Productos de AliExpress', 'ad-platform'); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="?post_type=adp_ad&page=adp-aliexpress&tab=pending" class="nav-tab <?php echo $tab === 'pending' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Pendientes', 'ad-platform'); ?>
        </a>
        <a href="?post_type=adp_ad&page=adp-aliexpress&tab=approved" class="nav-tab <?php echo $tab === 'approved' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Aprobados', 'ad-platform'); ?>
        </a>
        <a href="?post_type=adp_ad&page=adp-aliexpress&tab=search" class="nav-tab <?php echo $tab === 'search' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Buscar', 'ad-platform'); ?>
        </a>
    </nav>

    <?php if ($tab === 'pending'): ?>
        <div class="adp-tab-content">
            <h2><?php _e('Productos Pendientes de Aprobación', 'ad-platform'); ?></h2>
            <p><?php _e('Estos productos fueron encontrados automáticamente y están esperando tu aprobación.', 'ad-platform'); ?></p>

            <?php
            $products = $aliexpress->get_pending_products(50);

            if (empty($products)):
            ?>
                <div class="notice notice-info">
                    <p><?php _e('No hay productos pendientes de aprobación.', 'ad-platform'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Producto', 'ad-platform'); ?></th>
                            <th><?php _e('Precio', 'ad-platform'); ?></th>
                            <th><?php _e('Comisión', 'ad-platform'); ?></th>
                            <th><?php _e('Rating', 'ad-platform'); ?></th>
                            <th><?php _e('Ventas', 'ad-platform'); ?></th>
                            <th><?php _e('Score', 'ad-platform'); ?></th>
                            <th><?php _e('Peso Manual', 'ad-platform'); ?></th>
                            <th><?php _e('Acciones', 'ad-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if ($product['image_url']): ?>
                                            <img src="<?php echo esc_url($product['image_url']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo esc_html($product['title']); ?></strong><br>
                                            <small><?php echo esc_html($product['keywords']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    $<?php echo number_format($product['price'], 2); ?>
                                    <?php if ($product['discount_percent'] > 0): ?>
                                        <br><span class="adp-badge"><?php echo $product['discount_percent']; ?>% OFF</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $product['commission_rate']; ?>%</strong><br>
                                    <small>$<?php echo number_format($product['commission_value'], 2); ?></small>
                                </td>
                                <td>⭐ <?php echo number_format($product['rating'], 1); ?></td>
                                <td><?php echo number_format($product['orders_count']); ?></td>
                                <td><strong><?php echo number_format($product['auto_score'], 2); ?></strong></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('adp_aliexpress_nonce'); ?>
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="manual_weight" value="5" min="1" max="10" step="1" class="small-text">
                                    </form>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('adp_aliexpress_nonce'); ?>
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="manual_weight" value="5">
                                        <button type="submit" name="adp_approve_product" class="button button-primary button-small">✅ <?php _e('Aprobar', 'ad-platform'); ?></button>
                                    </form>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('adp_aliexpress_nonce'); ?>
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="adp_reject_product" class="button button-small">❌ <?php _e('Rechazar', 'ad-platform'); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php elseif ($tab === 'approved'): ?>
        <div class="adp-tab-content">
            <h2><?php _e('Productos Aprobados', 'ad-platform'); ?></h2>
            <p><?php _e('Banco de productos que se mostrarán como anuncios.', 'ad-platform'); ?></p>

            <?php
            $products = $aliexpress->get_approved_products(100);

            if (empty($products)):
            ?>
                <div class="notice notice-info">
                    <p><?php _e('No tienes productos aprobados aún.', 'ad-platform'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Producto', 'ad-platform'); ?></th>
                            <th><?php _e('Precio', 'ad-platform'); ?></th>
                            <th><?php _e('Comisión', 'ad-platform'); ?></th>
                            <th><?php _e('Peso Manual', 'ad-platform'); ?></th>
                            <th><?php _e('Impresiones', 'ad-platform'); ?></th>
                            <th><?php _e('Clicks', 'ad-platform'); ?></th>
                            <th><?php _e('CTR', 'ad-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product):
                            $ctr = $product['impressions_count'] > 0 ? ($product['clicks_count'] / $product['impressions_count'] * 100) : 0;
                        ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if ($product['image_url']): ?>
                                            <img src="<?php echo esc_url($product['image_url']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo esc_html($product['title']); ?></strong><br>
                                            <small><?php echo esc_html($product['keywords']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php echo $product['commission_rate']; ?>%<br>
                                    <small>$<?php echo number_format($product['commission_value'], 2); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $product['manual_weight']; ?></strong>
                                    <?php if ($product['manual_weight'] > 0): ?>
                                        <span style="color: green;">⬆️</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($product['impressions_count']); ?></td>
                                <td><?php echo number_format($product['clicks_count']); ?></td>
                                <td><?php echo number_format($ctr, 2); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php elseif ($tab === 'search'): ?>
        <div class="adp-tab-content">
            <h2><?php _e('Buscar Productos Manualmente', 'ad-platform'); ?></h2>

            <div class="notice notice-warning">
                <p><?php _e('⚠️ La búsqueda manual requiere configurar las credenciales de API de AliExpress en la pestaña Configuración.', 'ad-platform'); ?></p>
            </div>

            <form method="get" style="margin: 20px 0;">
                <input type="hidden" name="post_type" value="adp_ad">
                <input type="hidden" name="page" value="adp-aliexpress">
                <input type="hidden" name="tab" value="search">

                <input type="text" name="keywords" value="<?php echo esc_attr($_GET['keywords'] ?? ''); ?>" placeholder="<?php _e('Buscar productos...', 'ad-platform'); ?>" class="regular-text">
                <button type="submit" class="button button-primary"><?php _e('Buscar', 'ad-platform'); ?></button>
            </form>

            <?php
            if (isset($_GET['keywords']) && !empty($_GET['keywords'])):
                $keywords = sanitize_text_field($_GET['keywords']);
                $results = $aliexpress->search_products($keywords);

                if (empty($results)):
                ?>
                    <div class="notice notice-info">
                        <p><?php _e('No se encontraron productos.', 'ad-platform'); ?></p>
                    </div>
                <?php else: ?>
                    <p><?php printf(__('Se encontraron %d productos', 'ad-platform'), count($results)); ?></p>
                    <!-- Aquí se mostraría la lista de resultados similar a la tabla de pendientes -->
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.adp-badge {
    background: #ff6b00;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}
</style>
