<?php

class TX_Badges_REST_Controller {
    private $namespace;
    private $rest_base;
    
    public function __construct() {
        $this->namespace = 'tx-badges/v1';
        $this->rest_base = 'badges';
    }

    public function register_routes() {
        if (!function_exists('register_rest_route')) {
            return;
        }

        // Badge routes
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_items'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_item'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
            )
        ));

        // Settings routes
        register_rest_route($this->namespace, '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_settings'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args' => array(
                    'settings' => array(
                        'required' => true,
                        'type' => 'object',
                    ),
                ),
            )
        ));

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_item'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_item'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            )
        ));
    }

    public function get_items_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function create_item_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function update_item_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function delete_item_permissions_check($request) {
        return current_user_can('manage_options');
    }

    public function get_items($request) {
        return new WP_REST_Response([], 200);
    }

    public function create_item($request) {
        return new WP_REST_Response([], 201);
    }

    public function update_item($request) {
        return new WP_REST_Response([], 200);
    }

    public function delete_item($request) {
        return new WP_REST_Response([], 200);
    }

    public function get_settings($request) {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tx_badges_settings';
            
            $results = $wpdb->get_results("SELECT setting_name, setting_value FROM {$table_name} WHERE is_active = 1");
            
            if ($wpdb->last_error) {
                return new WP_Error(
                    'database_error',
                    $wpdb->last_error,
                    array('status' => 500)
                );
            }

            $settings = array();
            foreach ($results as $row) {
                $value = $row->setting_value;
                
                // Convert boolean strings
                if ($value === '1' || $value === '0') {
                    $value = $value === '1';
                }
                // Try to decode JSON values
                else if (strpos($value, '[') === 0 || strpos($value, '{') === 0) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }
                
                $settings[$row->setting_name] = $value;
            }

            return rest_ensure_response($settings);
        } catch (Exception $e) {
            return new WP_Error(
                'server_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    public function update_settings($request) {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tx_badges_settings';
            $settings = $request->get_param('settings');
            
            if (empty($settings) || !is_array($settings)) {
                return new WP_Error(
                    'invalid_settings',
                    __('Invalid settings data provided.', 'tx-badges'),
                    array('status' => 400)
                );
            }

            foreach ($settings as $name => $value) {
                $name = sanitize_text_field($name);
                
                // Convert value based on type
                if (is_bool($value)) {
                    $db_value = $value ? '1' : '0';
                } else if (is_array($value)) {
                    $db_value = wp_json_encode($value);
                } else {
                    $db_value = sanitize_text_field($value);
                }

                $result = $wpdb->update(
                    $table_name,
                    array('setting_value' => $db_value),
                    array('setting_name' => $name),
                    array('%s'),
                    array('%s')
                );

                if ($result === false && $wpdb->last_error) {
                    return new WP_Error(
                        'update_failed',
                        sprintf(__('Failed to update setting: %s. Error: %s', 'tx-badges'), $name, $wpdb->last_error),
                        array('status' => 500)
                    );
                }
            }

            return rest_ensure_response(array(
                'success' => true,
                'message' => __('Settings updated successfully.', 'tx-badges')
            ));
        } catch (Exception $e) {
            return new WP_Error(
                'server_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
}
