<?php

/**
 * Plugin Name: TX Trust Badges
 * Plugin URI: https://yourwebsite.com/tx-badges
 * Description: Display customizable trust badges on your website to boost visitor confidence and increase conversions.
 * Version: 1.0.0
 * Author: ThemeXpert
 * Author URI: https://yourwebsite.com
 * Text Domain: tx-badges
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TX_BADGES_VERSION', '1.0.0');
define('TX_BADGES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TX_BADGES_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin activation
register_activation_hook(__FILE__, 'tx_badges_activate');
function tx_badges_activate()
{
    require_once TX_BADGES_PLUGIN_DIR . 'includes/class-tx-badges-activator.php';
    TX_Badges_Activator::activate();
    flush_rewrite_rules();
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'tx_badges_deactivate');
function tx_badges_deactivate()
{
    require_once TX_BADGES_PLUGIN_DIR . 'includes/class-tx-badges-deactivator.php';
    TX_Badges_Deactivator::deactivate();
    flush_rewrite_rules();
}

// Initialize REST API
function tx_badges_init_rest_api()
{
    require_once TX_BADGES_PLUGIN_DIR . 'includes/class-tx-badges-rest-controller.php';
    $rest_controller = new TX_Badges_REST_Controller();
    $rest_controller->register_routes();
}
add_action('rest_api_init', 'tx_badges_init_rest_api');

// Initialize the plugin
function tx_badges_init()
{
    require_once TX_BADGES_PLUGIN_DIR . 'includes/class-tx-badges.php';
    $plugin = new TX_Badges();
    $plugin->run();
}
add_action('plugins_loaded', 'tx_badges_init');

// Enqueue admin scripts and styles
function tx_badges_admin_enqueue_scripts($hook)
{
    if ('toplevel_page_tx-badges' !== $hook) {
        return;
    }

    // WordPress core scripts
    wp_enqueue_media();
    wp_enqueue_script('jquery');
    wp_enqueue_script('wp-element');
    wp_enqueue_script('wp-components');
    wp_enqueue_script('wp-i18n');
    wp_enqueue_script('wp-api-fetch');

    // Enqueue main CSS
    wp_enqueue_style(
        'tx-badges-admin',
        TX_BADGES_PLUGIN_URL . 'dist/main.css',
        [],
        TX_BADGES_VERSION
    );

    // Add settings to window before any scripts
    wp_add_inline_script('wp-element', 'window.txBadgesSettings = ' . wp_json_encode([
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tx_badges_nonce'),
        'restUrl' => rest_url('tx-badges/v1'),
        'pluginUrl' => TX_BADGES_PLUGIN_URL,
        'mediaTitle' => __('Select or Upload Badge Image', 'tx-badges'),
        'mediaButton' => __('Use this image', 'tx-badges')
    ]) . ';', 'before');

    // Enqueue main JS
    wp_enqueue_script(
        'tx-badges-admin',
        TX_BADGES_PLUGIN_URL . 'dist/main.js',
        ['wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch'],
        TX_BADGES_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'tx_badges_admin_enqueue_scripts');

// Add admin menu
function tx_badges_admin_menu()
{
    add_menu_page(
        __('TX Trust Badges', 'tx-badges'),
        __('Trust Badges', 'tx-badges'),
        'manage_options',
        'tx-badges',
        'tx_badges_admin_page',
        'dashicons-shield',
        25
    );
}
add_action('admin_menu', 'tx_badges_admin_menu');

// Admin page callback
function tx_badges_admin_page()
{
    // Add a container for WordPress media scripts
    echo '<div id="tx-badges-app"></div>';
}

// Add shortcode for displaying badges
function tx_badges_shortcode($atts)
{
    ob_start();
    include TX_BADGES_PLUGIN_DIR . 'public/partials/tx-badges-public-display.php';
    return ob_get_clean();
}
add_shortcode('tx_badges', 'tx_badges_shortcode');

// AJAX handlers
function tx_badges_get_settings() {
    check_ajax_referer('tx_badges_nonce', 'nonce');

    try {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges_settings';
        
        $results = $wpdb->get_results("SELECT setting_name, setting_value FROM {$table_name} WHERE is_active = 1");
        
        if ($wpdb->last_error) {
            wp_send_json_error(['message' => $wpdb->last_error]);
            return;
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

        wp_send_json_success($settings);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_tx_badges_get_settings', 'tx_badges_get_settings');

function tx_badges_save_settings() {
    check_ajax_referer('tx_badges_nonce', 'nonce');

    try {
        if (empty($_POST['settings']) || !is_string($_POST['settings'])) {
            wp_send_json_error(['message' => 'Invalid settings data']);
            return;
        }

        $settings = json_decode(stripslashes($_POST['settings']), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Invalid JSON data']);
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tx_badges_settings';

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
                wp_send_json_error(['message' => sprintf('Failed to update setting: %s. Error: %s', $name, $wpdb->last_error)]);
                return;
            }
        }

        wp_send_json_success(['message' => 'Settings updated successfully']);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_tx_badges_save_settings', 'tx_badges_save_settings');
