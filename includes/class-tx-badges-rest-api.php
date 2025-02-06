<?php

class TX_Badges_REST_API {
    private $namespace = 'tx-badges/v1';

    public function __construct() {
        // Include plugin.php for is_plugin_active function
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
    }

    public function register_routes() {
        // Get installed plugins status
        register_rest_route($this->namespace, '/installed-plugins', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_installed_plugins'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        // Get all badges
        register_rest_route($this->namespace, '/badges', [
            'methods' => 'GET',
            'callback' => [$this, 'get_badges'],
            'permission_callback' => [$this, 'get_badges_permissions_check'],
        ]);

        // Create badge
        register_rest_route($this->namespace, '/badges', [
            'methods' => 'POST',
            'callback' => [$this, 'create_badge'],
            'permission_callback' => [$this, 'create_badge_permissions_check'],
        ]);

        // Update badge
        register_rest_route($this->namespace, '/badges/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_badge'],
            'permission_callback' => [$this, 'update_badge_permissions_check'],
        ]);

        // Delete badge
        register_rest_route($this->namespace, '/badges/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_badge'],
            'permission_callback' => [$this, 'delete_badge_permissions_check'],
        ]);

        // Get settings
        register_rest_route($this->namespace, '/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        ]);

        // Check installed plugins
        register_rest_route($this->namespace, '/installed-plugins', [
            'methods' => 'GET',
            'callback' => [$this, 'get_installed_plugins'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    public function get_badges_permissions_check($request) {
        return true; // Public access for viewing badges
    }

    public function create_badge_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function update_badge_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function delete_badge_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function get_settings_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function update_settings_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function get_badges($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges';
        
        $badges = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY position ASC");
        return new WP_REST_Response($badges, 200);
    }

    public function create_badge($request) {
        $params = $request->get_params();
        
        // Validate required fields
        if (empty($params['title']) || empty($params['image_url'])) {
            return new WP_Error('missing_fields', 'Required fields are missing', ['status' => 400]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'title' => sanitize_text_field($params['title']),
                'image_url' => esc_url_raw($params['image_url']),
                'link_url' => isset($params['link_url']) ? esc_url_raw($params['link_url']) : '',
                'position' => isset($params['position']) ? intval($params['position']) : 0,
                'status' => isset($params['status']) ? sanitize_text_field($params['status']) : 'active',
            ],
            ['%s', '%s', '%s', '%d', '%s']
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Could not create badge', ['status' => 500]);
        }

        return new WP_REST_Response([
            'id' => $wpdb->insert_id,
            'message' => 'Badge created successfully'
        ], 201);
    }

    public function get_installed_plugins() {
        // Make sure we have access to WordPress plugin functions
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Get all plugins
        $all_plugins = get_plugins();
        
        // Check WooCommerce
        $woo_active = class_exists('WooCommerce') && function_exists('WC');
        
        // Check EDD
        $edd_active = class_exists('Easy_Digital_Downloads') || function_exists('EDD');
        
        // Additional check for plugin files
        if (!$woo_active) {
            $woo_active = is_plugin_active('woocommerce/woocommerce.php');
        }
        
        if (!$edd_active) {
            $edd_active = is_plugin_active('easy-digital-downloads/easy-digital-downloads.php');
        }

        // Debug information
        $debug = [
            'all_plugins' => array_keys($all_plugins),
            'woo_class_exists' => class_exists('WooCommerce'),
            'woo_function_exists' => function_exists('WC'),
            'edd_class_exists' => class_exists('Easy_Digital_Downloads'),
            'edd_function_exists' => function_exists('EDD'),
            'woo_active' => $woo_active,
            'edd_active' => $edd_active,
            'plugin_dir' => WP_PLUGIN_DIR,
            'plugins_url' => plugins_url()
        ];

        error_log('TX Badges Plugin Check Debug: ' . print_r($debug, true));

        return new WP_REST_Response([
            'woocommerce' => $woo_active,
            'edd' => $edd_active,
            'debug' => $debug
        ], 200);
    }

    // Implement other methods (update_badge, delete_badge, get_settings, update_settings)
    // with similar structure and proper validation
}
