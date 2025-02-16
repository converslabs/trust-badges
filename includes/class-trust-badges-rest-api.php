<?php

class TX_Badges_REST_API {
    private $namespace = 'trust-badges/v1';
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

    /**
     * Register REST API endpoints with proper error handling
     */
    public function register_routes() {
        try {
            // Register settings endpoint
            register_rest_route(
                'trust-badges/v1',
                '/settings',
                array(
                    array(
                        'methods' => 'GET',
                        'callback' => array($this, 'get_settings'),
                        'permission_callback' => array($this, 'check_permissions'),
                    ),
                    array(
                        'methods' => 'POST',
                        'callback' => array($this, 'save_settings'),
                        'permission_callback' => array($this, 'check_permissions'),
                    ),
                )
            );

            // Register group settings endpoint
            register_rest_route(
                'trust-badges/v1',
                '/settings/group',
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'save_group_settings'),
                    'permission_callback' => array($this, 'check_permissions'),
                    'args' => array(
                        'group' => array(
                            'required' => true,
                            'type' => 'object',
                        ),
                    ),
                )
            );

            // Register delete group endpoint
            register_rest_route(
                'trust-badges/v1',
                '/settings/group/(?P<id>[a-zA-Z0-9-]+)',
                array(
                    'methods' => 'DELETE',
                    'callback' => array($this, 'delete_group'),
                    'permission_callback' => array($this, 'check_permissions'),
                )
            );

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

            // Add new routes for individual group operations
            register_rest_route($this->namespace, '/settings/group/(?P<id>[a-zA-Z0-9-_]+)', [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'get_group'],
                    'permission_callback' => [$this, 'get_settings_permissions_check'],
                    'args' => [
                        'id' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
            ]);
        } catch (Exception $e) {
            error_log('TX Badges REST API Error: ' . $e->getMessage());
        }
    }

    /**
     * Check user permissions and nonce for REST API requests
     * @return bool|WP_Error
     */
    public function check_permissions() {
        try {
            // Verify user is logged in
            if (!is_user_logged_in()) {
                return new WP_Error(
                    'rest_not_logged_in',
                    __('You must be logged in to manage Trust Badges.', 'trust-badges'),
                    array('status' => 401)
                );
            }

            // Verify user capabilities
            if (!current_user_can('manage_options')) {
                return new WP_Error(
                    'rest_forbidden_capability',
                    __('You do not have sufficient permissions to manage Trust Badges.', 'trust-badges'),
                    array('status' => 403)
                );
            }

            // Get nonce from headers
            $nonce = null;
            if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
                $nonce = $_SERVER['HTTP_X_WP_NONCE'];
            }

            // Verify nonce
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'rest_cookie_invalid_nonce',
                    __('Session expired. Please refresh the page and try again.', 'trust-badges'),
                    array('status' => 403)
                );
            }

            return true;
        } catch (Exception $e) {
            error_log('TX Badges Permission Check Error: ' . $e->getMessage());
            return new WP_Error(
                'rest_error',
                __('An unexpected error occurred.', 'trust-badges'),
                array('status' => 500)
            );
        }
    }

    /**
     * Save group settings with improved error handling
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function save_group_settings($request) {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'converswp_trust_badges';

            // Get and validate group data
            $group = $request->get_param('group');
            if (empty($group) || !is_array($group)) {
                return new WP_Error(
                    'invalid_group_data',
                    __('Invalid group data provided.', 'trust-badges'),
                    array('status' => 400)
                );
            }

            // Start transaction
            $wpdb->query('START TRANSACTION');

            try {
                // Prepare data for database
                $data = array(
                    'group_name' => sanitize_text_field($group['name']),
                    'is_active' => isset($group['isActive']) ? (bool)$group['isActive'] : true,
                    'settings' => wp_json_encode($group['settings'])
                );

                $where = array('group_id' => sanitize_text_field($group['id']));
                
                // Check if group exists
                $existing = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE group_id = %s",
                        $group['id']
                    )
                );

                if ($existing) {
                    // Update existing group
                    $result = $wpdb->update($table_name, $data, $where);
                } else {
                    // Insert new group
                    $data['group_id'] = sanitize_text_field($group['id']);
                    $data['is_default'] = isset($group['isDefault']) ? (bool)$group['isDefault'] : false;
                    $result = $wpdb->insert($table_name, $data);
                }

                // Check for database errors
                if ($error = $this->handle_db_error($wpdb, 'save_group_settings')) {
                    throw new Exception($error->get_error_message());
                }

                if ($result === false) {
                    throw new Exception(__('Failed to save group settings.', 'trust-badges'));
                }

                // Commit transaction
                $wpdb->query('COMMIT');

                // Clear cache
                wp_cache_delete('tx_badges_settings');

                // Return success response with updated group data
                $updated_group = array(
                    'id' => $group['id'],
                    'name' => $data['group_name'],
                    'isActive' => $data['is_active'],
                    'isDefault' => isset($data['is_default']) ? $data['is_default'] : false,
                    'settings' => json_decode($data['settings'], true),
                    'requiredPlugin' => isset($group['requiredPlugin']) ? $group['requiredPlugin'] : null
                );

                return rest_ensure_response(array(
                    'success' => true,
                    'message' => __('Group settings saved successfully.', 'trust-badges'),
                    'group' => $updated_group
                ));

            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
        } catch (Exception $e) {
            error_log('TX Badges Save Group Error: ' . $e->getMessage());
            return new WP_Error(
                'save_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
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

    public function get_settings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        
        $groups = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY id ASC"
        );
        
        if (!$groups) {
            return new WP_REST_Response([], 200);
        }
        
        $formatted_groups = array_map(function($group) {
            return [
                'id' => $group->group_id,
                'name' => $group->group_name,
                'isDefault' => (bool)$group->is_default,
                'isActive' => (bool)$group->is_active,
                'requiredPlugin' => $group->required_plugin,
                'settings' => json_decode($group->settings, true)
            ];
        }, $groups);
        
        return new WP_REST_Response($formatted_groups, 200);
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

    public function get_group($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        $group_id = sanitize_text_field($request['id']);
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE group_id = %s",
                $group_id
            )
        );
        
        if ($error = $this->handle_db_error($wpdb, 'get_group')) {
            return $error;
        }

        if (!$result) {
            return new WP_Error(
                'not_found',
                'Group not found',
                ['status' => 404]
            );
        }

        return new WP_REST_Response([
            'id' => $result->group_id,
            'name' => $result->group_name,
            'isDefault' => (bool)$result->is_default,
            'isActive' => (bool)$result->is_active,
            'requiredPlugin' => $result->required_plugin,
            'settings' => json_decode($result->settings, true)
        ], 200);
    }

    public function delete_group($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        $group_id = sanitize_text_field($request['id']);

        // Don't allow deletion of default groups
        $is_default = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT is_default FROM $table_name WHERE group_id = %s",
                $group_id
            )
        );

        if ($is_default) {
            return new WP_Error(
                'delete_failed',
                'Cannot delete default groups',
                ['status' => 403]
            );
        }

        $result = $wpdb->delete(
            $table_name,
            ['group_id' => $group_id],
            ['%s']
        );
        
        if ($error = $this->handle_db_error($wpdb, 'delete_group')) {
            return $error;
        }

        if ($result === false) {
            return new WP_Error(
                'delete_failed',
                'Failed to delete group',
                ['status' => 500]
            );
        }

        // Clear cache
        wp_cache_delete('tx_badges_settings');
        
        return new WP_REST_Response([
            'message' => 'Group deleted successfully'
        ], 200);
    }

    public function get_installed_plugins() {
        return new WP_REST_Response([
            'woocommerce' => is_plugin_active('woocommerce/woocommerce.php'),
            'edd' => is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')
        ], 200);
    }
}