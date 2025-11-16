<?php
/**
 * Panel de Administración
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Admin {

    /**
     * Añadir menús de administración
     */
    public function add_admin_menu() {
        // El menú principal ya existe por el CPT de anuncios

        // Submenu: Configuración
        add_submenu_page(
            'edit.php?post_type=adp_ad',
            __('Configuración', 'ad-platform'),
            __('Configuración', 'ad-platform'),
            'manage_options',
            'adp-settings',
            [$this, 'render_settings_page']
        );

        // Submenu: Productos AliExpress
        add_submenu_page(
            'edit.php?post_type=adp_ad',
            __('Productos AliExpress', 'ad-platform'),
            __('AliExpress', 'ad-platform'),
            'manage_options',
            'adp-aliexpress',
            [$this, 'render_aliexpress_page']
        );

        // Submenu: Estadísticas
        add_submenu_page(
            'edit.php?post_type=adp_ad',
            __('Estadísticas', 'ad-platform'),
            __('Estadísticas', 'ad-platform'),
            'manage_options',
            'adp-stats',
            [$this, 'render_stats_page']
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        // Configuración General
        register_setting('ad_platform_general', 'ad_platform_general');
        register_setting('ad_platform_aliexpress', 'ad_platform_aliexpress');
        register_setting('ad_platform_tracking', 'ad_platform_tracking');
        register_setting('ad_platform_redirect', 'ad_platform_redirect');
    }

    /**
     * Cargar estilos del admin
     */
    public function enqueue_styles() {
        $screen = get_current_screen();

        if (strpos($screen->id, 'adp') !== false || $screen->post_type === 'adp_ad') {
            wp_enqueue_style(
                'ad-platform-admin',
                AD_PLATFORM_PLUGIN_URL . 'admin/css/admin.css',
                array(),
                AD_PLATFORM_VERSION
            );
        }
    }

    /**
     * Cargar scripts del admin
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();

        if (strpos($screen->id, 'adp') !== false || $screen->post_type === 'adp_ad') {
            wp_enqueue_script(
                'ad-platform-admin',
                AD_PLATFORM_PLUGIN_URL . 'admin/js/admin.js',
                array('jquery'),
                AD_PLATFORM_VERSION,
                true
            );

            wp_localize_script('ad-platform-admin', 'adpAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('adp_admin_nonce'),
                'api_url' => rest_url('adplatform/v1/'),
            ));
        }
    }

    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Guardar configuración
        if (isset($_POST['adp_save_settings'])) {
            check_admin_referer('adp_settings_nonce');

            if (isset($_POST['ad_platform_general'])) {
                update_option('ad_platform_general', $_POST['ad_platform_general']);
            }
            if (isset($_POST['ad_platform_aliexpress'])) {
                update_option('ad_platform_aliexpress', $_POST['ad_platform_aliexpress']);
            }
            if (isset($_POST['ad_platform_tracking'])) {
                update_option('ad_platform_tracking', $_POST['ad_platform_tracking']);
            }
            if (isset($_POST['ad_platform_redirect'])) {
                update_option('ad_platform_redirect', $_POST['ad_platform_redirect']);
            }

            echo '<div class="notice notice-success"><p>' . __('Configuración guardada', 'ad-platform') . '</p></div>';
        }

        include AD_PLATFORM_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Renderizar página de AliExpress
     */
    public function render_aliexpress_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Manejar acciones
        if (isset($_POST['adp_approve_product'])) {
            check_admin_referer('adp_aliexpress_nonce');
            $product_id = intval($_POST['product_id']);
            $weight = intval($_POST['manual_weight'] ?? 5);

            require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-aliexpress.php';
            $aliexpress = new Ad_Platform_AliExpress();
            $aliexpress->approve_product($product_id, $weight);

            echo '<div class="notice notice-success"><p>' . __('Producto aprobado', 'ad-platform') . '</p></div>';
        }

        if (isset($_POST['adp_reject_product'])) {
            check_admin_referer('adp_aliexpress_nonce');
            $product_id = intval($_POST['product_id']);

            require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-aliexpress.php';
            $aliexpress = new Ad_Platform_AliExpress();
            $aliexpress->reject_product($product_id);

            echo '<div class="notice notice-success"><p>' . __('Producto rechazado', 'ad-platform') . '</p></div>';
        }

        include AD_PLATFORM_PLUGIN_DIR . 'admin/views/aliexpress-products.php';
    }

    /**
     * Renderizar página de estadísticas
     */
    public function render_stats_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        include AD_PLATFORM_PLUGIN_DIR . 'admin/views/stats.php';
    }
}
