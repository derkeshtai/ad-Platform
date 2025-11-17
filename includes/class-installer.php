<?php
/**
 * Instalador del plugin
 * Maneja la creación de tablas, activación y desactivación
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Installer {

    /**
     * Activación del plugin
     */
    public static function activate() {
        self::create_tables();
        self::create_default_options();
        self::create_default_zones();
        self::schedule_cron_jobs();

        // Flush rewrite rules para los custom post types
        flush_rewrite_rules();

        // Guardar versión del plugin
        update_option('ad_platform_version', AD_PLATFORM_VERSION);
        update_option('ad_platform_activated', time());
    }

    /**
     * Desactivación del plugin
     */
    public static function deactivate() {
        self::clear_cron_jobs();
        flush_rewrite_rules();
    }

    /**
     * Crear tablas de base de datos
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabla de impresiones
        $table_impressions = $wpdb->prefix . 'adp_impressions';
        $sql_impressions = "CREATE TABLE IF NOT EXISTS $table_impressions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED NOT NULL,
            site_url varchar(255) NOT NULL,
            page_url varchar(512) DEFAULT NULL,
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            device_type varchar(20) DEFAULT 'desktop',
            referrer varchar(512) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ad_id (ad_id),
            KEY created_at (created_at),
            KEY site_url (site_url),
            KEY country_code (country_code)
        ) $charset_collate;";
        dbDelta($sql_impressions);

        // Tabla de clicks
        $table_clicks = $wpdb->prefix . 'adp_clicks';
        $sql_clicks = "CREATE TABLE IF NOT EXISTS $table_clicks (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED NOT NULL,
            impression_id bigint(20) UNSIGNED DEFAULT NULL,
            site_url varchar(255) NOT NULL,
            page_url varchar(512) DEFAULT NULL,
            user_ip varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            device_type varchar(20) DEFAULT 'desktop',
            referrer varchar(512) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ad_id (ad_id),
            KEY impression_id (impression_id),
            KEY created_at (created_at),
            KEY site_url (site_url)
        ) $charset_collate;";
        dbDelta($sql_clicks);

        // Tabla de conversiones (para tracking futuro)
        $table_conversions = $wpdb->prefix . 'adp_conversions';
        $sql_conversions = "CREATE TABLE IF NOT EXISTS $table_conversions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED NOT NULL,
            click_id bigint(20) UNSIGNED DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT 0.00,
            commission_value decimal(10,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'pending',
            external_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ad_id (ad_id),
            KEY click_id (click_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_conversions);

        // Tabla de productos de AliExpress en caché
        $table_aliexpress = $wpdb->prefix . 'adp_aliexpress_products';
        $sql_aliexpress = "CREATE TABLE IF NOT EXISTS $table_aliexpress (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id varchar(100) NOT NULL,
            title varchar(500) NOT NULL,
            description text DEFAULT NULL,
            image_url varchar(512) DEFAULT NULL,
            price decimal(10,2) DEFAULT 0.00,
            original_price decimal(10,2) DEFAULT 0.00,
            discount_percent int(3) DEFAULT 0,
            commission_rate decimal(5,2) DEFAULT 0.00,
            commission_value decimal(10,2) DEFAULT 0.00,
            rating decimal(3,2) DEFAULT 0.00,
            orders_count int(11) DEFAULT 0,
            category varchar(100) DEFAULT NULL,
            affiliate_url varchar(1000) NOT NULL,
            keywords text DEFAULT NULL,
            free_shipping tinyint(1) DEFAULT 0,
            is_adult_content tinyint(1) DEFAULT 0,
            manual_weight int(3) DEFAULT 0,
            auto_score decimal(5,2) DEFAULT 0.00,
            status varchar(20) DEFAULT 'pending',
            impressions_count int(11) DEFAULT 0,
            clicks_count int(11) DEFAULT 0,
            last_updated datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id),
            KEY keywords (keywords(100)),
            KEY status (status),
            KEY manual_weight (manual_weight),
            KEY auto_score (auto_score)
        ) $charset_collate;";
        dbDelta($sql_aliexpress);

        // Tabla de estadísticas diarias agregadas (para rendimiento)
        $table_stats = $wpdb->prefix . 'adp_stats_daily';
        $sql_stats = "CREATE TABLE IF NOT EXISTS $table_stats (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) UNSIGNED NOT NULL,
            date date NOT NULL,
            impressions int(11) DEFAULT 0,
            clicks int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0.00,
            PRIMARY KEY (id),
            UNIQUE KEY ad_date (ad_id, date),
            KEY date (date)
        ) $charset_collate;";
        dbDelta($sql_stats);

        // Tabla de sitios registrados
        $table_sites = $wpdb->prefix . 'adp_sites';
        $sql_sites = "CREATE TABLE IF NOT EXISTS $table_sites (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            site_url varchar(255) NOT NULL,
            site_name varchar(255) DEFAULT NULL,
            api_key varchar(64) NOT NULL,
            status varchar(20) DEFAULT 'active',
            allowed_zones text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_request_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY site_url (site_url),
            UNIQUE KEY api_key (api_key),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_sites);
    }

    /**
     * Crear opciones por defecto
     */
    private static function create_default_options() {
        $default_options = array(
            // Configuración general
            'ad_platform_general' => array(
                'enable_tracking' => 1,
                'enable_cache' => 1,
                'cache_duration' => 3600,
                'default_ad_fallback' => 'aliexpress',
            ),

            // Configuración de AliExpress
            'ad_platform_aliexpress' => array(
                'enabled' => 0,
                'app_key' => '',
                'app_secret' => '',
                'tracking_id' => '',
                'mode' => 'semi-auto', // auto, semi-auto, manual
                'sort_by' => 'balanced', // commission, rating, orders, balanced
                'min_commission' => 5,
                'min_rating' => 4.0,
                'min_orders' => 100,
                'min_price' => 1,
                'max_price' => 500,
                'free_shipping_only' => 1,
                'cache_hours' => 24,
                'products_to_consider' => 20,
                'display_template' => 'card',
                'cta_text' => 'Ver en AliExpress',
                'adult_categories' => 'health,wellness,massage,supplements,bedroom', // Categorías adultas permitidas
            ),

            // Configuración de tracking
            'ad_platform_tracking' => array(
                'anonymize_ip' => 1,
                'track_user_agent' => 1,
                'track_referrer' => 1,
                'enable_geolocation' => 1,
            ),

            // Configuración de redirección
            'ad_platform_redirect' => array(
                'enable_short_links' => 1,
                'redirect_delay' => 0,
                'redirect_type' => '302', // 301, 302, 307
                'base_slug' => 'go',
            ),
        );

        foreach ($default_options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $option_value);
            }
        }
    }

    /**
     * Crear zonas de anuncio por defecto
     */
    private static function create_default_zones() {
        $default_zones = array(
            array(
                'title' => 'Header',
                'slug' => 'header',
                'description' => 'Zona de anuncio en el encabezado (728x90)',
                'default_size' => '728x90',
            ),
            array(
                'title' => 'Sidebar',
                'slug' => 'sidebar',
                'description' => 'Zona de anuncio en la barra lateral (300x250)',
                'default_size' => '300x250',
            ),
            array(
                'title' => 'In-Content',
                'slug' => 'in-content',
                'description' => 'Zona de anuncio dentro del contenido',
                'default_size' => 'responsive',
            ),
            array(
                'title' => 'Footer',
                'slug' => 'footer',
                'description' => 'Zona de anuncio en el pie de página',
                'default_size' => '728x90',
            ),
        );

        foreach ($default_zones as $zone) {
            // Solo crear si no existe
            $existing = get_page_by_path($zone['slug'], OBJECT, 'adp_zone');
            if (!$existing) {
                wp_insert_post(array(
                    'post_title' => $zone['title'],
                    'post_name' => $zone['slug'],
                    'post_content' => $zone['description'],
                    'post_status' => 'publish',
                    'post_type' => 'adp_zone',
                    'meta_input' => array(
                        '_adp_zone_size' => $zone['default_size'],
                    ),
                ));
            }
        }
    }

    /**
     * Programar tareas cron
     */
    private static function schedule_cron_jobs() {
        // Actualizar estadísticas diarias
        if (!wp_next_scheduled('adp_update_daily_stats')) {
            wp_schedule_event(time(), 'daily', 'adp_update_daily_stats');
        }

        // Actualizar productos de AliExpress
        if (!wp_next_scheduled('adp_update_aliexpress_products')) {
            wp_schedule_event(time(), 'twicedaily', 'adp_update_aliexpress_products');
        }

        // Limpiar caché antiguo
        if (!wp_next_scheduled('adp_clean_old_cache')) {
            wp_schedule_event(time(), 'weekly', 'adp_clean_old_cache');
        }
    }

    /**
     * Limpiar tareas cron
     */
    private static function clear_cron_jobs() {
        wp_clear_scheduled_hook('adp_update_daily_stats');
        wp_clear_scheduled_hook('adp_update_aliexpress_products');
        wp_clear_scheduled_hook('adp_clean_old_cache');
    }
}
