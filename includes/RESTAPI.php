<?php
namespace TrustBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class RESTAPI {
	private $namespace = 'trust-badges/v1';

	// Utility method for handling database errors
	private function handle_db_error( $wpdb, $context = '' ) {
		if ( $wpdb->last_error ) {
			trust_badges_log_error(
				'Database error',
				array(
					'context' => $context,
					'error'   => $wpdb->last_error,
				)
			);

			// The raw driver message is deliberately not returned to the client.
			return new WP_Error(
				'database_error',
				__( 'A database error occurred.', 'trust-badges' ),
				array( 'status' => 500 )
			);
		}
		return null;
	}

	/**
	 * Register REST API endpoints with proper error handling
	 */
	public function register_routes() {
		try {
			// Register settings endpoint
			register_rest_route(
				$this->namespace,
				'/settings',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'save_settings' ),
						'permission_callback' => array( $this, 'check_permissions' ),
					),
				)
			);

			// Register group settings endpoint
			register_rest_route(
				$this->namespace,
				'/settings/group',
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_group_settings' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => array(
						'group' => array(
							'required' => true,
							'type'     => 'object',
						),
					),
				)
			);

			// Add installed plugins endpoint
			register_rest_route(
				$this->namespace,
				'/installed-plugins',
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_installed_plugins' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				)
			);

			// Individual group operations
			register_rest_route(
				$this->namespace,
				'/settings/group/(?P<id>[a-zA-Z0-9_-]+)',
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_group' ),
						'permission_callback' => array( $this, 'check_permissions' ),
						'args'                => array(
							'id' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => array( Renderer::class, 'sanitize_group_id' ),
							),
						),
					),
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'delete_group' ),
						'permission_callback' => array( $this, 'check_permissions' ),
						'args'                => array(
							'id' => array(
								'required'          => true,
								'type'              => 'string',
								'sanitize_callback' => array( Renderer::class, 'sanitize_group_id' ),
							),
						),
					),
				)
			);
		} catch ( \Exception $e ) {
			trust_badges_log_error(
				'REST API Registration Error',
				array(
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				)
			);
		}
	}

	/**
	 * Check user permissions and nonce for REST API requests
	 *
	 * @return bool|WP_Error
	 */
	public function check_permissions() {
		try {
			// Verify user is logged in
			if ( ! is_user_logged_in() ) {
				return new WP_Error(
					'rest_not_logged_in',
					__( 'You must be logged in to manage Trust Badges.', 'trust-badges' ),
					array( 'status' => 401 )
				);
			}

			// Verify user capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'rest_forbidden_capability',
					__( 'You do not have sufficient permissions to manage Trust Badges.', 'trust-badges' ),
					array( 'status' => 403 )
				);
			}

			// Get nonce from headers
			$nonce = null;
			if ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
			}

			// Verify nonce
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error(
					'rest_cookie_invalid_nonce',
					__( 'Session expired. Please refresh the page and try again.', 'trust-badges' ),
					array( 'status' => 403 )
				);
			}

			return true;
		} catch ( \Exception $e ) {
			return new WP_Error(
				'rest_error',
				__( 'An unexpected error occurred.', 'trust-badges' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Recursively sanitise a settings payload before it is persisted.
	 *
	 * The settings object is free-form (the admin UI adds keys over time), so
	 * rather than an allowlist every scalar is sanitised in place and the
	 * structure is depth-limited to stop pathological payloads.
	 *
	 * @param mixed $value Value to sanitise.
	 * @param int   $depth Current recursion depth.
	 * @return mixed
	 */
	public static function sanitize_settings( $value, $depth = 0 ) {
		if ( $depth > 10 ) {
			return null;
		}

		if ( is_array( $value ) ) {
			$clean = array();

			foreach ( $value as $key => $item ) {
				if ( ! is_int( $key ) ) {
					// Note: sanitize_key() must not be used here. It lowercases,
					// and every settings key is camelCase (headerText,
					// selectedBadges, ...), so it would silently rename them all.
					$key = preg_replace( '/[^A-Za-z0-9_-]/', '', $key );

					if ( '' === $key ) {
						continue;
					}
				}

				$clean[ $key ] = self::sanitize_settings( $item, $depth + 1 );
			}

			return $clean;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Save group settings with improved error handling
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_group_settings( $request ) {
		try {
			global $wpdb;
			$table_name = $wpdb->prefix . 'converswp_trust_badges';

			// Get and validate group data
			$group = $request->get_param( 'group' );
			if ( empty( $group ) || ! is_array( $group ) ) {
				return new WP_Error(
					'invalid_group_data',
					__( 'Invalid group data provided.', 'trust-badges' ),
					array( 'status' => 400 )
				);
			}

			$raw_group_id = isset( $group['id'] ) ? (string) $group['id'] : '';
			$group_id     = Renderer::sanitize_group_id( $raw_group_id );

			// Reject rather than silently rewrite: quietly mangling the ID would
			// create a second group under a name the caller never asked for.
			if ( '' === $group_id || $group_id !== $raw_group_id ) {
				return new WP_Error(
					'invalid_group_id',
					__( 'Group IDs may only contain letters, numbers, hyphens and underscores.', 'trust-badges' ),
					array( 'status' => 400 )
				);
			}

			if ( ! isset( $group['settings'] ) || ! is_array( $group['settings'] ) ) {
				return new WP_Error(
					'invalid_group_settings',
					__( 'Group settings must be provided as an object.', 'trust-badges' ),
					array( 'status' => 400 )
				);
			}

			// Start transaction
			$wpdb->query( 'START TRANSACTION' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			try {
				// Prepare data for database
				$data = array(
					'group_name' => sanitize_text_field( isset( $group['name'] ) ? $group['name'] : $group_id ),
					'is_active'  => isset( $group['isActive'] ) ? (int) (bool) $group['isActive'] : 1,
					'settings'   => wp_json_encode( self::sanitize_settings( $group['settings'] ) ),
				);

				$where = array( 'group_id' => $group_id );

				// Check if group exists
				$existing = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						'SELECT COUNT(*) FROM `' . esc_sql( $table_name ) . '` WHERE group_id = %s',
						$group_id
					)
				);

				if ( $existing ) {
					// Update existing group
					$result = $wpdb->update( $table_name, $data, $where );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				} else {
					// Insert new group
					$data['group_id']   = $group_id;
					$data['is_default'] = isset( $group['isDefault'] ) ? (int) (bool) $group['isDefault'] : 0;
					$result             = $wpdb->insert( $table_name, $data );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				}

				// Check for database errors
				if ( $error = $this->handle_db_error( $wpdb, 'save_group_settings' ) ) {
					throw new \Exception( $error->get_error_message() );
				}

				if ( $result === false ) {
					throw new \Exception( __( 'Failed to save group settings.', 'trust-badges' ) );
				}

				// Commit transaction
				$wpdb->query( 'COMMIT' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				// Clear cache so the front end picks the change up immediately
				Renderer::flush_cache( $group_id );

				// Return success response with updated group data
				$updated_group = array(
					'id'             => $group_id,
					'name'           => $data['group_name'],
					'isActive'       => (bool) $data['is_active'],
					'isDefault'      => isset( $data['is_default'] ) ? (bool) $data['is_default'] : false,
					'settings'       => json_decode( $data['settings'], true ),
					'requiredPlugin' => isset( $group['requiredPlugin'] ) ? sanitize_text_field( $group['requiredPlugin'] ) : null,
				);

				return rest_ensure_response(
					array(
						'success' => true,
						'message' => __( 'Group settings saved successfully.', 'trust-badges' ),
						'group'   => $updated_group,
					)
				);

			} catch ( \Exception $e ) {
				$wpdb->query( 'ROLLBACK' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				throw $e;
			}
		} catch ( \Exception $e ) {
			return new WP_Error(
				'save_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	public function get_settings() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'converswp_trust_badges';

		$cache_key = 'all_groups';
		$groups    = wp_cache_get( $cache_key, Renderer::CACHE_GROUP );

		if ( false === $groups ) {
			// No placeholders here, so prepare() must not be used - WordPress
			// raises _doing_it_wrong() for a prepare() call with no arguments.
			$groups = $wpdb->get_results(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
				'SELECT * FROM `' . esc_sql( $table_name ) . '` ORDER BY id ASC'
			);

			if ( $error = $this->handle_db_error( $wpdb, 'get_settings' ) ) {
				return $error;
			}

			$groups = is_array( $groups ) ? $groups : array();

			wp_cache_set( $cache_key, $groups, Renderer::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		if ( ! $groups ) {
			return new WP_REST_Response( array(), 200 );
		}

		$formatted_groups = array_map(
			function ( $group ) {
				return array(
					'id'             => $group->group_id,
					'name'           => $group->group_name,
					'isDefault'      => (bool) $group->is_default,
					'isActive'       => (bool) $group->is_active,
					'requiredPlugin' => $group->required_plugin,
					'settings'       => json_decode( $group->settings, true ),
				);
			},
			$groups
		);

		return new WP_REST_Response( $formatted_groups, 200 );
	}

	public function save_settings( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'converswp_trust_badges';

		$groups = $request->get_param( 'groups' );

		if ( ! is_array( $groups ) ) {
			return new WP_Error( 'invalid_data', 'Invalid groups data', array( 'status' => 400 ) );
		}

		$wpdb->query( 'START TRANSACTION' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		try {
			foreach ( $groups as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}

				$group_id = Renderer::sanitize_group_id( isset( $group['id'] ) ? $group['id'] : '' );

				if ( '' === $group_id || ! isset( $group['settings'] ) || ! is_array( $group['settings'] ) ) {
					throw new \Exception( __( 'Invalid group data provided.', 'trust-badges' ) );
				}

				$data = array(
					'group_name' => sanitize_text_field( isset( $group['name'] ) ? $group['name'] : $group_id ),
					'is_active'  => isset( $group['isActive'] ) ? (int) (bool) $group['isActive'] : 1,
					'settings'   => wp_json_encode( self::sanitize_settings( $group['settings'] ) ),
				);

				$where = array( 'group_id' => $group_id );

				$wpdb->update( $table_name, $data, $where );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery

				if ( $error = $this->handle_db_error( $wpdb, 'save_settings' ) ) {
					throw new \Exception( $error->get_error_message() );
				}
			}

			$wpdb->query( 'COMMIT' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			// Clear cache
			Renderer::flush_cache();

			return new WP_REST_Response(
				array(
					'message' => 'Settings updated successfully',
				),
				200
			);
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			return new WP_Error( 'update_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	public function get_group( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'converswp_trust_badges';
		$group_id   = Renderer::sanitize_group_id( $request['id'] );

		if ( '' === $group_id ) {
			return new WP_Error( 'invalid_group_id', __( 'A valid group ID is required.', 'trust-badges' ), array( 'status' => 400 ) );
		}

		$result = $wpdb->get_row(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM `' . esc_sql( $table_name ) . '` WHERE group_id = %s',
				$group_id
			)
		);

		if ( $error = $this->handle_db_error( $wpdb, 'get_group' ) ) {
			return $error;
		}

		if ( ! $result ) {
			return new WP_Error(
				'not_found',
				'Group not found',
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			array(
				'id'             => $result->group_id,
				'name'           => $result->group_name,
				'isDefault'      => (bool) $result->is_default,
				'isActive'       => (bool) $result->is_active,
				'requiredPlugin' => $result->required_plugin,
				'settings'       => json_decode( $result->settings, true ),
			),
			200
		);
	}

	public function delete_group( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'converswp_trust_badges';
		$group_id   = Renderer::sanitize_group_id( $request['id'] );

		if ( '' === $group_id ) {
			return new WP_Error( 'invalid_group_id', __( 'A valid group ID is required.', 'trust-badges' ), array( 'status' => 400 ) );
		}

		// Read the flag straight from the database: this is a destructive,
		// admin-only operation, so a stale cached value must not decide whether
		// a default group may be deleted.
		$is_default = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT is_default FROM `' . esc_sql( $table_name ) . '` WHERE group_id = %s',
				$group_id
			)
		);

		if ( null === $is_default ) {
			return new WP_Error( 'not_found', __( 'Group not found.', 'trust-badges' ), array( 'status' => 404 ) );
		}

		if ( $is_default ) {
			return new WP_Error(
				'delete_failed',
				'Cannot delete default groups',
				array( 'status' => 403 )
			);
		}

		// Delete the group and clear caches
		$result = $wpdb->delete(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table_name,
			array( 'group_id' => $group_id ),
			array( '%s' )
		);

		if ( $error = $this->handle_db_error( $wpdb, 'delete_group' ) ) {
			return $error;
		}

		if ( $result === false ) {
			return new WP_Error(
				'delete_failed',
				'Failed to delete group',
				array( 'status' => 500 )
			);
		}

		// Clear all related caches
		Renderer::flush_cache( $group_id );

		return new WP_REST_Response(
			array(
				'message' => __( 'Group deleted successfully.', 'trust-badges' ),
			),
			200
		);
	}

	public function get_installed_plugins() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return new WP_REST_Response(
			array(
				'woocommerce' => is_plugin_active( 'woocommerce/woocommerce.php' ),
				'edd'         => is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ),
			),
			200
		);
	}
}
