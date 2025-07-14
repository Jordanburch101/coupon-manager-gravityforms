<?php
/**
 * Plugin Name: Coupon Manager for GravityForms
 * Description: Bulk generate and manage coupons for GravityForms Coupons Add-On
 * Version: 0.0.4
 * Author: Jordan Burch
 * Author URI: https://github.com/Jordanburch101
 * Text Domain: coupon-manager-for-gravityforms
 * Package: Coupmafo_Coupon_Manager
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <https://www.gnu.org/licenses/>.
 *
 * @package Coupmafo_Coupon_Manager
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'COUPMAFO_COUPON_GEN_VERSION', '1.0.0' );
define( 'COUPMAFO_COUPON_GEN_PATH', plugin_dir_path( __FILE__ ) );
define( 'COUPMAFO_COUPON_GEN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class for Coupon Manager for GravityForms.
 */
class Coupmafo_Coupon_Generator {

	/**
	 * Single instance of the class.
	 *
	 * @var Coupmafo_Coupon_Generator|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of the class.
	 *
	 * @return Coupmafo_Coupon_Generator
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - initialize hooks and actions.
	 */
	private function __construct() {
		// Check if GravityForms is active.
		add_action( 'admin_init', array( $this, 'check_dependencies' ) );

		// Use GravityForms own hooks for adding menu items.
		add_action( 'gform_addon_navigation', array( $this, 'add_menu_item' ) );

		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Ajax handlers.
		add_action( 'wp_ajax_generate_coupmafo_coupons', array( $this, 'generate_coupons_ajax' ) );
		add_action( 'wp_ajax_update_coupmafo_coupons', array( $this, 'update_coupons_ajax' ) );
	}

	/**
	 * Check if required dependencies are active.
	 */
	public function check_dependencies() {
		if ( ! class_exists( 'GFForms' ) || ! class_exists( 'GFCoupons' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_gf' ) );
		}
	}

	/**
	 * Display admin notice when GravityForms or Coupons addon is missing.
	 */
	public function admin_notice_missing_gf() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Coupon Manager for GravityForms requires both Gravity Forms and Gravity Forms Coupons Add-On to be installed and activated.', 'coupon-manager-for-gravityforms' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Add menu item to GravityForms navigation.
	 *
	 * @param array $menus Existing menu items.
	 * @return array Modified menu items.
	 */
	public function add_menu_item( $menus ) {
		$menus[] = array(
			'name'       => 'coupmafo_coupon_generator',
			'label'      => __( 'Coupon Manager', 'coupon-manager-for-gravityforms' ),
			'callback'   => array( $this, 'render_admin_page' ),
			'permission' => 'manage_options',
		);

		return $menus;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Unused parameter but required by WordPress hook.
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		unset( $hook );
		// Updated hook check for GF pages.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_admin() && isset( $_GET['page'] ) && 'coupmafo_coupon_generator' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			wp_enqueue_style(
				'coupmafo-coupon-generator-css',
				COUPMAFO_COUPON_GEN_URL . 'assets/css/admin.css',
				array(),
				COUPMAFO_COUPON_GEN_VERSION
			);

			wp_enqueue_script(
				'coupmafo-coupon-generator-js',
				COUPMAFO_COUPON_GEN_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				COUPMAFO_COUPON_GEN_VERSION,
				true
			);

			wp_localize_script(
				'coupmafo-coupon-generator-js',
				'coupmafoCouponGen',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'coupmafo_coupon_generator_nonce' ),
				)
			);
		}
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		include COUPMAFO_COUPON_GEN_PATH . 'views/admin-page.php';
	}

	/**
	 * Handle AJAX request for generating coupons.
	 */
	public function generate_coupons_ajax() {
		check_ajax_referer( 'coupmafo_coupon_generator_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$form_id       = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$coupon_prefix = isset( $_POST['coupon_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prefix'] ) ) : '';
		$coupon_length = isset( $_POST['coupon_length'] ) ? intval( $_POST['coupon_length'] ) : 8;
		$amount_type   = isset( $_POST['amount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['amount_type'] ) ) : 'percentage';
		$amount_value  = isset( $_POST['amount_value'] ) ? sanitize_text_field( wp_unslash( $_POST['amount_value'] ) ) : '0';
		$start_date    = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$expiry_date   = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
		$usage_limit   = isset( $_POST['usage_limit'] ) ? intval( $_POST['usage_limit'] ) : 1;
		$is_stackable  = isset( $_POST['is_stackable'] ) ? intval( $_POST['is_stackable'] ) : 0;
		$quantity      = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;

		// Validate inputs.
		if ( empty( $form_id ) ) {
			wp_send_json_error( 'Form ID is required' );
		}

		if ( 1 > $quantity || 1000 < $quantity ) {
			wp_send_json_error( 'Quantity must be between 1 and 1000' );
		}

		$results = $this->generate_coupons(
			$form_id,
			$coupon_prefix,
			$coupon_length,
			$amount_type,
			$amount_value,
			$start_date,
			$expiry_date,
			$usage_limit,
			$is_stackable,
			$quantity
		);

		wp_send_json_success( $results );
	}

	/**
	 * Generate multiple coupons.
	 *
	 * @param int    $form_id       The form ID.
	 * @param string $coupon_prefix The coupon prefix.
	 * @param int    $coupon_length The length of the random part.
	 * @param string $amount_type   The discount type (percentage or flat).
	 * @param string $amount_value  The discount value.
	 * @param string $start_date    The start date.
	 * @param string $expiry_date   The expiry date.
	 * @param int    $usage_limit   The usage limit.
	 * @param int    $is_stackable  Whether the coupon is stackable.
	 * @param int    $quantity      Number of coupons to generate.
	 * @return array Results of the generation process.
	 */
	public function generate_coupons(
		$form_id,
		$coupon_prefix,
		$coupon_length,
		$amount_type,
		$amount_value,
		$start_date,
		$expiry_date,
		$usage_limit,
		$is_stackable,
		$quantity
	) {
		global $wpdb;

		// Validate required parameters to prevent NULL database errors.
		if ( empty( $form_id ) || ! is_numeric( $form_id ) ) {
			return array(
				'success' => 0,
				'failed'  => $quantity,
				'coupons' => array(),
				'error'   => 'Invalid form_id provided',
			);
		}

		// Ensure coupon_prefix is a string (can be empty).
		$coupon_prefix = (string) $coupon_prefix;

		// Validate amount_type and amount_value.
		if ( ! in_array( $amount_type, array( 'percentage', 'flat' ), true ) ) {
			$amount_type = 'percentage';
		}

		if ( empty( $amount_value ) || ! is_numeric( $amount_value ) ) {
			$amount_value = '0';
		}

		// Ensure usage_limit and is_stackable are integers.
		$usage_limit  = max( 1, intval( $usage_limit ) );
		$is_stackable = intval( $is_stackable );

		$results = array(
			'success' => 0,
			'failed'  => 0,
			'coupons' => array(),
		);

		for ( $i = 0; $i < $quantity; $i++ ) {
			// Generate coupon code.
			$random_part = $this->generate_random_code( $coupon_length );
			$coupon_code = $coupon_prefix . $random_part;

			// Initial coupon name - will be updated after insertion.
			$coupon_name = 'Coupon';

			// Format amount value based on type (add $ for flat amounts).
			$amount_display = ( 'flat' === $amount_type ) ? '$' . $amount_value : $amount_value;

			// Prepare meta data - match the exact structure GF Coupons expects.
			$meta = array(
				'gravityForm'      => $form_id,
				'couponName'       => $coupon_name,
				'couponCode'       => $coupon_code,
				'couponAmountType' => $amount_type,
				'couponAmount'     => $amount_display,
				'startDate'        => $start_date,
				'endDate'          => $expiry_date, // Always include endDate field, even if empty.
				'usageLimit'       => (string) $usage_limit, // Convert to string to match GF format.
				'isStackable'      => (string) $is_stackable, // Convert to string to match GF format.
			);

			// Validate all required fields before database insertion.
			$insert_data = array(
				'form_id'    => intval( $form_id ),
				'is_active'  => 1,
				'feed_order' => 0,
				'meta'       => wp_json_encode( $meta ),
				'addon_slug' => 'gravityformscoupons',
			);

			// Validate all required fields before database insertion.
			if ( ! $this->validate_insert_data( $insert_data, array( 'form_id', 'is_active', 'meta', 'addon_slug' ) ) ) {
				++$results['failed'];
				continue; // Skip to next coupon iteration.
			}

			// Insert into database.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'gf_addon_feed',
				$insert_data
			);

			if ( $inserted ) {
				$feed_id = $wpdb->insert_id;

				// Now update the name with the ID.
				$updated_name       = "Coupon - #{$feed_id}";
				$meta['couponName'] = $updated_name;

				// Update the record with the new name.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update(
					$wpdb->prefix . 'gf_addon_feed',
					array( 'meta' => wp_json_encode( $meta ) ),
					array( 'id' => $feed_id )
				);

				++$results['success'];
				$results['coupons'][] = array(
					'id'          => $feed_id,
					'coupon_code' => $coupon_code,
				);
			} else {
				++$results['failed'];
			}
		}

		return $results;
	}

	/**
	 * Generate a random code for coupon.
	 *
	 * @param int $length The length of the code.
	 * @return string The generated code.
	 */
	private function generate_random_code( $length = 8 ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$code  = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$code .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
		}

		return $code;
	}

	/**
	 * Validate database insert data to prevent NULL constraint violations.
	 *
	 * @param array $data The data array to validate.
	 * @param array $required_fields Array of required field names.
	 * @return bool True if all required fields are valid, false otherwise.
	 */
	private function validate_insert_data( $data, $required_fields ) {
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) || null === $data[ $field ] || '' === $data[ $field ] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Handle AJAX request for updating coupons.
	 */
	public function update_coupons_ajax() {
		check_ajax_referer( 'coupmafo_coupon_generator_nonce', 'nonce' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$csv_content   = isset( $_POST['csv_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['csv_content'] ) ) : '';
		$update_action = isset( $_POST['update_action'] ) ? sanitize_text_field( wp_unslash( $_POST['update_action'] ) ) : '';

		if ( empty( $csv_content ) || empty( $update_action ) ) {
			wp_send_json_error( 'Missing required parameters' );
		}

		// Parse CSV.
		$coupon_codes = $this->parse_csv_for_codes( $csv_content );

		if ( empty( $coupon_codes ) ) {
			wp_send_json_error( 'No valid coupon codes found in CSV' );
		}

		// Collect update parameters.
		$update_params = array();

		switch ( $update_action ) {
			case 'discount':
				$update_params['amount_type']  = isset( $_POST['new_amount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['new_amount_type'] ) ) : '';
				$update_params['amount_value'] = isset( $_POST['new_amount_value'] ) ? sanitize_text_field( wp_unslash( $_POST['new_amount_value'] ) ) : '';
				break;
			case 'dates':
				$update_params['start_date']  = isset( $_POST['new_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['new_start_date'] ) ) : '';
				$update_params['expiry_date'] = isset( $_POST['new_expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['new_expiry_date'] ) ) : '';
				break;
			case 'usage':
				$update_params['usage_limit'] = isset( $_POST['new_usage_limit'] ) ? intval( $_POST['new_usage_limit'] ) : 1;
				break;
			case 'stackable':
				$update_params['is_stackable'] = isset( $_POST['new_is_stackable'] ) ? intval( $_POST['new_is_stackable'] ) : 0;
				break;
			case 'activate':
				$update_params['is_active'] = 1;
				break;
			case 'deactivate':
				$update_params['is_active'] = 0;
				break;
		}

		// Update coupons.
		$results = $this->update_coupons( $coupon_codes, $update_action, $update_params );

		wp_send_json_success( $results );
	}

	/**
	 * Parse CSV content to extract coupon codes.
	 *
	 * @param string $csv_content The CSV content.
	 * @return array Array of coupon codes.
	 */
	private function parse_csv_for_codes( $csv_content ) {
		$codes        = array();
		$lines        = explode( "\n", $csv_content );
		$header_found = false;

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Parse CSV line (handle quoted values).
			$values = str_getcsv( $line );

			if ( ! $header_found ) {
				// Check if first line contains "coupon_code" header.
				if ( 'coupon_code' === strtolower( trim( $values[0] ) ) ) {
					$header_found = true;
					continue;
				}
				// If no header, treat first line as data.
			}

			// Get the first column value (coupon code).
			if ( ! empty( $values[0] ) ) {
				$codes[] = trim( $values[0] );
			}
		}

		return array_unique( $codes ); // Remove duplicates.
	}

	/**
	 * Update multiple coupons based on provided parameters.
	 *
	 * @param array  $coupon_codes  Array of coupon codes to update.
	 * @param string $update_action The update action to perform.
	 * @param array  $update_params Parameters for the update.
	 * @return array Results of the update process.
	 */
	private function update_coupons( $coupon_codes, $update_action, $update_params ) {
		global $wpdb;

		$results = array(
			'results' => array(),
		);

		foreach ( $coupon_codes as $coupon_code ) {
			$result = array(
				'coupon_code' => $coupon_code,
				'status'      => 'error',
				'message'     => '',
			);

			// Find coupon by code.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$coupon = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}gf_addon_feed
					WHERE addon_slug = 'gravityformscoupons'
					AND meta LIKE %s",
					'%"couponCode":"' . $wpdb->esc_like( $coupon_code ) . '"%'
				)
			);

			if ( ! $coupon ) {
				$result['message']    = 'Coupon not found';
				$results['results'][] = $result;
				continue;
			}

			// Decode existing meta.
			$meta = json_decode( $coupon->meta, true );
			if ( ! $meta ) {
				$result['message']    = 'Invalid coupon data';
				$results['results'][] = $result;
				continue;
			}

			// Update meta based on action.
			$updated = false;

			switch ( $update_action ) {
				case 'discount':
					if ( ! empty( $update_params['amount_type'] ) && ! empty( $update_params['amount_value'] ) ) {
						$meta['couponAmountType'] = $update_params['amount_type'];
						$meta['couponAmount']     = ( 'flat' === $update_params['amount_type'] ) ?
							'$' . $update_params['amount_value'] : $update_params['amount_value'];
						$updated                  = true;
					}
					break;

				case 'dates':
					if ( ! empty( $update_params['start_date'] ) ) {
						$meta['startDate'] = $update_params['start_date'];
						$updated           = true;
					}
					if ( isset( $update_params['expiry_date'] ) ) {
						$meta['endDate'] = $update_params['expiry_date'];
						$updated         = true;
					}
					break;

				case 'usage':
					$meta['usageLimit'] = (string) $update_params['usage_limit'];
					$updated            = true;
					break;

				case 'stackable':
					$meta['isStackable'] = (string) $update_params['is_stackable'];
					$updated             = true;
					break;

				case 'activate':
				case 'deactivate':
					// Validate the is_active value before update.
					$is_active_value = isset( $update_params['is_active'] ) ? intval( $update_params['is_active'] ) : 1;

					// Update is_active field directly.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$update_result = $wpdb->update(
						$wpdb->prefix . 'gf_addon_feed',
						array( 'is_active' => $is_active_value ),
						array( 'id' => intval( $coupon->id ) )
					);

					if ( false !== $update_result ) {
						$result['status']  = 'success';
						$result['message'] = ( 'activate' === $update_action ) ? 'Coupon activated' : 'Coupon deactivated';
					} else {
						$result['message'] = 'Failed to update coupon status';
					}
					$results['results'][] = $result;
					continue 2;
			}

			// Save updated meta.
			if ( $updated ) {
				// Validate meta data before update.
				$encoded_meta = wp_json_encode( $meta );
				if ( false === $encoded_meta || null === $encoded_meta || '' === $encoded_meta ) {
					$result['message']    = 'Failed to encode coupon meta data';
					$results['results'][] = $result;
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$update_result = $wpdb->update(
					$wpdb->prefix . 'gf_addon_feed',
					array( 'meta' => $encoded_meta ),
					array( 'id' => intval( $coupon->id ) )
				);

				if ( false !== $update_result ) {
					$result['status']  = 'success';
					$result['message'] = 'Coupon updated successfully';
				} else {
					$result['message'] = 'Failed to update coupon';
				}
			} else {
				$result['message'] = 'No changes to apply';
			}

			$results['results'][] = $result;
		}

		return $results;
	}
}

// Initialize the plugin.
Coupmafo_Coupon_Generator::get_instance();
