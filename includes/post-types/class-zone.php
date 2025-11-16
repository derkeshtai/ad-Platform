<?php
/**
 * Custom Post Type: Zonas de Anuncio (Ad Zones)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Zone_CPT {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_adp_zone', [$this, 'save_meta_boxes'], 10, 2);
    }

    /**
     * Registrar Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Zonas', 'ad-platform'),
            'singular_name' => __('Zona', 'ad-platform'),
            'add_new' => __('Añadir Nueva', 'ad-platform'),
            'add_new_item' => __('Añadir Nueva Zona', 'ad-platform'),
            'edit_item' => __('Editar Zona', 'ad-platform'),
            'new_item' => __('Nueva Zona', 'ad-platform'),
            'view_item' => __('Ver Zona', 'ad-platform'),
            'search_items' => __('Buscar Zonas', 'ad-platform'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=adp_ad',
            'capability_type' => 'post',
            'supports' => array('title', 'editor'),
            'show_in_rest' => true,
        );

        register_post_type('adp_zone', $args);
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'adp_zone_settings',
            __('Configuración de Zona', 'ad-platform'),
            [$this, 'render_settings_metabox'],
            'adp_zone',
            'normal',
            'high'
        );

        add_meta_box(
            'adp_zone_shortcode',
            __('Código de Integración', 'ad-platform'),
            [$this, 'render_shortcode_metabox'],
            'adp_zone',
            'side',
            'default'
        );
    }

    /**
     * Renderizar metabox de configuración
     */
    public function render_settings_metabox($post) {
        wp_nonce_field('adp_zone_meta', 'adp_zone_meta_nonce');

        $size = get_post_meta($post->ID, '_adp_zone_size', true) ?: 'responsive';
        $max_ads = get_post_meta($post->ID, '_adp_zone_max_ads', true) ?: 1;
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Tamaño de Zona', 'ad-platform'); ?></label></th>
                <td>
                    <select name="adp_zone_size" class="widefat">
                        <option value="728x90" <?php selected($size, '728x90'); ?>>728x90 (Leaderboard)</option>
                        <option value="300x250" <?php selected($size, '300x250'); ?>>300x250 (Medium Rectangle)</option>
                        <option value="160x600" <?php selected($size, '160x600'); ?>>160x600 (Wide Skyscraper)</option>
                        <option value="300x600" <?php selected($size, '300x600'); ?>>300x600 (Half Page)</option>
                        <option value="320x50" <?php selected($size, '320x50'); ?>>320x50 (Mobile Banner)</option>
                        <option value="responsive" <?php selected($size, 'responsive'); ?>>Responsive</option>
                        <option value="custom" <?php selected($size, 'custom'); ?>>Personalizado</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Máximo de Anuncios', 'ad-platform'); ?></label></th>
                <td>
                    <input type="number" name="adp_zone_max_ads" value="<?php echo esc_attr($max_ads); ?>" min="1" max="10" class="small-text">
                    <p class="description"><?php _e('Cuántos anuncios mostrar simultáneamente en esta zona', 'ad-platform'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar metabox de shortcode
     */
    public function render_shortcode_metabox($post) {
        ?>
        <p><strong><?php _e('Shortcode:', 'ad-platform'); ?></strong></p>
        <input type="text" value='[ad_zone id="<?php echo $post->ID; ?>"]' readonly class="widefat" onclick="this.select()">

        <p style="margin-top: 15px;"><strong><?php _e('Función PHP:', 'ad-platform'); ?></strong></p>
        <textarea readonly class="widefat code" rows="2" onclick="this.select()"><?php echo "<?php ad_platform_zone({$post->ID}); ?>"; ?></textarea>

        <p style="margin-top: 15px;"><strong><?php _e('JavaScript SDK:', 'ad-platform'); ?></strong></p>
        <textarea readonly class="widefat code" rows="3" onclick="this.select()"><div class="adp-zone" data-zone-id="<?php echo $post->ID; ?>"></div></textarea>
        <?php
    }

    /**
     * Guardar meta boxes
     */
    public function save_meta_boxes($post_id, $post) {
        if (!isset($_POST['adp_zone_meta_nonce']) || !wp_verify_nonce($_POST['adp_zone_meta_nonce'], 'adp_zone_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['adp_zone_size'])) {
            update_post_meta($post_id, '_adp_zone_size', sanitize_text_field($_POST['adp_zone_size']));
        }

        if (isset($_POST['adp_zone_max_ads'])) {
            update_post_meta($post_id, '_adp_zone_max_ads', absint($_POST['adp_zone_max_ads']));
        }
    }
}

new Ad_Platform_Zone_CPT();
