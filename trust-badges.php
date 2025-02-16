<?php

/**
 * Plugin Name: Trust Badges
 * Plugin URI: https://converswp.com/trust-badges
 * Description: Display customizable trust badges on your website to boost visitor confidence and increase conversions.
 * Version: 1.0.0
 * Author: ConversWP
 * Author URI: https://converswp.com
 * Text Domain: trust-badges
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TX_BADGES_VERSION', '1.0.0');
define('TX_BADGES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TX_BADGES_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-activator.php';
require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-utilities.php';
require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-renderer.php';

// Initialize REST API
function tx_badges_init_rest_api()
{
    require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-rest-api.php';
    $rest_api = new TX_Badges_REST_API();
    $rest_api->register_routes();
}
add_action('rest_api_init', 'tx_badges_init_rest_api');

// Start the plugin
add_action( 'plugins_loaded', function () {
    require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges.php';
    $plugin = new TX_Badges();
    $plugin->run();
} );

// Plugin activation with improved error handling
register_activation_hook(__FILE__, ['TX_Badges_Activator', 'activate']);


// Update the admin enqueue function with proper nonce handling
add_action('admin_enqueue_scripts', ['TX_Badges_Utilities', 'tx_badges_admin_enqueue_scripts']);


function tx_badges_save_settings() {
    check_ajax_referer('tx_badges_nonce', 'nonce');

    try {
        if (empty($_POST['settings']) || !is_string($_POST['settings'])) {
            wp_send_json_error(['message' => 'Invalid settings data']);
            return;
        }

        $badge_groups = json_decode(stripslashes($_POST['settings']), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON data']);
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges_settings';

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // First, deactivate all existing settings
            $wpdb->update(
                $table_name,
                array('is_active' => 0),
                array(),
                array('%d')
            );

            foreach ($badge_groups as $group) {
                $group_id = sanitize_text_field($group['id']);
                $is_active = $group['isActive'] ? 1 : 0;
                
                foreach ($group['settings'] as $name => $value) {
                    $setting_name = sanitize_text_field($name);
                    
                    // Convert value based on type
                    if (is_bool($value)) {
                        $db_value = $value ? '1' : '0';
                    } else if (is_array($value)) {
                        $db_value = wp_json_encode($value);
                    } else {
                        $db_value = sanitize_text_field($value);
                    }

                    // Try to update existing setting first
                    $result = $wpdb->update(
                        $table_name,
                        array(
                            'setting_value' => $db_value,
                            'is_active' => $is_active,
                            'group_id' => $group_id
                        ),
                        array(
                            'setting_name' => $setting_name,
                            'group_id' => $group_id
                        ),
                        array('%s', '%d', '%s'),
                        array('%s', '%s')
                    );

                    // If setting doesn't exist, insert it
                    if ($result === 0) {
                        $result = $wpdb->insert(
                            $table_name,
                            array(
                                'setting_name' => $setting_name,
                                'setting_value' => $db_value,
                                'is_active' => $is_active,
                                'group_id' => $group_id
                            ),
                            array('%s', '%s', '%d', '%s')
                        );
                    }

                    if ($result === false) {
                        throw new Exception($wpdb->last_error);
                    }
                }
            }

            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'Settings updated successfully']);
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_tx_badges_save_settings', 'tx_badges_save_settings');

// Update REST API authentication with improved error handling
function tx_badges_rest_authentication($result) {
    // If a previous authentication check was applied,
    // pass that result along without modification
    if (true === $result || is_wp_error($result)) {
        return $result;
    }

    // Skip authentication for non-plugin endpoints
    $current_route = $_SERVER['REQUEST_URI'];
    if (strpos($current_route, '/trust-badges/v1/') === false) {
        return $result;
    }

    // Verify user is logged in and has proper capabilities
    if (!is_user_logged_in()) {
        return new WP_Error(
            'rest_not_logged_in',
            __('You must be logged in to manage Trust Badges.', 'trust-badges'),
            array('status' => 401)
        );
    }

    if (!current_user_can('manage_options')) {
        return new WP_Error(
            'rest_forbidden_capability',
            __('You do not have sufficient permissions to manage Trust Badges.', 'trust-badges'),
            array('status' => 403)
        );
    }

    // Get the nonce from headers or request parameters
    $nonce = null;
    if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
        $nonce = $_SERVER['HTTP_X_WP_NONCE'];
    } elseif (isset($_REQUEST['_wpnonce'])) {
        $nonce = $_REQUEST['_wpnonce'];
    }

    // Verify the nonce
    if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_Error(
            'rest_cookie_invalid_nonce',
            __('Session expired. Please refresh the page and try again.', 'trust-badges'),
            array('status' => 403)
        );
    }

    return true;
}
add_filter('rest_authentication_errors', 'tx_badges_rest_authentication');

// Add error logging function
function tx_badges_log_error($message, $context = []) {
    error_log(sprintf(
        '[TX Badges Error] %s | Context: %s',
        $message,
        json_encode($context)
    ));
}

// AJAX handlers
function tx_badges_get_settings() {
    check_ajax_referer('tx_badges_nonce', 'nonce');

    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges_settings';

        // Get all badge groups
        $results = $wpdb->get_results("
            SELECT DISTINCT group_id, setting_name, setting_value, is_active
            FROM {$table_name}
            ORDER BY group_id ASC
        ");

        if ($wpdb->last_error) {
            wp_send_json_error(['message' => $wpdb->last_error]);
            return;
        }

        // Organize settings by group
        $groups = array();
        foreach ($results as $row) {
            $group_id = $row->group_id ?: 'default';
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

            if (!isset($groups[$group_id])) {
                $groups[$group_id] = array(
                    'id' => $group_id,
                    'isDefault' => $group_id === 'default',
                    'isActive' => (bool)$row->is_active,
                    'settings' => array()
                );
            }

            $groups[$group_id]['settings'][$row->setting_name] = $value;
        }

        wp_send_json_success(array_values($groups));
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_tx_badges_get_settings', 'tx_badges_get_settings');