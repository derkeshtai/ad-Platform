<?php
/**
 * Template para Archivo de Landing Pages
 *
 * @package Ad_Platform
 */

if (!defined('ABSPATH')) exit;

get_header();
?>

<div class="adp-archive-wrapper">
    <header class="adp-archive-header">
        <h1 class="adp-archive-title">
            <?php
            if (is_tax('adp_platform')) {
                $term = get_queried_object();
                printf(__('Productos de %s', 'ad-platform'), esc_html($term->name));
            } elseif (is_tax('adp_product_cat')) {
                $term = get_queried_object();
                echo esc_html($term->name);
            } else {
                _e('Todos los Productos', 'ad-platform');
            }
            ?>
        </h1>

        <?php if (is_tax() && term_description()): ?>
            <div class="adp-archive-description">
                <?php echo term_description(); ?>
            </div>
        <?php endif; ?>

        <!-- Filtros de plataforma -->
        <?php
        $platforms = get_terms([
            'taxonomy' => 'adp_platform',
            'hide_empty' => true,
        ]);

        if (!is_wp_error($platforms) && !empty($platforms)):
        ?>
            <nav class="adp-platform-filter">
                <a href="<?php echo get_post_type_archive_link('adp_landing'); ?>"
                   class="<?php echo !is_tax('adp_platform') ? 'active' : ''; ?>">
                    <?php _e('Todos', 'ad-platform'); ?>
                </a>
                <?php foreach ($platforms as $platform): ?>
                    <a href="<?php echo get_term_link($platform); ?>"
                       class="<?php echo (is_tax('adp_platform', $platform->term_id)) ? 'active' : ''; ?>">
                        <?php echo esc_html(ucfirst($platform->name)); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </header>

    <?php if (have_posts()): ?>
        <div class="adp-products-grid">
            <?php while (have_posts()): the_post();
                $price = get_post_meta(get_the_ID(), '_adp_price', true);
                $original_price = get_post_meta(get_the_ID(), '_adp_original_price', true);
                $currency = get_post_meta(get_the_ID(), '_adp_currency', true) ?: 'USD';
                $rating = get_post_meta(get_the_ID(), '_adp_rating', true);
                $orders = get_post_meta(get_the_ID(), '_adp_orders', true);
                $free_shipping = get_post_meta(get_the_ID(), '_adp_free_shipping', true);
                $affiliate_url = get_post_meta(get_the_ID(), '_adp_affiliate_url', true);

                $discount = 0;
                if ($original_price && $price && $original_price > $price) {
                    $discount = round((($original_price - $price) / $original_price) * 100);
                }

                $platforms = get_the_terms(get_the_ID(), 'adp_platform');
                $platform_name = $platforms ? $platforms[0]->name : '';
            ?>
                <article class="adp-product-card">
                    <?php if ($discount > 0): ?>
                        <span class="adp-card-discount">-<?php echo $discount; ?>%</span>
                    <?php endif; ?>

                    <a href="<?php the_permalink(); ?>" class="adp-card-link">
                        <?php if (has_post_thumbnail()): ?>
                            <div class="adp-card-image">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="adp-card-content">
                            <?php if ($platform_name): ?>
                                <span class="adp-card-platform"><?php echo esc_html(ucfirst($platform_name)); ?></span>
                            <?php endif; ?>

                            <h2 class="adp-card-title"><?php the_title(); ?></h2>

                            <?php if ($rating > 0): ?>
                                <div class="adp-card-rating">
                                    <span class="adp-stars-small">
                                        <?php
                                        $full_stars = floor($rating);
                                        for ($i = 0; $i < $full_stars; $i++) {
                                            echo '&#9733;';
                                        }
                                        ?>
                                    </span>
                                    <span><?php echo number_format($rating, 1); ?></span>
                                    <?php if ($orders > 0): ?>
                                        <span class="adp-card-orders">(<?php echo number_format($orders); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($price): ?>
                                <div class="adp-card-price">
                                    <span class="adp-price-main"><?php echo esc_html($currency); ?> <?php echo number_format($price, 2); ?></span>
                                    <?php if ($original_price && $discount > 0): ?>
                                        <span class="adp-price-old"><?php echo esc_html($currency); ?> <?php echo number_format($original_price, 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($free_shipping): ?>
                                <span class="adp-card-badge"><?php _e('Envio Gratis', 'ad-platform'); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Paginación -->
        <nav class="adp-pagination">
            <?php
            the_posts_pagination([
                'mid_size' => 2,
                'prev_text' => '&larr; ' . __('Anterior', 'ad-platform'),
                'next_text' => __('Siguiente', 'ad-platform') . ' &rarr;',
            ]);
            ?>
        </nav>

    <?php else: ?>
        <div class="adp-no-products">
            <p><?php _e('No se encontraron productos.', 'ad-platform'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.adp-archive-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.adp-archive-header {
    text-align: center;
    margin-bottom: 40px;
}

.adp-archive-title {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 16px;
}

.adp-archive-description {
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto 24px;
}

.adp-platform-filter {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
}

.adp-platform-filter a {
    padding: 8px 20px;
    background: #f3f4f6;
    color: #4b5563;
    text-decoration: none;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.adp-platform-filter a:hover,
.adp-platform-filter a.active {
    background: #3b82f6;
    color: white;
}

.adp-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.adp-product-card {
    position: relative;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
}

.adp-product-card:hover {
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transform: translateY(-4px);
}

.adp-card-discount {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #dc2626;
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    z-index: 10;
}

.adp-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.adp-card-image {
    aspect-ratio: 1;
    background: #f9fafb;
    overflow: hidden;
}

.adp-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.adp-product-card:hover .adp-card-image img {
    transform: scale(1.05);
}

.adp-card-content {
    padding: 16px;
}

.adp-card-platform {
    display: inline-block;
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.adp-card-title {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 10px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.adp-card-rating {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    margin-bottom: 10px;
}

.adp-stars-small {
    color: #fbbf24;
}

.adp-card-orders {
    color: #9ca3af;
}

.adp-card-price {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.adp-price-main {
    font-size: 20px;
    font-weight: 700;
    color: #dc2626;
}

.adp-price-old {
    font-size: 14px;
    color: #9ca3af;
    text-decoration: line-through;
}

.adp-card-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #d1fae5;
    color: #059669;
    font-size: 11px;
    font-weight: 500;
    border-radius: 4px;
}

.adp-pagination {
    margin-top: 40px;
    text-align: center;
}

.adp-pagination .nav-links {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.adp-pagination a,
.adp-pagination span {
    padding: 8px 16px;
    background: #f3f4f6;
    color: #4b5563;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
}

.adp-pagination a:hover {
    background: #e5e7eb;
}

.adp-pagination .current {
    background: #3b82f6;
    color: white;
}

.adp-no-products {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

@media (max-width: 768px) {
    .adp-archive-title {
        font-size: 24px;
    }

    .adp-products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .adp-card-title {
        font-size: 13px;
    }

    .adp-price-main {
        font-size: 16px;
    }
}

@media (prefers-color-scheme: dark) {
    .adp-archive-wrapper {
        background: #1f2937;
    }

    .adp-archive-title {
        color: #f9fafb;
    }

    .adp-product-card {
        background: #111827;
        border-color: #374151;
    }

    .adp-card-title {
        color: #f9fafb;
    }
}
</style>

<?php
get_footer();
