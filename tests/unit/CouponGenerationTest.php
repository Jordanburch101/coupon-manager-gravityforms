<?php
/**
 * Unit tests for coupon generation
 */

class Test_Coupon_Generation extends GF_Coupon_Test_Case {

    /**
     * Test basic coupon generation
     */
    public function test_generate_single_coupon() {
        $result = $this->create_test_coupon(array(
            'quantity' => 1,
            'coupon_prefix' => 'SINGLE_',
            'amount_type' => 'percentage',
            'amount_value' => '25'
        ));

        $this->assertEquals(1, $result['success'], 'Should generate 1 coupon successfully');
        $this->assertEquals(0, $result['failed'], 'Should have no failures');
        $this->assertCount(1, $result['coupons'], 'Should return 1 coupon');

        // Verify coupon in database
        $coupon_code = $result['coupons'][0]['coupon_code'];
        $this->assertCouponExists($coupon_code, array(
            'couponAmountType' => 'percentage',
            'couponAmount' => '25'
        ));
    }

    /**
     * Test bulk coupon generation
     */
    public function test_generate_bulk_coupons() {
        $quantity = 50;
        $result = $this->create_test_coupon(array(
            'quantity' => $quantity,
            'coupon_prefix' => 'BULK_'
        ));

        $this->assertEquals($quantity, $result['success'], "Should generate {$quantity} coupons successfully");
        $this->assertEquals(0, $result['failed'], 'Should have no failures');
        $this->assertCount($quantity, $result['coupons'], "Should return {$quantity} coupons");

        // Verify all coupon codes are unique
        $codes = array_column($result['coupons'], 'coupon_code');
        $unique_codes = array_unique($codes);
        $this->assertCount($quantity, $unique_codes, 'All coupon codes should be unique');
    }

    /**
     * Test flat amount coupons
     */
    public function test_generate_flat_amount_coupon() {
        $result = $this->create_test_coupon(array(
            'amount_type' => 'flat',
            'amount_value' => '15.50'
        ));

        $coupon_code = $result['coupons'][0]['coupon_code'];
        $coupon = $this->get_coupon_by_code($coupon_code);

        $this->assertEquals('flat', $coupon->meta['couponAmountType']);
        $this->assertEquals('$15.50', $coupon->meta['couponAmount'], 'Flat amounts should include $ prefix');
    }

    /**
     * Test coupon with dates
     */
    public function test_generate_coupon_with_dates() {
        $start_date = '2024-01-01';
        $expiry_date = '2024-12-31';

        $result = $this->create_test_coupon(array(
            'start_date' => $start_date,
            'expiry_date' => $expiry_date
        ));

        $coupon_code = $result['coupons'][0]['coupon_code'];
        $this->assertCouponExists($coupon_code, array(
            'startDate' => $start_date,
            'endDate' => $expiry_date
        ));
    }

    /**
     * Test coupon without expiry date
     */
    public function test_generate_coupon_without_expiry() {
        $result = $this->create_test_coupon(array(
            'start_date' => '2024-01-01',
            'expiry_date' => ''
        ));

        $coupon_code = $result['coupons'][0]['coupon_code'];
        $coupon = $this->get_coupon_by_code($coupon_code);

        $this->assertArrayHasKey('endDate', $coupon->meta, 'endDate field should exist even when empty');
        $this->assertEquals('', $coupon->meta['endDate'], 'Empty expiry date should be preserved');
    }

    /**
     * Test stackable coupons
     */
    public function test_generate_stackable_coupon() {
        $result = $this->create_test_coupon(array(
            'is_stackable' => 1
        ));

        $coupon_code = $result['coupons'][0]['coupon_code'];
        $coupon = $this->get_coupon_by_code($coupon_code);

        $this->assertEquals('1', $coupon->meta['isStackable'], 'Stackable should be stored as string "1"');
    }

    /**
     * Test usage limit
     */
    public function test_generate_coupon_with_usage_limit() {
        $usage_limit = 10;

        $result = $this->create_test_coupon(array(
            'usage_limit' => $usage_limit
        ));

        $coupon_code = $result['coupons'][0]['coupon_code'];
        $coupon = $this->get_coupon_by_code($coupon_code);

        $this->assertEquals((string)$usage_limit, $coupon->meta['usageLimit'], 'Usage limit should be stored as string');
    }

    /**
     * Test coupon name generation
     */
    public function test_coupon_name_includes_id() {
        $result = $this->create_test_coupon();

        $coupon_id = $result['coupons'][0]['id'];
        $coupon_code = $result['coupons'][0]['coupon_code'];
        $coupon = $this->get_coupon_by_code($coupon_code);

        $expected_name = "Coupon - #{$coupon_id}";
        $this->assertEquals($expected_name, $coupon->meta['couponName'], 'Coupon name should include ID');
    }

    /**
     * Test database integrity after bulk generation
     */
    public function test_database_integrity_after_bulk_generation() {
        // Generate multiple batches
        $this->create_test_coupon(array('quantity' => 20, 'coupon_prefix' => 'BATCH1_'));
        $this->create_test_coupon(array('quantity' => 30, 'coupon_prefix' => 'BATCH2_'));
        $this->create_test_coupon(array('quantity' => 25, 'coupon_prefix' => 'BATCH3_'));

        // Check database integrity
        $this->assertDatabaseIntegrity();
    }

    /**
     * Test maximum quantity limit
     */
    public function test_quantity_limits() {
        // Test maximum allowed
        $result = $this->create_test_coupon(array('quantity' => 1000));
        $this->assertEquals(1000, $result['success'], 'Should allow up to 1000 coupons');

        // Test AJAX validation for over limit
        $response = $this->make_ajax_request('generate_gf_coupons', array(
            'form_id' => 1,
            'quantity' => 1001,
            'coupon_prefix' => 'OVERLIMIT_'
        ));

        $this->assertFalse($response['success'], 'Should reject quantity over 1000');
        $this->assertStringContainsString('between 1 and 1000', $response['data']);
    }

    /**
     * Test different code lengths
     */
    public function test_different_code_lengths() {
        $lengths = array(4, 8, 12, 16);

        foreach ($lengths as $length) {
            $result = $this->create_test_coupon(array(
                'coupon_length' => $length,
                'coupon_prefix' => "LEN{$length}_"
            ));

            $coupon_code = $result['coupons'][0]['coupon_code'];
            $code_without_prefix = str_replace("LEN{$length}_", '', $coupon_code);

            $this->assertEquals($length, strlen($code_without_prefix),
                "Generated code should be {$length} characters long");
        }
    }
}
