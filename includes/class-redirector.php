<?php
/**
 * Sistema de Redirección de Enlaces Cortos
 * Maneja /go/{ad_id} → URL de afiliado
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Redirector {

    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_redirect']);
    }

    /**
     * Agregar reglas de rewrite
     */
    public function add_rewrite_rules() {
        $options = get_option('ad_platform_redirect', array());
        $base_slug = $options['base_slug'] ?? 'go';

        add_rewrite_rule(
            "^{$base_slug}/([0-9]+)/?$",
            'index.php?adp_redirect=$matches[1]',
            'top'
        );

        add_rewrite_tag('%adp_redirect%', '([0-9]+)');
    }

    /**
     * Manejar redirección
     */
    public function handle_redirect() {
        $ad_id = get_query_var('adp_redirect');

        if (!$ad_id) {
            return;
        }

        $ad_id = absint($ad_id);

        // Verificar que el anuncio existe
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'adp_ad') {
            wp_die(__('Anuncio no encontrado', 'ad-platform'), 404);
            return;
        }

        // Obtener URL de afiliado
        $affiliate_url = get_post_meta($ad_id, '_adp_affiliate_url', true);

        if (empty($affiliate_url)) {
            wp_die(__('URL no disponible', 'ad-platform'), 404);
            return;
        }

        // Registrar el click
        Ad_Platform_Tracking::track_click($ad_id);

        // Obtener configuración de redirección
        $options = get_option('ad_platform_redirect', array());
        $redirect_type = $options['redirect_type'] ?? '302';
        $redirect_delay = $options['redirect_delay'] ?? 0;

        // Si hay delay, mostrar página intermedia
        if ($redirect_delay > 0) {
            $this->show_redirect_page($affiliate_url, $redirect_delay);
            return;
        }

        // Redirección directa
        wp_redirect($affiliate_url, intval($redirect_type));
        exit;
    }

    /**
     * Mostrar página de redirección con delay
     */
    private function show_redirect_page($url, $delay) {
        $delay_seconds = intval($delay);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Redirigiendo...', 'ad-platform'); ?></title>
            <meta http-equiv="refresh" content="<?php echo $delay_seconds; ?>;url=<?php echo esc_url($url); ?>">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: #fff;
                }
                .container {
                    text-align: center;
                    padding: 2rem;
                }
                .spinner {
                    border: 4px solid rgba(255,255,255,0.3);
                    border-top: 4px solid #fff;
                    border-radius: 50%;
                    width: 60px;
                    height: 60px;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 2rem;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                h1 { margin: 0 0 1rem; font-size: 2rem; }
                p { margin: 0; opacity: 0.9; }
                a { color: #fff; text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="spinner"></div>
                <h1><?php _e('Redirigiendo...', 'ad-platform'); ?></h1>
                <p><?php printf(__('Serás redirigido en %d segundos', 'ad-platform'), $delay_seconds); ?></p>
                <p style="margin-top: 1rem;">
                    <a href="<?php echo esc_url($url); ?>"><?php _e('Haz click aquí si no eres redirigido automáticamente', 'ad-platform'); ?></a>
                </p>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = <?php echo json_encode($url); ?>;
                }, <?php echo $delay_seconds * 1000; ?>);
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Generar URL corta para un anuncio
     */
    public static function get_short_url($ad_id) {
        $options = get_option('ad_platform_redirect', array());
        $base_slug = $options['base_slug'] ?? 'go';

        return home_url("/{$base_slug}/{$ad_id}");
    }
}

new Ad_Platform_Redirector();
