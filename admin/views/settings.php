<?php
/**
 * Vista de Configuración
 */

if (!defined('ABSPATH')) exit;

$general = get_option('ad_platform_general', array());
$aliexpress = get_option('ad_platform_aliexpress', array());
$tracking = get_option('ad_platform_tracking', array());
$facebook = get_option('ad_platform_facebook', array());
$redirect = get_option('ad_platform_redirect', array());
?>

<div class="wrap">
    <h1><?php _e('Configuración de Ad Platform', 'ad-platform'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('adp_settings_nonce'); ?>

        <div class="adp-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'ad-platform'); ?></a>
                <a href="#aliexpress" class="nav-tab"><?php _e('AliExpress', 'ad-platform'); ?></a>
                <a href="#tracking" class="nav-tab"><?php _e('Tracking', 'ad-platform'); ?></a>
                <a href="#facebook" class="nav-tab"><?php _e('Facebook Pixel', 'ad-platform'); ?></a>
                <a href="#redirect" class="nav-tab"><?php _e('Redirección', 'ad-platform'); ?></a>
            </nav>

            <!-- Tab: General -->
            <div id="general" class="adp-tab-content adp-tab-active">
                <h2><?php _e('Configuración General', 'ad-platform'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Habilitar Tracking', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_general[enable_tracking]" value="1" <?php checked(!empty($general['enable_tracking'])); ?>>
                                <?php _e('Registrar impresiones y clicks', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Habilitar Caché', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_general[enable_cache]" value="1" <?php checked(!empty($general['enable_cache'])); ?>>
                                <?php _e('Cachear anuncios para mejor rendimiento', 'ad-platform'); ?>
                            </label>
                            <p class="description"><?php _e('Duración del caché en segundos:', 'ad-platform'); ?>
                                <input type="number" name="ad_platform_general[cache_duration]" value="<?php echo esc_attr($general['cache_duration'] ?? 3600); ?>" min="60" class="small-text">
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Anuncio de Fallback', 'ad-platform'); ?></th>
                        <td>
                            <select name="ad_platform_general[default_ad_fallback]">
                                <option value="none" <?php selected($general['default_ad_fallback'] ?? '', 'none'); ?>><?php _e('No mostrar nada', 'ad-platform'); ?></option>
                                <option value="aliexpress" <?php selected($general['default_ad_fallback'] ?? '', 'aliexpress'); ?>><?php _e('AliExpress', 'ad-platform'); ?></option>
                            </select>
                            <p class="description"><?php _e('Qué mostrar cuando no hay anuncios disponibles', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Tab: AliExpress -->
            <div id="aliexpress" class="adp-tab-content">
                <h2><?php _e('Integración con AliExpress', 'ad-platform'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Habilitar AliExpress', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_aliexpress[enabled]" value="1" <?php checked(!empty($aliexpress['enabled'])); ?>>
                                <?php _e('Activar integración con AliExpress', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('App Key', 'ad-platform'); ?></th>
                        <td>
                            <input type="text" name="ad_platform_aliexpress[app_key]" value="<?php echo esc_attr($aliexpress['app_key'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('App Secret', 'ad-platform'); ?></th>
                        <td>
                            <input type="password" name="ad_platform_aliexpress[app_secret]" value="<?php echo esc_attr($aliexpress['app_secret'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Tracking ID', 'ad-platform'); ?></th>
                        <td>
                            <input type="text" name="ad_platform_aliexpress[tracking_id]" value="<?php echo esc_attr($aliexpress['tracking_id'] ?? ''); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Modo de Operación', 'ad-platform'); ?></th>
                        <td>
                            <select name="ad_platform_aliexpress[mode]">
                                <option value="auto" <?php selected($aliexpress['mode'] ?? '', 'auto'); ?>><?php _e('Automático', 'ad-platform'); ?></option>
                                <option value="semi-auto" <?php selected($aliexpress['mode'] ?? '', 'semi-auto'); ?>><?php _e('Semi-automático', 'ad-platform'); ?></option>
                                <option value="manual" <?php selected($aliexpress['mode'] ?? '', 'manual'); ?>><?php _e('Manual', 'ad-platform'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Ordenar Por', 'ad-platform'); ?></th>
                        <td>
                            <select name="ad_platform_aliexpress[sort_by]">
                                <option value="commission" <?php selected($aliexpress['sort_by'] ?? '', 'commission'); ?>><?php _e('Comisión más alta', 'ad-platform'); ?></option>
                                <option value="rating" <?php selected($aliexpress['sort_by'] ?? '', 'rating'); ?>><?php _e('Mejor rating', 'ad-platform'); ?></option>
                                <option value="orders" <?php selected($aliexpress['sort_by'] ?? '', 'orders'); ?>><?php _e('Más vendidos', 'ad-platform'); ?></option>
                                <option value="balanced" <?php selected($aliexpress['sort_by'] ?? '', 'balanced'); ?>><?php _e('Balanceado', 'ad-platform'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Filtros de Productos', 'ad-platform'); ?></th>
                        <td>
                            <p>
                                <?php _e('Comisión mínima:', 'ad-platform'); ?>
                                <input type="number" name="ad_platform_aliexpress[min_commission]" value="<?php echo esc_attr($aliexpress['min_commission'] ?? 5); ?>" min="0" max="100" step="0.1" class="small-text">%
                            </p>
                            <p>
                                <?php _e('Rating mínimo:', 'ad-platform'); ?>
                                <input type="number" name="ad_platform_aliexpress[min_rating]" value="<?php echo esc_attr($aliexpress['min_rating'] ?? 4.0); ?>" min="0" max="5" step="0.1" class="small-text"> ⭐
                            </p>
                            <p>
                                <?php _e('Órdenes mínimas:', 'ad-platform'); ?>
                                <input type="number" name="ad_platform_aliexpress[min_orders]" value="<?php echo esc_attr($aliexpress['min_orders'] ?? 100); ?>" min="0" class="small-text">
                            </p>
                            <p>
                                <?php _e('Precio:', 'ad-platform'); ?>
                                $<input type="number" name="ad_platform_aliexpress[min_price]" value="<?php echo esc_attr($aliexpress['min_price'] ?? 1); ?>" min="0" step="0.01" class="small-text">
                                - $<input type="number" name="ad_platform_aliexpress[max_price]" value="<?php echo esc_attr($aliexpress['max_price'] ?? 500); ?>" min="0" step="0.01" class="small-text">
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="ad_platform_aliexpress[free_shipping_only]" value="1" <?php checked(!empty($aliexpress['free_shipping_only'])); ?>>
                                    <?php _e('Solo productos con envío gratis', 'ad-platform'); ?>
                                </label>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Caché', 'ad-platform'); ?></th>
                        <td>
                            <?php _e('Guardar productos en caché por:', 'ad-platform'); ?>
                            <input type="number" name="ad_platform_aliexpress[cache_hours]" value="<?php echo esc_attr($aliexpress['cache_hours'] ?? 24); ?>" min="1" max="168" class="small-text"> <?php _e('horas', 'ad-platform'); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Visualización', 'ad-platform'); ?></th>
                        <td>
                            <select name="ad_platform_aliexpress[display_template]">
                                <option value="card" <?php selected($aliexpress['display_template'] ?? '', 'card'); ?>><?php _e('Tarjeta de producto', 'ad-platform'); ?></option>
                                <option value="banner" <?php selected($aliexpress['display_template'] ?? '', 'banner'); ?>><?php _e('Banner horizontal', 'ad-platform'); ?></option>
                            </select>
                            <p>
                                <?php _e('Texto del botón:', 'ad-platform'); ?>
                                <input type="text" name="ad_platform_aliexpress[cta_text]" value="<?php echo esc_attr($aliexpress['cta_text'] ?? 'Ver en AliExpress'); ?>" class="regular-text">
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Categorías de Adultos', 'ad-platform'); ?></th>
                        <td>
                            <textarea name="ad_platform_aliexpress[adult_categories]" rows="3" class="regular-text" style="width: 100%;"><?php echo esc_textarea($aliexpress['adult_categories'] ?? 'health,wellness,massage,supplements,bedroom'); ?></textarea>
                            <p class="description">
                                <?php _e('⚠️ Categorías de AliExpress permitidas para contenido adulto (separadas por comas).', 'ad-platform'); ?><br>
                                <?php _e('Ejemplos: health, wellness, massage, supplements, bedroom, personal-care', 'ad-platform'); ?><br>
                                <?php _e('Estos productos solo se mostrarán en sitios marcados como adultos (ej: xlatinas.com)', 'ad-platform'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Tab: Tracking -->
            <div id="tracking" class="adp-tab-content">
                <h2><?php _e('Configuración de Tracking', 'ad-platform'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Anonimizar IP', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_tracking[anonymize_ip]" value="1" <?php checked(!empty($tracking['anonymize_ip'])); ?>>
                                <?php _e('Cumplir con GDPR anonimizando direcciones IP', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('User Agent', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_tracking[track_user_agent]" value="1" <?php checked(!empty($tracking['track_user_agent'])); ?>>
                                <?php _e('Registrar navegador del usuario', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Referrer', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_tracking[track_referrer]" value="1" <?php checked(!empty($tracking['track_referrer'])); ?>>
                                <?php _e('Registrar página de referencia', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Geolocalización', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_tracking[enable_geolocation]" value="1" <?php checked(!empty($tracking['enable_geolocation'])); ?>>
                                <?php _e('Detectar país del usuario', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Tab: Facebook Pixel -->
            <div id="facebook" class="adp-tab-content">
                <h2><?php _e('Integración con Facebook Pixel', 'ad-platform'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Habilitar Facebook Pixel', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_facebook[enabled]" value="1" <?php checked(!empty($facebook['enabled'])); ?>>
                                <?php _e('Activar tracking con Facebook Pixel', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Pixel ID', 'ad-platform'); ?></th>
                        <td>
                            <input type="text" name="ad_platform_facebook[pixel_id]" value="<?php echo esc_attr($facebook['pixel_id'] ?? ''); ?>" class="regular-text" placeholder="123456789012345">
                            <p class="description"><?php _e('Tu ID de Facebook Pixel (15-16 digitos)', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Advanced Matching', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_facebook[advanced_matching]" value="1" <?php checked(!empty($facebook['advanced_matching'])); ?>>
                                <?php _e('Habilitar Advanced Matching (email, nombre)', 'ad-platform'); ?>
                            </label>
                            <p class="description"><?php _e('Mejora la precision del tracking usando datos de usuarios logueados', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Eventos a Trackear', 'ad-platform'); ?></th>
                        <td>
                            <p>
                                <label>
                                    <input type="checkbox" name="ad_platform_facebook[track_page_view]" value="1" <?php checked($facebook['track_page_view'] ?? true); ?>>
                                    PageView
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="ad_platform_facebook[track_view_content]" value="1" <?php checked($facebook['track_view_content'] ?? true); ?>>
                                    ViewContent (Landing Pages)
                                </label>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="ad_platform_facebook[track_add_to_cart]" value="1" <?php checked(!empty($facebook['track_add_to_cart'])); ?>>
                                    AddToCart (Clicks en CTA)
                                </label>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Remarketing', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_facebook[enable_remarketing]" value="1" <?php checked(!empty($facebook['enable_remarketing'])); ?>>
                                <?php _e('Habilitar carrusel de productos vistos', 'ad-platform'); ?>
                            </label>
                            <p class="description"><?php _e('Muestra productos que el usuario ha visitado anteriormente', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Productos en Carrusel', 'ad-platform'); ?></th>
                        <td>
                            <input type="number" name="ad_platform_facebook[carousel_items]" value="<?php echo esc_attr($facebook['carousel_items'] ?? 5); ?>" min="1" max="10" class="small-text">
                            <p class="description"><?php _e('Cantidad de productos a mostrar en el carrusel (1-10)', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Filtrar por Plataforma', 'ad-platform'); ?></th>
                        <td>
                            <select name="ad_platform_facebook[carousel_platform]">
                                <option value="" <?php selected($facebook['carousel_platform'] ?? '', ''); ?>><?php _e('Todas las plataformas', 'ad-platform'); ?></option>
                                <option value="aliexpress" <?php selected($facebook['carousel_platform'] ?? '', 'aliexpress'); ?>>AliExpress</option>
                                <option value="hotmart" <?php selected($facebook['carousel_platform'] ?? '', 'hotmart'); ?>>Hotmart</option>
                                <option value="fiverr" <?php selected($facebook['carousel_platform'] ?? '', 'fiverr'); ?>>Fiverr</option>
                                <option value="hostinger" <?php selected($facebook['carousel_platform'] ?? '', 'hostinger'); ?>>Hostinger</option>
                            </select>
                            <p class="description"><?php _e('Mostrar solo productos de una plataforma especifica', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Shortcode de Carrusel', 'ad-platform'); ?></h3>
                <p class="description">
                    <?php _e('Usa el siguiente shortcode para mostrar productos vistos:', 'ad-platform'); ?>
                    <code>[adp_remarketing_carousel limit="5" platform="aliexpress"]</code>
                </p>
            </div>

            <!-- Tab: Redirección -->
            <div id="redirect" class="adp-tab-content">
                <h2><?php _e('Configuración de Enlaces Cortos', 'ad-platform'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Habilitar Enlaces Cortos', 'ad-platform'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ad_platform_redirect[enable_short_links]" value="1" <?php checked(!empty($redirect['enable_short_links'])); ?>>
                                <?php _e('Usar sistema de redirección /go/{id}', 'ad-platform'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Slug Base', 'ad-platform'); ?></th>
                        <td>
                            <?php echo home_url('/'); ?><input type="text" name="ad_platform_redirect[base_slug]" value="<?php echo esc_attr($redirect['base_slug'] ?? 'go'); ?>" class="regular-text">/{id}
                            <p class="description"><?php _e('Cambia "go" por cualquier palabra (ej: "link", "out", "ref")', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Delay de Redirección', 'ad-platform'); ?></th>
                        <td>
                            <input type="number" name="ad_platform_redirect[redirect_delay]" value="<?php echo esc_attr($redirect['redirect_delay'] ?? 0); ?>" min="0" max="10" class="small-text"> <?php _e('segundos', 'ad-platform'); ?>
                            <p class="description"><?php _e('0 = inmediato', 'ad-platform'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Tipo de Redirección', 'ad-platform'); ?></th>
                        <td>
                            <select name="ad_platform_redirect[redirect_type]">
                                <option value="302" <?php selected($redirect['redirect_type'] ?? '', '302'); ?>>302 (Temporal)</option>
                                <option value="301" <?php selected($redirect['redirect_type'] ?? '', '301'); ?>>301 (Permanente)</option>
                                <option value="307" <?php selected($redirect['redirect_type'] ?? '', '307'); ?>>307 (Temporal, mantiene método)</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <button type="submit" name="adp_save_settings" class="button button-primary"><?php _e('Guardar Configuración', 'ad-platform'); ?></button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.adp-tab-content').removeClass('adp-tab-active');
        $(target).addClass('adp-tab-active');
    });
});
</script>
