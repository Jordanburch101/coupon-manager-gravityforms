<?php
/**
 * Plugin Name: GravityForms Coupon Generator
 * Description: Bulk generate coupons for GravityForms Coupons addon
 * Version: 1.0.0
 * Author: Jordan Burch
 * Author URI: https://github.com/Jordanburch101
 * Text Domain: gf-coupon-generator
 * Package: GF_Coupon_Generator
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'GF_COUPON_GEN_VERSION', '1.0.0' );
define( 'GF_COUPON_GEN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GF_COUPON_GEN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class for GravityForms Coupon Generator.
 */
class GF_Coupon_Generator {

	/**
	 * Single instance of the class.
	 *
	 * @var GF_Coupon_Generator|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance of the class.
	 *
	 * @return GF_Coupon_Generator
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
		add_action( 'wp_ajax_generate_gf_coupons', array( $this, 'generate_coupons_ajax' ) );
		add_action( 'wp_ajax_update_gf_coupons', array( $this, 'update_coupons_ajax' ) );
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
			<p><?php esc_html_e( 'GF Coupon Generator requires both GravityForms and GravityForms Coupons addon to be installed and activated.', 'gf-coupon-generator' ); ?></p>
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
			'name'       => 'gf_coupon_generator',
			'label'      => __( 'Coupon Generator', 'gf-coupon-generator' ),
			'callback'   => array( $this, 'render_admin_page' ),
			'permission' => 'gravityforms_edit_forms',
		);

		return $menus;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Updated hook check for GF pages.
		if ( is_admin() && isset( $_GET['page'] ) && 'gf_coupon_generator' === $_GET['page'] ) {
			wp_enqueue_style(
				'gf-coupon-generator-css',
				GF_COUPON_GEN_URL . 'assets/css/admin.css',
				array(),
				GF_COUPON_GEN_VERSION
			);

			wp_enqueue_script(
				'gf-coupon-generator-js',
				GF_COUPON_GEN_URL . 'assets/js/admin.js',
				array( 'jquery' ),
				GF_COUPON_GEN_VERSION,
				true
			);

			wp_localize_script(
				'gf-coupon-generator-js',
				'gfCouponGen',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'gf_coupon_generator_nonce' ),
				)
			);
		}
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		include GF_COUPON_GEN_PATH . 'views/admin-page.php';
	}

	/**
	 * Handle AJAX request for generating coupons.
	 */
	public function generate_coupons_ajax() {
		check_ajax_referer( 'gf_coupon_generator_nonce', 'nonce' );

		if ( ! current_user_can( 'gravityforms_edit_forms' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$form_id        = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;
		$coupon_prefix  = isset( $_POST['coupon_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prefix'] ) ) : '';
		$coupon_length  = isset( $_POST['coupon_length'] ) ? intval( $_POST['coupon_length'] ) : 8;
		$amount_type    = isset( $_POST['amount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['amount_type'] ) ) : 'percentage';
		$amount_value   = isset( $_POST['amount_value'] ) ? sanitize_text_field( wp_unslash( $_POST['amount_value'] ) ) : '0';
		$start_date     = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$expiry_date    = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
		$usage_limit    = isset( $_POST['usage_limit'] ) ? intval( $_POST['usage_limit'] ) : 1;
		$is_stackable   = isset( $_POST['is_stackable'] ) ? intval( $_POST['is_stackable'] ) : 0;
		$quantity       = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;

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

		$results = array(
			'success' => 0,
			'failed'  => 0,
			'coupons' => array(),
		);

		for ( $i = 0; $i < $quantity; $i++ ) {
			// Generate coupon code.
			$random_part  = $this->generate_random_code( $coupon_length );
			$coupon_code  = $coupon_prefix . $random_part;

			// Initial coupon name - will be updated after insertion.
			$coupon_name = 'Coupon';

			// Format amount value based on type (add $ for flat amounts).
			$amount_display = ( 'flat' === $amount_type ) ? '$' . $amount_value : $amount_value;

			// Prepare meta data - match the exact structure GF Coupons expects.
			$meta = array(
				'gravityForm'       => $form_id,
				'couponName'        => $coupon_name,
				'couponCode'        => $coupon_code,
				'couponAmountType'  => $amount_type,
				'couponAmount'      => $amount_display,
				'startDate'         => $start_date,
				'endDate'           => $expiry_date, // Always include endDate field, even if empty.
				'usageLimit'        => (string) $usage_limit, // Convert to string to match GF format.
				'isStackable'       => (string) $is_stackable, // Convert to string to match GF format.
			);

			// Insert into database.
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'gf_addon_feed',
				array(
					'form_id'     => $form_id,
					'is_active'   => 1,
					'feed_order'  => 0,
					'meta'        => wp_json_encode( $meta ),
					'addon_slug'  => 'gravityformscoupons',
				)
			);

			if ( $inserted ) {
				$feed_id = $wpdb->insert_id;

				// Now update the name with the ID.
				$updated_name         = "Coupon - #{$feed_id}";
				$meta['couponName']   = $updated_name;

				// Update the record with the new name.
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
	 * Handle AJAX request for updating coupons.
	 */
	public function update_coupons_ajax() {
		check_ajax_referer( 'gf_coupon_generator_nonce', 'nonce' );

		if ( ! current_user_can( 'gravityforms_edit_forms' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$csv_content    = isset( $_POST['csv_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['csv_content'] ) ) : '';
		$update_action  = isset( $_POST['update_action'] ) ? sanitize_text_field( wp_unslash( $_POST['update_action'] ) ) : '';

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
						$updated = true;
					}
					break;

				case 'dates':
					if ( ! empty( $update_params['start_date'] ) ) {
						$meta['startDate'] = $update_params['start_date'];
						$updated = true;
					}
					if ( isset( $update_params['expiry_date'] ) ) {
						$meta['endDate'] = $update_params['expiry_date'];
						$updated = true;
					}
					break;

				case 'usage':
					$meta['usageLimit'] = (string) $update_params['usage_limit'];
					$updated = true;
					break;

				case 'stackable':
					$meta['isStackable'] = (string) $update_params['is_stackable'];
					$updated = true;
					break;

				case 'activate':
				case 'deactivate':
					// Update is_active field directly.
					$update_result = $wpdb->update(
						$wpdb->prefix . 'gf_addon_feed',
						array( 'is_active' => $update_params['is_active'] ),
						array( 'id' => $coupon->id )
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
				$update_result = $wpdb->update(
					$wpdb->prefix . 'gf_addon_feed',
					array( 'meta' => wp_json_encode( $meta ) ),
					array( 'id' => $coupon->id )
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

/**
 * Initialize plugin.
 *
 * @return GF_Coupon_Generator
 */
function gf_coupon_generator_init() {
	return GF_Coupon_Generator::get_instance();
}

// Start the plugin.
gf_coupon_generator_init(); 