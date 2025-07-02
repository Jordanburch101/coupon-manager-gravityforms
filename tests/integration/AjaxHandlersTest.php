<?php
/**
 * Integration tests for AJAX handlers
 */

class Test_Ajax_Handlers extends GF_Coupon_Test_Case {

    /**
     * Test AJAX coupon generation with valid data
     */
    public function test_ajax_generate_coupons_success() {
        // Create admin user
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        // Simulate AJAX request
        $response = $this->make_ajax_request('generate_gf_coupons', array(
            'form_id' => 1,
            'coupon_prefix' => 'AJAX_TEST_',
            'coupon_length' => 8,
            'amount_type' => 'percentage',
            'amount_value' => '15',
            'start_date' => '2024-01-01',
            'expiry_date' => '2024-12-31',
            'usage_limit' => 5,
            'is_stackable' => 0,
            'quantity' => 3
        ));

        // Verify response
        $this->assertTrue($response['success']);
        $this->assertEquals(3, $response['data']['success']);
        $this->assertEquals(0, $response['data']['failed']);
        $this->assertCount(3, $response['data']['coupons']);

        // Verify coupons in database
        foreach ($response['data']['coupons'] as $coupon) {
            $this->assertCouponExists($coupon['coupon_code'], array(
                'couponAmountType' => 'percentage',
                'couponAmount' => '15',
                'startDate' => '2024-01-01',
                'endDate' => '2024-12-31',
                'usageLimit' => '5'
            ));
        }
    }

    /**
     * Test AJAX coupon generation with missing form ID
     */
    public function test_ajax_generate_coupons_missing_form_id() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        $response = $this->make_ajax_request('generate_gf_coupons', array(
            'coupon_prefix' => 'TEST_',
            'quantity' => 1
        ));

        $this->assertFalse($response['success']);
        $this->assertEquals('Form ID is required', $response['data']);
    }

    /**
     * Test AJAX coupon generation with invalid quantity
     */
    public function test_ajax_generate_coupons_invalid_quantity() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        // Test quantity too high
        $response = $this->make_ajax_request('generate_gf_coupons', array(
            'form_id' => 1,
            'quantity' => 1001
        ));

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('between 1 and 1000', $response['data']);

        // Test quantity too low
        $response = $this->make_ajax_request('generate_gf_coupons', array(
            'form_id' => 1,
            'quantity' => 0
        ));

        $this->assertFalse($response['success']);
    }

    /**
     * Test AJAX coupon generation without proper permissions
     */
    public function test_ajax_generate_coupons_no_permission() {
        // Create subscriber user (no permission)
        $subscriber = $this->factory->user->create(array('role' => 'subscriber'));
        wp_set_current_user($subscriber);

        $response = $this->make_ajax_request('generate_gf_coupons', array(
            'form_id' => 1,
            'quantity' => 1
        ));

        $this->assertFalse($response['success']);
        $this->assertEquals('Permission denied', $response['data']);
    }

    /**
     * Test AJAX coupon update with CSV
     */
    public function test_ajax_update_coupons_discount() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        // First create some coupons
        $create_result = $this->create_test_coupon(array(
            'quantity' => 3,
            'coupon_prefix' => 'UPDATE_TEST_',
            'amount_type' => 'percentage',
            'amount_value' => '10'
        ));

        // Prepare CSV content
        $csv_content = "coupon_code\n";
        foreach ($create_result['coupons'] as $coupon) {
            $csv_content .= $coupon['coupon_code'] . "\n";
        }

        // Update coupons via AJAX
        $response = $this->make_ajax_request('update_gf_coupons', array(
            'csv_content' => $csv_content,
            'update_action' => 'discount',
            'new_amount_type' => 'flat',
            'new_amount_value' => '25.00'
        ));

        $this->assertTrue($response['success']);
        $this->assertCount(3, $response['data']['results']);

        // Verify all updates succeeded
        foreach ($response['data']['results'] as $result) {
            $this->assertEquals('success', $result['status']);

            // Verify in database
            $this->assertCouponExists($result['coupon_code'], array(
                'couponAmountType' => 'flat',
                'couponAmount' => '$25.00'
            ));
        }
    }

    /**
     * Test AJAX coupon update - dates
     */
    public function test_ajax_update_coupons_dates() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        // Create coupon
        $result = $this->create_test_coupon(array(
            'coupon_prefix' => 'DATE_UPDATE_'
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];

        // Update dates
        $response = $this->make_ajax_request('update_gf_coupons', array(
            'csv_content' => $coupon_code,
            'update_action' => 'dates',
            'new_start_date' => '2024-03-01',
            'new_expiry_date' => '2024-09-30'
        ));

        $this->assertTrue($response['success']);
        $this->assertCouponExists($coupon_code, array(
            'startDate' => '2024-03-01',
            'endDate' => '2024-09-30'
        ));
    }

    /**
     * Test AJAX coupon activation/deactivation
     */
    public function test_ajax_update_coupons_activation() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        // Create coupon
        $result = $this->create_test_coupon(array(
            'coupon_prefix' => 'ACTIVATION_TEST_'
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];

        // Deactivate
        $response = $this->make_ajax_request('update_gf_coupons', array(
            'csv_content' => $coupon_code,
            'update_action' => 'deactivate'
        ));

        $this->assertTrue($response['success']);
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals(0, $coupon->is_active);

        // Reactivate
        $response = $this->make_ajax_request('update_gf_coupons', array(
            'csv_content' => $coupon_code,
            'update_action' => 'activate'
        ));

        $this->assertTrue($response['success']);
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals(1, $coupon->is_active);
    }

    /**
     * Test AJAX update with invalid CSV
     */
    public function test_ajax_update_coupons_invalid_csv() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        // Empty CSV
        $response = $this->make_ajax_request('update_gf_coupons', array(
            'csv_content' => '',
            'update_action' => 'discount'
        ));

        $this->assertFalse($response['success']);
        $this->assertEquals('No valid coupon codes found in CSV', $response['data']);
    }

    /**
     * Test AJAX nonce verification
     */
    public function test_ajax_invalid_nonce() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        // Manually set invalid nonce
        $_POST = array(
            'action' => 'generate_gf_coupons',
            'nonce' => 'invalid_nonce',
            'form_id' => 1,
            'quantity' => 1
        );

        // This should trigger wp_die with error
        $this->expectException('WPDieException');
        do_action('wp_ajax_generate_gf_coupons');
    }

    /**
     * Test concurrent AJAX requests
     */
    public function test_concurrent_ajax_requests() {
        $admin_user = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($admin_user);

        $responses = array();

        // Simulate multiple concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->make_ajax_request('generate_gf_coupons', array(
                'form_id' => 1,
                'coupon_prefix' => "CONCURRENT{$i}_",
                'quantity' => 10
            ));
        }

        // All should succeed
        foreach ($responses as $response) {
            $this->assertTrue($response['success']);
            $this->assertEquals(10, $response['data']['success']);
        }

        // Verify no duplicate codes across all batches
        $all_codes = array();
        foreach ($responses as $response) {
            foreach ($response['data']['coupons'] as $coupon) {
                $all_codes[] = $coupon['coupon_code'];
            }
        }

        $unique_codes = array_unique($all_codes);
        $this->assertCount(50, $unique_codes, 'All 50 codes should be unique');
    }
}
