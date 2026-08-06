<?php
namespace TrustBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renderer {

	/**
	 * Object cache group used for every cache entry this plugin writes.
	 */
	const CACHE_GROUP = 'trust_badges';

	/**
	 * In-request memo of the badge id => filename map.
	 *
	 * @var array|null
	 */
	private static $badge_map = null;

	/**
	 * Normalise a group ID so it is safe to use in an HTML id and a CSS selector.
	 *
	 * @param string $group_id Raw group ID.
	 * @return string Sanitised group ID, empty string if nothing usable remains.
	 */
	public static function sanitize_group_id( $group_id ) {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $group_id );
	}

	/**
	 * Resolve a shortcode/API identifier to a real group_id.
	 *
	 * Groups are keyed by slug ("footer", "cart", "product_page"), but the
	 * shortcode has always been documented with numeric IDs. A numeric value is
	 * therefore looked up against the table's primary key so that
	 * [trust_badges id="1"] resolves instead of silently rendering nothing.
	 *
	 * @param string|int $group_id Raw identifier.
	 * @return string Resolved group_id, or an empty string when unresolvable.
	 */
	public static function resolve_group_id( $group_id ) {
		$group_id = self::sanitize_group_id( $group_id );

		if ( '' === $group_id || ! ctype_digit( $group_id ) ) {
			return $group_id;
		}

		$cache_key = 'resolved_' . $group_id;
		$resolved  = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $resolved ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'converswp_trust_badges';

			// A group whose slug is literally "1" must win over the row whose
			// primary key happens to be 1, so order the exact match first.
			$resolved = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					'SELECT group_id FROM `' . esc_sql( $table_name ) . '`
					 WHERE group_id = %s OR id = %d
					 ORDER BY ( group_id = %s ) DESC
					 LIMIT 1',
					$group_id,
					(int) $group_id,
					$group_id
				)
			);

			$resolved = is_string( $resolved ) ? $resolved : '';

			wp_cache_set( $cache_key, $resolved, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return self::sanitize_group_id( $resolved );
	}

	public static function renderBadgeById( $group_id = '' ) {
		$group_id = self::resolve_group_id( $group_id );

		if ( '' === $group_id ) {
			trust_badges_log_error( 'Group ID is empty', array( 'group_id' => $group_id ) );
			return false;
		}

		$settings = self::getBadgeByGroup( $group_id );
		if ( empty( $settings ) ) {
			trust_badges_log_error(
				'Group settings not found.',
				array( 'group_id' => $group_id )
			);
			return false;
		}

		if ( empty( $settings->is_active ) ) {
			trust_badges_log_error( 'Badge is disabled.', array( 'group_id' => $group_id ) );
			return false;
		}

		if ( ! is_array( $settings->settings ) ) {
			trust_badges_log_error( 'Badge settings are malformed.', array( 'group_id' => $group_id ) );
			return false;
		}

		$badgeHtml = self::renderBadgeHtml( $group_id, $settings->settings );

		// Create container with position class
		$html  = '<div id="convers-trust-badges-' . esc_attr( $group_id ) . '">';
		$html .= $badgeHtml;
		$html .= '</div>';

		return $html;
	}

	public static function getGroupIdByPosition( $position ): string {
		if ( $position === 'showAfterAddToCart' || $position === 'eddPurchaseLinkEnd' ) {
			$group_id = 'product_page';
		} elseif ( $position === 'checkoutBeforeOrderReview' || $position === 'eddCheckoutBeforePurchaseForm' ) {
			$group_id = 'checkout';
		} elseif ( $position === 'woocommerce_cart_collaterals' || $position === 'woocommerce_before_cart' || $position === 'woocommerce_blocks_cart_block_after_content' ) {
			$group_id = 'cart';
		} else {
			$group_id = 'footer';
		}

		return $group_id;
	}

	/**
	 * Get badge settings by group ID with caching
	 *
	 * Note: Direct database query is necessary here as this is a core functionality
	 * that requires specific data selection. The performance impact is mitigated by:
	 * 1. Caching results for 1 hour
	 * 2. Using proper SQL preparation
	 * 3. Minimal and targeted query
	 *
	 * @param string $group_id The group ID to fetch
	 * @return object|false Badge settings object or false if not found
	 */
	public static function getBadgeByGroup( $group_id ) {
		$group_id = self::sanitize_group_id( $group_id );

		if ( '' === $group_id ) {
			return false;
		}

		// Try to get from cache first
		$cache_key = self::group_cache_key( $group_id );
		$group     = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $group ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'converswp_trust_badges';

			$group = $wpdb->get_row(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $table_name ) . '` WHERE is_active = 1 AND group_id = %s',
					$group_id
				)
			);

			if ( $group ) {
				$group->settings = json_decode( $group->settings, true );
			} else {
				// Cache the miss too, otherwise every page load re-queries for
				// groups that simply do not exist (e.g. the footer group on a
				// site that never configured one).
				$group = 0;
			}

			wp_cache_set( $cache_key, $group, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return $group ? $group : false;
	}

	/**
	 * Cache key for a single badge group row.
	 *
	 * @param string $group_id Sanitised group ID.
	 * @return string
	 */
	public static function group_cache_key( $group_id ) {
		return 'group_' . $group_id;
	}

	/**
	 * Drop every cache entry this plugin owns.
	 *
	 * Called whenever badge groups are written through the REST API so the
	 * front end never serves stale badge markup.
	 *
	 * @param string $group_id Optional single group to flush. Flushes all when empty.
	 */
	public static function flush_cache( $group_id = '' ) {
		self::$badge_map = null;

		// Preferred path: drop the whole group in one call. This also clears the
		// resolved_* entries that map numeric shortcode IDs onto slugs, which
		// targeted deletes cannot find (the numeric ID is not known here).
		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( self::CACHE_GROUP );
			return;
		}

		// Fallback for object caches without group flushing: delete the keys we
		// can name. Without a persistent object cache these are per-request
		// anyway, so the resolved_* entries cannot go stale across requests.
		wp_cache_delete( 'all_groups', self::CACHE_GROUP );

		$group_id = self::sanitize_group_id( $group_id );

		if ( '' !== $group_id ) {
			wp_cache_delete( self::group_cache_key( $group_id ), self::CACHE_GROUP );
			wp_cache_delete( 'resolved_' . $group_id, self::CACHE_GROUP );
		}
	}

	/**
	 * Badge ID => image filename map, parsed once per request.
	 *
	 * assets/badges.json is ~460 entries. Previously it was read from disk and
	 * JSON-decoded once per rendered badge; now it is parsed at most once per
	 * request and kept in the object cache between requests.
	 *
	 * @return array
	 */
	private static function get_badge_map() {
		if ( null !== self::$badge_map ) {
			return self::$badge_map;
		}

		$cache_key = 'badge_filename_map';
		$map       = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $map ) {
			$map  = array();
			$path = TRUST_BADGES_PLUGIN_DIR . 'assets/badges.json';

			if ( is_readable( $path ) ) {
				$badges = json_decode( file_get_contents( $path ), true );  // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

				if ( is_array( $badges ) ) {
					foreach ( $badges as $badge ) {
						if ( isset( $badge['id'], $badge['image'] ) ) {
							$map[ (string) $badge['id'] ] = basename( $badge['image'] );
						}
					}
				}
			}

			if ( empty( $map ) ) {
				trust_badges_log_error( 'Badge manifest could not be read.', array( 'path' => $path ) );
			}

			wp_cache_set( $cache_key, $map, self::CACHE_GROUP, DAY_IN_SECONDS );
		}

		self::$badge_map = $map;

		return $map;
	}


	/**
	 * Render badges with settings
	 */
	public static function renderBadgeHtml( $group_id, $settings ): string {
		if ( ! is_array( $settings ) ) {
			return '';
		}

		$badge_style = isset( $settings['badgeStyle'] ) ? $settings['badgeStyle'] : 'original';
		$animation   = isset( $settings['animation'] ) ? $settings['animation'] : '';

		// Get exact alignment class from settings
		$alignment_class = 'align-' . ( isset( $settings['badgeAlignment'] ) ? $settings['badgeAlignment'] : 'center' );
		$style_class     = 'style-' . $badge_style;
		$animation_class = $animation ? self::get_animation_class( $animation ) : '';

		// Get margin style if custom margin is enabled
		$margin_style = self::get_margin_style( $settings );

		// Get exact sizes
		$mobile_size = self::get_size_values( isset( $settings['badgeSizeMobile'] ) ? $settings['badgeSizeMobile'] : '' );
		$badge_color = isset( $settings['badgeColor'] ) ? $settings['badgeColor'] : '#0066FF';

		// Start badge container without margin style
		$html  = '';
		$html .= '<div class="convers-trust-badges ' . esc_attr( $alignment_class ) . ' ' . esc_attr( $animation_class ) . '">';

		// Show header if enabled with exact settings
		if ( ! empty( $settings['showHeader'] ) ) {
			$html .= '<div class="trust-badges-header" style="';
			$html .= 'font-size: ' . (int) ( isset( $settings['fontSize'] ) ? $settings['fontSize'] : 18 ) . 'px;';
			$html .= 'color: ' . esc_attr( isset( $settings['textColor'] ) ? $settings['textColor'] : '#000000' ) . ';';
			$html .= 'text-align: ' . esc_attr( self::get_text_alignment( isset( $settings['alignment'] ) ? $settings['alignment'] : 'center' ) ) . ';';
			if ( ! empty( $settings['customStyles'] ) ) {
				$html .= esc_attr( $settings['customStyles'] );
			}
			$html .= '">';
			$html .= esc_html( isset( $settings['headerText'] ) ? $settings['headerText'] : '' );
			$html .= '</div>';
		}

		// Start badges wrapper
		$html .= '<div class="trust-badges-wrapper ' . esc_attr( $style_class ) . '" style="';
		$html .= 'display: flex;';
		$html .= 'flex-wrap: wrap;';
		$html .= 'gap: 10px;';
		$html .= 'justify-content: ' . self::get_alignment_style( isset( $settings['badgeAlignment'] ) ? $settings['badgeAlignment'] : 'center' ) . ';';
		$html .= 'align-items: stretch;';
		$html .= '">';

		// Display selected badges with exact settings
		if ( ! empty( $settings['selectedBadges'] ) && is_array( $settings['selectedBadges'] ) ) {
			$index = 0;

			foreach ( $settings['selectedBadges'] as $badge_id ) {
				$filename = self::get_badge_filename( $badge_id );

				// Unknown badge ID (e.g. removed from the manifest) - skip it
				// rather than emitting a broken image.
				if ( '' === $filename ) {
					continue;
				}

				$badge_url = plugins_url( 'assets/images/badges/' . $filename, __DIR__ );

				// Add badge index and margin style to each badge container
				$html .= '<div class="badge-container" style="--badge-index: ' . (int) $index . ';' . $margin_style . '">';
				++$index;

				if ( in_array( $badge_style, array( 'mono', 'mono-card' ), true ) ) {
					// The mask image is passed as a custom property and applied
					// from the stylesheet: wp_kses() strips a literal
					// "mask: url(...)" declaration, but preserves url() inside a
					// custom property.
					$html .= '<div class="badge-image" style="';
					$html .= '--badge-mask: url(' . esc_url( $badge_url ) . ');';
					$html .= 'background-color: ' . esc_attr( $badge_color ) . ';';
					$html .= 'width: ' . (int) $mobile_size . 'px;';
					$html .= 'height: ' . (int) $mobile_size . 'px;';
					$html .= '"></div>';
				} else {
					// Deliberately not loading="lazy": these badges render with
					// height:auto and often sit at the very end of the document,
					// so a lazy image collapses to zero height and the
					// intersection observer never fires - the badge never loads.
					$html .= '<img src="' . esc_url( $badge_url ) . '" alt="" decoding="async" class="badge-image" style="';  // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
					$html .= 'width: ' . (int) $mobile_size . 'px;';
					$html .= 'height: auto;';
					$html .= 'max-height: ' . (int) $mobile_size . 'px;';
					$html .= 'transition: all 0.3s ease;';
					$html .= 'object-fit: contain;';
					$html .= '" />';
				}

				$html .= '</div>';
			}
		}

		$html .= '</div>'; // Close badges wrapper
		$html .= '</div>'; // Close badge container

		// Add responsive styles with exact sizes
		self::add_responsive_styles( $group_id, $settings );

		return $html;
	}

	/**
	 * Add responsive styles for badge sizes
	 */
	public static function add_responsive_styles( $group_id, $settings ) {
		$group_id = self::sanitize_group_id( $group_id );

		if ( '' === $group_id || ! is_array( $settings ) ) {
			return;
		}

		$desktop_size = self::get_size_values( isset( $settings['badgeSizeDesktop'] ) ? $settings['badgeSizeDesktop'] : '' );
		$mobile_size  = self::get_size_values( isset( $settings['badgeSizeMobile'] ) ? $settings['badgeSizeMobile'] : '' );
		$animation    = isset( $settings['animation'] ) ? $settings['animation'] : '';
		$badge_color  = isset( $settings['badgeColor'] ) ? $settings['badgeColor'] : '#0066FF';

		// Get design settings from database
		$badge_padding    = isset( $settings['badgePadding'] ) ? intval( $settings['badgePadding'] ) : 5;
		$badge_gap        = isset( $settings['badgeGap'] ) ? intval( $settings['badgeGap'] ) : 10;
		$container_margin = isset( $settings['containerMargin'] ) ? intval( $settings['containerMargin'] ) : 15;
		$border_radius    = isset( $settings['borderRadius'] ) ? intval( $settings['borderRadius'] ) : 4;
		$hover_transform  = self::sanitize_css_value( isset( $settings['hoverTransform'] ) ? $settings['hoverTransform'] : '', 'translateY(-2px)' );
		$transition       = self::sanitize_css_value( isset( $settings['transition'] ) ? $settings['transition'] : '', 'all 0.3s ease' );
		$badge_color      = self::sanitize_css_value( $badge_color, '#0066FF' );

		$html_id = '#convers-trust-badges-' . $group_id;

		// Get animation styles based on settings
		$animation_styles = self::get_animation_styles( $html_id, $animation );
		
		$custom_css = '
        ' . $html_id . ' .convers-trust-badges {
            margin: ' . (int) $container_margin . 'px 0;
            width: 100%;
        }
        ' . $html_id . ' .trust-badges-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: ' . (int) $badge_gap . 'px;
            align-items: stretch;
            width: 100%;
        }
        ' . $html_id . ' .badge-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: ' . (int) $badge_padding . 'px;
            transition: ' . $transition . ';
        }

        /* Mobile styles */
        ' . $html_id . ' .badge-image {
            width: ' . (int) $mobile_size . 'px !important;
            height: auto !important;
            max-height: ' . (int) $mobile_size . 'px !important;
            transition: ' . $transition . ';
            object-fit: contain;
        }

        ' . $html_id . ' .style-mono .badge-image,
        ' . $html_id . ' .style-mono-card .badge-image {
            width: ' . (int) $mobile_size . 'px !important;
            height: ' . (int) $mobile_size . 'px !important;
            -webkit-mask-image: var(--badge-mask);
            mask-image: var(--badge-mask);
            -webkit-mask-size: contain;
            mask-size: contain;
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-position: center;
            mask-position: center;
            background-color: ' . $badge_color . ';
            transition: ' . $transition . ';
        }

        /* Desktop styles */
        @media screen and (min-width: 768px) {
            ' . $html_id . ' .badge-image {
                width: ' . (int) $desktop_size . 'px !important;
                max-height: ' . (int) $desktop_size . 'px !important;
            }
            
            ' . $html_id . ' .style-mono .badge-image,
            ' . $html_id . ' .style-mono-card .badge-image {
                width: ' . (int) $desktop_size . 'px !important;
                height: ' . (int) $desktop_size . 'px !important;
            }
        }

        /* Hover effects */
        ' . $html_id . ' .badge-container:hover {
            transform: ' . $hover_transform . ';
        }
        ' . $html_id . ' .badge-container:hover .badge-image {
            transform: scale(1.05);
        }

        /* Card styles */
        ' . $html_id . ' .style-card .badge-container,
        ' . $html_id . ' .style-mono-card .badge-container {
            background-color: #e5e7eb;
            padding: ' . ( (int) $badge_padding + 3 ) . 'px ' . ( (int) $badge_padding + 7 ) . 'px;
            border-radius: ' . (int) $border_radius . 'px;
        }

        /* Alignment */
        ' . $html_id . ' .align-left .trust-badges-wrapper { justify-content: flex-start; }
        ' . $html_id . ' .align-center .trust-badges-wrapper { justify-content: center; }
        ' . $html_id . ' .align-right .trust-badges-wrapper { justify-content: flex-end; }

        /* Animation styles */
        ' . wp_strip_all_tags( $animation_styles ) . '
        ';

		// Register and enqueue main styles (only once)
		static $main_styles_enqueued = false;
		if ( ! $main_styles_enqueued ) {
			wp_register_style(
				'trust-badges-main',
				plugins_url( 'assets/css/main.css', __DIR__ ),
				array(),
				TRUST_BADGES_VERSION
			);
			wp_enqueue_style( 'trust-badges-main' );
			$main_styles_enqueued = true;
		}

		// Create unique style handle for each badge group
		$unique_style_handle = 'trust-badges-group-' . esc_attr( $group_id );
		
		// Register and enqueue unique styles for this badge group
		wp_register_style(
			$unique_style_handle,
			false,
			array( 'trust-badges-main' ),
			TRUST_BADGES_VERSION
		);
		wp_enqueue_style( $unique_style_handle );

		// Add inline styles for this specific badge group
		wp_add_inline_style(
			$unique_style_handle,
			wp_strip_all_tags($custom_css)
		);
	}

	/**
	 * Convert size names to pixel values
	 */
	public static function get_size_values( $size ) {
		$sizes = array(
			'extra-small' => 32,
			'small'       => 48,
			'medium'      => 64,
			'large'       => 80,
		);

		return $sizes[ $size ] ?? 48;
	}

	/**
	 * Get alignment style value
	 */
	public static function get_alignment_style( $alignment ) {
		$styles = array(
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		);
		return $styles[ $alignment ] ?? 'center';
	}

	/**
	 * Restrict a header alignment to a known CSS text-align keyword.
	 *
	 * @param string $alignment Raw alignment from settings.
	 * @return string
	 */
	public static function get_text_alignment( $alignment ) {
		$allowed = array( 'left', 'center', 'right', 'justify' );

		return in_array( $alignment, $allowed, true ) ? $alignment : 'center';
	}

	/**
	 * Sanitise a free-text CSS value before it is written into a stylesheet.
	 *
	 * These values come from the settings screen (manage_options only), but
	 * they land in a <style> block rather than an attribute, so escaping is not
	 * the right tool - characters that could terminate the declaration or the
	 * block are stripped instead.
	 *
	 * @param string $value    Raw value.
	 * @param string $fallback Value to use when nothing usable remains.
	 * @return string
	 */
	public static function sanitize_css_value( $value, $fallback = '' ) {
		$value = preg_replace( '/[<>{};@\\\\]/', '', (string) $value );
		$value = trim( str_replace( array( 'javascript:', 'expression(' ), '', $value ) );

		return '' !== $value ? $value : $fallback;
	}

	/**
	 * Get margin style string if custom margins are enabled
	 */
	public static function get_margin_style( $settings ) {
		if ( empty( $settings['customMargin'] ) ) {
			return '';
		}

		$top    = isset( $settings['marginTop'] ) ? intval( $settings['marginTop'] ) : 0;
		$right  = isset( $settings['marginRight'] ) ? intval( $settings['marginRight'] ) : 0;
		$bottom = isset( $settings['marginBottom'] ) ? intval( $settings['marginBottom'] ) : 0;
		$left   = isset( $settings['marginLeft'] ) ? intval( $settings['marginLeft'] ) : 0;

		return sprintf(
			'margin: %dpx %dpx %dpx %dpx;',
			$top,
			$right,
			$bottom,
			$left
		);
	}

	/**
	 * Get animation class based on settings
	 */
	public static function get_animation_class( $animation ) {
		if ( empty( $animation ) ) {
			return '';
		}
		return 'badge-' . esc_attr( $animation );
	}

	/**
	 * Get animation styles based on settings
	 */
	public static function get_animation_styles( $html_id, $animation ) {
		if ( empty( $animation ) ) {
			return '';
		}

		$styles = '';

		// Base opacity for all animations
		$styles .= $html_id . ' .convers-trust-badges { opacity: 1; }';
		$styles .= $html_id . ' .badge-container { opacity: 0; }';

		// Animation definition based on type
		switch ( $animation ) {
			case 'fade':
				$styles .= $html_id . '
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
				$styles .= $html_id . '
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
				$styles .= $html_id . '
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
				$styles .= $html_id . '
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
	 * Resolve a badge ID to its image filename.
	 *
	 * @param string $badge_id Badge identifier from the saved settings.
	 * @return string Filename, or an empty string when the badge is unknown.
	 */
	public static function get_badge_filename( $badge_id ) {
		$map = self::get_badge_map();

		return isset( $map[ (string) $badge_id ] ) ? $map[ (string) $badge_id ] : '';
	}

	/**
	 * Helper method to sanitize and return position styles.
	 *
	 * @param string $position The alignment position.
	 * @return string Sanitized CSS value for justify-content.
	 */
	public static function get_position_style( $position ) {
		// Define allowed positions and their corresponding CSS values
		$allowed_positions = array(
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		);

		// Return sanitized value or default to 'center' if invalid
		return $allowed_positions[ $position ] ?? 'center';
	}
}
