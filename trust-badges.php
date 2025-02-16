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
require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-renderer.php';

// Start the plugin
add_action( 'plugins_loaded', function () {
    require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges.php';
    $plugin = new TX_Badges();
    $plugin->run();
} );

// Plugin activation with improved error handling
register_activation_hook(__FILE__, ['TX_Badges_Activator', 'activate']);

// Add error logging function
function tx_badges_log_error($message, $context = []) {
    error_log(sprintf(
        '[TX Badges Error] %s | Context: %s',
        $message,
        json_encode($context)
    ));
}