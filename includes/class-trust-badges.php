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
        // $this->define_public_hooks();
        $this->define_rest_api();

        add_filter( 'woocommerce_should_load_cart_block', '__return_false' );
        add_filter( 'woocommerce_should_load_checkout_block', '__return_false' );

        // Add WooCommerce hooks if WooCommerce is active
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            // on woocommerce_after_add_to_cart_form call handle_badge_display and pass the hook and plugin name
            add_action('woocommerce_after_add_to_cart_form', function() {
                $this->handle_badge_display('woocommerce_after_add_to_cart_form', 'product_page');
            });

            add_action('woocommerce_pay_order_after_submit', function() {
                $this->handle_badge_display('woocommerce_pay_order_after_submit', 'checkout');
            });
            
        }

        // Add EDD hooks if EDD is active
        if (is_plugin_active('easy-digital-downloads/easy-digital-downloads.php')) {
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
        // Extract the 'id' attribute, defaulting to 1 if not provided
        $attributes = shortcode_atts(array(
            'id' => '1'
        ), $attributes);

        // Sanitize and get the ID
        $id = intval($attributes['id']);

        // now get the badge from ids
        $output = $this->getBadgesById($id);

        // Return the output with the dynamic ID
        return $output;
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
            $group_id = TX_Badges_Renderer::getGroupIdByPosition($position);
        }

        return $this->getBadgesById($group_id);
    }



    public function getBadgesById($group_id = '') {
        if($group_id == '') {
            tx_badges_log_error('Group ID is empty', ['group_id' => $group_id]);
            return false;
        }

        $settings = TX_Badges_Renderer::getBadgeByGroup($group_id);
        if(empty($settings)) {
            tx_badges_log_error('Group settings not found.', ['group_id' => $group_id, 'settings' => $settings]);
            return false;
        }

        // @todo: add translation for the header text
        if($settings->is_active){
            // Render badges with settings
            $badgeHtml = TX_Badges_Renderer::render_badges($settings->settings);
        } else {
            tx_badges_log_error('Badge is disabled.', ['settings' => $settings]);
            return false;
        }

        // Create container with position class
        $html =  '<div id="convers-trust-badges-'.$group_id.'">';
        $html .= $badgeHtml;
        $html .= '</div>';

        // Add footer-specific styles with position. Get position from settings (left, center, right)
        $position = $settings->settings['position'] ?? 'center';
        $this->add_footer_styles($position);

        return $html;
    }

    /**
     * Display badges in footer
     */
    public function display_footer_badges() {
        echo $this->getBadgesById('footer'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function add_footer_styles($position) {
        // Sanitize the position value
        $position = sanitize_key($position); // Ensures it's a safe CSS value
    
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
        echo '<style>
            .convers-trust-badges-footer {
                width: 100%;
                padding: 20px;
            }
            
            .convers-trust-badges-footer .trust-badges-wrapper {
                justify-content: ' . esc_attr($this->get_position_style($position)) . ';
            }
            
            @media screen and (max-width: 768px) {
                .convers-trust-badges-footer {
                    padding: 15px;
                }
            }
        </style>';
        // phpcs:enable
    }
    
    /**
     * Helper method to sanitize and return position styles.
     *
     * @param string $position The alignment position.
     * @return string Sanitized CSS value for justify-content.
     */
    private function get_position_style($position) {
        // Define allowed positions and their corresponding CSS values
        $allowed_positions = array(
            'left'   => 'flex-start',
            'center' => 'center',
            'right'  => 'flex-end',
        );
    
        // Return sanitized value or default to 'center' if invalid
        return isset($allowed_positions[$position]) ? $allowed_positions[$position] : 'center';
    }

    /**
     * Handle the product page shortcode
     */
    public function product_shortcode($atts) {
        // Start output buffering to capture the rendered badges
        ob_start();

        // Get the current page ID
        $current_page_id = get_the_ID();

        // Check if we're on a WooCommerce product page
        $is_woo_product = function_exists('is_product') && is_product();
        
        // Check if we're on an EDD download page
        $is_edd_download = function_exists('is_singular') && is_singular('download');

        // Only proceed if we're on a product/download page
        if ($is_woo_product || $is_edd_download) {
            $this->display_badges_by_position('showAfterAddToCart', $is_woo_product ? 'woocommerce' : 'edd');
        }

        // Return the captured output
        return ob_get_clean();
    }

    /**
     * Handle the checkout shortcode
     */
    public function checkout_shortcode($atts) {
        // Start output buffering to capture the rendered badges
        ob_start();

        // Check if we're on a WooCommerce checkout page
        $is_woo_checkout = function_exists('is_checkout') && is_checkout();
        
        // Check if we're on an EDD checkout page
        $is_edd_checkout = function_exists('edd_is_checkout') && edd_is_checkout();

        // Only proceed if we're on a checkout page
        if ($is_woo_checkout || $is_edd_checkout) {
            $this->display_badges_by_position('checkoutBeforeOrderReview', $is_woo_checkout ? 'woocommerce' : 'edd');
        }

        // Return the captured output
        return ob_get_clean();
    }
}
