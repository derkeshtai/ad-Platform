<?php
/**
 * Vista de Backup y Restauración
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('Backup y Restauración', 'ad-platform'); ?></h1>

    <?php settings_errors('adp_backup'); ?>

    <div class="adp-backup-container">

        <!-- Exportar -->
        <div class="adp-backup-section">
            <h2><?php _e('Exportar Datos', 'ad-platform'); ?></h2>
            <p class="description">
                <?php _e('Exporta todas las configuraciones y contenido del plugin a un archivo JSON.', 'ad-platform'); ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field('adp_backup_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Seleccionar datos a exportar', 'ad-platform'); ?></th>
                        <td>
                            <p>
                                <label>
                                    <input type="checkbox" name="export_settings" value="1" checked>
                                    <?php _e('Configuraciones del plugin', 'ad-platform'); ?>
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="export_ads" value="1" checked>
                                    <?php _e('Anuncios', 'ad-platform'); ?>
                                    <span class="description">(<?php echo wp_count_posts('adp_ad')->publish; ?> publicados)</span>
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="export_campaigns" value="1" checked>
                                    <?php _e('Campañas', 'ad-platform'); ?>
                                    <span class="description">(<?php echo wp_count_posts('adp_campaign')->publish; ?> publicadas)</span>
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="export_zones" value="1" checked>
                                    <?php _e('Zonas de anuncios', 'ad-platform'); ?>
                                    <span class="description">(<?php echo wp_count_posts('adp_zone')->publish; ?> publicadas)</span>
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="export_landings" value="1" checked>
                                    <?php _e('Landing Pages', 'ad-platform'); ?>
                                    <span class="description">(<?php echo wp_count_posts('adp_landing')->publish; ?> publicadas)</span>
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="export_aliexpress" value="1" checked>
                                    <?php _e('Productos de AliExpress', 'ad-platform'); ?>
                                    <?php
                                    global $wpdb;
                                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adp_aliexpress_products");
                                    ?>
                                    <span class="description">(<?php echo $count; ?> productos)</span>
                                </label>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="adp_export_backup" class="button button-primary">
                        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                        <?php _e('Descargar Backup', 'ad-platform'); ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Importar -->
        <div class="adp-backup-section">
            <h2><?php _e('Importar Datos', 'ad-platform'); ?></h2>
            <p class="description">
                <?php _e('Restaura configuraciones y contenido desde un archivo JSON de backup.', 'ad-platform'); ?>
            </p>

            <form method="post" action="" enctype="multipart/form-data">
                <?php wp_nonce_field('adp_backup_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Archivo de Backup', 'ad-platform'); ?></th>
                        <td>
                            <input type="file" name="backup_file" accept=".json" required>
                            <p class="description"><?php _e('Selecciona un archivo JSON exportado anteriormente.', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Opciones de importación', 'ad-platform'); ?></th>
                        <td>
                            <p>
                                <label>
                                    <input type="checkbox" name="import_settings" value="1" checked>
                                    <?php _e('Importar configuraciones', 'ad-platform'); ?>
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="import_content" value="1" checked>
                                    <?php _e('Importar contenido (anuncios, landings, etc.)', 'ad-platform'); ?>
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="overwrite_existing" value="1">
                                    <?php _e('Sobrescribir elementos existentes', 'ad-platform'); ?>
                                </label>
                                <span class="description"><?php _e('Si no se marca, solo se importarán elementos nuevos', 'ad-platform'); ?></span>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="adp_import_backup" class="button button-secondary">
                        <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
                        <?php _e('Importar Backup', 'ad-platform'); ?>
                    </button>
                </p>

                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php _e('Advertencia:', 'ad-platform'); ?></strong>
                        <?php _e('Importar con la opción de sobrescribir reemplazará los datos existentes. Se recomienda hacer un backup antes de importar.', 'ad-platform'); ?>
                    </p>
                </div>
            </form>
        </div>

        <!-- Información del sistema -->
        <div class="adp-backup-section">
            <h2><?php _e('Información del Sistema', 'ad-platform'); ?></h2>
            <table class="widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong><?php _e('Versión del Plugin', 'ad-platform'); ?></strong></td>
                        <td><?php echo AD_PLATFORM_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Versión de WordPress', 'ad-platform'); ?></strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Versión de PHP', 'ad-platform'); ?></strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Máximo tamaño de subida', 'ad-platform'); ?></strong></td>
                        <td><?php echo size_format(wp_max_upload_size()); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('URL del sitio', 'ad-platform'); ?></strong></td>
                        <td><?php echo get_site_url(); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.adp-backup-container {
    max-width: 800px;
}

.adp-backup-section {
    background: white;
    padding: 20px 25px;
    margin-bottom: 20px;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
}

.adp-backup-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #dcdcde;
}

.adp-backup-section .description {
    color: #646970;
}

.adp-backup-section .form-table th {
    width: 200px;
}

.adp-backup-section .notice.inline {
    margin: 15px 0 0;
}
</style>
