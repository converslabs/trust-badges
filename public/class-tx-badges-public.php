<?php

/**
 * The public-facing functionality of the plugin.
 */
class TX_Badges_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/tx-badges-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/tx-badges-public.js', array('jquery'), $this->version, false);
    }

    /**
     * Display trust badges on the product page
     */
    public function display_trust_badges() {
        include_once('partials/tx-badges-public-display.php');
    }
}
