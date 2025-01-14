<?php

class TX_Badges_REST_API {
    private $namespace = 'tx-badges/v1';

    public function register_routes() {
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
            'methods' => 'GET',
            'callback' => [$this, 'get_settings'],
            'permission_callback' => [$this, 'get_settings_permissions_check'],
        ]);

        // Update settings
        register_rest_route($this->namespace, '/settings', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_settings'],
            'permission_callback' => [$this, 'update_settings_permissions_check'],
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

    // Implement other methods (update_badge, delete_badge, get_settings, update_settings)
    // with similar structure and proper validation
}
