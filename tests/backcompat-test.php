<?php
/**
 * Backward-compatibility test for Trust Badges 1.2.0.
 *
 * Asserts that everything an existing 1.1.0 install could rely on still
 * behaves identically:
 *
 *   - [trust_badges] with no ID resolves to group "1", as it always did.
 *   - [trust_badges id="1"] / id="2" resolve to the numeric group IDs the
 *     settings screen generates.
 *   - The pre-1.2.0 handle_badge_display( $hook, $group_id ) signature works.
 *   - Existing stored settings render without being rewritten.
 *   - Group IDs that existing installs actually contain are accepted on save.
 *
 * Run with WP-CLI from the WordPress root:
 *
 *     wp eval-file wp-content/plugins/trust-badges/tests/backcompat-test.php
 *
 * Creates and removes its own fixtures; leaves existing rows untouched.
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

$settings_fixture = array(
	'showHeader'       => true,
	'headerText'       => 'Legacy Group',
	'fontSize'         => '18',
	'alignment'        => 'center',
	'badgeAlignment'   => 'center',
	'textColor'        => '#000000',
	'badgeStyle'       => 'original',
	'badgeSizeDesktop' => 'medium',
	'badgeSizeMobile'  => 'small',
	'badgeColor'       => '#0066FF',
	'animation'        => 'fade',
	'selectedBadges'   => array( 'mastercardcolor', 'visa1color' ),
);

// Fixture groups named the way the settings screen names them: "1", "2", ...
$fixtures = array( 'tbcompat_1' => '1', 'tbcompat_2' => '2' );
$created  = array();

foreach ( $fixtures as $group_id ) {
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE group_id = %s", $group_id ) ); // phpcs:ignore WordPress.DB

	if ( ! $exists ) {
		$wpdb->insert( // phpcs:ignore WordPress.DB
			$table,
			array(
				'group_id'   => $group_id,
				'group_name' => 'Legacy ' . $group_id,
				'is_default' => 0,
				'is_active'  => 1,
				'settings'   => wp_json_encode( $settings_fixture ),
			),
			array( '%s', '%s', '%d', '%d', '%s' )
		);
		$created[] = $group_id;
	}
}

Renderer::flush_cache();

$cleanup = function () use ( $wpdb, $table, $created ) {
	foreach ( $created as $group_id ) {
		$wpdb->delete( $table, array( 'group_id' => $group_id ), array( '%s' ) ); // phpcs:ignore WordPress.DB
	}
	Renderer::flush_cache();
};

echo "== 1. numeric shortcode IDs (the only kind the settings screen creates) ==\n";
$check( '[trust_badges id="1"]', false !== strpos( do_shortcode( '[trust_badges id="1"]' ), 'convers-trust-badges-1' ) );
$check( '[trust_badges id="2"]', false !== strpos( do_shortcode( '[trust_badges id="2"]' ), 'convers-trust-badges-2' ) );

echo "\n== 2. bare [trust_badges] still means group \"1\" ==\n";
$bare = do_shortcode( '[trust_badges]' );
$check( '[trust_badges] resolves to group 1', false !== strpos( $bare, 'convers-trust-badges-1' ) );
$check( '[trust_badges] did not silently switch to footer', false === strpos( $bare, 'convers-trust-badges-footer' ) );

echo "\n== 3. stored settings render unchanged ==\n";
$html = do_shortcode( '[trust_badges id="1"]' );
$check( 'header text preserved', false !== strpos( $html, 'Legacy Group' ) );
$check( 'both badges rendered', 2 === substr_count( $html, '<img ' ) );
$check( 'badge images resolve', false !== strpos( $html, 'mastercardcolor.svg' ) && false !== strpos( $html, 'visa1color.svg' ) );

$stored = json_decode( $wpdb->get_var( $wpdb->prepare( "SELECT settings FROM `{$table}` WHERE group_id = %s", '1' ) ), true ); // phpcs:ignore WordPress.DB
$check( 'stored camelCase keys untouched by the reader', isset( $stored['headerText'], $stored['selectedBadges'], $stored['badgeSizeDesktop'] ) );

echo "\n== 4. pre-1.2.0 handle_badge_display signature ==\n";
$plugin = $GLOBALS['trust_badges_plugin'];
TrustBadge::reset_rendered();
ob_start();
$plugin->handle_badge_display( 'woocommerce_after_add_to_cart_form', '1' );
$legacy_out = ob_get_clean();
$check( 'handle_badge_display( $hook, $group_id )', false !== strpos( $legacy_out, 'convers-trust-badges-1' ) );

echo "\n== 5. saving a legacy numeric group is still accepted ==\n";
$users = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( $users ) {
	wp_set_current_user( $users[0]->ID );
	$_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce( 'wp_rest' );

	$request = new WP_REST_Request( 'POST', '/trust-badges/v1/settings/group' );
	$request->set_header( 'content-type', 'application/json' );
	$request->set_body(
		wp_json_encode(
			array(
				'group' => array(
					'id'       => '1',
					'name'     => 'Legacy 1',
					'isActive' => true,
					'settings' => $settings_fixture,
				),
			)
		)
	);

	$response = rest_get_server()->dispatch( $request );
	$check( 'POST /settings/group with id="1" succeeds', 200 === $response->get_status() );

	$after = json_decode( $wpdb->get_var( $wpdb->prepare( "SELECT settings FROM `{$table}` WHERE group_id = %s", '1' ) ), true ); // phpcs:ignore WordPress.DB
	$check( 'camelCase keys survive a save round-trip', isset( $after['headerText'], $after['selectedBadges'], $after['badgeSizeDesktop'] ) );
	$check( 'header text survives a save round-trip', 'Legacy Group' === ( $after['headerText'] ?? '' ) );
	$check( 'selected badges survive a save round-trip', array( 'mastercardcolor', 'visa1color' ) === ( $after['selectedBadges'] ?? array() ) );
} else {
	echo "  SKIP  no administrator user available\n";
}

echo "\n== 6. built-in group slugs still resolve ==\n";
foreach ( array( 'footer', 'cart', 'product_page' ) as $slug ) {
	$check( "resolve_group_id('{$slug}')", $slug === Renderer::resolve_group_id( $slug ) );
}

$cleanup();

printf( "\n%d passed, %d failed\n", $passed, $failed );

exit( $failed > 0 ? 1 : 0 );
