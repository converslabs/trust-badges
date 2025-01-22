<?php

/**
 * The admin-specific functionality of the plugin.
 */
class TX_Badges_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/tx-badges-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/tx-badges-admin.js', array('jquery'), $this->version, false);
        
        // Add AJAX URL and nonce to script
        wp_localize_script($this->plugin_name, 'txBadgesSettings', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tx_badges_nonce'),
            'pluginUrl' => plugin_dir_url(dirname(__FILE__)),
        ));
    }

    /**
     * Add plugin admin menu
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            __('TX Trust Badges', 'tx-badges'),
            __('Trust Badges', 'tx-badges'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-shield',
            25
        );
    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_plugin_setup_page() {
        include_once('partials/tx-badges-admin-display.php');
    }

    /**
     * Handle AJAX request to save settings
     */
    public function save_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tx_badges_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        // Get and sanitize settings
        $settings = json_decode(stripslashes($_POST['settings']), true);
        if ($settings === null) {
            wp_send_json_error('Invalid settings data');
        }

        // Save settings
        update_option('tx_trust_badges_settings', $settings);
        wp_send_json_success();
    }

    /**
     * Register AJAX actions
     */
    public function register_ajax_actions() {
        add_action('wp_ajax_save_tx_badges_settings', array($this, 'save_settings'));
    }
}
