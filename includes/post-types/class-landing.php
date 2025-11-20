<?php
/**
 * Custom Post Type: Landing Pages de Productos
 * Para mostrar páginas completas de productos de afiliados
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Landing_CPT {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_adp_landing', [$this, 'save_meta_boxes'], 10, 2);
        add_filter('manage_adp_landing_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_adp_landing_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter('single_template', [$this, 'load_landing_template']);
    }

    /**
     * Registrar Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Landing Pages', 'ad-platform'),
            'singular_name' => __('Landing Page', 'ad-platform'),
            'menu_name' => __('Landings', 'ad-platform'),
            'add_new' => __('Añadir Nueva', 'ad-platform'),
            'add_new_item' => __('Añadir Nueva Landing', 'ad-platform'),
            'edit_item' => __('Editar Landing', 'ad-platform'),
            'new_item' => __('Nueva Landing', 'ad-platform'),
            'view_item' => __('Ver Landing', 'ad-platform'),
            'search_items' => __('Buscar Landings', 'ad-platform'),
            'not_found' => __('No se encontraron landings', 'ad-platform'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=adp_ad',
            'query_var' => true,
            'rewrite' => array('slug' => 'producto', 'with_front' => false),
            'capability_type' => 'post',
            'has_archive' => 'productos',
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true,
        );

        register_post_type('adp_landing', $args);
    }

    /**
     * Registrar taxonomías
     */
    public function register_taxonomies() {
        // Taxonomía: Plataforma de Afiliado
        register_taxonomy('adp_platform', 'adp_landing', array(
            'label' => __('Plataforma', 'ad-platform'),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'productos'),
            'show_in_rest' => true,
        ));

        // Crear plataformas por defecto
        $this->create_default_platforms();

        // Taxonomía: Categoría de Producto
        register_taxonomy('adp_product_cat', 'adp_landing', array(
            'label' => __('Categoría', 'ad-platform'),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'categoria-producto'),
            'show_in_rest' => true,
        ));
    }

    /**
     * Crear plataformas por defecto
     */
    private function create_default_platforms() {
        $platforms = array(
            'aliexpress' => 'AliExpress',
            'hotmart' => 'Hotmart',
            'fiverr' => 'Fiverr',
            'hostinger' => 'Hostinger',
            'binance' => 'Binance',
            'bitso' => 'Bitso',
            'freebitcoin' => 'FreeBitcoin',
            'nu' => 'Nu',
        );

        foreach ($platforms as $slug => $name) {
            if (!term_exists($slug, 'adp_platform')) {
                wp_insert_term($name, 'adp_platform', array('slug' => $slug));
            }
        }
    }

    /**
     * Añadir meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'adp_landing_product',
            __('Datos del Producto', 'ad-platform'),
            [$this, 'render_product_metabox'],
            'adp_landing',
            'normal',
            'high'
        );

        add_meta_box(
            'adp_landing_cta',
            __('Call to Action', 'ad-platform'),
            [$this, 'render_cta_metabox'],
            'adp_landing',
            'side',
            'default'
        );

        add_meta_box(
            'adp_landing_seo',
            __('SEO & Tracking', 'ad-platform'),
            [$this, 'render_seo_metabox'],
            'adp_landing',
            'normal',
            'default'
        );
    }

    /**
     * Renderizar metabox de datos del producto
     */
    public function render_product_metabox($post) {
        wp_nonce_field('adp_landing_meta', 'adp_landing_meta_nonce');

        $price = get_post_meta($post->ID, '_adp_price', true);
        $original_price = get_post_meta($post->ID, '_adp_original_price', true);
        $currency = get_post_meta($post->ID, '_adp_currency', true) ?: 'USD';
        $rating = get_post_meta($post->ID, '_adp_rating', true);
        $reviews_count = get_post_meta($post->ID, '_adp_reviews_count', true);
        $orders_count = get_post_meta($post->ID, '_adp_orders_count', true);
        $gallery = get_post_meta($post->ID, '_adp_gallery', true);
        $features = get_post_meta($post->ID, '_adp_features', true);
        $specifications = get_post_meta($post->ID, '_adp_specifications', true);
        $external_id = get_post_meta($post->ID, '_adp_external_id', true);
        $is_auto_generated = get_post_meta($post->ID, '_adp_auto_generated', true);
        ?>
        <div class="adp-metabox">
            <?php if ($is_auto_generated): ?>
                <div class="notice notice-info inline">
                    <p>🤖 <?php _e('Esta landing fue generada automáticamente desde AliExpress', 'ad-platform'); ?></p>
                </div>
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th><label><?php _e('Precio Actual', 'ad-platform'); ?></label></th>
                    <td>
                        <select name="adp_currency" style="width: 70px;">
                            <option value="USD" <?php selected($currency, 'USD'); ?>>$</option>
                            <option value="EUR" <?php selected($currency, 'EUR'); ?>>€</option>
                            <option value="MXN" <?php selected($currency, 'MXN'); ?>>MXN</option>
                        </select>
                        <input type="number" name="adp_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Precio Original', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="number" name="adp_original_price" value="<?php echo esc_attr($original_price); ?>" step="0.01" min="0" class="small-text">
                        <p class="description"><?php _e('Deja vacío si no hay descuento', 'ad-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Rating', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="number" name="adp_rating" value="<?php echo esc_attr($rating); ?>" step="0.1" min="0" max="5" class="small-text"> / 5
                        &nbsp;&nbsp;
                        <input type="number" name="adp_reviews_count" value="<?php echo esc_attr($reviews_count); ?>" min="0" class="small-text" placeholder="# reseñas">
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Ventas/Órdenes', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="number" name="adp_orders_count" value="<?php echo esc_attr($orders_count); ?>" min="0" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Galería de Imágenes', 'ad-platform'); ?></label></th>
                    <td>
                        <textarea name="adp_gallery" rows="3" class="widefat" placeholder="URL de imágenes separadas por línea"><?php echo esc_textarea($gallery); ?></textarea>
                        <p class="description"><?php _e('Una URL por línea. La imagen destacada será la principal.', 'ad-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Características', 'ad-platform'); ?></label></th>
                    <td>
                        <textarea name="adp_features" rows="4" class="widefat" placeholder="Una característica por línea"><?php echo esc_textarea($features); ?></textarea>
                        <p class="description"><?php _e('Lista de beneficios o características principales', 'ad-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Especificaciones', 'ad-platform'); ?></label></th>
                    <td>
                        <textarea name="adp_specifications" rows="4" class="widefat" placeholder="Formato: Nombre: Valor (uno por línea)"><?php echo esc_textarea($specifications); ?></textarea>
                        <p class="description"><?php _e('Ejemplo: Color: Negro, Tamaño: XL', 'ad-platform'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('ID Externo', 'ad-platform'); ?></label></th>
                    <td>
                        <input type="text" name="adp_external_id" value="<?php echo esc_attr($external_id); ?>" class="regular-text">
                        <p class="description"><?php _e('ID del producto en AliExpress, Hotmart, etc.', 'ad-platform'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Renderizar metabox de CTA
     */
    public function render_cta_metabox($post) {
        $affiliate_url = get_post_meta($post->ID, '_adp_affiliate_url', true);
        $cta_text = get_post_meta($post->ID, '_adp_cta_text', true) ?: 'Comprar Ahora';
        $cta_color = get_post_meta($post->ID, '_adp_cta_color', true) ?: '#ff6b00';
        $free_shipping = get_post_meta($post->ID, '_adp_free_shipping', true);
        $badge_text = get_post_meta($post->ID, '_adp_badge_text', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('URL de Afiliado', 'ad-platform'); ?></label></th>
                <td>
                    <input type="url" name="adp_affiliate_url" value="<?php echo esc_url($affiliate_url); ?>" class="widefat" required>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Texto del Botón', 'ad-platform'); ?></label></th>
                <td>
                    <input type="text" name="adp_cta_text" value="<?php echo esc_attr($cta_text); ?>" class="widefat">
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Color del Botón', 'ad-platform'); ?></label></th>
                <td>
                    <input type="color" name="adp_cta_color" value="<?php echo esc_attr($cta_color); ?>">
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Envío Gratis', 'ad-platform'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="adp_free_shipping" value="1" <?php checked($free_shipping); ?>>
                        <?php _e('Mostrar badge', 'ad-platform'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Badge Especial', 'ad-platform'); ?></label></th>
                <td>
                    <input type="text" name="adp_badge_text" value="<?php echo esc_attr($badge_text); ?>" class="widefat" placeholder="Ej: -50% OFF, Bestseller">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar metabox de SEO
     */
    public function render_seo_metabox($post) {
        $meta_title = get_post_meta($post->ID, '_adp_meta_title', true);
        $meta_description = get_post_meta($post->ID, '_adp_meta_description', true);
        $fb_pixel_event = get_post_meta($post->ID, '_adp_fb_pixel_event', true) ?: 'ViewContent';
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Meta Título', 'ad-platform'); ?></label></th>
                <td>
                    <input type="text" name="adp_meta_title" value="<?php echo esc_attr($meta_title); ?>" class="widefat">
                    <p class="description"><?php _e('Deja vacío para usar el título del post', 'ad-platform'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Meta Descripción', 'ad-platform'); ?></label></th>
                <td>
                    <textarea name="adp_meta_description" rows="3" class="widefat"><?php echo esc_textarea($meta_description); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Evento Facebook Pixel', 'ad-platform'); ?></label></th>
                <td>
                    <select name="adp_fb_pixel_event">
                        <option value="ViewContent" <?php selected($fb_pixel_event, 'ViewContent'); ?>>ViewContent</option>
                        <option value="AddToCart" <?php selected($fb_pixel_event, 'AddToCart'); ?>>AddToCart</option>
                        <option value="AddToWishlist" <?php selected($fb_pixel_event, 'AddToWishlist'); ?>>AddToWishlist</option>
                        <option value="Lead" <?php selected($fb_pixel_event, 'Lead'); ?>>Lead</option>
                    </select>
                    <p class="description"><?php _e('Evento que se dispara cuando el usuario visita esta landing', 'ad-platform'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Guardar meta boxes
     */
    public function save_meta_boxes($post_id, $post) {
        if (!isset($_POST['adp_landing_meta_nonce']) || !wp_verify_nonce($_POST['adp_landing_meta_nonce'], 'adp_landing_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Campos de texto
        $text_fields = array(
            '_adp_currency', '_adp_cta_text', '_adp_cta_color', '_adp_badge_text',
            '_adp_external_id', '_adp_meta_title', '_adp_fb_pixel_event',
            '_adp_gallery', '_adp_features', '_adp_specifications'
        );

        foreach ($text_fields as $field) {
            $post_key = str_replace('_adp_', 'adp_', $field);
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$post_key]));
            }
        }

        // Campos numéricos
        $numeric_fields = array(
            '_adp_price', '_adp_original_price', '_adp_rating',
            '_adp_reviews_count', '_adp_orders_count'
        );

        foreach ($numeric_fields as $field) {
            $post_key = str_replace('_adp_', 'adp_', $field);
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $field, floatval($_POST[$post_key]));
            }
        }

        // URL
        if (isset($_POST['adp_affiliate_url'])) {
            update_post_meta($post_id, '_adp_affiliate_url', esc_url_raw($_POST['adp_affiliate_url']));
        }

        // Meta description
        if (isset($_POST['adp_meta_description'])) {
            update_post_meta($post_id, '_adp_meta_description', sanitize_textarea_field($_POST['adp_meta_description']));
        }

        // Checkbox
        update_post_meta($post_id, '_adp_free_shipping', isset($_POST['adp_free_shipping']) ? '1' : '0');
    }

    /**
     * Columnas personalizadas
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['platform'] = __('Plataforma', 'ad-platform');
        $new_columns['price'] = __('Precio', 'ad-platform');
        $new_columns['rating'] = __('Rating', 'ad-platform');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Contenido de columnas personalizadas
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'platform':
                $terms = get_the_terms($post_id, 'adp_platform');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html($terms[0]->name);
                } else {
                    echo '-';
                }
                break;

            case 'price':
                $price = get_post_meta($post_id, '_adp_price', true);
                $currency = get_post_meta($post_id, '_adp_currency', true) ?: 'USD';
                if ($price) {
                    $symbols = array('USD' => '$', 'EUR' => '€', 'MXN' => 'MXN $');
                    echo $symbols[$currency] . number_format($price, 2);
                } else {
                    echo '-';
                }
                break;

            case 'rating':
                $rating = get_post_meta($post_id, '_adp_rating', true);
                if ($rating) {
                    echo '⭐ ' . number_format($rating, 1);
                } else {
                    echo '-';
                }
                break;
        }
    }

    /**
     * Cargar template personalizado para landings
     */
    public function load_landing_template($template) {
        global $post;

        if ($post->post_type === 'adp_landing') {
            // Primero buscar en el tema
            $theme_template = locate_template('single-adp_landing.php');
            if ($theme_template) {
                return $theme_template;
            }

            // Si no, usar el template del plugin
            $plugin_template = AD_PLATFORM_PLUGIN_DIR . 'public/templates/single-adp_landing.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Generar landing automáticamente desde producto de AliExpress
     */
    public static function create_from_aliexpress($product_data) {
        // Verificar si ya existe
        $existing = get_posts(array(
            'post_type' => 'adp_landing',
            'meta_key' => '_adp_external_id',
            'meta_value' => $product_data['product_id'],
            'posts_per_page' => 1,
        ));

        if (!empty($existing)) {
            return $existing[0]->ID;
        }

        // Crear el post
        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($product_data['title']),
            'post_content' => wp_kses_post($product_data['description'] ?? ''),
            'post_excerpt' => wp_trim_words($product_data['description'] ?? '', 30),
            'post_status' => 'publish',
            'post_type' => 'adp_landing',
        ));

        if (is_wp_error($post_id)) {
            return false;
        }

        // Asignar taxonomía
        wp_set_object_terms($post_id, 'aliexpress', 'adp_platform');

        // Guardar meta datos
        update_post_meta($post_id, '_adp_external_id', $product_data['product_id']);
        update_post_meta($post_id, '_adp_price', floatval($product_data['price']));
        update_post_meta($post_id, '_adp_original_price', floatval($product_data['original_price'] ?? 0));
        update_post_meta($post_id, '_adp_currency', 'USD');
        update_post_meta($post_id, '_adp_rating', floatval($product_data['rating'] ?? 0));
        update_post_meta($post_id, '_adp_orders_count', intval($product_data['orders_count'] ?? 0));
        update_post_meta($post_id, '_adp_affiliate_url', esc_url_raw($product_data['affiliate_url']));
        update_post_meta($post_id, '_adp_free_shipping', $product_data['free_shipping'] ? '1' : '0');
        update_post_meta($post_id, '_adp_auto_generated', '1');
        update_post_meta($post_id, '_adp_cta_text', 'Comprar en AliExpress');
        update_post_meta($post_id, '_adp_cta_color', '#ff6b00');

        // Establecer imagen destacada desde URL
        if (!empty($product_data['image_url'])) {
            self::set_featured_image_from_url($post_id, $product_data['image_url']);
        }

        return $post_id;
    }

    /**
     * Establecer imagen destacada desde URL
     */
    private static function set_featured_image_from_url($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($id)) {
            @unlink($tmp);
            return false;
        }

        set_post_thumbnail($post_id, $id);
        return $id;
    }
}

new Ad_Platform_Landing_CPT();
