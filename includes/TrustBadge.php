<?php
namespace TrustBadges;

class TrustBadge {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = TX_BADGES_VERSION;
        $this->plugin_name = 'trust-badges';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_rest_api();

        add_filter( 'woocommerce_should_load_cart_block', '__return_false' );
        add_filter( 'woocommerce_should_load_checkout_block', '__return_false' );

        // Add WooCommerce hooks if WooCommerce is active
        if (\is_plugin_active('woocommerce/woocommerce.php')) {
            // on woocommerce_after_add_to_cart_form call handle_badge_display and pass the hook and plugin name
            add_action('woocommerce_after_add_to_cart_form', function() {
                $this->handle_badge_display('woocommerce_after_add_to_cart_form', 'product_page');
            });

            add_action('woocommerce_pay_order_after_submit', function() {
                $this->handle_badge_display('woocommerce_pay_order_after_submit', 'checkout');
            });

        }

        // Add EDD hooks if EDD is active
        if (\is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')) {
            add_action('edd_purchase_link_end', function() {
                $this->handle_badge_display('edd_purchase_link_end', 'product_page');
            });
            add_action('edd_checkout_before_purchase_form', function() {
                $this->handle_badge_display('edd_checkout_before_purchase_form', 'checkout');
            });
        }

        // Add footer hook for displaying badges
        add_action('wp_footer', function() {
            $this->handle_badge_display('wp_footer', 'footer');
        });

        add_shortcode('trust_badges', array($this, 'render_shortcode'));// [trust_badges id="2"]
    }

    public function handle_badge_display($hook, $group_id) {
        // plugin can be woocommerce or edd
        if ($hook === 'woocommerce_after_add_to_cart_form') {
            echo $this->display_badges_by_position('showAfterAddToCart', $group_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } elseif ($hook === 'woocommerce_pay_order_after_submit') {
            echo $this->display_badges_by_position('checkoutBeforeOrderReview', $group_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } elseif ($hook === 'edd_purchase_link_end') {
            echo $this->display_badges_by_position('edd_purchase_link_end', $group_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } elseif ($hook === 'edd_checkout_before_purchase_form') {
            echo $this->display_badges_by_position('edd_checkout_before_purchase_form', $group_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo $this->display_badges_by_position($hook, $group_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    // Function to render the trust badges shortcode
    public function render_shortcode($attributes) {
        //if doing ajax or rest api, we need to include the renderer
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        // Extract the 'id' attribute, defaulting to 1 if not provided
        $attributes = shortcode_atts(array(
            'id' => '1'
        ), $attributes);

        // Sanitize and get the ID
        $id = intval($attributes['id']);

        // now get the badge from ids
        // Return the output with the dynamic ID
        return Renderer::renderBadgeById($id);
    }

    private function load_dependencies() {
        $this->loader = new Loader();
    }

    private function set_locale() {
        $plugin_i18n = new I18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        // Add menu
        $this->loader->add_action('admin_menu', $this, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $this, 'admin_enqueue_scripts');
    }

    private function define_rest_api() {
        $plugin_rest = new RESTAPI();
        $this->loader->add_action('rest_api_init', $plugin_rest, 'register_routes');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    /**
     * Add plugin admin menu
     */
    public function add_plugin_admin_menu() {
        add_options_page(
            __('Trust Badges', 'trust-badges'),
            __('Trust Badges', 'trust-badges'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page')
        );
    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_plugin_setup_page() {
        echo '<div id="trust-badges-app"></div>';
    }


    /**
     * Helper function to display badges based on position and plugin
     */
    private function display_badges_by_position($position, $group_id) {// wp_footer. footer
        if(!$group_id){
            $group_id = Renderer::getGroupIdByPosition($position);
        }

        return Renderer::renderBadgeById($group_id);
    }

    public static function admin_enqueue_scripts($hook)
    {
        // Only load on plugin admin page
        if ('settings_page_trust-badges' !== $hook) {
            return;
        }

        try {
            // WordPress core scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('wp-i18n');
            wp_enqueue_script('wp-api-fetch');

            // Check if main CSS file exists
            $css_file = TX_BADGES_PLUGIN_DIR . 'assets/css/main.css';
            if (!file_exists($css_file)) {
                throw new Exception('Required CSS file not found: ' . $css_file);
            }

            // Enqueue main CSS
            wp_enqueue_style(
                'trust-badges-admin',
                TX_BADGES_PLUGIN_URL . 'assets/css/main.css',
                [],
                TX_BADGES_VERSION
            );

            // Check if main JS file exists
            $js_file = TX_BADGES_PLUGIN_DIR . 'assets/js/main.js';
            if (!file_exists($js_file)) {
                throw new Exception('Required JS file not found: ' . $js_file);
            }
            // Enqueue main JS with proper dependencies
            wp_enqueue_script(
                'trust-badges-admin',
                TX_BADGES_PLUGIN_URL . 'assets/js/main.js',
                ['jquery', 'wp-i18n', 'wp-api-fetch'],
                TX_BADGES_VERSION,
                true
            );

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

        } catch (Exception $e) {
            cwp_trust_badges_log_error('Script Enqueue Error: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html__('Failed to load TX Badges plugin resources. Please check error logs for details.', 'trust-badges')
                );
            });
        }
    }
}
