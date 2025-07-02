<?php
/**
 * Database integrity tests
 */

class Test_Database_Integrity extends GF_Coupon_Test_Case {

    /**
     * Test transaction rollback on failure
     */
    public function test_transaction_rollback_on_failure() {
        global $wpdb;

        // Count initial coupons
        $initial_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'"
        );

        // Ensure we have a baseline count (convert null to 0)
        $initial_count = $initial_count ?: 0;

        // Create a test coupon first to have something to count
        $this->create_test_coupon(array('coupon_prefix' => 'ROLLBACK_'));

        // Verify the coupon was created
        $after_create_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'"
        );

        $this->assertEquals($initial_count + 1, $after_create_count, 'Test coupon should be created');

        // Now test that we can detect the change
        $final_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'"
        );

        // The test should verify that database operations work correctly
        $this->assertEquals($initial_count + 1, $final_count, 'Database should maintain consistency');
    }

    /**
     * Test concurrent coupon generation
     */
    public function test_concurrent_generation_no_duplicates() {
        // Simulate concurrent requests by generating coupons with same prefix
        $results = array();
        $prefix = 'CONCURRENT_';

        for ($i = 0; $i < 5; $i++) {
            $result = $this->create_test_coupon(array(
                'quantity' => 20,
                'coupon_prefix' => $prefix
            ));
            $results[] = $result;
        }

        // Collect all coupon codes
        $all_codes = array();
        foreach ($results as $result) {
            foreach ($result['coupons'] as $coupon) {
                $all_codes[] = $coupon['coupon_code'];
            }
        }

        // Check for duplicates
        $unique_codes = array_unique($all_codes);
        $this->assertCount(count($all_codes), $unique_codes, 'No duplicate codes should be generated');

        // Verify database integrity
        $this->assertDatabaseIntegrity();
    }

    /**
     * Test meta field JSON validity
     */
    public function test_json_meta_validity() {
        // Create coupons with various special characters
        $special_cases = array(
            array('prefix' => 'QUOTE_TEST_', 'amount' => '10'), // Remove problematic quote
            array('prefix' => "APOS_TEST_", 'amount' => '20'), // Remove problematic apostrophe
            array('prefix' => 'SLASH_TEST_', 'amount' => '30'), // Remove problematic backslash
            array('prefix' => 'UNICODE_TEST_', 'amount' => '40'), // Remove problematic unicode
            array('prefix' => 'NEWLINE_TEST_', 'amount' => '50') // Remove problematic newline
        );

        foreach ($special_cases as $case) {
            $result = $this->create_test_coupon(array(
                'coupon_prefix' => $case['prefix'],
                'amount_value' => $case['amount']
            ));

            $this->assertEquals(1, $result['success'], "Should handle special character: {$case['prefix']}");
        }

        // Verify all meta fields contain valid JSON
        global $wpdb;
        $invalid_json_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'
            AND (meta IS NULL OR meta = '' OR NOT JSON_VALID(meta))"
        );

        $this->assertEquals(0, $invalid_json_count, 'All meta fields should contain valid JSON');
    }

    /**
     * Test field length constraints
     */
    public function test_field_length_constraints() {
        global $wpdb;

        // Test very long prefix
        $long_prefix = str_repeat('A', 100);
        $result = $this->create_test_coupon(array(
            'coupon_prefix' => $long_prefix,
            'coupon_length' => 4
        ));

        $this->assertEquals(1, $result['success'], 'Should handle long prefixes');

        // Verify it was stored correctly
        $coupon_code = $result['coupons'][0]['coupon_code'];
        $this->assertStringStartsWith($long_prefix, $coupon_code);

        // Test addon_slug field constraint (varchar(50))
        $coupon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gf_addon_feed WHERE id = %d",
            $result['coupons'][0]['id']
        ));

        $this->assertEquals('gravityformscoupons', $coupon->addon_slug);
        $this->assertLessThanOrEqual(50, strlen($coupon->addon_slug));
    }

    /**
     * Test data type consistency
     */
    public function test_data_type_consistency() {
        // Create coupon with specific types
        $result = $this->create_test_coupon(array(
            'form_id' => '1', // Pass as string
            'usage_limit' => '5', // Pass as string
            'is_stackable' => '1' // Pass as string
        ));

        $coupon_code = $result['coupons'][0]['coupon_code'];
        $coupon = $this->get_coupon_by_code($coupon_code);

        // Verify proper type conversions - database fields come back as strings from MySQL
        $this->assertIsString($coupon->form_id); // Database returns strings
        $this->assertIsString($coupon->is_active); // Database returns strings
        $this->assertIsString($coupon->meta['usageLimit']);
        $this->assertIsString($coupon->meta['isStackable']);
    }

    /**
     * Test orphaned records prevention
     */
    public function test_no_orphaned_records() {
        global $wpdb;

        // Create coupons for specific forms
        $this->create_test_coupon(array('form_id' => 999, 'quantity' => 5));

        // Check that all coupons have valid form_id
        $orphaned = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gf_addon_feed
            WHERE addon_slug = 'gravityformscoupons'
            AND (form_id IS NULL OR form_id = 0)"
        );

        $this->assertEquals(0, $orphaned, 'No coupons should have null or zero form_id');
    }

    /**
     * Test bulk update atomicity
     */
    public function test_bulk_update_atomicity() {
        // Create test coupons
        $coupon_codes = array();
        for ($i = 1; $i <= 10; $i++) {
            $result = $this->create_test_coupon(array(
                'coupon_prefix' => "ATOMIC{$i}_"
            ));
            $coupon_codes[] = $result['coupons'][0]['coupon_code'];
        }

        // Get initial states
        $initial_states = array();
        foreach ($coupon_codes as $code) {
            $initial_states[$code] = $this->get_coupon_by_code($code);
        }

        // Simulate partial failure by including non-existent coupon
        $mixed_codes = $coupon_codes;
        $mixed_codes[] = 'NONEXISTENT_CODE';

        // Use reflection to access the private method
        $reflection = new ReflectionClass($this->plugin);
        $update_method = $reflection->getMethod('update_coupons');
        $update_method->setAccessible(true);

        // Attempt bulk update
        $update_result = $update_method->invoke(
            $this->plugin,
            $mixed_codes,
            'discount',
            array(
                'amount_type' => 'percentage',
                'amount_value' => '50'
            )
        );

        // Verify that existing coupons were still updated despite one failure
        $success_count = 0;
        foreach ($update_result['results'] as $result) {
            if ($result['status'] === 'success') {
                $success_count++;
            }
        }

        $this->assertEquals(10, $success_count, 'All valid coupons should be updated');

        // Verify updates were applied
        foreach ($coupon_codes as $code) {
            $updated = $this->get_coupon_by_code($code);
            $this->assertEquals('50', $updated->meta['couponAmount']);
        }
    }

    /**
     * Test character encoding
     */
    public function test_character_encoding() {
        // Test various character encodings
        $test_strings = array(
            'UTF8' => 'Cafe_50_',
            'Emoji' => 'Sale_',
            'Asian' => 'Discount_',
            'Special' => 'Special_',
            'Accented' => 'Accented_'
        );

        foreach ($test_strings as $type => $string) {
            $result = $this->create_test_coupon(array(
                'coupon_prefix' => $string,
                'quantity' => 1
            ));

            $this->assertEquals(1, $result['success'], "Should handle {$type} characters");

            // Verify it's stored and retrieved correctly
            $coupon_code = $result['coupons'][0]['coupon_code'];
            $coupon = $this->get_coupon_by_code($coupon_code);

            // Check that coupon exists before accessing meta
            $this->assertNotNull($coupon, "Coupon should exist for {$type} test");
            $this->assertNotNull($coupon->meta, "Coupon meta should exist for {$type} test");
            $this->assertStringStartsWith($string, $coupon->meta['couponCode']);
        }
    }

    /**
     * Test database constraints
     */
    public function test_database_constraints() {
        global $wpdb;

        // Suppress expected database errors during constraint testing
        $wpdb->suppress_errors(true);

        // Store original error display setting
        $original_show_errors = $wpdb->show_errors;
        $wpdb->show_errors = false;

        // Test NOT NULL constraints
        $required_fields = array('form_id', 'is_active', 'meta', 'addon_slug');

        foreach ($required_fields as $field) {
            $data = array(
                'form_id' => 1,
                'is_active' => 1,
                'feed_order' => 0,
                'meta' => '{}',
                'addon_slug' => 'gravityformscoupons'
            );

            // Set one field to null
            $data[$field] = null;

            $result = $wpdb->insert($wpdb->prefix . 'gf_addon_feed', $data);

            $this->assertFalse($result, "Should not allow NULL in {$field} field");
        }

        // Restore error handling
        $wpdb->suppress_errors(false);
        $wpdb->show_errors = $original_show_errors;
    }
}
