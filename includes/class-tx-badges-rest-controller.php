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
        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges';
        
        $badges = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY position ASC");
        
        if (is_wp_error($badges)) {
            return new WP_Error(
                'database_error',
                __('Failed to fetch badges from database.', 'tx-badges'),
                array('status' => 500)
            );
        }

        return new WP_REST_Response($badges, 200);
    }

    public function create_item($request) {
        if (!$request->get_param('title')) {
            return new WP_Error(
                'missing_title',
                __('Title is required.', 'tx-badges'),
                array('status' => 400)
            );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges';
        
        $badge = array(
            'title' => sanitize_text_field($request->get_param('title')),
            'image_url' => esc_url_raw($request->get_param('image_url')),
            'link_url' => esc_url_raw($request->get_param('link_url')),
            'position' => absint($request->get_param('position')),
            'status' => 'active'
        );

        $result = $wpdb->insert($table_name, $badge);
        
        if (false === $result) {
            return new WP_Error(
                'database_error',
                __('Failed to create badge.', 'tx-badges'),
                array('status' => 500)
            );
        }

        $badge['id'] = $wpdb->insert_id;
        
        return new WP_REST_Response($badge, 201);
    }

    public function update_item($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges';
        $id = absint($request->get_param('id'));

        if (!$id) {
            return new WP_Error(
                'invalid_id',
                __('Invalid badge ID.', 'tx-badges'),
                array('status' => 400)
            );
        }

        $badge = array(
            'title' => sanitize_text_field($request->get_param('title')),
            'image_url' => esc_url_raw($request->get_param('image_url')),
            'link_url' => esc_url_raw($request->get_param('link_url')),
            'position' => absint($request->get_param('position')),
            'status' => sanitize_text_field($request->get_param('status'))
        );

        $result = $wpdb->update($table_name, $badge, array('id' => $id));
        
        if (false === $result) {
            return new WP_Error(
                'database_error',
                __('Failed to update badge.', 'tx-badges'),
                array('status' => 500)
            );
        }

        $badge['id'] = $id;
        return new WP_REST_Response($badge, 200);
    }

    public function delete_item($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges';
        $id = absint($request->get_param('id'));

        if (!$id) {
            return new WP_Error(
                'invalid_id',
                __('Invalid badge ID.', 'tx-badges'),
                array('status' => 400)
            );
        }

        $result = $wpdb->delete($table_name, array('id' => $id));
        
        if (false === $result) {
            return new WP_Error(
                'database_error',
                __('Failed to delete badge.', 'tx-badges'),
                array('status' => 500)
            );
        }

        return new WP_REST_Response(null, 204);
    }
}
