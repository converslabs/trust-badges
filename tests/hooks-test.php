<?php
/**
 * Integration-hook test for Trust Badges.
 *
 * Verifies that every WooCommerce / EDD / footer hook is registered and renders
 * the badge group it is mapped to, that the shortcode resolves its IDs, and
 * that inactive groups render nothing.
 *
 * These hooks are registered unconditionally (the plugin no longer gates them
 * behind is_plugin_active()), so this also asserts that the EDD hook is present
 * on a site where EDD is not installed.
 *
 * Run with WP-CLI from the WordPress root:
 *
 *     wp eval-file wp-content/plugins/trust-badges/tests/hooks-test.php
 *
 * Exits 0 when everything passes, 1 otherwise. The test restores whatever
 * is_active flags it changed before exiting.
 *
 * @package TrustBadges
 */

use TrustBadges\Renderer;
use TrustBadges\TrustBadge;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

global $wpdb;

$table  = $wpdb->prefix . 'converswp_trust_badges';
$passed = 0;
$failed = 0;

$check = function ( $label, $condition ) use ( &$passed, &$failed ) {
	if ( $condition ) {
		++$passed;
		printf( "  PASS  %s\n", $label );
	} else {
		++$failed;
		printf( "  FAIL  %s\n", $label );
	}
};

// Badge group each integration hook is expected to render.
$hooks = array(
	'woocommerce_after_add_to_cart_form'          => 'product_page',
	'woocommerce_cart_collaterals'                => 'cart',
	'woocommerce_before_cart'                     => 'cart',
	'woocommerce_blocks_cart_block_after_content' => 'cart',
	'edd_purchase_link_end'                       => 'product_page',
	'wp_footer'                                   => 'footer',
);

$groups = array_values( array_unique( array_values( $hooks ) ) );

// Remember the current flags so the site is left exactly as it was found.
$original = array();
foreach ( $groups as $group ) {
	$original[ $group ] = $wpdb->get_var( $wpdb->prepare( "SELECT is_active FROM `{$table}` WHERE group_id = %s", $group ) ); // phpcs:ignore WordPress.DB
}

$restore = function () use ( $wpdb, $table, $original ) {
	foreach ( $original as $group => $was_active ) {
		if ( null !== $was_active ) {
			$wpdb->update( $table, array( 'is_active' => (int) $was_active ), array( 'group_id' => $group ) ); // phpcs:ignore WordPress.DB
		}
	}
	Renderer::flush_cache();
};

// Activate every group under test so rendering is observable.
foreach ( $groups as $group ) {
	$wpdb->update( $table, array( 'is_active' => 1 ), array( 'group_id' => $group ) ); // phpcs:ignore WordPress.DB
}
Renderer::flush_cache();

echo "== 1. hook registration ==\n";
foreach ( $hooks as $hook => $group ) {
	$check( "registered: {$hook}", false !== has_action( $hook ) );
}

echo "\n== 2. integration hooks register regardless of plugin presence ==\n";
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$edd_active = is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' );
printf( "  (EDD is %s on this site)\n", $edd_active ? 'active' : 'inactive' );
$check( 'edd_purchase_link_end registered', false !== has_action( 'edd_purchase_link_end' ) );

echo "\n== 3. each hook renders its mapped group ==\n";
// Reuse the instance the plugin already built on plugins_loaded. Constructing a
// second TrustBadge would re-run add_action() for all six hooks and
// add_shortcode(), leaving the process doubly hooked.
$plugin = $GLOBALS['trust_badges_plugin'];
foreach ( $hooks as $hook => $group ) {
	TrustBadge::reset_rendered();

	ob_start();
	$plugin->handle_badge_display( $group );
	$output = ob_get_clean();

	$check(
		sprintf( '%-45s -> #convers-trust-badges-%s', $hook, $group ),
		false !== strpos( $output, "convers-trust-badges-{$group}" )
	);
}

echo "\n== 3b. a group is emitted only once per request ==\n";
// woocommerce_before_cart and woocommerce_cart_collaterals both fire on the
// classic cart page; the badge block must not be duplicated.
TrustBadge::reset_rendered();
ob_start();
$plugin->handle_badge_display( 'cart' );
$first = ob_get_clean();
ob_start();
$plugin->handle_badge_display( 'cart' );
$second = ob_get_clean();
$check( 'first cart hook renders', false !== strpos( $first, 'convers-trust-badges-cart' ) );
$check( 'second cart hook renders nothing', '' === $second );

echo "\n== 3c. pre-1.2.0 two-argument call signature still works ==\n";
TrustBadge::reset_rendered();
ob_start();
$plugin->handle_badge_display( 'woocommerce_before_cart', 'cart' );
$legacy = ob_get_clean();
$check( 'handle_badge_display( $hook, $group_id )', false !== strpos( $legacy, 'convers-trust-badges-cart' ) );

echo "\n== 4. shortcode ==\n";
foreach ( $groups as $group ) {
	$check(
		"[trust_badges id=\"{$group}\"]",
		false !== strpos( do_shortcode( "[trust_badges id=\"{$group}\"]" ), "convers-trust-badges-{$group}" )
	);
}
$check(
	'[trust_badges] defaults to footer',
	false !== strpos( do_shortcode( '[trust_badges]' ), 'convers-trust-badges-footer' )
);
$check( '[trust_badges id="bogus"] renders nothing', '' === do_shortcode( '[trust_badges id="bogus"]' ) );
$check(
	'shortcode output contains no script tag',
	false === strpos( do_shortcode( '[trust_badges id="footer"]' ), '<script' )
);

echo "\n== 5. inactive group renders nothing ==\n";
$wpdb->update( $table, array( 'is_active' => 0 ), array( 'group_id' => 'cart' ) ); // phpcs:ignore WordPress.DB
Renderer::flush_cache();
ob_start();
$plugin->handle_badge_display( 'cart' );
$check( 'inactive group emits no output', '' === ob_get_clean() );

$restore();

printf( "\n%d passed, %d failed\n", $passed, $failed );

exit( $failed > 0 ? 1 : 0 );
