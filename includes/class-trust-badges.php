<?php

class TX_Badges {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = TX_BADGES_VERSION;
        $this->plugin_name = 'trust-badges';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_rest_api();

        add_filter( 'woocommerce_should_load_cart_block', '__return_false' );
        add_filter( 'woocommerce_should_load_checkout_block', '__return_false' );

        // Add WooCommerce hooks if WooCommerce is active
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            // Product page hook
            add_action('woocommerce_after_add_to_cart_form', array($this, 'display_badges_product_page_woo'));
            
            // Checkout page hook
            add_action('woocommerce_checkout_before_order_review', array($this, 'display_badges_checkout_woo'));
        }

        // Add EDD hooks if EDD is active
        if (is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')) {
            // Product page hook
            add_action('edd_purchase_link_end', array($this, 'display_badges_product_page_edd'));
            // Checkout page hook
            add_action('edd_checkout_before_purchase_form', array($this, 'display_badges_checkout_edd'));
        }

        // Add footer hook for displaying badges
        add_action('wp_footer', array($this, 'display_footer_badges'));
    }

    private function load_dependencies() {
        require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-loader.php';
        require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-i18n.php';
        require_once TX_BADGES_PLUGIN_DIR . 'includes/class-trust-badges-rest-api.php';

        $this->loader = new TX_Badges_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new TX_Badges_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        // Add menu
        $this->loader->add_action('admin_menu', $this, 'add_plugin_admin_menu');
        
        // Add admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
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
        add_options_page(
            __('Trust Badges', 'trust-badges'),
            __('Trust Badges', 'trust-badges'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page')
        );
    }

    /**
     * Enqueue admin assets only on plugin page
     */
    public function enqueue_admin_assets($hook) {
        // Check if we're on the correct settings page
        if ('settings_page_trust-badges' !== $hook && 'toplevel_page_trust-badges' !== $hook) {
            return;
        }

        // Get plugin directory URL
        $plugin_url = plugin_dir_url(dirname(__FILE__));

        // Debug information
        error_log('Plugin URL: ' . $plugin_url);
        error_log('Current Hook: ' . $hook);
        error_log('CSS Path: ' . $plugin_url . 'dist/main.css');
        error_log('JS Path: ' . $plugin_url . 'dist/main.js');

        // Enqueue admin assets with version as timestamp for cache busting
        $version = defined('WP_DEBUG') && WP_DEBUG ? time() : $this->version;

        wp_enqueue_style(
            $this->plugin_name . '-admin',
            $plugin_url . 'dist/main.css',
            array(),
            $version
        );

        wp_enqueue_script(
            $this->plugin_name . '-admin',
            $plugin_url . 'dist/main.js',
            array('wp-element', 'wp-components', 'wp-i18n'),
            $version,
            true
        );

        // Add any localized script data if needed
        wp_localize_script(
            $this->plugin_name . '-admin',
            'trustBadgesData',
            array(
                'apiUrl' => rest_url('trust-badges/v1'),
                'nonce' => wp_create_nonce('wp_rest'),
                'pluginUrl' => $plugin_url,
                'isDebug' => defined('WP_DEBUG') && WP_DEBUG
            )
        );
    }

    /**
     * Render the settings page for this plugin.
     */
    public function display_plugin_setup_page() {
        echo '<div id="trust-badges-app"></div>';
    }

    /**
     * Display badges on WooCommerce product page
     */
    public function display_badges_product_page_woo() {
        $this->display_badges_by_position('showAfterAddToCart', 'woocommerce');
    }

    /**
     * Display badges on WooCommerce checkout
     */
    public function display_badges_checkout_woo() {
        $this->display_badges_by_position('checkoutBeforeOrderReview', 'woocommerce');
    }

    /**
     * Display badges on EDD product page
     */
    public function display_badges_product_page_edd() {
        $this->display_badges_by_position('edd_purchase_link_end', 'edd');
    }

    /**
     * Display badges on EDD checkout
     */
    public function display_badges_checkout_edd() {
        $this->display_badges_by_position('edd_checkout_before_purchase_form', 'edd');
    }

    /**
     * Helper function to display badges based on position and plugin
     */
    private function display_badges_by_position($position, $plugin) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';

        // Get the correct group based on position
        $group_id = '';
        switch ($position) {
            case 'showAfterAddToCart':
            case 'eddPurchaseLinkEnd':
                $group_id = 'product_page';
                break;
            case 'checkoutBeforeOrderReview':
            case 'eddCheckoutBeforePurchaseForm':
                $group_id = 'checkout';
                break;
            default:
                $group_id = 'footer';
        }

        $group = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE is_active = 1 
                AND group_id = %s",
                $group_id
            )
        );

        if (!$group) {
            return;
        }

        $settings = json_decode($group->settings, true);
        
        // Check plugin state and corresponding feature flag
        if ($plugin === 'woocommerce') {
            if (!isset($settings['woocommerce']) || !$settings['woocommerce']) {
                return;
            }
            
            // Check specific feature flag based on position
            if ($position === 'showAfterAddToCart' && !$settings['showAfterAddToCart']) {
                return;
            }
            if ($position === 'checkoutBeforeOrderReview' && !$settings['checkoutBeforeOrderReview']) {
                return;
            }
        } else if ($plugin === 'edd') {
            if (!isset($settings['edd']) || !$settings['edd']) {
                return;
            }
            
            // Check specific feature flag based on position
            if ($position === 'eddPurchaseLinkEnd' && !$settings['eddPurchaseLinkEnd']) {
                return;
            }
            if ($position === 'eddCheckoutBeforePurchaseForm' && !$settings['eddCheckoutBeforePurchaseForm']) {
                return;
            }
        }

        // Render badges with settings
        $this->render_badges($settings);
    }

    /**
     * Get alignment style value
     */
    private function get_alignment_style($alignment) {
        $styles = [
            'left' => 'flex-start',
            'center' => 'center',
            'right' => 'flex-end'
        ];
        return $styles[$alignment] ?? 'center';
    }

    /**
     * Get margin style string if custom margins are enabled
     */
    private function get_margin_style($settings) {
        if (empty($settings['customMargin'])) {
            return '';
        }

        $top = isset($settings['marginTop']) ? intval($settings['marginTop']) : 0;
        $right = isset($settings['marginRight']) ? intval($settings['marginRight']) : 0;
        $bottom = isset($settings['marginBottom']) ? intval($settings['marginBottom']) : 0;
        $left = isset($settings['marginLeft']) ? intval($settings['marginLeft']) : 0;

        return sprintf('margin: %dpx %dpx %dpx %dpx;',
            $top,
            $right,
            $bottom,
            $left
        );
    }

    /**
     * Get animation class based on settings
     */
    private function get_animation_class($animation) {
        if (empty($animation)) {
            return '';
        }
        return 'badge-' . esc_attr($animation);
    }

    /**
     * Get animation styles based on settings
     */
    private function get_animation_styles($animation) {
        if (empty($animation)) {
            return '';
        }

        $styles = '';
        
        // Base opacity for all animations
        $styles .= '.convers-trust-badges { opacity: 1; }';
        $styles .= '.badge-container { opacity: 0; }';
        
        // Animation definition based on type
        switch ($animation) {
            case 'fade':
                $styles .= '
                    .badge-fade .badge-container {
                        animation: badgeFadeIn 0.5s ease forwards;
                        animation-delay: calc(var(--badge-index, 0) * 0.1s);
                    }
                    @keyframes badgeFadeIn {
                        0% { opacity: 0; }
                        100% { opacity: 1; }
                    }
                ';
                break;
                
            case 'slide':
                $styles .= '
                    .badge-slide .badge-container {
                        transform: translateY(20px);
                        animation: badgeSlideIn 0.5s ease forwards;
                        animation-delay: calc(var(--badge-index, 0) * 0.1s);
                    }
                    @keyframes badgeSlideIn {
                        0% { 
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        100% {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                ';
                break;
                
            case 'scale':
                $styles .= '
                    .badge-scale .badge-container {
                        transform: scale(0.8);
                        animation: badgeScaleIn 0.5s ease forwards;
                        animation-delay: calc(var(--badge-index, 0) * 0.1s);
                    }
                    @keyframes badgeScaleIn {
                        0% {
                            opacity: 0;
                            transform: scale(0.8);
                        }
                        100% {
                            opacity: 1;
                            transform: scale(1);
                        }
                    }
                ';
                break;
                
            case 'bounce':
                $styles .= '
                    .badge-bounce .badge-container {
                        animation: badgeBounceIn 0.6s cubic-bezier(0.36, 0, 0.66, -0.56) forwards;
                        animation-delay: calc(var(--badge-index, 0) * 0.1s);
                    }
                    @keyframes badgeBounceIn {
                        0% {
                            opacity: 0;
                            transform: scale(0.3);
                        }
                        50% {
                            opacity: 0.9;
                            transform: scale(1.1);
                        }
                        80% {
                            opacity: 1;
                            transform: scale(0.89);
                        }
                        100% {
                            opacity: 1;
                            transform: scale(1);
                        }
                    }
                ';
                break;
        }
        
        return $styles;
    }

    /**
     * Render badges with settings
     */
    private function render_badges($settings) {
        // Log the incoming settings for debugging
        error_log('Rendering badges with settings: ' . print_r($settings, true));

        // Get exact alignment class from settings
        $alignment_class = 'align-' . ($settings['badgeAlignment'] ?? 'center');
        $style_class = 'style-' . ($settings['badgeStyle'] ?? 'original');
        $animation_class = $settings['animation'] ? $this->get_animation_class($settings['animation']) : '';

        // Get margin style if custom margin is enabled
        $margin_style = $this->get_margin_style($settings);

        // Get exact sizes
        $desktop_size = $this->get_size_values($settings['badgeSizeDesktop']);
        $mobile_size = $this->get_size_values($settings['badgeSizeMobile']);

        // Start badge container without margin style
        echo '<div class="convers-trust-badges ' . esc_attr($alignment_class) . ' ' . esc_attr($animation_class) . '">';
        
        // Show header if enabled with exact settings
        if (!empty($settings['showHeader'])) {
            echo '<div class="trust-badges-header" style="';
            echo 'font-size: ' . esc_attr($settings['fontSize']) . 'px;';
            echo 'color: ' . esc_attr($settings['textColor']) . ';';
            echo 'text-align: ' . esc_attr($settings['alignment']) . ';';
            if (!empty($settings['customStyles'])) {
                echo esc_attr($settings['customStyles']);
            }
            echo '">';
            echo esc_html($settings['headerText']);
            echo '</div>';
        }

        // Start badges wrapper
        echo '<div class="trust-badges-wrapper ' . esc_attr($style_class) . '" style="';
        echo 'display: flex;';
        echo 'flex-wrap: wrap;';
        echo 'gap: 10px;';
        echo 'justify-content: ' . $this->get_alignment_style($settings['badgeAlignment'] ?? 'center') . ';';
        echo 'align-items: center;';
        echo '">';

        // Display selected badges with exact settings
        if (!empty($settings['selectedBadges'])) {
            foreach ($settings['selectedBadges'] as $index => $badge_id) {
                $filename = $this->get_badge_filename($badge_id);
                $badge_url = plugins_url('assets/images/badges/' . $filename, dirname(__FILE__));
                
                // Add badge index and margin style to each badge container
                echo '<div class="badge-container" style="--badge-index: ' . esc_attr($index) . ';' . $margin_style . '">';
                
                if (in_array($settings['badgeStyle'], ['mono', 'mono-card'])) {
                    echo '<div class="badge-image" style="';
                    echo '-webkit-mask: url(' . esc_url($badge_url) . ') center/contain no-repeat;';
                    echo 'mask: url(' . esc_url($badge_url) . ') center/contain no-repeat;';
                    echo 'background-color: ' . esc_attr($settings['badgeColor']) . ';';
                    echo 'width: ' . esc_attr($mobile_size) . 'px;';
                    echo 'height: ' . esc_attr($mobile_size) . 'px;';
                    echo 'transition: all 0.3s ease;';
                    echo '"></div>';
                } else {
                    echo '<img src="' . esc_url($badge_url) . '" alt="converswp-trust-badge" class="badge-image" style="';
                    echo 'width: ' . esc_attr($mobile_size) . 'px;';
                    echo 'height: auto;';
                    echo 'max-height: ' . esc_attr($mobile_size) . 'px;';
                    echo 'transition: all 0.3s ease;';
                    echo 'object-fit: contain;';
                    echo '" />';
                }
                
                echo '</div>';
            }
        }

        echo '</div>'; // Close badges wrapper
        echo '</div>'; // Close badge container

        // Add responsive styles with exact sizes
        $this->add_responsive_styles($settings);
    }

    /**
     * Add responsive styles for badge sizes
     */
    private function add_responsive_styles($settings) {
        $desktop_size = $this->get_size_values($settings['badgeSizeDesktop']);
        $mobile_size = $this->get_size_values($settings['badgeSizeMobile']);
        $animation = isset($settings['animation']) ? $settings['animation'] : '';

        // Get animation styles based on settings
        $animation_styles = $this->get_animation_styles($animation);

        // Get design settings from database
        $badge_padding = isset($settings['badgePadding']) ? intval($settings['badgePadding']) : 5;
        $badge_gap = isset($settings['badgeGap']) ? intval($settings['badgeGap']) : 10;
        $container_margin = isset($settings['containerMargin']) ? intval($settings['containerMargin']) : 15;
        $border_radius = isset($settings['borderRadius']) ? intval($settings['borderRadius']) : 4;
        $hover_transform = isset($settings['hoverTransform']) ? $settings['hoverTransform'] : 'translateY(-2px)';
        $transition = isset($settings['transition']) ? $settings['transition'] : 'all 0.3s ease';

        echo '<style>
            .convers-trust-badges {
                margin: ' . $container_margin . 'px 0;
                width: 100%;
            }
            .trust-badges-wrapper {
                display: flex;
                flex-wrap: wrap;
                gap: ' . $badge_gap . 'px;
                align-items: center;
                width: 100%;
            }
            .badge-container {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: ' . $badge_padding . 'px;
                transition: ' . esc_attr($transition) . ';
            }
            
            /* Mobile styles (default) */
            .badge-image {
                width: ' . esc_attr($mobile_size) . 'px !important;
                height: auto !important;
                max-height: ' . esc_attr($mobile_size) . 'px !important;
                transition: ' . esc_attr($transition) . ';
                object-fit: contain;
            }
            
            .style-mono .badge-image,
            .style-mono-card .badge-image {
                width: ' . esc_attr($mobile_size) . 'px !important;
                height: ' . esc_attr($mobile_size) . 'px !important;
                -webkit-mask-size: contain;
                mask-size: contain;
                -webkit-mask-repeat: no-repeat;
                mask-repeat: no-repeat;
                -webkit-mask-position: center;
                mask-position: center;
                background-color: ' . esc_attr($settings['badgeColor']) . ';
            }
            
            /* Desktop styles */
            @media screen and (min-width: 768px) {
                .badge-image {
                    width: ' . esc_attr($desktop_size) . 'px !important;
                    height: auto !important;
                    max-height: ' . esc_attr($desktop_size) . 'px !important;
                }
                
                .style-mono .badge-image,
                .style-mono-card .badge-image {
                    width: ' . esc_attr($desktop_size) . 'px !important;
                    height: ' . esc_attr($desktop_size) . 'px !important;
                }
            }
            
            /* Hover effects */
            .badge-container:hover {
                transform: ' . esc_attr($hover_transform) . ';
            }
            .badge-container:hover .badge-image {
                transform: scale(1.05);
            }
            
            /* Card styles */
            .style-card .badge-container,
            .style-mono-card .badge-container {
                background-color: #e5e7eb;
                padding: ' . ($badge_padding + 3) . 'px ' . ($badge_padding + 7) . 'px;
                border-radius: ' . esc_attr($border_radius) . 'px;
            }
            
            /* Alignment */
            .align-left .trust-badges-wrapper { justify-content: flex-start; }
            .align-center .trust-badges-wrapper { justify-content: center; }
            .align-right .trust-badges-wrapper { justify-content: flex-end; }

            /* Animation styles */
            ' . $animation_styles . '
        </style>';
    }

    /**
     * Convert size names to pixel values
     */
    private function get_size_values($size) {
        $sizes = [
            'extra-small' => 32,
            'small' => 48,
            'medium' => 64,
            'large' => 80
        ];

        return $sizes[$size] ?? 48;
    }

    private function get_badge_filename($badge_id) {
        // Implement the logic to determine the correct filename based on the badge_id
        // This is a placeholder and should be replaced with the actual implementation
        return str_replace('-', '_', $badge_id) . '.svg';
    }

    /**
     * Display badges in footer
     */
    public function display_footer_badges() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'converswp_trust_badges';

        // Get active footer badge group
        $group = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                WHERE is_active = 1 
                AND group_id = %s",
                'footer'
            )
        );

        if (!$group || !$group->settings) {
            return;
        }

        // Decode settings
        $settings = json_decode($group->settings, true);
        
        // Get position from settings (left, center, right)
        $position = isset($settings['position']) ? $settings['position'] : 'center';
        
        // Create container with position class
        echo '<div class="convers-trust-badges-footer">';
        $this->render_badges($settings);
        echo '</div>';

        // Add footer-specific styles with position
        $this->add_footer_styles($position);
    }

    /**
     * Add footer-specific styles
     */
    private function add_footer_styles($position) {
        echo '<style>
            .convers-trust-badges-footer {
                width: 100%;
                padding: 20px;
            }
            
            .convers-trust-badges-footer .trust-badges-wrapper {
                justify-content: ' . $this->get_position_style($position) . ';
            }
            
            @media screen and (max-width: 768px) {
                .convers-trust-badges-footer {
                    padding: 15px;
                }
            }
        </style>';
    }

    /**
     * Get position style value
     */
    private function get_position_style($position) {
        $styles = [
            'left' => 'flex-start',
            'center' => 'center',
            'right' => 'flex-end'
        ];
        return $styles[$position] ?? 'center';
    }
}
