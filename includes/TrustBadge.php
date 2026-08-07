<?php
namespace TrustBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TrustBadge {
	protected $loader;
	protected $plugin_name;
	protected $version;

	/**
	 * Groups already emitted this request, keyed by group ID.
	 *
	 * @var array<string, bool>
	 */
	private static $rendered = array();

	/**
	 * Forget which groups have been emitted.
	 *
	 * Only useful outside a normal page request - for tests, or for tooling
	 * that renders several previews in one process.
	 */
	public static function reset_rendered() {
		self::$rendered = array();
	}

	/**
	 * Badge group each supported integration hook renders.
	 *
	 * Registering these unconditionally is safe: the WooCommerce and EDD hooks
	 * simply never fire when those plugins are not installed. It also avoids
	 * loading wp-admin/includes/plugin.php on every front-end request just to
	 * call is_plugin_active().
	 *
	 * @var array<string, string>
	 */
	private static $integration_hooks = array(
		'woocommerce_after_add_to_cart_form'          => 'product_page',
		'woocommerce_cart_collaterals'                => 'cart',
		'woocommerce_before_cart'                     => 'cart',
		'woocommerce_blocks_cart_block_after_content' => 'cart',
		'edd_purchase_link_end'                       => 'product_page',
		'wp_footer'                                   => 'footer',
	);

	public function __construct() {
		$this->version     = TRUST_BADGES_VERSION;
		$this->plugin_name = 'trust-badges';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_rest_api();

		foreach ( self::$integration_hooks as $hook => $group_id ) {
			add_action(
				$hook,
				function () use ( $group_id ) {
					$this->handle_badge_display( $group_id );
				}
			);
		}

		add_shortcode( 'trust_badges', array( $this, 'render_shortcode' ) );// [trust_badges id="footer"]

		// One-off data migrations, gated on a stored schema version so they do
		// not run a query on every single page load.
		add_action( 'admin_init', array( $this, 'maybe_migrate' ) );
	}

	/**
	 * Run pending data migrations once per version.
	 *
	 * @since 1.2.0
	 */
	public function maybe_migrate() {
		if ( get_option( 'trust_badges_db_version' ) === TRUST_BADGES_VERSION ) {
			return;
		}

		$this->remove_checkout_badge_group();
		$this->backfill_cart_badge_group();

		update_option( 'trust_badges_db_version', TRUST_BADGES_VERSION, false );
	}

	/**
	 * Create the "cart" group on sites upgrading from a version that never had one.
	 *
	 * The cart hooks have always rendered the group with ID "cart", but no
	 * release ever created it, so those placements could not work. It is
	 * inserted **inactive** here: an existing site sees no front-end change, it
	 * just gains a group it can switch on from the settings screen.
	 *
	 * @since 1.2.0
	 */
	private function backfill_cart_badge_group() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'converswp_trust_badges';

		$exists = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . '` WHERE group_id = %s',
				'cart'
			)
		);

		if ( $exists ) {
			return;
		}

		$wpdb->insert(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table_name,
			array(
				'group_id'        => 'cart',
				'group_name'      => 'Cart',
				'is_default'      => 1,
				'is_active'       => 0,
				'required_plugin' => 'woocommerce',
				'settings'        => wp_json_encode( Activator::cart_group_settings() ),
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		Renderer::flush_cache();
	}

	/**
	 * Remove the checkout badge group from database
	 *
	 * The checkout placement was withdrawn in 1.1.0; this clears the leftover
	 * row on sites that were configured before then.
	 *
	 * @since 1.0.0
	 */
	private function remove_checkout_badge_group() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'converswp_trust_badges';

		$deleted = $wpdb->delete(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table_name,
			array( 'group_id' => 'checkout' ),
			array( '%s' )
		);

		if ( $deleted ) {
			Renderer::flush_cache( 'checkout' );
		}
	}

	/**
	 * Echo the badge markup for a group.
	 *
	 * Before 1.2.0 this took ( $hook, $group_id ). That signature is still
	 * accepted so any theme or plugin calling it directly keeps working.
	 *
	 * @param string      $group_id Badge group to render, or the hook name when
	 *                              called with the pre-1.2.0 signature.
	 * @param string|null $legacy   Deprecated. The group ID, when called with
	 *                              the pre-1.2.0 two-argument signature.
	 */
	public function handle_badge_display( $group_id, $legacy = null ) {
		if ( null !== $legacy ) {
			$group_id = $legacy;
		}

		// woocommerce_before_cart and woocommerce_cart_collaterals both fire on
		// the classic cart page and both map to the "cart" group. Render each
		// group at most once per request so the markup is not duplicated (which
		// would also repeat the element id and the inline stylesheet).
		if ( isset( self::$rendered[ $group_id ] ) ) {
			return;
		}

		$html = Renderer::renderBadgeById( $group_id );

		if ( ! $html ) {
			return;
		}

		self::$rendered[ $group_id ] = true;

		echo wp_kses( $html, self::allowed_badge_html() );
	}

	/**
	 * Markup the badge renderer is allowed to output.
	 *
	 * wp_kses_post() strips the CSS mask properties the "mono" badge styles
	 * depend on, so an explicit, narrower allowlist is used instead.
	 *
	 * @return array
	 */
	public static function allowed_badge_html() {
		$attrs = array(
			'id'    => true,
			'class' => true,
			'style' => true,
		);

		return array(
			'div' => $attrs,
			'img' => array_merge(
				$attrs,
				array(
					'src'      => true,
					'alt'      => true,
					'decoding' => true,
				)
			),
		);
	}

	// Function to render the trust badges shortcode
	public function render_shortcode( $attributes ) {
		// if doing ajax or rest api, we need to include the renderer
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '';
		}

		// Was an ID written in the shortcode itself, or are we falling back to
		// the default? Checked before shortcode_atts() fills the default in.
		$id_supplied = is_array( $attributes ) && isset( $attributes['id'] );

		// The default has always been "1", which is the ID the settings screen
		// assigns to the first custom group. Existing sites using a bare
		// [trust_badges] rely on it, so it must not change.
		$attributes = shortcode_atts(
			array(
				'id' => '1',
			),
			$attributes,
			'trust_badges'
		);

		$html = Renderer::renderBadgeById( $attributes['id'] );

		// Only when no ID was given and group "1" does not exist - a fresh
		// install that never created a custom group - fall back to the footer
		// group. Sites that do have a group "1", and anyone who wrote an
		// explicit ID, behave exactly as before.
		if ( ! $html && ! $id_supplied ) {
			$html = Renderer::renderBadgeById( 'footer' );
		}

		return $html ? wp_kses( $html, self::allowed_badge_html() ) : '';
	}

	private function load_dependencies() {
		$this->loader = new Loader();
	}

	private function define_admin_hooks() {
		// Add menu
		$this->loader->add_action( 'admin_menu', $this, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'admin_enqueue_scripts' );
	}

	private function define_rest_api() {
		$plugin_rest = new RESTAPI();
		$this->loader->add_action( 'rest_api_init', $plugin_rest, 'register_routes' );
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
			__( 'Trust Badges', 'trust-badges' ),
			__( 'Trust Badges', 'trust-badges' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_setup_page' )
		);
	}

	/**
	 * Render the settings page for this plugin.
	 */
	public function display_plugin_setup_page() {
		echo '<div id="trust-badges-app"></div>';
	}


	public static function admin_enqueue_scripts( $hook ) {
		// Only load on plugin admin page
		if ( 'settings_page_trust-badges' !== $hook ) {
			return;
		}

		try {
			// WordPress core scripts
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'wp-i18n' );
			wp_enqueue_script( 'wp-api-fetch' );

			// Check if main CSS file exists
			$css_file = TRUST_BADGES_PLUGIN_DIR . 'assets/css/main.css';
			if ( ! file_exists( $css_file ) ) {
				throw new \Exception( 'Required CSS file not found: ' . $css_file );
			}

			// Enqueue main CSS
			wp_enqueue_style(
				'trust-badges-admin',
				TRUST_BADGES_PLUGIN_URL . 'assets/css/main.css',
				array(),
				TRUST_BADGES_VERSION
			);

			// Check if main JS file exists
			$js_file = TRUST_BADGES_PLUGIN_DIR . 'assets/js/main.js';
			if ( ! file_exists( $js_file ) ) {
				throw new \Exception( 'Required JS file not found: ' . $js_file );
			}
			// Enqueue main JS with proper dependencies
			wp_enqueue_script(
				'trust-badges-admin',
				TRUST_BADGES_PLUGIN_URL . 'assets/js/main.js',
				array( 'jquery', 'wp-i18n', 'wp-api-fetch' ),
				TRUST_BADGES_VERSION,
				true
			);

			// Create nonce specifically for REST API
			$rest_nonce = wp_create_nonce( 'wp_rest' );

			// Configure REST API settings
			wp_localize_script(
				'wp-api-fetch',
				'wpApiSettings',
				array(
					'root'  => esc_url_raw( rest_url() ),
					'nonce' => $rest_nonce,
				)
			);

			// Plugin settings object with proper nonce
			$settings = array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'restUrl'     => rest_url( 'trust-badges/v1/' ),
				'pluginUrl'   => TRUST_BADGES_PLUGIN_URL,
				'mediaTitle'  => __( 'Select or Upload Badge Image', 'trust-badges' ),
				'mediaButton' => __( 'Use this image', 'trust-badges' ),
				'debug'       => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'restNonce'   => $rest_nonce, // Use the same REST nonce
			);

			// Add settings to window object before any scripts
			wp_add_inline_script(
				'wp-api-fetch',
				'window.txBadgesSettings = ' . wp_json_encode( $settings ) . ';',
				'before'
			);

		} catch ( \Exception $e ) {
			trust_badges_log_error( 'Script Enqueue Error: ' . $e->getMessage() );
			add_action(
				'admin_notices',
				function () use ( $e ) {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'Failed to load Trust Badges plugin resources. Please check error logs for details.', 'trust-badges' )
					);
				}
			);
		}
	}
}
