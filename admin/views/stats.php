<?php
/**
 * Vista de Estadísticas
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Fecha de rango
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

// Obtener estadísticas generales
$total_impressions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adp_impressions WHERE created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'");
$total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adp_clicks WHERE created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'");
$total_conversions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adp_conversions WHERE created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'");
$total_revenue = $wpdb->get_var("SELECT SUM(commission_value) FROM {$wpdb->prefix}adp_conversions WHERE created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59' AND status = 'approved'");

$ctr = $total_impressions > 0 ? ($total_clicks / $total_impressions * 100) : 0;
$conversion_rate = $total_clicks > 0 ? ($total_conversions / $total_clicks * 100) : 0;

// Top anuncios
$top_ads = $wpdb->get_results("
    SELECT
        p.ID,
        p.post_title,
        COUNT(DISTINCT i.id) as impressions,
        COUNT(DISTINCT c.id) as clicks,
        (COUNT(DISTINCT c.id) / NULLIF(COUNT(DISTINCT i.id), 0) * 100) as ctr
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->prefix}adp_impressions i ON i.ad_id = p.ID AND i.created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'
    LEFT JOIN {$wpdb->prefix}adp_clicks c ON c.ad_id = p.ID AND c.created_at BETWEEN '$date_from 00:00:00' AND '$date_to 23:59:59'
    WHERE p.post_type = 'adp_ad' AND p.post_status = 'publish'
    GROUP BY p.ID
    ORDER BY clicks DESC
    LIMIT 10
");
?>

<div class="wrap">
    <h1><?php _e('Estadísticas', 'ad-platform'); ?></h1>

    <form method="get" style="margin: 20px 0;">
        <input type="hidden" name="post_type" value="adp_ad">
        <input type="hidden" name="page" value="adp-stats">

        <?php _e('Desde:', 'ad-platform'); ?>
        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">

        <?php _e('Hasta:', 'ad-platform'); ?>
        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">

        <button type="submit" class="button"><?php _e('Filtrar', 'ad-platform'); ?></button>
    </form>

    <!-- Resumen General -->
    <div class="adp-stats-cards">
        <div class="adp-stat-card">
            <div class="adp-stat-icon">👁️</div>
            <div class="adp-stat-content">
                <div class="adp-stat-label"><?php _e('Impresiones', 'ad-platform'); ?></div>
                <div class="adp-stat-value"><?php echo number_format($total_impressions); ?></div>
            </div>
        </div>

        <div class="adp-stat-card">
            <div class="adp-stat-icon">🖱️</div>
            <div class="adp-stat-content">
                <div class="adp-stat-label"><?php _e('Clicks', 'ad-platform'); ?></div>
                <div class="adp-stat-value"><?php echo number_format($total_clicks); ?></div>
            </div>
        </div>

        <div class="adp-stat-card">
            <div class="adp-stat-icon">📊</div>
            <div class="adp-stat-content">
                <div class="adp-stat-label"><?php _e('CTR', 'ad-platform'); ?></div>
                <div class="adp-stat-value"><?php echo number_format($ctr, 2); ?>%</div>
            </div>
        </div>

        <div class="adp-stat-card">
            <div class="adp-stat-icon">✅</div>
            <div class="adp-stat-content">
                <div class="adp-stat-label"><?php _e('Conversiones', 'ad-platform'); ?></div>
                <div class="adp-stat-value"><?php echo number_format($total_conversions); ?></div>
            </div>
        </div>

        <div class="adp-stat-card">
            <div class="adp-stat-icon">💰</div>
            <div class="adp-stat-content">
                <div class="adp-stat-label"><?php _e('Ingresos Estimados', 'ad-platform'); ?></div>
                <div class="adp-stat-value">$<?php echo number_format($total_revenue ?? 0, 2); ?></div>
            </div>
        </div>
    </div>

    <!-- Top Anuncios -->
    <h2><?php _e('Top 10 Anuncios por Clicks', 'ad-platform'); ?></h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Anuncio', 'ad-platform'); ?></th>
                <th><?php _e('Impresiones', 'ad-platform'); ?></th>
                <th><?php _e('Clicks', 'ad-platform'); ?></th>
                <th><?php _e('CTR', 'ad-platform'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($top_ads)): ?>
                <?php foreach ($top_ads as $ad): ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link($ad->ID); ?>">
                                <?php echo esc_html($ad->post_title); ?>
                            </a>
                        </td>
                        <td><?php echo number_format($ad->impressions); ?></td>
                        <td><?php echo number_format($ad->clicks); ?></td>
                        <td><?php echo number_format($ad->ctr ?? 0, 2); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4"><?php _e('No hay datos disponibles', 'ad-platform'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.adp-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.adp-stat-card {
    background: white;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.adp-stat-icon {
    font-size: 32px;
    line-height: 1;
}

.adp-stat-content {
    flex: 1;
}

.adp-stat-label {
    font-size: 13px;
    color: #646970;
    margin-bottom: 5px;
}

.adp-stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}
</style>
