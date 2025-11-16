<?php
/**
 * REST API para servir anuncios
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_API {

    /**
     * Namespace de la API
     */
    private $namespace = 'adplatform/v1';

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // Obtener anuncio
        register_rest_route($this->namespace, '/ad/get', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_ad'],
            'permission_callback' => '__return_true',
            'args' => array(
                'zone_id' => array(
                    'required' => false,
                    'type' => 'integer',
                ),
                'keywords' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'category' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'country' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'device' => array(
                    'required' => false,
                    'type' => 'string',
                ),
            ),
        ));

        // Registrar impresión
        register_rest_route($this->namespace, '/track/impression', array(
            'methods' => 'POST',
            'callback' => [$this, 'track_impression'],
            'permission_callback' => '__return_true',
            'args' => array(
                'ad_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));

        // Registrar click
        register_rest_route($this->namespace, '/track/click', array(
            'methods' => 'POST',
            'callback' => [$this, 'track_click'],
            'permission_callback' => '__return_true',
            'args' => array(
                'ad_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'impression_id' => array(
                    'required' => false,
                    'type' => 'integer',
                ),
            ),
        ));

        // Estadísticas
        register_rest_route($this->namespace, '/stats/(?P<ad_id>\d+)', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ));
    }

    /**
     * Obtener anuncio
     */
    public function get_ad($request) {
        $zone_id = $request->get_param('zone_id');
        $keywords = $request->get_param('keywords');
        $category = $request->get_param('category');
        $country = $request->get_param('country');
        $device = $request->get_param('device') ?: 'desktop';

        // Buscar anuncio
        $ad = $this->find_best_ad(array(
            'zone_id' => $zone_id,
            'keywords' => $keywords,
            'category' => $category,
            'country' => $country,
            'device' => $device,
        ));

        if (!$ad) {
            // Intentar obtener anuncio de AliExpress
            require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-aliexpress.php';
            $aliexpress = new Ad_Platform_AliExpress();
            $ad = $aliexpress->get_product_ad($keywords);

            if (!$ad) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'No ad found',
                ), 404);
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'ad' => $ad,
        ), 200);
    }

    /**
     * Encontrar el mejor anuncio según criterios
     */
    private function find_best_ad($criteria) {
        $args = array(
            'post_type' => 'adp_ad',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_adp_weight',
            'order' => 'DESC',
        );

        // Filtrar por zona
        if (!empty($criteria['zone_id'])) {
            $args['meta_query'][] = array(
                'key' => '_adp_zones',
                'value' => serialize(strval($criteria['zone_id'])),
                'compare' => 'LIKE',
            );
        }

        // Filtrar por país
        if (!empty($criteria['country'])) {
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => '_adp_countries',
                    'value' => $criteria['country'],
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_adp_countries',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_adp_countries',
                    'value' => '',
                    'compare' => '=',
                ),
            );
        }

        // Filtrar por dispositivo
        if (!empty($criteria['device'])) {
            $args['meta_query'][] = array(
                'key' => '_adp_devices',
                'value' => $criteria['device'],
                'compare' => 'LIKE',
            );
        }

        $ads = get_posts($args);

        if (empty($ads)) {
            return null;
        }

        // Filtrar por límites diarios
        $ads = array_filter($ads, function($ad) {
            return !Ad_Platform_Tracking::has_reached_daily_limit($ad->ID);
        });

        if (empty($ads)) {
            return null;
        }

        // Filtrar por fechas de programación
        $ads = array_filter($ads, function($ad) {
            return $this->is_ad_scheduled($ad->ID);
        });

        if (empty($ads)) {
            return null;
        }

        // Si hay keywords, usar matching contextual
        if (!empty($criteria['keywords'])) {
            require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-matcher.php';
            $matcher = new Ad_Platform_Matcher();
            $ads = $matcher->rank_ads_by_relevance($ads, $criteria['keywords']);
        }

        // Selección ponderada por peso
        $selected_ad = $this->weighted_random_selection($ads);

        if (!$selected_ad) {
            return null;
        }

        // Construir respuesta
        return $this->build_ad_response($selected_ad);
    }

    /**
     * Verificar si el anuncio está programado
     */
    private function is_ad_scheduled($ad_id) {
        $start_date = get_post_meta($ad_id, '_adp_start_date', true);
        $end_date = get_post_meta($ad_id, '_adp_end_date', true);
        $now = current_time('timestamp');

        if ($start_date && strtotime($start_date) > $now) {
            return false;
        }

        if ($end_date && strtotime($end_date) < $now) {
            return false;
        }

        return true;
    }

    /**
     * Selección aleatoria ponderada por peso
     */
    private function weighted_random_selection($ads) {
        if (empty($ads)) {
            return null;
        }

        $weights = array();
        foreach ($ads as $ad) {
            $weight = get_post_meta($ad->ID, '_adp_weight', true) ?: 5;
            $weights[$ad->ID] = $weight;
        }

        $total_weight = array_sum($weights);
        $random = mt_rand(1, $total_weight);

        $current_weight = 0;
        foreach ($weights as $ad_id => $weight) {
            $current_weight += $weight;
            if ($random <= $current_weight) {
                foreach ($ads as $ad) {
                    if ($ad->ID == $ad_id) {
                        return $ad;
                    }
                }
            }
        }

        return $ads[0];
    }

    /**
     * Construir respuesta del anuncio
     */
    private function build_ad_response($ad) {
        $ad_type = get_post_meta($ad->ID, '_adp_ad_type', true);
        $affiliate_url = get_post_meta($ad->ID, '_adp_affiliate_url', true);
        $use_short_link = get_post_meta($ad->ID, '_adp_use_short_link', true);
        $open_new_tab = get_post_meta($ad->ID, '_adp_open_new_tab', true);
        $cta_text = get_post_meta($ad->ID, '_adp_cta_text', true) ?: 'Ver Más';

        $response = array(
            'id' => $ad->ID,
            'title' => get_the_title($ad->ID),
            'type' => $ad_type,
            'url' => $affiliate_url,
            'target' => $open_new_tab ? '_blank' : '_self',
            'cta' => $cta_text,
        );

        // Generar link corto si está habilitado
        if ($use_short_link) {
            $redirect_slug = get_option('ad_platform_redirect', array())['base_slug'] ?? 'go';
            $response['url'] = home_url("/{$redirect_slug}/{$ad->ID}");
        }

        // Datos específicos por tipo
        switch ($ad_type) {
            case 'banner_image':
                $image_url = get_post_meta($ad->ID, '_adp_image_url', true);
                if (empty($image_url)) {
                    $image_url = get_the_post_thumbnail_url($ad->ID, 'full');
                }
                $response['image'] = $image_url;
                break;

            case 'text':
                $response['text_title'] = get_post_meta($ad->ID, '_adp_text_title', true);
                $response['text_description'] = get_post_meta($ad->ID, '_adp_text_description', true);
                break;

            case 'html':
                $response['html'] = get_post_meta($ad->ID, '_adp_html_content', true);
                break;

            case 'native':
                $image_url = get_post_meta($ad->ID, '_adp_image_url', true);
                if (empty($image_url)) {
                    $image_url = get_the_post_thumbnail_url($ad->ID, 'full');
                }
                $response['image'] = $image_url;
                $response['text_title'] = get_post_meta($ad->ID, '_adp_text_title', true);
                $response['text_description'] = get_post_meta($ad->ID, '_adp_text_description', true);
                $response['content'] = get_the_content(null, false, $ad);
                break;
        }

        return $response;
    }

    /**
     * Registrar impresión
     */
    public function track_impression($request) {
        $ad_id = $request->get_param('ad_id');

        if (!$ad_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Missing ad_id',
            ), 400);
        }

        $impression_id = Ad_Platform_Tracking::track_impression($ad_id);

        return new WP_REST_Response(array(
            'success' => true,
            'impression_id' => $impression_id,
        ), 200);
    }

    /**
     * Registrar click
     */
    public function track_click($request) {
        $ad_id = $request->get_param('ad_id');
        $impression_id = $request->get_param('impression_id');

        if (!$ad_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Missing ad_id',
            ), 400);
        }

        $click_id = Ad_Platform_Tracking::track_click($ad_id, $impression_id);

        return new WP_REST_Response(array(
            'success' => true,
            'click_id' => $click_id,
        ), 200);
    }

    /**
     * Obtener estadísticas
     */
    public function get_stats($request) {
        $ad_id = $request->get_param('ad_id');

        if (!$ad_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Missing ad_id',
            ), 400);
        }

        $stats = Ad_Platform_Tracking::get_ad_stats($ad_id);

        return new WP_REST_Response(array(
            'success' => true,
            'stats' => $stats,
        ), 200);
    }

    /**
     * Verificar permisos de administrador
     */
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
}
