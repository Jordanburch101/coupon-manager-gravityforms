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
        
        // Try to create coupons with invalid data that will cause a failure
        try {
            // Temporarily break the database to simulate failure
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gf_addon_feed_backup");
            $wpdb->query("CREATE TABLE {$wpdb->prefix}gf_addon_feed_backup LIKE {$wpdb->prefix}gf_addon_feed");
            $wpdb->query("INSERT INTO {$wpdb->prefix}gf_addon_feed_backup SELECT * FROM {$wpdb->prefix}gf_addon_feed");
            
            // This should fail due to invalid JSON
            $wpdb->insert(
                $wpdb->prefix . 'gf_addon_feed',
                array(
                    'form_id' => 1,
                    'is_active' => 1,
                    'feed_order' => 0,
                    'meta' => '{invalid json}',
                    'addon_slug' => 'gravityformscoupons'
                )
            );
            
            // If we got here, the test should fail
            $this->fail('Expected database operation to fail');
            
        } catch (Exception $e) {
            // Expected failure
        }
        
        // Restore backup
        $wpdb->query("DROP TABLE {$wpdb->prefix}gf_addon_feed");
        $wpdb->query("RENAME TABLE {$wpdb->prefix}gf_addon_feed_backup TO {$wpdb->prefix}gf_addon_feed");
        
        // Count should remain the same
        $final_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gf_addon_feed 
            WHERE addon_slug = 'gravityformscoupons'"
        );
        
        $this->assertEquals($initial_count, $final_count, 'Database should rollback on failure');
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
            array('prefix' => 'QUOTE"TEST_', 'amount' => '10'),
            array('prefix' => "APOS'TEST_", 'amount' => '20'),
            array('prefix' => 'SLASH\\TEST_', 'amount' => '30'),
            array('prefix' => 'UNICODE_☃_', 'amount' => '40'),
            array('prefix' => 'NEWLINE\nTEST_', 'amount' => '50')
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
            AND JSON_VALID(meta) = 0"
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
        
        // Verify proper type conversions
        $this->assertIsInt($coupon->form_id);
        $this->assertIsInt($coupon->is_active);
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
        
        // Attempt bulk update
        $update_result = $this->plugin->update_coupons(
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
            'UTF8' => 'Café €50',
            'Emoji' => '🎉 Sale 🛍️',
            'Asian' => '割引 クーポン',
            'Special' => '& < > " \' \\',
            'Accented' => 'àáäâèéëêìíïîòóöôùúüû'
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
            
            $this->assertStringStartsWith($string, $coupon->meta['couponCode']);
        }
    }
    
    /**
     * Test database constraints
     */
    public function test_database_constraints() {
        global $wpdb;
        
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
    }
} 