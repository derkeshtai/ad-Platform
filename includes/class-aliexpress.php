<?php
/**
 * Integración con AliExpress Affiliate API
 * Sistema de productos con pesos manuales y automáticos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_AliExpress {

    private $app_key;
    private $app_secret;
    private $tracking_id;
    private $options;

    public function __construct() {
        $this->options = get_option('ad_platform_aliexpress', array());
        $this->app_key = $this->options['app_key'] ?? '';
        $this->app_secret = $this->options['app_secret'] ?? '';
        $this->tracking_id = $this->options['tracking_id'] ?? '';
    }

    /**
     * Obtener anuncio de producto de AliExpress
     * PRIORIDAD: Manual primero, luego automático
     */
    public function get_product_ad($keywords = '') {
        if (!$this->is_enabled()) {
            return null;
        }

        // PASO 1: Intentar obtener producto con peso manual > 0
        $manual_product = $this->get_manual_product($keywords);
        if ($manual_product) {
            $this->increment_product_stats($manual_product['id'], 'impression');
            return $this->build_product_ad($manual_product);
        }

        // PASO 2: Si no hay productos manuales, buscar automáticamente
        $auto_product = $this->get_auto_product($keywords);
        if ($auto_product) {
            $this->increment_product_stats($auto_product['id'], 'impression');
            return $this->build_product_ad($auto_product);
        }

        return null;
    }

    /**
     * Obtener producto con peso manual
     */
    private function get_manual_product($keywords) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        // Construir query base
        $where = "manual_weight > 0 AND status = 'approved'";

        // Si hay keywords, filtrar por relevancia
        if (!empty($keywords)) {
            $keywords_array = is_array($keywords) ? $keywords : explode(',', $keywords);
            $keywords_like = array();

            foreach ($keywords_array as $keyword) {
                $keyword = trim($keyword);
                if (!empty($keyword)) {
                    $keywords_like[] = $wpdb->prepare("keywords LIKE %s", '%' . $wpdb->esc_like($keyword) . '%');
                }
            }

            if (!empty($keywords_like)) {
                $where .= " AND (" . implode(' OR ', $keywords_like) . ")";
            }
        }

        // Ordenar por peso manual (mayor primero)
        $sql = "SELECT * FROM $table WHERE $where ORDER BY manual_weight DESC, RAND() LIMIT 1";

        $product = $wpdb->get_row($sql, ARRAY_A);

        return $product;
    }

    /**
     * Obtener producto automático
     */
    private function get_auto_product($keywords) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        // Verificar si hay productos en caché
        $cache_hours = $this->options['cache_hours'] ?? 24;
        $cache_time = date('Y-m-d H:i:s', strtotime("-{$cache_hours} hours"));

        $where = "status = 'approved' AND (last_updated >= '{$cache_time}' OR last_updated IS NULL)";

        // Filtrar por keywords si existen
        if (!empty($keywords)) {
            $keywords_array = is_array($keywords) ? $keywords : explode(',', $keywords);
            $keywords_like = array();

            foreach ($keywords_array as $keyword) {
                $keyword = trim($keyword);
                if (!empty($keyword)) {
                    $keywords_like[] = $wpdb->prepare("keywords LIKE %s", '%' . $wpdb->esc_like($keyword) . '%');
                }
            }

            if (!empty($keywords_like)) {
                $where .= " AND (" . implode(' OR ', $keywords_like) . ")";
            }

            // Si hay keywords, ordenar por score automático
            $sql = "SELECT * FROM $table WHERE $where ORDER BY auto_score DESC, RAND() LIMIT 1";
        } else {
            // Sin keywords, usar balanceado
            $sql = "SELECT * FROM $table WHERE $where ORDER BY auto_score DESC, RAND() LIMIT 1";
        }

        $product = $wpdb->get_row($sql, ARRAY_A);

        // Si no hay en caché, buscar en API
        if (!$product && !empty($keywords)) {
            $product = $this->search_api_and_cache($keywords);
        }

        return $product;
    }

    /**
     * Buscar en API de AliExpress y cachear
     */
    private function search_api_and_cache($keywords) {
        // Aquí iría la integración real con AliExpress API
        // Por ahora, simulamos la estructura

        /*
        NOTA: La API real de AliExpress requiere:
        1. Registro en AliExpress Affiliate Program
        2. Obtener App Key y App Secret
        3. Usar su SDK oficial o hacer requests HTTP

        Ejemplo de endpoint:
        https://api-sg.aliexpress.com/sync

        Métodos disponibles:
        - aliexpress.affiliate.product.query (buscar productos)
        - aliexpress.affiliate.link.generate (generar links de afiliado)
        - aliexpress.affiliate.hotproduct.query (productos populares)
        */

        // Simulación de respuesta de API
        $api_products = $this->call_aliexpress_api('search', array(
            'keywords' => $keywords,
            'page_size' => $this->options['products_to_consider'] ?? 20,
            'sort' => $this->options['sort_by'] ?? 'balanced',
        ));

        if (empty($api_products)) {
            return null;
        }

        // Filtrar productos según configuración
        $filtered_products = $this->filter_products($api_products);

        if (empty($filtered_products)) {
            return null;
        }

        // Calcular score automático para cada producto
        $scored_products = array_map(function($product) {
            $product['auto_score'] = $this->calculate_auto_score($product);
            return $product;
        }, $filtered_products);

        // Ordenar por score
        usort($scored_products, function($a, $b) {
            return $b['auto_score'] - $a['auto_score'];
        });

        // Cachear los mejores productos
        $mode = $this->options['mode'] ?? 'semi-auto';

        if ($mode === 'auto') {
            // Modo automático: cachear y aprobar directamente
            foreach (array_slice($scored_products, 0, 5) as $product) {
                $this->cache_product($product, 'approved');
            }
            return $this->array_to_product($scored_products[0]);
        } else {
            // Modo semi-automático: cachear como pending
            foreach (array_slice($scored_products, 0, 10) as $product) {
                $this->cache_product($product, 'pending');
            }
            // No retornar nada, esperar aprobación manual
            return null;
        }
    }

    /**
     * Llamar a API de AliExpress
     */
    private function call_aliexpress_api($method, $params) {
        // Implementación real de la API
        // Por ahora retornamos array vacío

        // TODO: Implementar con la API real de AliExpress
        // Usar wp_remote_post() para hacer requests

        return array();
    }

    /**
     * Filtrar productos según configuración
     */
    private function filter_products($products) {
        $min_commission = $this->options['min_commission'] ?? 5;
        $min_rating = $this->options['min_rating'] ?? 4.0;
        $min_orders = $this->options['min_orders'] ?? 100;
        $min_price = $this->options['min_price'] ?? 1;
        $max_price = $this->options['max_price'] ?? 500;
        $free_shipping_only = $this->options['free_shipping_only'] ?? 1;

        return array_filter($products, function($product) use ($min_commission, $min_rating, $min_orders, $min_price, $max_price, $free_shipping_only) {
            // Filtrar por comisión
            if ($product['commission_rate'] < $min_commission) {
                return false;
            }

            // Filtrar por rating
            if ($product['rating'] < $min_rating) {
                return false;
            }

            // Filtrar por órdenes
            if ($product['orders_count'] < $min_orders) {
                return false;
            }

            // Filtrar por precio
            if ($product['price'] < $min_price || ($max_price > 0 && $product['price'] > $max_price)) {
                return false;
            }

            // Filtrar por envío gratis
            if ($free_shipping_only && !$product['free_shipping']) {
                return false;
            }

            return true;
        });
    }

    /**
     * Calcular score automático (algoritmo balanceado)
     */
    private function calculate_auto_score($product) {
        $score = 0;

        // 40% Comisión
        $score += ($product['commission_rate'] / 20) * 40;

        // 30% Rating
        $score += ($product['rating'] / 5) * 30;

        // 20% Popularidad (ventas)
        $orders_normalized = min($product['orders_count'] / 10000, 1);
        $score += $orders_normalized * 20;

        // 10% base
        $score += 10;

        // Bonus por envío gratis
        if ($product['free_shipping']) {
            $score += 5;
        }

        // Penalización por precio muy bajo (posible baja calidad)
        if ($product['price'] < 5) {
            $score -= 10;
        }

        return round($score, 2);
    }

    /**
     * Cachear producto en base de datos
     */
    private function cache_product($product, $status = 'pending') {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        $data = array(
            'product_id' => $product['product_id'],
            'title' => sanitize_text_field($product['title']),
            'description' => sanitize_textarea_field($product['description'] ?? ''),
            'image_url' => esc_url_raw($product['image_url']),
            'price' => floatval($product['price']),
            'original_price' => floatval($product['original_price'] ?? $product['price']),
            'discount_percent' => intval($product['discount_percent'] ?? 0),
            'commission_rate' => floatval($product['commission_rate']),
            'commission_value' => floatval($product['commission_value'] ?? 0),
            'rating' => floatval($product['rating']),
            'orders_count' => intval($product['orders_count']),
            'category' => sanitize_text_field($product['category'] ?? ''),
            'affiliate_url' => esc_url_raw($product['affiliate_url']),
            'keywords' => sanitize_text_field($product['keywords'] ?? ''),
            'free_shipping' => intval($product['free_shipping'] ?? 0),
            'manual_weight' => 0,
            'auto_score' => $this->calculate_auto_score($product),
            'status' => $status,
            'last_updated' => current_time('mysql'),
        );

        // Verificar si ya existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE product_id = %s",
            $product['product_id']
        ));

        if ($exists) {
            // Actualizar
            $wpdb->update($table, $data, array('product_id' => $product['product_id']));
        } else {
            // Insertar
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
        }
    }

    /**
     * Construir respuesta de anuncio desde producto
     */
    private function build_product_ad($product) {
        $display_template = $this->options['display_template'] ?? 'card';
        $cta_text = $this->options['cta_text'] ?? 'Ver en AliExpress';

        $ad = array(
            'id' => 'ali_' . $product['product_id'],
            'type' => 'aliexpress',
            'template' => $display_template,
            'title' => $product['title'],
            'description' => $product['description'],
            'image' => $product['image_url'],
            'price' => $product['price'],
            'original_price' => $product['original_price'],
            'discount' => $product['discount_percent'],
            'rating' => $product['rating'],
            'orders' => $product['orders_count'],
            'free_shipping' => (bool) $product['free_shipping'],
            'url' => $product['affiliate_url'],
            'cta' => $cta_text,
            'target' => '_blank',
        );

        return $ad;
    }

    /**
     * Convertir array de API a formato de producto
     */
    private function array_to_product($product) {
        return $product;
    }

    /**
     * Incrementar estadísticas del producto
     */
    private function increment_product_stats($product_id, $type = 'impression') {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        if ($type === 'impression') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET impressions_count = impressions_count + 1 WHERE id = %d",
                $product_id
            ));
        } elseif ($type === 'click') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET clicks_count = clicks_count + 1 WHERE id = %d",
                $product_id
            ));
        }
    }

    /**
     * Verificar si está habilitado
     */
    private function is_enabled() {
        return !empty($this->options['enabled']) && !empty($this->app_key) && !empty($this->tracking_id);
    }

    /**
     * Aprobar producto manualmente
     */
    public function approve_product($product_id, $manual_weight = 5) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        $wpdb->update(
            $table,
            array(
                'status' => 'approved',
                'manual_weight' => intval($manual_weight),
            ),
            array('id' => intval($product_id))
        );
    }

    /**
     * Rechazar producto
     */
    public function reject_product($product_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        $wpdb->update(
            $table,
            array('status' => 'rejected'),
            array('id' => intval($product_id))
        );
    }

    /**
     * Obtener productos pendientes de aprobación
     */
    public function get_pending_products($limit = 50) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        return $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'pending' ORDER BY auto_score DESC LIMIT $limit",
            ARRAY_A
        );
    }

    /**
     * Obtener productos aprobados
     */
    public function get_approved_products($limit = 100) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        return $wpdb->get_results(
            "SELECT * FROM $table WHERE status = 'approved' ORDER BY manual_weight DESC, auto_score DESC LIMIT $limit",
            ARRAY_A
        );
    }

    /**
     * Actualizar peso manual de producto
     */
    public function update_product_weight($product_id, $weight) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        $wpdb->update(
            $table,
            array('manual_weight' => intval($weight)),
            array('id' => intval($product_id))
        );
    }

    /**
     * Buscar productos manualmente en la API
     */
    public function search_products($keywords, $page = 1, $page_size = 20) {
        $products = $this->call_aliexpress_api('search', array(
            'keywords' => $keywords,
            'page' => $page,
            'page_size' => $page_size,
        ));

        return $this->filter_products($products);
    }

    /**
     * Actualizar productos (cron job)
     */
    public function update_products() {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';
        $cache_hours = $this->options['cache_hours'] ?? 24;

        // Obtener productos que necesitan actualización
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE status = 'approved' AND last_updated < %s LIMIT 50",
                date('Y-m-d H:i:s', strtotime("-{$cache_hours} hours"))
            ),
            ARRAY_A
        );

        foreach ($products as $product) {
            // Actualizar información del producto desde la API
            // Por ahora solo actualizamos el timestamp
            $wpdb->update(
                $table,
                array('last_updated' => current_time('mysql')),
                array('id' => $product['id'])
            );
        }
    }
}
