<?php

class TX_Badges_REST_API {
    private $namespace = 'tx-badges/v1';
    private $cache_expiry = 3600; // 1 hour cache

    // Utility method for handling database errors
    private function handle_db_error($wpdb, $context = '') {
        if ($wpdb->last_error) {
            error_log("TX Badges DB Error ({$context}): " . $wpdb->last_error);
            return new WP_Error(
                'database_error',
                'A database error occurred: ' . $wpdb->last_error,
                ['status' => 500]
            );
        }
        return null;
    }

    // Rate limiting check
    private function check_rate_limit() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $cache_key = 'tx_badges_rate_limit_' . $ip;
        $requests = get_transient($cache_key);
        
        if ($requests > 100) { // 100 requests per hour
            return new WP_Error(
                'rate_limit_exceeded',
                'Too many requests. Please try again later.',
                ['status' => 429]
            );
        }
        
        set_transient($cache_key, ($requests ? $requests + 1 : 1), HOUR_IN_SECONDS);
        return true;
    }

    public function register_routes() {
        // Get all badges
        register_rest_route($this->namespace, '/badges', [
            'methods' => 'GET',
            'callback' => [$this, 'get_badges'],
            'permission_callback' => [$this, 'get_badges_permissions_check'],
        ]);

        // Add installed plugins endpoint
        register_rest_route($this->namespace, '/installed-plugins', [
            'methods' => 'GET',
            'callback' => [$this, 'get_installed_plugins'],
            'permission_callback' => [$this, 'get_settings_permissions_check'],
        ]);

        // Create badge
        register_rest_route($this->namespace, '/badges', [
            'methods' => 'POST',
            'callback' => [$this, 'create_badge'],
            'permission_callback' => [$this, 'create_badge_permissions_check'],
            'args' => [
                'name' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'settings' => [
                    'required' => true,
                    'type' => 'object',
                ],
            ],
        ]);

        // Update badge
        register_rest_route($this->namespace, '/badges/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_badge'],
            'permission_callback' => [$this, 'update_badge_permissions_check'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
        ]);

        // Delete badge
        register_rest_route($this->namespace, '/badges/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_badge'],
            'permission_callback' => [$this, 'delete_badge_permissions_check'],
            'args' => [
                'id' => [
                    'required' => true,
                    'type' => 'integer',
                ],
            ],
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
                'args' => [
                    'group' => [
                        'required' => true,
                        'type' => 'object',
                    ],
                ],
            ]
        ]);
    }

    // Permission checks with nonce verification
    public function get_badges_permissions_check($request) {
        return true; // Public access for viewing badges
    }

    public function create_badge_permissions_check($request) {
        return current_user_can('manage_options') && 
               check_ajax_referer('tx_badges_nonce', 'nonce', false);
    }

    public function update_badge_permissions_check($request) {
        return current_user_can('manage_options') && 
               check_ajax_referer('tx_badges_nonce', 'nonce', false);
    }

    public function delete_badge_permissions_check($request) {
        return current_user_can('manage_options') && 
               check_ajax_referer('tx_badges_nonce', 'nonce', false);
    }

    public function get_settings_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function update_settings_permissions_check($request) {
        return current_user_can('manage_options') && 
               check_ajax_referer('tx_badges_nonce', 'nonce', false);
    }

    // Badge management methods
    public function get_badges($request) {
        // Check rate limiting
        $rate_limit_check = $this->check_rate_limit();
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        
        // Try to get from cache first
        $cache_key = 'tx_badges_all';
        $badges = wp_cache_get($cache_key);
        
        if (false === $badges) {
            $results = $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1");
            
            if ($error = $this->handle_db_error($wpdb, 'get_badges')) {
                return $error;
            }
            
            $badges = array_map(function($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'settings' => json_decode($row->settings, true),
                    'isActive' => (bool)$row->is_active
                ];
            }, $results);
            
            wp_cache_set($cache_key, $badges, '', $this->cache_expiry);
        }
        
        return new WP_REST_Response($badges, 200);
    }

    public function create_badge($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        
        $name = sanitize_text_field($request->get_param('name'));
        $settings = $request->get_param('settings');
        
        if (empty($name) || empty($settings)) {
            return new WP_Error(
                'invalid_data',
                'Name and settings are required fields',
                ['status' => 400]
            );
        }

        $data = [
            'name' => $name,
            'settings' => wp_json_encode($settings),
            'is_active' => 1,
            'created_at' => current_time('mysql')
        ];

        $result = $wpdb->insert($table_name, $data);
        
        if ($error = $this->handle_db_error($wpdb, 'create_badge')) {
            return $error;
        }

        // Clear cache
        wp_cache_delete('tx_badges_all');
        
        return new WP_REST_Response([
            'id' => $wpdb->insert_id,
            'name' => $name,
            'settings' => $settings,
            'isActive' => true
        ], 201);
    }

    public function get_settings($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        
        // Try to get from cache first
        $cache_key = 'tx_badges_settings';
        $cached_settings = wp_cache_get($cache_key);
        
        if (false !== $cached_settings) {
            return new WP_REST_Response($cached_settings, 200);
        }
        
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC");
        
        if ($error = $this->handle_db_error($wpdb, 'get_settings')) {
            return $error;
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

        wp_cache_set($cache_key, $groups, '', $this->cache_expiry);
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
                    'settings' => wp_json_encode($group['settings'])
                ];

                $where = ['group_id' => sanitize_text_field($group['id'])];
                
                $result = $wpdb->update($table_name, $data, $where);
                
                if ($error = $this->handle_db_error($wpdb, 'save_settings')) {
                    throw new Exception($error->get_error_message());
                }
            }

            $wpdb->query('COMMIT');
            
            // Clear cache
            wp_cache_delete('tx_badges_settings');
            
            return new WP_REST_Response([
                'message' => 'Settings updated successfully'
            ], 200);
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
            'settings' => wp_json_encode($group['settings'])
        ];

        $result = $wpdb->insert($table_name, $data);
        
        if ($error = $this->handle_db_error($wpdb, 'create_group')) {
            return $error;
        }

        // Clear cache
        wp_cache_delete('tx_badges_settings');
        
        return new WP_REST_Response([
            'message' => 'Group created successfully',
            'group' => array_merge($group, ['id' => $wpdb->insert_id])
        ], 201);
    }

    public function get_installed_plugins() {
        return new WP_REST_Response([
            'woocommerce' => is_plugin_active('woocommerce/woocommerce.php'),
            'edd' => is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')
        ], 200);
    }
}
