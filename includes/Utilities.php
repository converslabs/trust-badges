<?php
namespace TrustBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Utilities {
	/**
	 * Sanitize and validate IP address
	 */
	public static function sanitize_ip_address( $ip ) {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		return $ip ? $ip : '0.0.0.0';
	}

	/**
	 * Sanitize and validate nonce
	 */
	public static function sanitize_nonce( $nonce ) {
		return preg_replace( '/[^a-zA-Z0-9]/', '', (string) $nonce );
	}

	/**
	 * Get the client IP address.
	 *
	 * Only REMOTE_ADDR is trusted. The X-Forwarded-For family of headers is
	 * attacker-controlled unless the site sits behind a proxy that is known to
	 * overwrite them, so a site that does must opt in via the filter below.
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		/**
		 * Filters the detected client IP.
		 *
		 * Sites behind a trusted reverse proxy can use this to read the real
		 * client address out of the appropriate forwarded header.
		 *
		 * @param string $ip The IP derived from REMOTE_ADDR.
		 */
		$ip = apply_filters( 'trust_badges_client_ip', $ip );

		return self::sanitize_ip_address( $ip );
	}
}
