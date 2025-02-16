<?php

class TX_Badges_Utilities
{

    public static function tx_badges_admin_enqueue_scripts($hook)
    {
        // Only load on plugin admin page
        if ('settings_page_trust-badges' !== $hook) {
            return;
        }

        try {
            // WordPress core scripts
            wp_enqueue_media();
            wp_enqueue_script('jquery');
            wp_enqueue_script('wp-i18n');
            wp_enqueue_script('wp-api-fetch');

            // Check if main CSS file exists
            $css_file = TX_BADGES_PLUGIN_DIR . 'dist/main.css';
            if (!file_exists($css_file)) {
                throw new Exception('Required CSS file not found: ' . $css_file);
            }

            // Enqueue main CSS
            wp_enqueue_style(
                'trust-badges-admin',
                TX_BADGES_PLUGIN_URL . 'dist/main.css',
                [],
                TX_BADGES_VERSION
            );

            // Check if main JS file exists
            $js_file = TX_BADGES_PLUGIN_DIR . 'dist/main.js';
            if (!file_exists($js_file)) {
                throw new Exception('Required JS file not found: ' . $js_file);
            }

            // Create nonce specifically for REST API
            $rest_nonce = wp_create_nonce('wp_rest');

            // Configure REST API settings
            wp_localize_script('wp-api-fetch', 'wpApiSettings', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => $rest_nonce
            ]);

            // Plugin settings object with proper nonce
            $settings = [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url('trust-badges/v1/'),
                'pluginUrl' => TX_BADGES_PLUGIN_URL,
                'mediaTitle' => __('Select or Upload Badge Image', 'trust-badges'),
                'mediaButton' => __('Use this image', 'trust-badges'),
                'debug' => WP_DEBUG,
                'restNonce' => $rest_nonce // Use the same REST nonce
            ];

            // Add settings to window object before any scripts
            wp_add_inline_script(
                'wp-api-fetch',
                'window.txBadgesSettings = ' . wp_json_encode($settings) . ';',
                'before'
            );

            // Enqueue main JS with proper dependencies
            wp_enqueue_script(
                'trust-badges-admin',
                TX_BADGES_PLUGIN_URL . 'dist/main.js',
                ['wp-api-fetch', 'wp-i18n'],
                TX_BADGES_VERSION,
                true
            );

        } catch (Exception $e) {
            tx_badges_log_error('Script Enqueue Error: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html__('Failed to load TX Badges plugin resources. Please check error logs for details.', 'trust-badges')
                );
            });
        }
    }

}