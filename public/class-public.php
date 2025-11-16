<?php
/**
 * Clase pública del plugin
 * Maneja shortcodes, widgets y funcionalidad frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Public {

    /**
     * Cargar estilos públicos
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'ad-platform-public',
            AD_PLATFORM_PLUGIN_URL . 'public/css/public.css',
            array(),
            AD_PLATFORM_VERSION
        );
    }

    /**
     * Cargar scripts públicos
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'ad-platform-sdk',
            AD_PLATFORM_PLUGIN_URL . 'public/js/ad-sdk.js',
            array('jquery'),
            AD_PLATFORM_VERSION,
            true
        );

        wp_localize_script('ad-platform-sdk', 'adpConfig', array(
            'api_url' => rest_url('adplatform/v1/'),
            'home_url' => home_url(),
        ));
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('ad_zone', [$this, 'shortcode_ad_zone']);
        add_shortcode('ad_single', [$this, 'shortcode_ad_single']);
    }

    /**
     * Shortcode: [ad_zone id="1"]
     */
    public function shortcode_ad_zone($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'keywords' => '',
            'category' => '',
        ), $atts);

        $zone_id = intval($atts['id']);

        if (!$zone_id) {
            return '';
        }

        ob_start();
        ?>
        <div class="adp-zone" data-zone-id="<?php echo esc_attr($zone_id); ?>" data-keywords="<?php echo esc_attr($atts['keywords']); ?>" data-category="<?php echo esc_attr($atts['category']); ?>">
            <div class="adp-loading"><?php _e('Cargando anuncio...', 'ad-platform'); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: [ad_single id="123"]
     */
    public function shortcode_ad_single($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $ad_id = intval($atts['id']);

        if (!$ad_id) {
            return '';
        }

        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'adp_ad') {
            return '';
        }

        // Registrar impresión
        Ad_Platform_Tracking::track_impression($ad_id);

        // Obtener datos del anuncio
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-api.php';
        $api = new Ad_Platform_API();
        $reflection = new ReflectionClass($api);
        $method = $reflection->getMethod('build_ad_response');
        $method->setAccessible(true);
        $ad_data = $method->invoke($api, $ad);

        return $this->render_ad($ad_data);
    }

    /**
     * Renderizar anuncio
     */
    private function render_ad($ad) {
        ob_start();

        $type = $ad['type'] ?? 'banner_image';

        switch ($type) {
            case 'banner_image':
                $this->render_banner_ad($ad);
                break;

            case 'text':
                $this->render_text_ad($ad);
                break;

            case 'html':
                echo $ad['html'] ?? '';
                break;

            case 'native':
                $this->render_native_ad($ad);
                break;

            case 'aliexpress':
                $this->render_aliexpress_ad($ad);
                break;
        }

        return ob_get_clean();
    }

    /**
     * Renderizar anuncio de banner
     */
    private function render_banner_ad($ad) {
        ?>
        <div class="adp-ad adp-ad-banner" data-ad-id="<?php echo esc_attr($ad['id']); ?>">
            <a href="<?php echo esc_url($ad['url']); ?>" target="<?php echo esc_attr($ad['target']); ?>" class="adp-ad-link" rel="noopener sponsored">
                <?php if (!empty($ad['image'])): ?>
                    <img src="<?php echo esc_url($ad['image']); ?>" alt="<?php echo esc_attr($ad['title']); ?>" class="adp-ad-image">
                <?php endif; ?>
            </a>
        </div>
        <?php
    }

    /**
     * Renderizar anuncio de texto
     */
    private function render_text_ad($ad) {
        ?>
        <div class="adp-ad adp-ad-text" data-ad-id="<?php echo esc_attr($ad['id']); ?>">
            <div class="adp-ad-content">
                <h3 class="adp-ad-title"><?php echo esc_html($ad['text_title'] ?? $ad['title']); ?></h3>
                <?php if (!empty($ad['text_description'])): ?>
                    <p class="adp-ad-description"><?php echo esc_html($ad['text_description']); ?></p>
                <?php endif; ?>
                <a href="<?php echo esc_url($ad['url']); ?>" target="<?php echo esc_attr($ad['target']); ?>" class="adp-ad-cta" rel="noopener sponsored">
                    <?php echo esc_html($ad['cta']); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar anuncio nativo
     */
    private function render_native_ad($ad) {
        ?>
        <div class="adp-ad adp-ad-native" data-ad-id="<?php echo esc_attr($ad['id']); ?>">
            <div class="adp-ad-label"><?php _e('Publicidad', 'ad-platform'); ?></div>
            <?php if (!empty($ad['image'])): ?>
                <div class="adp-ad-image-wrapper">
                    <img src="<?php echo esc_url($ad['image']); ?>" alt="<?php echo esc_attr($ad['title']); ?>" class="adp-ad-image">
                </div>
            <?php endif; ?>
            <div class="adp-ad-content">
                <h3 class="adp-ad-title"><?php echo esc_html($ad['text_title'] ?? $ad['title']); ?></h3>
                <?php if (!empty($ad['text_description'])): ?>
                    <p class="adp-ad-description"><?php echo esc_html($ad['text_description']); ?></p>
                <?php endif; ?>
                <a href="<?php echo esc_url($ad['url']); ?>" target="<?php echo esc_attr($ad['target']); ?>" class="adp-ad-cta" rel="noopener sponsored">
                    <?php echo esc_html($ad['cta']); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar anuncio de AliExpress
     */
    private function render_aliexpress_ad($ad) {
        $template = $ad['template'] ?? 'card';

        if ($template === 'card') {
            $this->render_aliexpress_card($ad);
        } else {
            $this->render_aliexpress_banner($ad);
        }
    }

    /**
     * Renderizar tarjeta de AliExpress
     */
    private function render_aliexpress_card($ad) {
        ?>
        <div class="adp-ad adp-ad-aliexpress adp-ad-aliexpress-card" data-ad-id="<?php echo esc_attr($ad['id']); ?>">
            <div class="adp-ad-label"><?php _e('Publicidad', 'ad-platform'); ?></div>
            <a href="<?php echo esc_url($ad['url']); ?>" target="_blank" class="adp-ad-link" rel="noopener sponsored">
                <?php if (!empty($ad['image'])): ?>
                    <div class="adp-ad-image-wrapper">
                        <img src="<?php echo esc_url($ad['image']); ?>" alt="<?php echo esc_attr($ad['title']); ?>" class="adp-ad-image">
                    </div>
                <?php endif; ?>
                <div class="adp-ad-content">
                    <h3 class="adp-ad-title"><?php echo esc_html($ad['title']); ?></h3>

                    <?php if ($ad['rating'] > 0): ?>
                        <div class="adp-ad-rating">
                            <span class="adp-stars"><?php echo $this->render_stars($ad['rating']); ?></span>
                            <span class="adp-rating-number"><?php echo number_format($ad['rating'], 1); ?></span>
                            <?php if ($ad['orders'] > 0): ?>
                                <span class="adp-orders">(<?php echo number_format($ad['orders']); ?> <?php _e('ventas', 'ad-platform'); ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="adp-ad-price">
                        <span class="adp-price-current">$<?php echo number_format($ad['price'], 2); ?></span>
                        <?php if ($ad['original_price'] > $ad['price']): ?>
                            <span class="adp-price-original">$<?php echo number_format($ad['original_price'], 2); ?></span>
                            <span class="adp-discount"><?php echo $ad['discount']; ?>% OFF</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($ad['free_shipping']): ?>
                        <div class="adp-ad-badge adp-badge-shipping"><?php _e('🚚 Envío Gratis', 'ad-platform'); ?></div>
                    <?php endif; ?>

                    <div class="adp-ad-cta-wrapper">
                        <span class="adp-ad-cta"><?php echo esc_html($ad['cta']); ?></span>
                    </div>
                </div>
            </a>
        </div>
        <?php
    }

    /**
     * Renderizar banner de AliExpress
     */
    private function render_aliexpress_banner($ad) {
        ?>
        <div class="adp-ad adp-ad-aliexpress adp-ad-aliexpress-banner" data-ad-id="<?php echo esc_attr($ad['id']); ?>">
            <a href="<?php echo esc_url($ad['url']); ?>" target="_blank" class="adp-ad-link" rel="noopener sponsored">
                <?php if (!empty($ad['image'])): ?>
                    <img src="<?php echo esc_url($ad['image']); ?>" alt="<?php echo esc_attr($ad['title']); ?>" class="adp-ad-image">
                <?php endif; ?>
                <div class="adp-ad-overlay">
                    <h3><?php echo esc_html($ad['title']); ?></h3>
                    <div class="adp-price">$<?php echo number_format($ad['price'], 2); ?></div>
                </div>
            </a>
        </div>
        <?php
    }

    /**
     * Renderizar estrellas de rating
     */
    private function render_stars($rating) {
        $stars = '';
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;

        for ($i = 0; $i < $full_stars; $i++) {
            $stars .= '⭐';
        }

        if ($half_star) {
            $stars .= '⭐'; // En producción usarías un ícono de media estrella
        }

        return $stars;
    }

    /**
     * Registrar widgets
     */
    public function register_widgets() {
        // TODO: Implementar widget de WordPress
    }
}
