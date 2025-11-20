<?php
/**
 * Clase de Integración con Facebook Pixel
 *
 * Maneja la integración con Facebook Pixel para remarketing
 *
 * @package Ad_Platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Facebook_Pixel {

    /**
     * ID del Pixel
     */
    private $pixel_id;

    /**
     * Configuración
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('ad_platform_facebook', array());
        $this->pixel_id = $this->settings['pixel_id'] ?? '';

        if (!empty($this->pixel_id)) {
            $this->init_hooks();
        }
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Insertar código base del Pixel en el head
        add_action('wp_head', array($this, 'render_pixel_base'), 1);

        // Eventos de página
        add_action('wp_footer', array($this, 'track_page_view'), 99);

        // Eventos específicos
        add_action('wp_footer', array($this, 'track_product_views'), 99);

        // AJAX para tracking de eventos
        add_action('wp_ajax_adp_track_fb_event', array($this, 'ajax_track_event'));
        add_action('wp_ajax_nopriv_adp_track_fb_event', array($this, 'ajax_track_event'));

        // Agregar datos para JavaScript
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Renderizar código base del Pixel
     */
    public function render_pixel_base() {
        if (empty($this->pixel_id)) {
            return;
        }

        $advanced_matching = !empty($this->settings['advanced_matching']);
        $user_data = $advanced_matching ? $this->get_user_data() : '{}';
        ?>
        <!-- Facebook Pixel Code - Ad Platform -->
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        <?php if ($advanced_matching): ?>
        fbq('init', '<?php echo esc_js($this->pixel_id); ?>', <?php echo $user_data; ?>);
        <?php else: ?>
        fbq('init', '<?php echo esc_js($this->pixel_id); ?>');
        <?php endif; ?>
        </script>
        <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=<?php echo esc_attr($this->pixel_id); ?>&ev=PageView&noscript=1"
        /></noscript>
        <!-- End Facebook Pixel Code -->
        <?php
    }

    /**
     * Obtener datos de usuario para Advanced Matching
     */
    private function get_user_data() {
        $data = array();

        if (is_user_logged_in()) {
            $user = wp_get_current_user();

            if ($user->user_email) {
                $data['em'] = $user->user_email;
            }

            if ($user->first_name) {
                $data['fn'] = strtolower($user->first_name);
            }

            if ($user->last_name) {
                $data['ln'] = strtolower($user->last_name);
            }
        }

        return !empty($data) ? json_encode($data) : '{}';
    }

    /**
     * Trackear PageView
     */
    public function track_page_view() {
        if (empty($this->pixel_id)) {
            return;
        }
        ?>
        <script>
        fbq('track', 'PageView');
        </script>
        <?php
    }

    /**
     * Trackear vista de productos
     */
    public function track_product_views() {
        if (empty($this->pixel_id)) {
            return;
        }

        // Landing Pages
        if (is_singular('adp_landing')) {
            $post_id = get_the_ID();
            $price = get_post_meta($post_id, '_adp_price', true);
            $currency = get_post_meta($post_id, '_adp_currency', true) ?: 'USD';
            $external_id = get_post_meta($post_id, '_adp_external_id', true);

            $platforms = get_the_terms($post_id, 'adp_platform');
            $platform = $platforms ? $platforms[0]->slug : 'general';

            $categories = get_the_terms($post_id, 'adp_product_cat');
            $category = $categories ? $categories[0]->name : '';

            // Guardar en historial de usuario
            $this->save_to_history($post_id, array(
                'product_id' => $external_id ?: $post_id,
                'title' => get_the_title(),
                'price' => $price,
                'currency' => $currency,
                'platform' => $platform,
                'image' => get_the_post_thumbnail_url($post_id, 'medium'),
                'url' => get_permalink($post_id),
            ));
            ?>
            <script>
            fbq('track', 'ViewContent', {
                content_ids: ['<?php echo esc_js($external_id ?: $post_id); ?>'],
                content_type: 'product',
                content_name: '<?php echo esc_js(get_the_title()); ?>',
                content_category: '<?php echo esc_js($category); ?>',
                value: <?php echo $price ? floatval($price) : 0; ?>,
                currency: '<?php echo esc_js($currency); ?>'
            });
            </script>
            <?php
        }

        // Archivo de productos
        if (is_post_type_archive('adp_landing') || is_tax('adp_platform') || is_tax('adp_product_cat')) {
            $content_ids = array();
            while (have_posts()) {
                the_post();
                $external_id = get_post_meta(get_the_ID(), '_adp_external_id', true);
                $content_ids[] = $external_id ?: get_the_ID();
            }
            rewind_posts();

            if (!empty($content_ids)) {
                ?>
                <script>
                fbq('track', 'ViewContent', {
                    content_ids: <?php echo json_encode(array_slice($content_ids, 0, 10)); ?>,
                    content_type: 'product_group'
                });
                </script>
                <?php
            }
        }
    }

    /**
     * Guardar producto en historial de usuario
     */
    private function save_to_history($post_id, $product_data) {
        $cookie_name = 'adp_viewed_products';
        $max_items = 20;

        // Obtener historial actual
        $history = array();
        if (isset($_COOKIE[$cookie_name])) {
            $history = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
            if (!is_array($history)) {
                $history = array();
            }
        }

        // Remover si ya existe (para reordenar)
        $history = array_filter($history, function($item) use ($post_id) {
            return $item['post_id'] != $post_id;
        });

        // Agregar al inicio
        array_unshift($history, array_merge($product_data, array(
            'post_id' => $post_id,
            'viewed_at' => time(),
        )));

        // Limitar cantidad
        $history = array_slice($history, 0, $max_items);

        // Guardar cookie (30 días)
        setcookie(
            $cookie_name,
            json_encode($history),
            time() + (30 * DAY_IN_SECONDS),
            '/',
            '',
            is_ssl(),
            false // JavaScript necesita acceso
        );
    }

    /**
     * Obtener historial de productos vistos
     */
    public static function get_viewed_products($limit = 10, $platform = '') {
        $cookie_name = 'adp_viewed_products';

        if (!isset($_COOKIE[$cookie_name])) {
            return array();
        }

        $history = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
        if (!is_array($history)) {
            return array();
        }

        // Filtrar por plataforma si se especifica
        if ($platform) {
            $history = array_filter($history, function($item) use ($platform) {
                return $item['platform'] === $platform;
            });
        }

        return array_slice($history, 0, $limit);
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (empty($this->pixel_id)) {
            return;
        }

        wp_localize_script('adp-public', 'adpFacebook', array(
            'pixel_id' => $this->pixel_id,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('adp_fb_track'),
        ));
    }

    /**
     * AJAX handler para tracking de eventos
     */
    public function ajax_track_event() {
        check_ajax_referer('adp_fb_track', 'nonce');

        $event_name = sanitize_text_field($_POST['event_name'] ?? '');
        $event_data = isset($_POST['event_data']) ? array_map('sanitize_text_field', $_POST['event_data']) : array();

        // Validar evento permitido
        $allowed_events = array(
            'ViewContent',
            'AddToCart',
            'InitiateCheckout',
            'Purchase',
            'Lead',
            'CompleteRegistration',
            'Search',
        );

        if (!in_array($event_name, $allowed_events)) {
            wp_send_json_error('Invalid event');
        }

        // Aquí podrías enviar el evento vía Server-Side API de Facebook
        // Por ahora solo confirmamos que se recibió
        wp_send_json_success(array(
            'event' => $event_name,
            'received' => true,
        ));
    }

    /**
     * Generar código de evento para JavaScript
     */
    public static function get_event_code($event_name, $params = array()) {
        $params_json = !empty($params) ? json_encode($params) : '';

        if ($params_json) {
            return sprintf("fbq('track', '%s', %s);", esc_js($event_name), $params_json);
        }

        return sprintf("fbq('track', '%s');", esc_js($event_name));
    }

    /**
     * Verificar si el Pixel está configurado
     */
    public static function is_configured() {
        $settings = get_option('ad_platform_facebook', array());
        return !empty($settings['pixel_id']);
    }
}

// Inicializar
new Ad_Platform_Facebook_Pixel();
