<?php
/**
 * Clase de Remarketing
 *
 * Sistema de carrusel de productos vistos para remarketing
 *
 * @package Ad_Platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Remarketing {

    /**
     * Constructor
     */
    public function __construct() {
        // Registrar shortcodes
        add_shortcode('adp_remarketing_carousel', array($this, 'render_carousel_shortcode'));
        add_shortcode('adp_recently_viewed', array($this, 'render_recently_viewed_shortcode'));

        // Registrar widget
        add_action('widgets_init', array($this, 'register_widgets'));

        // Enqueue estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Registrar widgets
     */
    public function register_widgets() {
        register_widget('Ad_Platform_Remarketing_Widget');
    }

    /**
     * Enqueue estilos del carrusel
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'adp-remarketing',
            AD_PLATFORM_PLUGIN_URL . 'public/css/remarketing.css',
            array(),
            AD_PLATFORM_VERSION
        );
    }

    /**
     * Renderizar shortcode de carrusel
     *
     * [adp_remarketing_carousel limit="5" platform="aliexpress" title="Productos que viste"]
     */
    public function render_carousel_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'platform' => '',
            'title' => __('Productos que viste', 'ad-platform'),
            'columns' => 4,
            'show_price' => true,
            'show_rating' => true,
        ), $atts);

        return $this->render_carousel($atts);
    }

    /**
     * Renderizar shortcode de productos vistos (versión simple)
     *
     * [adp_recently_viewed limit="3"]
     */
    public function render_recently_viewed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 3,
            'platform' => '',
            'style' => 'list', // 'list' or 'grid'
        ), $atts);

        return $this->render_recently_viewed($atts);
    }

    /**
     * Renderizar carrusel de productos
     */
    public function render_carousel($args) {
        $products = Ad_Platform_Facebook_Pixel::get_viewed_products(
            intval($args['limit']),
            sanitize_text_field($args['platform'])
        );

        if (empty($products)) {
            return '';
        }

        $show_price = filter_var($args['show_price'], FILTER_VALIDATE_BOOLEAN);
        $show_rating = filter_var($args['show_rating'], FILTER_VALIDATE_BOOLEAN);

        ob_start();
        ?>
        <div class="adp-remarketing-carousel" data-columns="<?php echo intval($args['columns']); ?>">
            <?php if (!empty($args['title'])): ?>
                <h3 class="adp-carousel-title"><?php echo esc_html($args['title']); ?></h3>
            <?php endif; ?>

            <div class="adp-carousel-wrapper">
                <button class="adp-carousel-btn adp-carousel-prev" aria-label="<?php _e('Anterior', 'ad-platform'); ?>">&#10094;</button>

                <div class="adp-carousel-track">
                    <?php foreach ($products as $product): ?>
                        <div class="adp-carousel-item">
                            <a href="<?php echo esc_url($product['url']); ?>" class="adp-carousel-link">
                                <?php if (!empty($product['image'])): ?>
                                    <div class="adp-carousel-image">
                                        <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['title']); ?>" loading="lazy">
                                    </div>
                                <?php endif; ?>

                                <div class="adp-carousel-content">
                                    <h4 class="adp-carousel-product-title"><?php echo esc_html($product['title']); ?></h4>

                                    <?php if ($show_rating && !empty($product['rating'])): ?>
                                        <div class="adp-carousel-rating">
                                            <span class="adp-stars">
                                                <?php
                                                $full_stars = floor($product['rating']);
                                                for ($i = 0; $i < $full_stars; $i++) {
                                                    echo '&#9733;';
                                                }
                                                ?>
                                            </span>
                                            <span><?php echo number_format($product['rating'], 1); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($show_price && !empty($product['price'])): ?>
                                        <div class="adp-carousel-price">
                                            <?php echo esc_html($product['currency'] ?? 'USD'); ?> <?php echo number_format($product['price'], 2); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="adp-carousel-btn adp-carousel-next" aria-label="<?php _e('Siguiente', 'ad-platform'); ?>">&#10095;</button>
            </div>

            <div class="adp-carousel-dots"></div>
        </div>

        <script>
        (function() {
            const carousel = document.querySelector('.adp-remarketing-carousel');
            if (!carousel) return;

            const track = carousel.querySelector('.adp-carousel-track');
            const items = carousel.querySelectorAll('.adp-carousel-item');
            const prevBtn = carousel.querySelector('.adp-carousel-prev');
            const nextBtn = carousel.querySelector('.adp-carousel-next');
            const dotsContainer = carousel.querySelector('.adp-carousel-dots');

            const columns = parseInt(carousel.dataset.columns) || 4;
            const totalItems = items.length;
            const totalSlides = Math.ceil(totalItems / columns);
            let currentSlide = 0;

            // Crear dots
            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('span');
                dot.className = 'adp-carousel-dot' + (i === 0 ? ' active' : '');
                dot.addEventListener('click', () => goToSlide(i));
                dotsContainer.appendChild(dot);
            }

            function goToSlide(index) {
                currentSlide = index;
                const offset = -(currentSlide * 100) + '%';
                track.style.transform = 'translateX(' + offset + ')';

                // Actualizar dots
                dotsContainer.querySelectorAll('.adp-carousel-dot').forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentSlide);
                });
            }

            prevBtn.addEventListener('click', () => {
                currentSlide = currentSlide > 0 ? currentSlide - 1 : totalSlides - 1;
                goToSlide(currentSlide);
            });

            nextBtn.addEventListener('click', () => {
                currentSlide = currentSlide < totalSlides - 1 ? currentSlide + 1 : 0;
                goToSlide(currentSlide);
            });

            // Auto-play opcional
            let autoPlay = setInterval(() => {
                currentSlide = currentSlide < totalSlides - 1 ? currentSlide + 1 : 0;
                goToSlide(currentSlide);
            }, 5000);

            carousel.addEventListener('mouseenter', () => clearInterval(autoPlay));
            carousel.addEventListener('mouseleave', () => {
                autoPlay = setInterval(() => {
                    currentSlide = currentSlide < totalSlides - 1 ? currentSlide + 1 : 0;
                    goToSlide(currentSlide);
                }, 5000);
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar lista de productos vistos
     */
    public function render_recently_viewed($args) {
        $products = Ad_Platform_Facebook_Pixel::get_viewed_products(
            intval($args['limit']),
            sanitize_text_field($args['platform'])
        );

        if (empty($products)) {
            return '';
        }

        $style = $args['style'] === 'grid' ? 'grid' : 'list';

        ob_start();
        ?>
        <div class="adp-recently-viewed adp-style-<?php echo $style; ?>">
            <?php foreach ($products as $product): ?>
                <div class="adp-viewed-item">
                    <a href="<?php echo esc_url($product['url']); ?>" class="adp-viewed-link">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['title']); ?>" class="adp-viewed-image">
                        <?php endif; ?>

                        <div class="adp-viewed-info">
                            <span class="adp-viewed-title"><?php echo esc_html($product['title']); ?></span>
                            <?php if (!empty($product['price'])): ?>
                                <span class="adp-viewed-price">
                                    <?php echo esc_html($product['currency'] ?? 'USD'); ?> <?php echo number_format($product['price'], 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Widget de Remarketing
 */
class Ad_Platform_Remarketing_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'adp_remarketing_widget',
            __('Ad Platform - Productos Vistos', 'ad-platform'),
            array(
                'description' => __('Muestra productos que el usuario ha visto recientemente', 'ad-platform'),
            )
        );
    }

    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Productos que viste', 'ad-platform');
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        $platform = !empty($instance['platform']) ? $instance['platform'] : '';
        $style = !empty($instance['style']) ? $instance['style'] : 'list';

        $products = Ad_Platform_Facebook_Pixel::get_viewed_products($limit, $platform);

        if (empty($products)) {
            return;
        }

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }

        $remarketing = new Ad_Platform_Remarketing();
        echo $remarketing->render_recently_viewed(array(
            'limit' => $limit,
            'platform' => $platform,
            'style' => $style,
        ));

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Productos que viste', 'ad-platform');
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        $platform = !empty($instance['platform']) ? $instance['platform'] : '';
        $style = !empty($instance['style']) ? $instance['style'] : 'list';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Título:', 'ad-platform'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Cantidad:', 'ad-platform'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" min="1" max="10" value="<?php echo esc_attr($limit); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('platform'); ?>"><?php _e('Plataforma:', 'ad-platform'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('platform'); ?>" name="<?php echo $this->get_field_name('platform'); ?>">
                <option value="" <?php selected($platform, ''); ?>><?php _e('Todas', 'ad-platform'); ?></option>
                <option value="aliexpress" <?php selected($platform, 'aliexpress'); ?>>AliExpress</option>
                <option value="hotmart" <?php selected($platform, 'hotmart'); ?>>Hotmart</option>
                <option value="fiverr" <?php selected($platform, 'fiverr'); ?>>Fiverr</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Estilo:', 'ad-platform'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
                <option value="list" <?php selected($style, 'list'); ?>><?php _e('Lista', 'ad-platform'); ?></option>
                <option value="grid" <?php selected($style, 'grid'); ?>><?php _e('Cuadrícula', 'ad-platform'); ?></option>
            </select>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
        $instance['platform'] = (!empty($new_instance['platform'])) ? sanitize_text_field($new_instance['platform']) : '';
        $instance['style'] = (!empty($new_instance['style'])) ? sanitize_text_field($new_instance['style']) : 'list';
        return $instance;
    }
}

// Inicializar
new Ad_Platform_Remarketing();
