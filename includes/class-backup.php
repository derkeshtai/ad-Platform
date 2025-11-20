<?php
/**
 * Clase de Backup y Restauración
 *
 * Sistema de exportación e importación de configuraciones y contenido
 *
 * @package Ad_Platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ad_Platform_Backup {

    /**
     * Constructor
     */
    public function __construct() {
        // Registrar endpoints de admin
        add_action('admin_init', array($this, 'handle_export'));
        add_action('admin_init', array($this, 'handle_import'));

        // AJAX handlers
        add_action('wp_ajax_adp_export_backup', array($this, 'ajax_export'));
        add_action('wp_ajax_adp_import_backup', array($this, 'ajax_import'));
    }

    /**
     * Obtener datos para exportar
     */
    public function get_export_data($options = array()) {
        $defaults = array(
            'include_settings' => true,
            'include_ads' => true,
            'include_campaigns' => true,
            'include_zones' => true,
            'include_landings' => true,
            'include_aliexpress_products' => true,
        );

        $options = wp_parse_args($options, $defaults);

        $data = array(
            'plugin_version' => AD_PLATFORM_VERSION,
            'export_date' => current_time('mysql'),
            'site_url' => get_site_url(),
        );

        // Configuraciones
        if ($options['include_settings']) {
            $data['settings'] = array(
                'general' => get_option('ad_platform_general', array()),
                'aliexpress' => get_option('ad_platform_aliexpress', array()),
                'tracking' => get_option('ad_platform_tracking', array()),
                'facebook' => get_option('ad_platform_facebook', array()),
                'redirect' => get_option('ad_platform_redirect', array()),
            );
        }

        // Anuncios
        if ($options['include_ads']) {
            $data['ads'] = $this->export_post_type('adp_ad');
        }

        // Campañas
        if ($options['include_campaigns']) {
            $data['campaigns'] = $this->export_post_type('adp_campaign');
        }

        // Zonas
        if ($options['include_zones']) {
            $data['zones'] = $this->export_post_type('adp_zone');
        }

        // Landing Pages
        if ($options['include_landings']) {
            $data['landings'] = $this->export_post_type('adp_landing');
        }

        // Productos de AliExpress
        if ($options['include_aliexpress_products']) {
            $data['aliexpress_products'] = $this->export_aliexpress_products();
        }

        // Taxonomías
        $data['taxonomies'] = array(
            'adp_keyword' => $this->export_taxonomy('adp_keyword'),
            'adp_ad_category' => $this->export_taxonomy('adp_ad_category'),
            'adp_platform' => $this->export_taxonomy('adp_platform'),
            'adp_product_cat' => $this->export_taxonomy('adp_product_cat'),
        );

        return $data;
    }

    /**
     * Exportar post type
     */
    private function export_post_type($post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));

        $exported = array();

        foreach ($posts as $post) {
            $post_data = array(
                'ID' => $post->ID,
                'post_title' => $post->post_title,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_status' => $post->post_status,
                'post_name' => $post->post_name,
                'post_date' => $post->post_date,
                'meta' => array(),
                'terms' => array(),
            );

            // Meta datos
            $meta = get_post_meta($post->ID);
            foreach ($meta as $key => $values) {
                if (strpos($key, '_adp_') === 0 || strpos($key, 'adp_') === 0) {
                    $post_data['meta'][$key] = maybe_unserialize($values[0]);
                }
            }

            // Taxonomías
            $taxonomies = get_object_taxonomies($post_type);
            foreach ($taxonomies as $tax) {
                $terms = wp_get_object_terms($post->ID, $tax, array('fields' => 'slugs'));
                if (!is_wp_error($terms) && !empty($terms)) {
                    $post_data['terms'][$tax] = $terms;
                }
            }

            // Imagen destacada
            if (has_post_thumbnail($post->ID)) {
                $post_data['featured_image_url'] = get_the_post_thumbnail_url($post->ID, 'full');
            }

            $exported[] = $post_data;
        }

        return $exported;
    }

    /**
     * Exportar taxonomía
     */
    private function export_taxonomy($taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        if (is_wp_error($terms)) {
            return array();
        }

        $exported = array();

        foreach ($terms as $term) {
            $exported[] = array(
                'term_id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'parent' => $term->parent,
            );
        }

        return $exported;
    }

    /**
     * Exportar productos de AliExpress
     */
    private function export_aliexpress_products() {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';
        $products = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

        return $products ?: array();
    }

    /**
     * Manejar exportación
     */
    public function handle_export() {
        if (!isset($_POST['adp_export_backup']) || !check_admin_referer('adp_backup_nonce')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción', 'ad-platform'));
        }

        $options = array(
            'include_settings' => !empty($_POST['export_settings']),
            'include_ads' => !empty($_POST['export_ads']),
            'include_campaigns' => !empty($_POST['export_campaigns']),
            'include_zones' => !empty($_POST['export_zones']),
            'include_landings' => !empty($_POST['export_landings']),
            'include_aliexpress_products' => !empty($_POST['export_aliexpress']),
        );

        $data = $this->get_export_data($options);

        $filename = 'ad-platform-backup-' . date('Y-m-d-His') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * AJAX export
     */
    public function ajax_export() {
        check_ajax_referer('adp_backup_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $data = $this->get_export_data();

        wp_send_json_success(array(
            'data' => $data,
            'filename' => 'ad-platform-backup-' . date('Y-m-d-His') . '.json',
        ));
    }

    /**
     * Manejar importación
     */
    public function handle_import() {
        if (!isset($_POST['adp_import_backup']) || !check_admin_referer('adp_backup_nonce')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción', 'ad-platform'));
        }

        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            add_settings_error(
                'adp_backup',
                'file_error',
                __('Error al subir el archivo', 'ad-platform'),
                'error'
            );
            return;
        }

        $file = $_FILES['backup_file'];

        // Validar extensión
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($ext !== 'json') {
            add_settings_error(
                'adp_backup',
                'invalid_file',
                __('El archivo debe ser un JSON válido', 'ad-platform'),
                'error'
            );
            return;
        }

        // Leer contenido
        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            add_settings_error(
                'adp_backup',
                'invalid_json',
                __('El archivo JSON no es válido', 'ad-platform'),
                'error'
            );
            return;
        }

        // Realizar importación
        $options = array(
            'import_settings' => !empty($_POST['import_settings']),
            'import_content' => !empty($_POST['import_content']),
            'overwrite' => !empty($_POST['overwrite_existing']),
        );

        $result = $this->import_data($data, $options);

        if ($result['success']) {
            add_settings_error(
                'adp_backup',
                'import_success',
                sprintf(
                    __('Importación completada: %d configuraciones, %d posts, %d términos importados', 'ad-platform'),
                    $result['settings_count'],
                    $result['posts_count'],
                    $result['terms_count']
                ),
                'success'
            );
        } else {
            add_settings_error(
                'adp_backup',
                'import_error',
                $result['message'],
                'error'
            );
        }
    }

    /**
     * Importar datos
     */
    public function import_data($data, $options = array()) {
        $defaults = array(
            'import_settings' => true,
            'import_content' => true,
            'overwrite' => false,
        );

        $options = wp_parse_args($options, $defaults);

        $result = array(
            'success' => true,
            'settings_count' => 0,
            'posts_count' => 0,
            'terms_count' => 0,
            'message' => '',
        );

        // Importar configuraciones
        if ($options['import_settings'] && !empty($data['settings'])) {
            foreach ($data['settings'] as $key => $value) {
                $option_name = 'ad_platform_' . $key;
                update_option($option_name, $value);
                $result['settings_count']++;
            }
        }

        // Importar taxonomías primero
        if (!empty($data['taxonomies'])) {
            foreach ($data['taxonomies'] as $taxonomy => $terms) {
                foreach ($terms as $term_data) {
                    $existing = get_term_by('slug', $term_data['slug'], $taxonomy);

                    if (!$existing) {
                        $new_term = wp_insert_term(
                            $term_data['name'],
                            $taxonomy,
                            array(
                                'slug' => $term_data['slug'],
                                'description' => $term_data['description'],
                            )
                        );

                        if (!is_wp_error($new_term)) {
                            $result['terms_count']++;
                        }
                    }
                }
            }
        }

        // Importar contenido
        if ($options['import_content']) {
            // Importar por tipo
            $post_types = array('ads', 'campaigns', 'zones', 'landings');
            $type_mapping = array(
                'ads' => 'adp_ad',
                'campaigns' => 'adp_campaign',
                'zones' => 'adp_zone',
                'landings' => 'adp_landing',
            );

            foreach ($post_types as $type_key) {
                if (empty($data[$type_key])) continue;

                $post_type = $type_mapping[$type_key];

                foreach ($data[$type_key] as $post_data) {
                    $imported = $this->import_post($post_data, $post_type, $options['overwrite']);
                    if ($imported) {
                        $result['posts_count']++;
                    }
                }
            }

            // Importar productos de AliExpress
            if (!empty($data['aliexpress_products'])) {
                $this->import_aliexpress_products($data['aliexpress_products'], $options['overwrite']);
            }
        }

        return $result;
    }

    /**
     * Importar post individual
     */
    private function import_post($post_data, $post_type, $overwrite = false) {
        // Buscar existente por slug
        $existing = get_page_by_path($post_data['post_name'], OBJECT, $post_type);

        if ($existing && !$overwrite) {
            return false;
        }

        $post_args = array(
            'post_title' => $post_data['post_title'],
            'post_content' => $post_data['post_content'],
            'post_excerpt' => $post_data['post_excerpt'] ?? '',
            'post_status' => $post_data['post_status'],
            'post_name' => $post_data['post_name'],
            'post_type' => $post_type,
        );

        if ($existing && $overwrite) {
            $post_args['ID'] = $existing->ID;
            $post_id = wp_update_post($post_args);
        } else {
            $post_id = wp_insert_post($post_args);
        }

        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }

        // Importar meta
        if (!empty($post_data['meta'])) {
            foreach ($post_data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }

        // Importar términos
        if (!empty($post_data['terms'])) {
            foreach ($post_data['terms'] as $taxonomy => $term_slugs) {
                wp_set_object_terms($post_id, $term_slugs, $taxonomy);
            }
        }

        // Importar imagen destacada
        if (!empty($post_data['featured_image_url'])) {
            $this->set_featured_image_from_url($post_id, $post_data['featured_image_url']);
        }

        return true;
    }

    /**
     * Establecer imagen destacada desde URL
     */
    private function set_featured_image_from_url($post_id, $url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_sideload_image($url, $post_id, null, 'id');

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    /**
     * Importar productos de AliExpress
     */
    private function import_aliexpress_products($products, $overwrite = false) {
        global $wpdb;

        $table = $wpdb->prefix . 'adp_aliexpress_products';

        foreach ($products as $product) {
            // Verificar si existe
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE product_id = %s",
                $product['product_id']
            ));

            if ($exists && !$overwrite) {
                continue;
            }

            // Preparar datos
            unset($product['id']); // Remover ID para insert

            if ($exists && $overwrite) {
                $wpdb->update($table, $product, array('id' => $exists));
            } else {
                $wpdb->insert($table, $product);
            }
        }
    }

    /**
     * AJAX import
     */
    public function ajax_import() {
        check_ajax_referer('adp_backup_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (empty($_POST['data'])) {
            wp_send_json_error('No data provided');
        }

        $data = json_decode(stripslashes($_POST['data']), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON');
        }

        $result = $this->import_data($data);

        wp_send_json_success($result);
    }
}

// Inicializar
new Ad_Platform_Backup();
