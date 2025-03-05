<?php
namespace TrustBadges;

class Activator {

	public static function activate() {
		// Start output buffering to catch any unexpected output
		ob_start();

		try {
			// Ensure database tables are created
			self::updateDB();

			flush_rewrite_rules();
			// Clean the buffer and discard any output
			ob_end_clean();
		} catch ( Exception $e ) {
			// Clean the buffer and log the error
			ob_end_clean();
			trust_badges_log_error( 'Plugin Activation Error: ' . $e->getMessage() );
			wp_die( 'Failed to activate Trust Badges plugin. Please check error logs for details.' );
		}
	}

	public static function updateDB() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'converswp_trust_badges';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id varchar(50) NOT NULL,
            group_name varchar(255) NOT NULL,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            required_plugin varchar(50) DEFAULT NULL,
            settings longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY group_id (group_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Check if default groups are already cached
		$cache_key            = 'trust_badges_default_groups';
		$default_groups_exist = wp_cache_get( $cache_key );

		if ( false === $default_groups_exist ) {
			// Check if any default groups exist in database
			$existing_groups = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					'SELECT COUNT(*) FROM `' . $wpdb->prefix . 'converswp_trust_badges` WHERE is_default = %d',
					1
				)
			);

			if ( $existing_groups > 0 ) {
				wp_cache_set( $cache_key, true, '', HOUR_IN_SECONDS );
				return;
			}
		} else {
			return;
		}

		// Check if required plugins are active
		$woocommerce_active = is_plugin_active( 'woocommerce/woocommerce.php' );
		$edd_active         = is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' );

		// Insert default groups if they don't exist
		$default_groups = array(
			array(
				'group_id'        => 'checkout',
				'group_name'      => 'Checkout',
				'is_default'      => 1,
				'is_active'       => $woocommerce_active || $edd_active ? 1 : 0,
				'required_plugin' => 'woocommerce',
				'settings'        => json_encode(
					array(
						'showHeader'                    => true,
						'headerText'                    => 'Secure Checkout With',
						'fontSize'                      => '18',
						'alignment'                     => 'left',
						'badgeAlignment'                => 'left',
						'position'                      => 'center',
						'textColor'                     => '#000000',
						'badgeStyle'                    => 'original',
						'badgeSizeDesktop'              => 'medium',
						'badgeSizeMobile'               => 'small',
						'badgeColor'                    => '#0066FF',
						'customMargin'                  => false,
						'marginTop'                     => '0',
						'marginBottom'                  => '0',
						'marginLeft'                    => '0',
						'marginRight'                   => '0',
						'animation'                     => 'fade',
						'woocommerce'                   => $woocommerce_active,
						'edd'                           => $edd_active,
						'checkoutBeforeOrderReview'     => $woocommerce_active,
						'eddCheckoutBeforePurchaseForm' => $edd_active,
						'selectedBadges'                => array(
							'mastercardcolor',
							'visa1color',
							'paypal1color',
							'applepaycolor',
							'stripecolor',
							'amazonpay2color',
							'americanexpress1color',
						),
					)
				),
			),
			array(
				'group_id'        => 'product_page',
				'group_name'      => 'Product Page',
				'is_default'      => 1,
				'is_active'       => $woocommerce_active || $edd_active ? 1 : 0,
				'required_plugin' => 'woocommerce',
				'settings'        => json_encode(
					array(
						'showHeader'         => true,
						'headerText'         => 'Secure Payment Methods',
						'fontSize'           => '18',
						'alignment'          => 'left',
						'badgeAlignment'     => 'center',
						'position'           => 'center',
						'textColor'          => '#000000',
						'badgeStyle'         => 'original',
						'badgeSizeDesktop'   => 'medium',
						'badgeSizeMobile'    => 'small',
						'badgeColor'         => '#0066FF',
						'customMargin'       => false,
						'marginTop'          => '0',
						'marginBottom'       => '0',
						'marginLeft'         => '0',
						'marginRight'        => '0',
						'animation'          => 'fade',
						'woocommerce'        => $woocommerce_active,
						'edd'                => $edd_active,
						'showAfterAddToCart' => $woocommerce_active,
						'eddPurchaseLinkEnd' => $edd_active,
						'selectedBadges'     => array(
							'mastercardcolor',
							'visa1color',
							'paypal1color',
						),
					)
				),
			),
			array(
				'group_id'        => 'footer',
				'group_name'      => 'Footer',
				'is_default'      => 1,
				'is_active'       => 1,
				'required_plugin' => null,
				'settings'        => json_encode(
					array(
						'showHeader'       => true,
						'headerText'       => 'Payment Options',
						'fontSize'         => '18',
						'alignment'        => 'center',
						'badgeAlignment'   => 'center',
						'position'         => 'center',
						'textColor'        => '#000000',
						'badgeStyle'       => 'original',
						'badgeSizeDesktop' => 'medium',
						'badgeSizeMobile'  => 'small',
						'badgeColor'       => '#0066FF',
						'customMargin'     => false,
						'marginTop'        => '0',
						'marginBottom'     => '0',
						'marginLeft'       => '0',
						'marginRight'      => '0',
						'animation'        => 'fade',
						'selectedBadges'   => array(
							'mastercardcolor',
							'visa1color',
							'paypal1color',
						),
					)
				),
			),
		);

		foreach ( $default_groups as $group ) {
			$wpdb->replace(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table_name,
				array_map( 'sanitize_text_field', $group ),
				array( '%s', '%s', '%d', '%d', '%s', '%s' )
			);
		}

		// Cache that default groups have been inserted
		wp_cache_set( $cache_key, true, '', HOUR_IN_SECONDS );
	}
}
