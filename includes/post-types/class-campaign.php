<?php
/**
 * Custom Post Type: Campañas (Campaigns)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Campaign_CPT {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_adp_campaign', [$this, 'save_meta_boxes'], 10, 2);
    }

    /**
     * Registrar Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Campañas', 'ad-platform'),
            'singular_name' => __('Campaña', 'ad-platform'),
            'add_new' => __('Añadir Nueva', 'ad-platform'),
            'add_new_item' => __('Añadir Nueva Campaña', 'ad-platform'),
            'edit_item' => __('Editar Campaña', 'ad-platform'),
            'new_item' => __('Nueva Campaña', 'ad-platform'),
            'view_item' => __('Ver Campaña', 'ad-platform'),
            'search_items' => __('Buscar Campañas', 'ad-platform'),
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

        register_post_type('adp_campaign', $args);
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'adp_campaign_settings',
            __('Configuración de Campaña', 'ad-platform'),
            [$this, 'render_settings_metabox'],
            'adp_campaign',
            'normal',
            'high'
        );
    }

    /**
     * Renderizar metabox
     */
    public function render_settings_metabox($post) {
        wp_nonce_field('adp_campaign_meta', 'adp_campaign_meta_nonce');

        $budget = get_post_meta($post->ID, '_adp_budget', true) ?: 0;
        $start_date = get_post_meta($post->ID, '_adp_start_date', true);
        $end_date = get_post_meta($post->ID, '_adp_end_date', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Presupuesto', 'ad-platform'); ?></label></th>
                <td>
                    $<input type="number" name="adp_budget" value="<?php echo esc_attr($budget); ?>" min="0" step="0.01" class="small-text">
                    <p class="description"><?php _e('Presupuesto estimado para esta campaña', 'ad-platform'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Fecha de Inicio', 'ad-platform'); ?></label></th>
                <td>
                    <input type="date" name="adp_start_date" value="<?php echo esc_attr($start_date); ?>">
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Fecha de Fin', 'ad-platform'); ?></label></th>
                <td>
                    <input type="date" name="adp_end_date" value="<?php echo esc_attr($end_date); ?>">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guardar meta boxes
     */
    public function save_meta_boxes($post_id, $post) {
        if (!isset($_POST['adp_campaign_meta_nonce']) || !wp_verify_nonce($_POST['adp_campaign_meta_nonce'], 'adp_campaign_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['adp_budget'])) {
            update_post_meta($post_id, '_adp_budget', floatval($_POST['adp_budget']));
        }

        if (isset($_POST['adp_start_date'])) {
            update_post_meta($post_id, '_adp_start_date', sanitize_text_field($_POST['adp_start_date']));
        }

        if (isset($_POST['adp_end_date'])) {
            update_post_meta($post_id, '_adp_end_date', sanitize_text_field($_POST['adp_end_date']));
        }
    }
}

new Ad_Platform_Campaign_CPT();
