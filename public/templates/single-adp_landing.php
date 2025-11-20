<?php
/**
 * Template para Landing Page de Producto
 *
 * @package Ad_Platform
 */

if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();

    // Obtener meta datos
    $price = get_post_meta(get_the_ID(), '_adp_price', true);
    $original_price = get_post_meta(get_the_ID(), '_adp_original_price', true);
    $currency = get_post_meta(get_the_ID(), '_adp_currency', true) ?: 'USD';
    $rating = get_post_meta(get_the_ID(), '_adp_rating', true);
    $orders = get_post_meta(get_the_ID(), '_adp_orders', true);
    $gallery = get_post_meta(get_the_ID(), '_adp_gallery', true);
    $features = get_post_meta(get_the_ID(), '_adp_features', true);
    $specifications = get_post_meta(get_the_ID(), '_adp_specifications', true);
    $affiliate_url = get_post_meta(get_the_ID(), '_adp_affiliate_url', true);
    $cta_text = get_post_meta(get_the_ID(), '_adp_cta_text', true) ?: __('Comprar Ahora', 'ad-platform');
    $cta_color = get_post_meta(get_the_ID(), '_adp_cta_color', true) ?: '#ff6b00';
    $badges = get_post_meta(get_the_ID(), '_adp_badges', true);
    $free_shipping = get_post_meta(get_the_ID(), '_adp_free_shipping', true);
    $fb_pixel_event = get_post_meta(get_the_ID(), '_adp_fb_pixel_event', true) ?: 'ViewContent';
    $external_id = get_post_meta(get_the_ID(), '_adp_external_id', true);

    // Calcular descuento
    $discount = 0;
    if ($original_price && $price && $original_price > $price) {
        $discount = round((($original_price - $price) / $original_price) * 100);
    }

    // Obtener plataforma
    $platforms = get_the_terms(get_the_ID(), 'adp_platform');
    $platform = $platforms ? $platforms[0]->name : '';
    $platform_slug = $platforms ? $platforms[0]->slug : '';

    // Parsear galería
    $gallery_images = array();
    if ($gallery) {
        $gallery_images = array_filter(array_map('trim', explode("\n", $gallery)));
    }

    // Parsear características
    $features_list = array();
    if ($features) {
        $features_list = array_filter(array_map('trim', explode("\n", $features)));
    }

    // Parsear especificaciones
    $specs_array = array();
    if ($specifications) {
        $specs_lines = array_filter(array_map('trim', explode("\n", $specifications)));
        foreach ($specs_lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $specs_array[trim($key)] = trim($value);
            }
        }
    }
?>

<div class="adp-landing-wrapper">
    <article id="post-<?php the_ID(); ?>" <?php post_class('adp-landing-product'); ?>>

        <!-- Breadcrumb -->
        <nav class="adp-breadcrumb">
            <a href="<?php echo home_url(); ?>"><?php _e('Inicio', 'ad-platform'); ?></a>
            <span class="adp-separator">/</span>
            <a href="<?php echo get_post_type_archive_link('adp_landing'); ?>"><?php _e('Productos', 'ad-platform'); ?></a>
            <?php if ($platform): ?>
                <span class="adp-separator">/</span>
                <span><?php echo esc_html(ucfirst($platform)); ?></span>
            <?php endif; ?>
        </nav>

        <div class="adp-landing-main">
            <!-- Galería de Imágenes -->
            <div class="adp-landing-gallery">
                <?php if (has_post_thumbnail()): ?>
                    <div class="adp-main-image">
                        <?php the_post_thumbnail('large', ['class' => 'adp-featured-image', 'id' => 'adp-main-img']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($gallery_images)): ?>
                    <div class="adp-thumbnails">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="adp-thumb adp-thumb-active" data-src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>">
                                <?php the_post_thumbnail('thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($gallery_images as $index => $img_url): ?>
                            <div class="adp-thumb" data-src="<?php echo esc_url($img_url); ?>">
                                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(get_the_title() . ' - ' . ($index + 2)); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Información del Producto -->
            <div class="adp-landing-info">
                <h1 class="adp-landing-title"><?php the_title(); ?></h1>

                <?php if ($rating > 0): ?>
                    <div class="adp-landing-rating">
                        <span class="adp-stars">
                            <?php
                            $full_stars = floor($rating);
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<span class="adp-star adp-star-full">&#9733;</span>';
                            }
                            if ($rating - $full_stars >= 0.5) {
                                echo '<span class="adp-star adp-star-half">&#9733;</span>';
                            }
                            ?>
                        </span>
                        <span class="adp-rating-value"><?php echo number_format($rating, 1); ?></span>
                        <?php if ($orders > 0): ?>
                            <span class="adp-orders-count">(<?php echo number_format($orders); ?> <?php _e('ventas', 'ad-platform'); ?>)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Precio -->
                <div class="adp-landing-price-box">
                    <?php if ($price): ?>
                        <span class="adp-current-price">
                            <?php echo esc_html($currency); ?> <?php echo number_format($price, 2); ?>
                        </span>
                        <?php if ($original_price && $discount > 0): ?>
                            <span class="adp-original-price">
                                <?php echo esc_html($currency); ?> <?php echo number_format($original_price, 2); ?>
                            </span>
                            <span class="adp-discount-badge">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Badges -->
                <div class="adp-landing-badges">
                    <?php if ($free_shipping): ?>
                        <span class="adp-badge adp-badge-shipping"><?php _e('Envio Gratis', 'ad-platform'); ?></span>
                    <?php endif; ?>
                    <?php if ($badges): ?>
                        <?php foreach (array_filter(array_map('trim', explode(',', $badges))) as $badge): ?>
                            <span class="adp-badge"><?php echo esc_html($badge); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Descripción corta -->
                <?php if (has_excerpt()): ?>
                    <div class="adp-landing-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>

                <!-- Características principales -->
                <?php if (!empty($features_list)): ?>
                    <div class="adp-landing-features">
                        <h3><?php _e('Caracteristicas', 'ad-platform'); ?></h3>
                        <ul>
                            <?php foreach (array_slice($features_list, 0, 5) as $feature): ?>
                                <li><span class="adp-check">&#10003;</span> <?php echo esc_html($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- CTA Button -->
                <?php if ($affiliate_url): ?>
                    <div class="adp-landing-cta">
                        <a href="<?php echo esc_url($affiliate_url); ?>"
                           target="_blank"
                           rel="noopener sponsored"
                           class="adp-cta-button"
                           style="background-color: <?php echo esc_attr($cta_color); ?>;"
                           data-product-id="<?php echo esc_attr($external_id ?: get_the_ID()); ?>"
                           data-platform="<?php echo esc_attr($platform_slug); ?>">
                            <?php echo esc_html($cta_text); ?>
                        </a>
                        <p class="adp-platform-note">
                            <?php printf(__('Sera redirigido a %s', 'ad-platform'), ucfirst($platform ?: 'la tienda')); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Trust badges -->
                <div class="adp-trust-badges">
                    <span><span class="adp-icon">&#128274;</span> <?php _e('Compra Segura', 'ad-platform'); ?></span>
                    <span><span class="adp-icon">&#128230;</span> <?php _e('Garantia', 'ad-platform'); ?></span>
                    <span><span class="adp-icon">&#128176;</span> <?php _e('Mejor Precio', 'ad-platform'); ?></span>
                </div>
            </div>
        </div>

        <!-- Contenido completo -->
        <div class="adp-landing-content">
            <?php if (get_the_content()): ?>
                <div class="adp-description-full">
                    <h2><?php _e('Descripcion del Producto', 'ad-platform'); ?></h2>
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>

            <!-- Especificaciones -->
            <?php if (!empty($specs_array)): ?>
                <div class="adp-specifications">
                    <h2><?php _e('Especificaciones', 'ad-platform'); ?></h2>
                    <table class="adp-specs-table">
                        <?php foreach ($specs_array as $key => $value): ?>
                            <tr>
                                <th><?php echo esc_html($key); ?></th>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Todas las características -->
            <?php if (count($features_list) > 5): ?>
                <div class="adp-all-features">
                    <h2><?php _e('Todas las Caracteristicas', 'ad-platform'); ?></h2>
                    <ul>
                        <?php foreach ($features_list as $feature): ?>
                            <li><span class="adp-check">&#10003;</span> <?php echo esc_html($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- CTA Final -->
        <?php if ($affiliate_url): ?>
            <div class="adp-landing-final-cta">
                <h3><?php _e('Te interesa este producto?', 'ad-platform'); ?></h3>
                <a href="<?php echo esc_url($affiliate_url); ?>"
                   target="_blank"
                   rel="noopener sponsored"
                   class="adp-cta-button adp-cta-large"
                   style="background-color: <?php echo esc_attr($cta_color); ?>;"
                   data-product-id="<?php echo esc_attr($external_id ?: get_the_ID()); ?>"
                   data-platform="<?php echo esc_attr($platform_slug); ?>">
                    <?php echo esc_html($cta_text); ?>
                </a>
            </div>
        <?php endif; ?>

    </article>
</div>

<!-- Estilos del Landing -->
<style>
.adp-landing-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.adp-breadcrumb {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 20px;
}

.adp-breadcrumb a {
    color: #3b82f6;
    text-decoration: none;
}

.adp-breadcrumb a:hover {
    text-decoration: underline;
}

.adp-separator {
    margin: 0 8px;
}

.adp-landing-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

/* Galería */
.adp-landing-gallery {
    position: sticky;
    top: 20px;
    align-self: start;
}

.adp-main-image {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 16px;
    background: #f9fafb;
}

.adp-featured-image {
    width: 100%;
    height: auto;
    display: block;
}

.adp-thumbnails {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.adp-thumb {
    width: 80px;
    height: 80px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.3s;
}

.adp-thumb:hover,
.adp-thumb-active {
    border-color: #3b82f6;
}

.adp-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Info */
.adp-landing-title {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 16px;
    line-height: 1.3;
}

.adp-landing-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.adp-stars {
    color: #fbbf24;
    font-size: 18px;
}

.adp-star-half {
    opacity: 0.5;
}

.adp-rating-value {
    font-weight: 600;
    color: #374151;
}

.adp-orders-count {
    color: #6b7280;
    font-size: 14px;
}

.adp-landing-price-box {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.adp-current-price {
    font-size: 32px;
    font-weight: 700;
    color: #dc2626;
}

.adp-original-price {
    font-size: 20px;
    color: #9ca3af;
    text-decoration: line-through;
}

.adp-discount-badge {
    background: #fef2f2;
    color: #dc2626;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
}

.adp-landing-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.adp-badge {
    display: inline-block;
    padding: 6px 12px;
    background: #ecfdf5;
    color: #059669;
    font-size: 13px;
    font-weight: 500;
    border-radius: 6px;
}

.adp-badge-shipping {
    background: #d1fae5;
}

.adp-landing-excerpt {
    color: #4b5563;
    line-height: 1.6;
    margin-bottom: 20px;
}

.adp-landing-features {
    margin-bottom: 24px;
}

.adp-landing-features h3 {
    font-size: 18px;
    margin: 0 0 12px;
    color: #111827;
}

.adp-landing-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.adp-landing-features li {
    padding: 8px 0;
    color: #374151;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.adp-check {
    color: #10b981;
    font-weight: bold;
}

/* CTA Button */
.adp-landing-cta {
    margin-bottom: 24px;
}

.adp-cta-button {
    display: inline-block;
    width: 100%;
    padding: 16px 32px;
    color: white !important;
    text-decoration: none;
    font-size: 18px;
    font-weight: 600;
    text-align: center;
    border-radius: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

.adp-cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    filter: brightness(1.1);
}

.adp-platform-note {
    font-size: 12px;
    color: #6b7280;
    margin-top: 8px;
    text-align: center;
}

.adp-trust-badges {
    display: flex;
    justify-content: space-between;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.adp-trust-badges span {
    font-size: 12px;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 4px;
}

.adp-icon {
    font-size: 16px;
}

/* Contenido */
.adp-landing-content {
    max-width: 800px;
    margin: 0 auto;
}

.adp-description-full,
.adp-specifications,
.adp-all-features {
    margin-bottom: 40px;
}

.adp-landing-content h2 {
    font-size: 24px;
    color: #111827;
    margin: 0 0 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
}

.adp-specs-table {
    width: 100%;
    border-collapse: collapse;
}

.adp-specs-table tr:nth-child(even) {
    background: #f9fafb;
}

.adp-specs-table th,
.adp-specs-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.adp-specs-table th {
    width: 40%;
    color: #6b7280;
    font-weight: 500;
}

.adp-specs-table td {
    color: #111827;
}

.adp-all-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
    columns: 2;
    column-gap: 24px;
}

.adp-all-features li {
    padding: 8px 0;
    color: #374151;
    break-inside: avoid;
}

/* Final CTA */
.adp-landing-final-cta {
    text-align: center;
    padding: 40px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 16px;
    margin-top: 40px;
}

.adp-landing-final-cta h3 {
    font-size: 24px;
    margin: 0 0 20px;
    color: #111827;
}

.adp-cta-large {
    display: inline-block;
    width: auto;
    min-width: 300px;
}

/* Responsive */
@media (max-width: 768px) {
    .adp-landing-main {
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .adp-landing-gallery {
        position: static;
    }

    .adp-landing-title {
        font-size: 22px;
    }

    .adp-current-price {
        font-size: 26px;
    }

    .adp-trust-badges {
        flex-direction: column;
        gap: 12px;
    }

    .adp-all-features ul {
        columns: 1;
    }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .adp-landing-wrapper {
        background: #1f2937;
    }

    .adp-landing-title,
    .adp-landing-content h2 {
        color: #f9fafb;
    }

    .adp-main-image,
    .adp-thumb {
        border-color: #374151;
        background: #111827;
    }

    .adp-landing-features li,
    .adp-all-features li,
    .adp-specs-table td {
        color: #e5e7eb;
    }

    .adp-trust-badges,
    .adp-specs-table tr:nth-child(even) {
        background: #374151;
    }

    .adp-landing-final-cta {
        background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
    }

    .adp-landing-final-cta h3 {
        color: #f9fafb;
    }
}
</style>

<!-- JavaScript para galería -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Galería de imágenes
    const thumbs = document.querySelectorAll('.adp-thumb');
    const mainImg = document.getElementById('adp-main-img');

    if (thumbs.length && mainImg) {
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const src = this.dataset.src;
                if (src) {
                    mainImg.src = src;
                    thumbs.forEach(t => t.classList.remove('adp-thumb-active'));
                    this.classList.add('adp-thumb-active');
                }
            });
        });
    }

    // Tracking de clicks en CTA
    const ctaButtons = document.querySelectorAll('.adp-cta-button');
    ctaButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const platform = this.dataset.platform;

            // Enviar evento a la API de tracking
            if (window.adpConfig && window.adpConfig.api_url) {
                fetch(window.adpConfig.api_url + 'track/click', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        landing_id: <?php echo get_the_ID(); ?>,
                        product_id: productId,
                        platform: platform
                    })
                }).catch(console.error);
            }

            // Facebook Pixel event
            if (typeof fbq !== 'undefined') {
                fbq('track', 'AddToCart', {
                    content_ids: [productId],
                    content_type: 'product',
                    value: <?php echo $price ? floatval($price) : 0; ?>,
                    currency: '<?php echo esc_js($currency); ?>'
                });
            }
        });
    });

    // Facebook Pixel ViewContent (ya definido en el evento de página)
    if (typeof fbq !== 'undefined') {
        fbq('track', '<?php echo esc_js($fb_pixel_event); ?>', {
            content_ids: ['<?php echo esc_js($external_id ?: get_the_ID()); ?>'],
            content_type: 'product',
            content_name: '<?php echo esc_js(get_the_title()); ?>',
            value: <?php echo $price ? floatval($price) : 0; ?>,
            currency: '<?php echo esc_js($currency); ?>'
        });
    }
});
</script>

<?php
endwhile;

get_footer();
