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
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'get_settings_permissions_check'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_settings'],
                'permission_callback' => [$this, 'update_settings_permissions_check'],
            ]
        ]);

        register_rest_route($this->namespace, '/settings/group', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_group'],
                'permission_callback' => [$this, 'update_settings_permissions_check'],
            ]
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
        // Remove database query code
        // We'll rewrite this later
        return new WP_REST_Response([], 200);
    }

    public function create_badge($request) {
        // Remove database insert code
        // We'll rewrite this later
        return new WP_REST_Response([], 201);
    }

    public function get_settings($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC");
        
        if ($wpdb->last_error) {
            return new WP_Error('database_error', $wpdb->last_error, ['status' => 500]);
        }

        $groups = array_map(function($row) {
            return [
                'id' => $row->group_id,
                'name' => $row->group_name,
                'isDefault' => (bool)$row->is_default,
                'isActive' => (bool)$row->is_active,
                'requiredPlugin' => $row->required_plugin,
                'settings' => json_decode($row->settings, true)
            ];
        }, $results);

        return new WP_REST_Response($groups, 200);
    }

    public function save_settings($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        
        $groups = $request->get_param('groups');
        
        if (!is_array($groups)) {
            return new WP_Error('invalid_data', 'Invalid groups data', ['status' => 400]);
        }

        $wpdb->query('START TRANSACTION');

        try {
            foreach ($groups as $group) {
                $data = [
                    'group_name' => sanitize_text_field($group['name']),
                    'is_active' => (bool)$group['isActive'],
                    'settings' => json_encode($group['settings'])
                ];

                $where = ['group_id' => $group['id']];
                
                $result = $wpdb->update($table_name, $data, $where);
                
                if ($result === false) {
                    throw new Exception($wpdb->last_error);
                }
            }

            $wpdb->query('COMMIT');
            return new WP_REST_Response(['message' => 'Settings updated successfully'], 200);
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('update_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    public function create_group($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        
        $group = $request->get_param('group');
        
        if (!isset($group['id']) || !isset($group['name']) || !isset($group['settings'])) {
            return new WP_Error('invalid_data', 'Missing required fields', ['status' => 400]);
        }

        $data = [
            'group_id' => sanitize_text_field($group['id']),
            'group_name' => sanitize_text_field($group['name']),
            'is_default' => 0,
            'is_active' => 1,
            'settings' => json_encode($group['settings'])
        ];

        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            return new WP_Error('insert_failed', $wpdb->last_error, ['status' => 500]);
        }

        return new WP_REST_Response([
            'message' => 'Group created successfully',
            'group' => array_merge($group, ['id' => $wpdb->insert_id])
        ], 201);
    }

    // Implement other methods (update_badge, delete_badge, get_settings, update_settings)
    // with similar structure and proper validation
}