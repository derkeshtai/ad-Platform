<?php
/**
 * Plugin Name: Ad Platform - Affiliate Ad Server
 * Plugin URI: https://github.com/derkeshtai/ad-Platform
 * Description: Sistema completo de gestión de anuncios de afiliados con integración AliExpress, tracking avanzado, matching contextual y SDK para sitios externos.
 * Version: 1.0.0
 * Author: DerKeshtai
 * Author URI: https://github.com/derkeshtai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ad-platform
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Constantes del plugin
define('AD_PLATFORM_VERSION', '1.0.0');
define('AD_PLATFORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AD_PLATFORM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AD_PLATFORM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
final class Ad_Platform {

    /**
     * Instancia única del plugin (Singleton)
     */
    private static $instance = null;

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }

    /**
     * Cargar dependencias del plugin
     */
    private function load_dependencies() {
        // Core
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-installer.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-api.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-tracking.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-matcher.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-aliexpress.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-redirector.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-facebook-pixel.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-remarketing.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-backup.php';

        // Post Types
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/post-types/class-ad.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/post-types/class-campaign.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/post-types/class-zone.php';
        require_once AD_PLATFORM_PLUGIN_DIR . 'includes/post-types/class-landing.php';

        // Admin
        if (is_admin()) {
            require_once AD_PLATFORM_PLUGIN_DIR . 'admin/class-admin.php';
        }

        // Public
        require_once AD_PLATFORM_PLUGIN_DIR . 'public/class-public.php';
    }

    /**
     * Cargar traducciones
     */
    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'ad-platform',
                false,
                dirname(AD_PLATFORM_PLUGIN_BASENAME) . '/languages/'
            );
        });
    }

    /**
     * Registrar hooks del admin
     */
    private function define_admin_hooks() {
        if (is_admin()) {
            $admin = new Ad_Platform_Admin();

            add_action('admin_menu', [$admin, 'add_admin_menu']);
            add_action('admin_enqueue_scripts', [$admin, 'enqueue_styles']);
            add_action('admin_enqueue_scripts', [$admin, 'enqueue_scripts']);
            add_action('admin_init', [$admin, 'register_settings']);
        }
    }

    /**
     * Registrar hooks públicos
     */
    private function define_public_hooks() {
        $public = new Ad_Platform_Public();

        add_action('wp_enqueue_scripts', [$public, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$public, 'enqueue_scripts']);
        add_action('init', [$public, 'register_shortcodes']);
        add_action('widgets_init', [$public, 'register_widgets']);
    }

    /**
     * Registrar hooks de la API
     */
    private function define_api_hooks() {
        $api = new Ad_Platform_API();
        add_action('rest_api_init', [$api, 'register_routes']);
    }
}

/**
 * Activación del plugin
 */
function activate_ad_platform() {
    require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-installer.php';
    Ad_Platform_Installer::activate();
}
register_activation_hook(__FILE__, 'activate_ad_platform');

/**
 * Desactivación del plugin
 */
function deactivate_ad_platform() {
    require_once AD_PLATFORM_PLUGIN_DIR . 'includes/class-installer.php';
    Ad_Platform_Installer::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_ad_platform');

/**
 * Iniciar el plugin
 */
function ad_platform() {
    return Ad_Platform::get_instance();
}

// Iniciar el plugin
ad_platform();
