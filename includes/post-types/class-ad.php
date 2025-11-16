<?php
/**
 * Custom Post Type: Anuncios (Ads)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Ad_CPT {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_adp_ad', [$this, 'save_meta_boxes'], 10, 2);
        add_filter('manage_adp_ad_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_adp_ad_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
    }

    /**
     * Registrar Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Anuncios', 'ad-platform'),
            'singular_name' => __('Anuncio', 'ad-platform'),
            'menu_name' => __('Ad Platform', 'ad-platform'),
            'add_new' => __('Añadir Nuevo', 'ad-platform'),
            'add_new_item' => __('Añadir Nuevo Anuncio', 'ad-platform'),
            'edit_item' => __('Editar Anuncio', 'ad-platform'),
            'new_item' => __('Nuevo Anuncio', 'ad-platform'),
            'view_item' => __('Ver Anuncio', 'ad-platform'),
            'search_items' => __('Buscar Anuncios', 'ad-platform'),
            'not_found' => __('No se encontraron anuncios', 'ad-platform'),
            'not_found_in_trash' => __('No hay anuncios en la papelera', 'ad-platform'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-megaphone',
            'menu_position' => 25,
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
        );

        register_post_type('adp_ad', $args);
    }

    /**
     * Registrar taxonomías
     */
    public function register_taxonomies() {
        // Taxonomía de Keywords
        register_taxonomy('adp_keyword', 'adp_ad', array(
            'label' => __('Keywords', 'ad-platform'),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
        ));

        // Taxonomía de Categorías de anuncio
        register_taxonomy('adp_category', 'adp_ad', array(
            'label' => __('Categorías', 'ad-platform'),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
        ));
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'adp_ad_type',
            __('Configuración del Anuncio', 'ad-platform'),
            [$this, 'render_ad_type_metabox'],
            'adp_ad',
            'normal',
            'high'
        );

        add_meta_box(
            'adp_ad_targeting',
            __('Targeting y Programación', 'ad-platform'),
            [$this, 'render_targeting_metabox'],
            'adp_ad',
            'normal',
            'default'
        );

        add_meta_box(
            'adp_ad_zones',
            __('Zonas de Anuncio', 'ad-platform'),
            [$this, 'render_zones_metabox'],
            'adp_ad',
            'side',
            'default'
        );

        add_meta_box(
            'adp_ad_stats',
            __('Estadísticas', 'ad-platform'),
            [$this, 'render_stats_metabox'],
            'adp_ad',
            'side',
            'low'
        );
    }

    /**
     * Renderizar metabox de configuración
     */
    public function render_ad_type_metabox($post) {
        wp_nonce_field('adp_ad_meta', 'adp_ad_meta_nonce');

        $ad_type = get_post_meta($post->ID, '_adp_ad_type', true) ?: 'banner_image';
        $affiliate_url = get_post_meta($post->ID, '_adp_affiliate_url', true);
        $image_url = get_post_meta($post->ID, '_adp_image_url', true);
        $html_content = get_post_meta($post->ID, '_adp_html_content', true);
        $text_title = get_post_meta($post->ID, '_adp_text_title', true);
        $text_description = get_post_meta($post->ID, '_adp_text_description', true);
        $cta_text = get_post_meta($post->ID, '_adp_cta_text', true) ?: 'Ver Más';
        $open_new_tab = get_post_meta($post->ID, '_adp_open_new_tab', true) !== '0';
        $use_short_link = get_post_meta($post->ID, '_adp_use_short_link', true) === '1';
        $weight = get_post_meta($post->ID, '_adp_weight', true) ?: 5;
        $commission = get_post_meta($post->ID, '_adp_commission', true) ?: 0;
        ?>
        <div class="adp-metabox">
            <table class="form-table">
                <tr>
                    <th><label><?php _e('Tipo de Anuncio', 'ad-platform'); ?></label></th>
                    <td>
                        <select name="adp_ad_type" id="adp_ad_type" class="widefat">
                            <option value="banner_image" <?php selected($ad_type, 'banner_image'); ?>><?php _e('Banner con Imagen', 'ad-platform'); ?></option>
                            <option value="text" <?php selected($ad_type, 'text'); ?>><?php _e('Anuncio de Texto', 'ad-platform'); ?></option>
                            <option value="html" <?php selected($ad_type, 'html'); ?>><?php _e('HTML Personalizado', 'ad-platform'); ?></option>
                            <option value="native" <?php selected($ad_type, 'native'); ?>><?php _e('Native Ad', 'ad-platform'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr class="adp-field adp-field-banner adp-field-native">
                    <th><label><?php _e('Imagen del Anuncio', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="text" name="adp_image_url" id="adp_image_url" value="<?php echo esc_attr($image_url); ?>" class="widefat" placeholder="URL de la imagen o usar imagen destacada">
                        <p class="description"><?php _e('Deja vacío para usar la imagen destacada del post', 'ad-platform'); ?></p>
                    </td>
                </tr>

                <tr class="adp-field adp-field-text adp-field-native">
                    <th><label><?php _e('Título del Anuncio', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="text" name="adp_text_title" value="<?php echo esc_attr($text_title); ?>" class="widefat">
                    </td>
                </tr>

                <tr class="adp-field adp-field-text adp-field-native">
                    <th><label><?php _e('Descripción', 'ad-platform'); ?></label></th>
                    <td>
                        <textarea name="adp_text_description" rows="3" class="widefat"><?php echo esc_textarea($text_description); ?></textarea>
                    </td>
                </tr>

                <tr class="adp-field adp-field-html">
                    <th><label><?php _e('Código HTML', 'ad-platform'); ?></label></th>
                    <td>
                        <textarea name="adp_html_content" rows="8" class="widefat code"><?php echo esc_textarea($html_content); ?></textarea>
                        <p class="description"><?php _e('Pega aquí tu código HTML/CSS/JS personalizado', 'ad-platform'); ?></p>
                    </td>
                </tr>

                <tr class="adp-field adp-field-banner adp-field-text adp-field-native">
                    <th><label><?php _e('URL de Afiliado', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="url" name="adp_affiliate_url" value="<?php echo esc_url($affiliate_url); ?>" class="widefat" required>
                    </td>
                </tr>

                <tr class="adp-field adp-field-banner adp-field-text adp-field-native">
                    <th><label><?php _e('Texto del Botón', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="text" name="adp_cta_text" value="<?php echo esc_attr($cta_text); ?>" class="widefat">
                    </td>
                </tr>

                <tr>
                    <th><label><?php _e('Opciones', 'ad-platform'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="adp_open_new_tab" value="1" <?php checked($open_new_tab); ?>>
                            <?php _e('Abrir en nueva pestaña', 'ad-platform'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="adp_use_short_link" value="1" <?php checked($use_short_link); ?>>
                            <?php _e('Usar enlace corto de redirección', 'ad-platform'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th><label><?php _e('Peso/Prioridad', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="number" name="adp_weight" value="<?php echo esc_attr($weight); ?>" min="1" max="10" step="1" class="small-text">
                        <p class="description"><?php _e('1-10 (mayor número = más frecuente)', 'ad-platform'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label><?php _e('Comisión Estimada', 'ad-platform'); ?></label></th>
                    <td>
                        $<input type="number" name="adp_commission" value="<?php echo esc_attr($commission); ?>" min="0" step="0.01" class="small-text">
                        <p class="description"><?php _e('Por conversión (solo para tus reportes)', 'ad-platform'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <style>
            .adp-field { display: none; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            function toggleFields() {
                var type = $('#adp_ad_type').val();
                $('.adp-field').hide();
                $('.adp-field-' + type.replace('_', '-')).show();
            }
            $('#adp_ad_type').on('change', toggleFields);
            toggleFields();
        });
        </script>
        <?php
    }

    /**
     * Renderizar metabox de targeting
     */
    public function render_targeting_metabox($post) {
        $countries = get_post_meta($post->ID, '_adp_countries', true);
        $devices = get_post_meta($post->ID, '_adp_devices', true) ?: array('desktop', 'mobile', 'tablet');
        $start_date = get_post_meta($post->ID, '_adp_start_date', true);
        $end_date = get_post_meta($post->ID, '_adp_end_date', true);
        $max_impressions = get_post_meta($post->ID, '_adp_max_impressions', true) ?: 0;
        $max_clicks = get_post_meta($post->ID, '_adp_max_clicks', true) ?: 0;
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Países (códigos ISO)', 'ad-platform'); ?></label></th>
                <td>
                    <input type="text" name="adp_countries" value="<?php echo esc_attr($countries); ?>" class="widefat" placeholder="MX, US, ES, AR, CO">
                    <p class="description"><?php _e('Separados por comas. Vacío = todos los países', 'ad-platform'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Dispositivos', 'ad-platform'); ?></label></th>
                <td>
                    <label><input type="checkbox" name="adp_devices[]" value="desktop" <?php checked(in_array('desktop', $devices)); ?>> Desktop</label><br>
                    <label><input type="checkbox" name="adp_devices[]" value="mobile" <?php checked(in_array('mobile', $devices)); ?>> Móvil</label><br>
                    <label><input type="checkbox" name="adp_devices[]" value="tablet" <?php checked(in_array('tablet', $devices)); ?>> Tablet</label>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Fecha de Inicio', 'ad-platform'); ?></label></th>
                <td>
                    <input type="datetime-local" name="adp_start_date" value="<?php echo esc_attr($start_date); ?>">
                    <p class="description"><?php _e('Vacío = sin límite', 'ad-platform'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Fecha de Fin', 'ad-platform'); ?></label></th>
                <td>
                    <input type="datetime-local" name="adp_end_date" value="<?php echo esc_attr($end_date); ?>">
                    <p class="description"><?php _e('Vacío = sin límite', 'ad-platform'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Límite de Impresiones/día', 'ad-platform'); ?></label></th>
                <td>
                    <input type="number" name="adp_max_impressions" value="<?php echo esc_attr($max_impressions); ?>" min="0" class="small-text">
                    <p class="description"><?php _e('0 = ilimitado', 'ad-platform'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Límite de Clicks/día', 'ad-platform'); ?></label></th>
                <td>
                    <input type="number" name="adp_max_clicks" value="<?php echo esc_attr($max_clicks); ?>" min="0" class="small-text">
                    <p class="description"><?php _e('0 = ilimitado', 'ad-platform'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar metabox de zonas
     */
    public function render_zones_metabox($post) {
        $assigned_zones = get_post_meta($post->ID, '_adp_zones', true) ?: array();

        $zones = get_posts(array(
            'post_type' => 'adp_zone',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));

        echo '<div style="max-height: 200px; overflow-y: auto;">';
        foreach ($zones as $zone) {
            $checked = in_array($zone->ID, $assigned_zones);
            echo '<label style="display: block; margin-bottom: 8px;">';
            echo '<input type="checkbox" name="adp_zones[]" value="' . esc_attr($zone->ID) . '" ' . checked($checked, true, false) . '> ';
            echo esc_html($zone->post_title);
            echo '</label>';
        }
        echo '</div>';
    }

    /**
     * Renderizar metabox de estadísticas
     */
    public function render_stats_metabox($post) {
        global $wpdb;

        $impressions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}adp_impressions WHERE ad_id = %d",
            $post->ID
        ));

        $clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}adp_clicks WHERE ad_id = %d",
            $post->ID
        ));

        $ctr = $impressions > 0 ? ($clicks / $impressions * 100) : 0;
        ?>
        <div class="adp-stats">
            <p><strong><?php _e('Impresiones:', 'ad-platform'); ?></strong> <?php echo number_format($impressions); ?></p>
            <p><strong><?php _e('Clicks:', 'ad-platform'); ?></strong> <?php echo number_format($clicks); ?></p>
            <p><strong><?php _e('CTR:', 'ad-platform'); ?></strong> <?php echo number_format($ctr, 2); ?>%</p>
        </div>
        <?php
    }

    /**
     * Guardar meta boxes
     */
    public function save_meta_boxes($post_id, $post) {
        // Verificar nonce
        if (!isset($_POST['adp_ad_meta_nonce']) || !wp_verify_nonce($_POST['adp_ad_meta_nonce'], 'adp_ad_meta')) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar campos (sanitizados)
        $fields = array(
            '_adp_ad_type' => 'sanitize_text_field',
            '_adp_affiliate_url' => 'esc_url_raw',
            '_adp_image_url' => 'esc_url_raw',
            '_adp_html_content' => 'wp_kses_post',
            '_adp_text_title' => 'sanitize_text_field',
            '_adp_text_description' => 'sanitize_textarea_field',
            '_adp_cta_text' => 'sanitize_text_field',
            '_adp_weight' => 'absint',
            '_adp_commission' => 'floatval',
            '_adp_countries' => 'sanitize_text_field',
            '_adp_start_date' => 'sanitize_text_field',
            '_adp_end_date' => 'sanitize_text_field',
            '_adp_max_impressions' => 'absint',
            '_adp_max_clicks' => 'absint',
        );

        foreach ($fields as $meta_key => $sanitize_callback) {
            $post_key = str_replace('_adp_', 'adp_', $meta_key);
            if (isset($_POST[$post_key])) {
                $value = call_user_func($sanitize_callback, $_POST[$post_key]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }

        // Checkboxes
        update_post_meta($post_id, '_adp_open_new_tab', isset($_POST['adp_open_new_tab']) ? '1' : '0');
        update_post_meta($post_id, '_adp_use_short_link', isset($_POST['adp_use_short_link']) ? '1' : '0');

        // Arrays
        $devices = isset($_POST['adp_devices']) ? array_map('sanitize_text_field', $_POST['adp_devices']) : array();
        update_post_meta($post_id, '_adp_devices', $devices);

        $zones = isset($_POST['adp_zones']) ? array_map('absint', $_POST['adp_zones']) : array();
        update_post_meta($post_id, '_adp_zones', $zones);
    }

    /**
     * Columnas personalizadas
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['type'] = __('Tipo', 'ad-platform');
        $new_columns['zones'] = __('Zonas', 'ad-platform');
        $new_columns['impressions'] = __('Impresiones', 'ad-platform');
        $new_columns['clicks'] = __('Clicks', 'ad-platform');
        $new_columns['ctr'] = __('CTR', 'ad-platform');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Contenido de columnas personalizadas
     */
    public function custom_column_content($column, $post_id) {
        global $wpdb;

        switch ($column) {
            case 'type':
                $type = get_post_meta($post_id, '_adp_ad_type', true);
                $types = array(
                    'banner_image' => 'Banner',
                    'text' => 'Texto',
                    'html' => 'HTML',
                    'native' => 'Native',
                );
                echo $types[$type] ?? '-';
                break;

            case 'zones':
                $zones = get_post_meta($post_id, '_adp_zones', true);
                if (!empty($zones) && is_array($zones)) {
                    echo count($zones);
                } else {
                    echo '-';
                }
                break;

            case 'impressions':
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}adp_impressions WHERE ad_id = %d",
                    $post_id
                ));
                echo number_format($count);
                break;

            case 'clicks':
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}adp_clicks WHERE ad_id = %d",
                    $post_id
                ));
                echo number_format($count);
                break;

            case 'ctr':
                $impressions = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}adp_impressions WHERE ad_id = %d",
                    $post_id
                ));
                $clicks = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}adp_clicks WHERE ad_id = %d",
                    $post_id
                ));
                $ctr = $impressions > 0 ? ($clicks / $impressions * 100) : 0;
                echo number_format($ctr, 2) . '%';
                break;
        }
    }
}

// Inicializar
new Ad_Platform_Ad_CPT();
