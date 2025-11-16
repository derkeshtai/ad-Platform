<?php
/**
 * Sistema de Tracking
 * Maneja impresiones, clicks y conversiones
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Tracking {

    /**
     * Registrar impresión
     */
    public static function track_impression($ad_id, $data = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_impressions';

        $defaults = array(
            'ad_id' => absint($ad_id),
            'site_url' => self::get_site_url(),
            'page_url' => self::get_page_url(),
            'user_ip' => self::get_user_ip(),
            'user_agent' => self::get_user_agent(),
            'country_code' => self::get_country_code(),
            'device_type' => self::get_device_type(),
            'referrer' => self::get_referrer(),
            'created_at' => current_time('mysql'),
        );

        $data = wp_parse_args($data, $defaults);

        // Sanitizar datos
        $data = array_map(function($value) {
            if (is_string($value)) {
                return sanitize_text_field($value);
            }
            return $value;
        }, $data);

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Registrar click
     */
    public static function track_click($ad_id, $impression_id = null, $data = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_clicks';

        $defaults = array(
            'ad_id' => absint($ad_id),
            'impression_id' => $impression_id ? absint($impression_id) : null,
            'site_url' => self::get_site_url(),
            'page_url' => self::get_page_url(),
            'user_ip' => self::get_user_ip(),
            'user_agent' => self::get_user_agent(),
            'country_code' => self::get_country_code(),
            'device_type' => self::get_device_type(),
            'referrer' => self::get_referrer(),
            'created_at' => current_time('mysql'),
        );

        $data = wp_parse_args($data, $defaults);

        // Sanitizar datos
        $data = array_map(function($value) {
            if (is_string($value)) {
                return sanitize_text_field($value);
            }
            return $value;
        }, $data);

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Registrar conversión
     */
    public static function track_conversion($ad_id, $click_id = null, $data = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_conversions';

        $defaults = array(
            'ad_id' => absint($ad_id),
            'click_id' => $click_id ? absint($click_id) : null,
            'conversion_value' => 0,
            'commission_value' => 0,
            'status' => 'pending',
            'external_id' => null,
            'created_at' => current_time('mysql'),
        );

        $data = wp_parse_args($data, $defaults);

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Obtener estadísticas de un anuncio
     */
    public static function get_ad_stats($ad_id, $date_from = null, $date_to = null) {
        global $wpdb;

        $where = $wpdb->prepare("ad_id = %d", $ad_id);

        if ($date_from) {
            $where .= $wpdb->prepare(" AND created_at >= %s", $date_from);
        }

        if ($date_to) {
            $where .= $wpdb->prepare(" AND created_at <= %s", $date_to);
        }

        $impressions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adp_impressions WHERE $where");
        $clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adp_clicks WHERE $where");
        $conversions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}adp_conversions WHERE $where");

        $ctr = $impressions > 0 ? ($clicks / $impressions * 100) : 0;
        $conversion_rate = $clicks > 0 ? ($conversions / $clicks * 100) : 0;

        return array(
            'impressions' => (int) $impressions,
            'clicks' => (int) $clicks,
            'conversions' => (int) $conversions,
            'ctr' => round($ctr, 2),
            'conversion_rate' => round($conversion_rate, 2),
        );
    }

    /**
     * Verificar si se ha alcanzado el límite diario
     */
    public static function has_reached_daily_limit($ad_id) {
        $max_impressions = get_post_meta($ad_id, '_adp_max_impressions', true);
        $max_clicks = get_post_meta($ad_id, '_adp_max_clicks', true);

        if (!$max_impressions && !$max_clicks) {
            return false;
        }

        $today = date('Y-m-d');
        $stats = self::get_ad_stats($ad_id, $today . ' 00:00:00', $today . ' 23:59:59');

        if ($max_impressions > 0 && $stats['impressions'] >= $max_impressions) {
            return true;
        }

        if ($max_clicks > 0 && $stats['clicks'] >= $max_clicks) {
            return true;
        }

        return false;
    }

    /**
     * Obtener URL del sitio
     */
    private static function get_site_url() {
        if (isset($_SERVER['HTTP_HOST'])) {
            return sanitize_text_field($_SERVER['HTTP_HOST']);
        }
        return parse_url(home_url(), PHP_URL_HOST);
    }

    /**
     * Obtener URL de la página
     */
    private static function get_page_url() {
        if (isset($_SERVER['REQUEST_URI'])) {
            return esc_url_raw($_SERVER['REQUEST_URI']);
        }
        return '';
    }

    /**
     * Obtener IP del usuario (anonimizada si está configurado)
     */
    private static function get_user_ip() {
        $options = get_option('ad_platform_tracking', array());
        $anonymize = isset($options['anonymize_ip']) ? $options['anonymize_ip'] : true;

        $ip = '';

        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $ip = sanitize_text_field($ip);

        // Anonimizar IP si está habilitado
        if ($anonymize && filter_var($ip, FILTER_VALIDATE_IP)) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                // IPv4: Reemplazar el último octeto con 0
                $ip = preg_replace('/\.\d+$/', '.0', $ip);
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                // IPv6: Reemplazar los últimos 80 bits con ceros
                $ip = preg_replace('/([:0-9a-f]+):[:0-9a-f]+:[:0-9a-f]+:[:0-9a-f]+$/i', '$1:0:0:0', $ip);
            }
        }

        return $ip;
    }

    /**
     * Obtener User Agent
     */
    private static function get_user_agent() {
        $options = get_option('ad_platform_tracking', array());
        $track = isset($options['track_user_agent']) ? $options['track_user_agent'] : true;

        if (!$track) {
            return null;
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        }

        return null;
    }

    /**
     * Obtener código de país
     */
    private static function get_country_code() {
        $options = get_option('ad_platform_tracking', array());
        $enable_geo = isset($options['enable_geolocation']) ? $options['enable_geolocation'] : true;

        if (!$enable_geo) {
            return null;
        }

        // Cloudflare
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']);
        }

        // Intentar detectar por IP (requeriría servicio externo)
        // Por ahora retornamos null
        return null;
    }

    /**
     * Obtener tipo de dispositivo
     */
    private static function get_device_type() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return 'desktop';
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // Detectar móvil
        if (preg_match('/(android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini)/i', $user_agent)) {
            // Diferenciar entre tablet y móvil
            if (preg_match('/(ipad|tablet|playbook|silk)/i', $user_agent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Obtener referrer
     */
    private static function get_referrer() {
        $options = get_option('ad_platform_tracking', array());
        $track = isset($options['track_referrer']) ? $options['track_referrer'] : true;

        if (!$track) {
            return null;
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            return esc_url_raw($_SERVER['HTTP_REFERER']);
        }

        return null;
    }

    /**
     * Actualizar estadísticas diarias agregadas (para rendimiento)
     */
    public static function update_daily_stats() {
        global $wpdb;

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Obtener todos los anuncios
        $ads = get_posts(array(
            'post_type' => 'adp_ad',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));

        foreach ($ads as $ad) {
            $stats = self::get_ad_stats(
                $ad->ID,
                $yesterday . ' 00:00:00',
                $yesterday . ' 23:59:59'
            );

            $wpdb->replace(
                $wpdb->prefix . 'adp_stats_daily',
                array(
                    'ad_id' => $ad->ID,
                    'date' => $yesterday,
                    'impressions' => $stats['impressions'],
                    'clicks' => $stats['clicks'],
                    'conversions' => $stats['conversions'],
                ),
                array('%d', '%s', '%d', '%d', '%d')
            );
        }
    }

    /**
     * Limpiar datos antiguos (GDPR compliance)
     */
    public static function clean_old_data($days = 365) {
        global $wpdb;

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}adp_impressions WHERE created_at < %s",
            $date
        ));

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}adp_clicks WHERE created_at < %s",
            $date
        ));
    }
}
