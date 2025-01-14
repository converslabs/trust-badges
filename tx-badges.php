<?php

/**
 * Plugin Name: TX Trust Badges
 * Plugin URI: https://yourwebsite.com/tx-badges
 * Description: Display customizable trust badges on your WordPress site to boost customer confidence.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: tx-badges
 * Domain Path: /languages
 * Requires at least: 5.8
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

    // Enqueue main JS with proper dependencies
    wp_enqueue_script(
        'tx-badges-admin',
        TX_BADGES_PLUGIN_URL . 'dist/main.js',
        ['jquery', 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch'],
        TX_BADGES_VERSION,
        true
    );

    // Localize script with proper object format
    wp_localize_script('tx-badges-admin', 'txBadgesSettings', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_rest'),
        'restUrl' => rest_url('tx-badges/v1'),
        'pluginUrl' => TX_BADGES_PLUGIN_URL,
        'mediaTitle' => __('Select or Upload Badge Image', 'tx-badges'),
        'mediaButton' => __('Use this image', 'tx-badges')
    ));
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
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Trust Badges', 'tx-badges') . '</h1>';
    echo '<div id="tx-badges-app"></div>';
    echo '</div>';
}

// Add shortcode for displaying badges
function tx_badges_shortcode($atts)
{
    ob_start();
    include TX_BADGES_PLUGIN_DIR . 'public/partials/tx-badges-public-display.php';
    return ob_get_clean();
}
add_shortcode('tx_badges', 'tx_badges_shortcode');
