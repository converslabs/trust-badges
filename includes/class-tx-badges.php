<?php

class TX_Badges {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = TX_BADGES_VERSION;
        $this->plugin_name = 'tx-badges';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_rest_api();

        // Add WooCommerce hooks if WooCommerce is active
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            add_action('woocommerce_after_add_to_cart_button', array($this, 'display_badges_after_add_to_cart'));
            add_action('woocommerce_before_add_to_cart_button', array($this, 'display_badges_before_add_to_cart'));
            add_action('woocommerce_review_order_before_payment', array($this, 'display_badges_on_checkout'));
        }
    }

    private function load_dependencies() {
        require_once TX_BADGES_PLUGIN_DIR . 'includes/class-tx-badges-loader.php';
        require_once TX_BADGES_PLUGIN_DIR . 'includes/class-tx-badges-i18n.php';
        // require_once TX_BADGES_PLUGIN_DIR . 'admin/class-tx-badges-admin.php';
        // require_once TX_BADGES_PLUGIN_DIR . 'public/class-tx-badges-public.php';
        require_once TX_BADGES_PLUGIN_DIR . 'includes/class-tx-badges-rest-api.php';

        $this->loader = new TX_Badges_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new TX_Badges_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        // $plugin_admin = new TX_Badges_Admin($this->get_plugin_name(), $this->get_version());

        // $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        // $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this, 'add_plugin_admin_menu');
    }

    private function define_public_hooks() {
        // $plugin_public = new TX_Badges_Public($this->get_plugin_name(), $this->get_version());

        // $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        // $this->loader->add_action('wp_0enqueue_scripts', $plugin_public, 'enqueue_scripts');
        // $this->loader->add_action('woocommerce_after_add_to_cart_form', $plugin_public, 'display_trust_badges');
    }

    private function define_rest_api() {
        $plugin_rest = new TX_Badges_REST_API();
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
        echo '<div id="tx-badges-app"></div>';
    }

    /**
     * Display badges after add to cart button
     */
    public function display_badges_after_add_to_cart() {
        $this->display_badges_by_position('showAfterAddToCart');
    }

    /**
     * Display badges before add to cart button
     */
    public function display_badges_before_add_to_cart() {
        $this->display_badges_by_position('showBeforeAddToCart');
    }

    /**
     * Display badges on checkout page
     */
    public function display_badges_on_checkout() {
        $this->display_badges_by_position('showOnCheckout');
    }

    /**
     * Helper function to display badges based on position
     */
    private function display_badges_by_position($position) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';

        // Get active WooCommerce badge groups
        $groups = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE is_active = 1 
                AND required_plugin = 'woocommerce'
                AND group_id = 'woocommerce'"
            )
        );

        if (empty($groups)) {
            return;
        }

        foreach ($groups as $group) {
            $settings = json_decode($group->settings, true);
            
            // Check if this position is enabled
            if (!isset($settings[$position]) || !$settings[$position]) {
                continue;
            }

            // Generate badge HTML
            $this->render_badges($settings);
        }
    }

    /**
     * Render badges with settings
     */
    private function render_badges($settings) {
        // Get alignment class
        $alignment_class = 'align-' . ($settings['badgeAlignment'] ?? 'center');
        $style_class = 'style-' . ($settings['badgeStyle'] ?? 'original');

        // Start badge container
        echo '<div class="convers-trust-badges ' . esc_attr($alignment_class) . '">';
        
        // Show header if enabled
        if (!empty($settings['showHeader'])) {
            echo '<div class="trust-badges-header" style="';
            echo 'font-size: ' . esc_attr($settings['fontSize']) . 'px;';
            echo 'color: ' . esc_attr($settings['textColor']) . ';';
            echo 'text-align: ' . esc_attr($settings['alignment']) . ';">';
            echo esc_html($settings['headerText']);
            echo '</div>';
        }

        // Start badges wrapper
        echo '<div class="trust-badges-wrapper ' . esc_attr($style_class) . '">';

        // Display selected badges
        if (!empty($settings['selectedBadges'])) {
            foreach ($settings['selectedBadges'] as $badge_id) {
                $badge_url = plugin_dir_url(dirname(__FILE__)) . 'assets/images/badges/' . $badge_id . '_color.svg';
                
                // Badge container with margins if custom margins are enabled
                echo '<div class="badge-container"' . $this->get_margin_style($settings) . '>';
                
                // For mono styles, use div with mask
                if (in_array($settings['badgeStyle'], ['mono', 'mono-card'])) {
                    echo '<div class="badge-image" style="';
                    echo '-webkit-mask: url(' . esc_url($badge_url) . ') center/contain no-repeat;';
                    echo 'mask: url(' . esc_url($badge_url) . ') center/contain no-repeat;';
                    echo 'background-color: ' . esc_attr($settings['badgeColor']) . ';';
                    echo '"></div>';
                } else {
                    echo '<img src="' . esc_url($badge_url) . '" alt="Trust Badge" class="badge-image" />';
                }
                
                echo '</div>';
            }
        }

        // Close badges wrapper
        echo '</div>';
        
        // Close badge container
        echo '</div>';

        // Add CSS for responsive sizes
        $this->add_responsive_styles($settings);
    }

    /**
     * Get margin style string if custom margins are enabled
     */
    private function get_margin_style($settings) {
        if (empty($settings['customMargin'])) {
            return '';
        }

        return sprintf(
            ' style="margin: %spx %spx %spx %spx;"',
            esc_attr($settings['marginTop']),
            esc_attr($settings['marginRight']),
            esc_attr($settings['marginBottom']),
            esc_attr($settings['marginLeft'])
        );
    }

    /**
     * Add responsive styles for badge sizes
     */
    private function add_responsive_styles($settings) {
        $desktop_size = $this->get_size_values($settings['badgeSizeDesktop']);
        $mobile_size = $this->get_size_values($settings['badgeSizeMobile']);

        echo '<style>
            .badge-image {
                width: ' . esc_attr($mobile_size) . 'px;
                height: ' . esc_attr($mobile_size) . 'px;
            }
            @media (min-width: 768px) {
                .badge-image {
                    width: ' . esc_attr($desktop_size) . 'px;
                    height: ' . esc_attr($desktop_size) . 'px;
                }
            }
        </style>';
    }

    /**
     * Convert size names to pixel values
     */
    private function get_size_values($size) {
        $sizes = [
            'extra-small' => 24,
            'small' => 32,
            'medium' => 48,
            'large' => 64
        ];

        return $sizes[$size] ?? 48;
    }
}
