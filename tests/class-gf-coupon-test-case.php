<?php
/**
 * Base test case class for GF Coupon Generator tests
 */

class GF_Coupon_Test_Case extends WP_UnitTestCase {

    protected $plugin;
    protected $original_tables = array();

    /**
     * Set up test environment before each test
     */
    public function setUp(): void {
        parent::setUp();

        // Get plugin instance
        $this->plugin = GF_Coupon_Generator::get_instance();

        // Create necessary database tables
        $this->create_gf_tables();

        // Start database transaction for rollback
        global $wpdb;
        $wpdb->query('START TRANSACTION');
    }

    /**
     * Clean up after each test
     */
    public function tearDown(): void {
        global $wpdb;

        // Rollback database changes
        $wpdb->query('ROLLBACK');

        // Clean up any test data
        $this->cleanup_test_data();

        parent::tearDown();
    }

    /**
     * Create GravityForms tables for testing
     */
    protected function create_gf_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create gf_addon_feed table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gf_addon_feed (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_id mediumint(9) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            feed_order mediumint(9) NOT NULL DEFAULT 0,
            meta longtext NOT NULL,
            addon_slug varchar(50) NOT NULL,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY addon_slug (addon_slug)
        ) $charset_collate;";

        $wpdb->query($sql);
    }

    /**
     * Clean up test data
     */
    protected function cleanup_test_data() {
        global $wpdb;

        // Remove test coupons
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'
            AND meta LIKE '%TEST_%'"
        );
    }

    /**
     * Create a test coupon
     */
    protected function create_test_coupon($args = array()) {
        $defaults = array(
            'form_id' => 1,
            'coupon_prefix' => 'TEST_',
            'coupon_length' => 8,
            'amount_type' => 'percentage',
            'amount_value' => '10',
            'start_date' => '',
            'expiry_date' => '',
            'usage_limit' => 1,
            'is_stackable' => 0,
            'quantity' => 1
        );

        $args = wp_parse_args($args, $defaults);

        return $this->plugin->generate_coupons(
            $args['form_id'],
            $args['coupon_prefix'],
            $args['coupon_length'],
            $args['amount_type'],
            $args['amount_value'],
            $args['start_date'],
            $args['expiry_date'],
            $args['usage_limit'],
            $args['is_stackable'],
            $args['quantity']
        );
    }

    /**
     * Get coupon by code
     */
    protected function get_coupon_by_code($coupon_code) {
        global $wpdb;

        $coupon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'
            AND meta LIKE %s",
            '%"couponCode":"' . $wpdb->esc_like($coupon_code) . '"%'
        ));

        if ($coupon) {
            $coupon->meta = json_decode($coupon->meta, true);
        }

        return $coupon;
    }

    /**
     * Assert coupon exists with expected properties
     */
    protected function assertCouponExists($coupon_code, $expected_properties = array()) {
        $coupon = $this->get_coupon_by_code($coupon_code);

        $this->assertNotNull($coupon, "Coupon with code {$coupon_code} should exist");

        foreach ($expected_properties as $key => $value) {
            if (isset($coupon->meta[$key])) {
                $this->assertEquals($value, $coupon->meta[$key], "Coupon property {$key} should match");
            }
        }
    }

    /**
     * Assert database integrity
     */
    protected function assertDatabaseIntegrity() {
        global $wpdb;

        // Check for invalid JSON in meta fields
        $invalid_json = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'
            AND (meta IS NULL OR meta = '' OR JSON_VALID(meta) = 0)"
        );

        $this->assertEquals(0, $invalid_json, "All coupons should have valid JSON meta data");

        // Check for duplicate coupon codes
        $duplicates = $wpdb->get_var(
            "SELECT COUNT(*) FROM (
                SELECT JSON_EXTRACT(meta, '$.couponCode') as code, COUNT(*) as count
                FROM {$wpdb->prefix}gf_addon_feed
                WHERE addon_slug = 'gravityformscoupons'
                GROUP BY code
                HAVING count > 1
            ) as duplicates"
        );

        $this->assertEquals(0, $duplicates, "There should be no duplicate coupon codes");
    }

    /**
     * Simulate AJAX request
     */
    protected function make_ajax_request($action, $data = array()) {
        // Set up the request
        $_POST = array_merge($data, array(
            'action' => $action,
            'nonce' => wp_create_nonce('gf_coupon_generator_nonce')
        ));

        // Simulate admin user
        wp_set_current_user(1);

        // Capture the response
        ob_start();

        try {
            do_action('wp_ajax_' . $action);
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        $response = ob_get_clean();

        return json_decode($response, true);
    }
}
