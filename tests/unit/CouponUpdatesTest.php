<?php
/**
 * Unit tests for coupon updates
 */

class Test_Coupon_Updates extends GF_Coupon_Test_Case {
    
    /**
     * Test updating discount amount
     */
    public function test_update_discount_percentage() {
        // Create initial coupon
        $result = $this->create_test_coupon(array(
            'amount_type' => 'percentage',
            'amount_value' => '10'
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];
        
        // Update via CSV
        $csv_content = "coupon_code\n{$coupon_code}";
        $update_result = $this->plugin->update_coupons(
            array($coupon_code), 
            'discount',
            array(
                'amount_type' => 'percentage',
                'amount_value' => '25'
            )
        );
        
        // Verify update
        $this->assertEquals('success', $update_result['results'][0]['status']);
        $this->assertCouponExists($coupon_code, array(
            'couponAmountType' => 'percentage',
            'couponAmount' => '25'
        ));
    }
    
    /**
     * Test changing from percentage to flat amount
     */
    public function test_update_discount_type_change() {
        // Create percentage coupon
        $result = $this->create_test_coupon(array(
            'amount_type' => 'percentage',
            'amount_value' => '20'
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];
        
        // Change to flat amount
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'discount',
            array(
                'amount_type' => 'flat',
                'amount_value' => '10.00'
            )
        );
        
        // Verify update
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals('flat', $coupon->meta['couponAmountType']);
        $this->assertEquals('$10.00', $coupon->meta['couponAmount']);
    }
    
    /**
     * Test updating dates
     */
    public function test_update_dates() {
        // Create coupon with initial dates
        $result = $this->create_test_coupon(array(
            'start_date' => '2024-01-01',
            'expiry_date' => '2024-06-30'
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];
        
        // Update dates
        $new_start = '2024-02-01';
        $new_expiry = '2024-12-31';
        
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'dates',
            array(
                'start_date' => $new_start,
                'expiry_date' => $new_expiry
            )
        );
        
        // Verify update
        $this->assertCouponExists($coupon_code, array(
            'startDate' => $new_start,
            'endDate' => $new_expiry
        ));
    }
    
    /**
     * Test clearing expiry date
     */
    public function test_clear_expiry_date() {
        // Create coupon with expiry
        $result = $this->create_test_coupon(array(
            'expiry_date' => '2024-12-31'
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];
        
        // Clear expiry date
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'dates',
            array(
                'start_date' => '2024-01-01',
                'expiry_date' => ''
            )
        );
        
        // Verify update
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals('', $coupon->meta['endDate']);
    }
    
    /**
     * Test updating usage limit
     */
    public function test_update_usage_limit() {
        // Create coupon
        $result = $this->create_test_coupon(array(
            'usage_limit' => 1
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];
        
        // Update usage limit
        $new_limit = 50;
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'usage',
            array('usage_limit' => $new_limit)
        );
        
        // Verify update
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals((string)$new_limit, $coupon->meta['usageLimit']);
    }
    
    /**
     * Test updating stackable status
     */
    public function test_update_stackable() {
        // Create non-stackable coupon
        $result = $this->create_test_coupon(array(
            'is_stackable' => 0
        ));
        $coupon_code = $result['coupons'][0]['coupon_code'];
        
        // Make stackable
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'stackable',
            array('is_stackable' => 1)
        );
        
        // Verify update
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals('1', $coupon->meta['isStackable']);
        
        // Make non-stackable again
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'stackable',
            array('is_stackable' => 0)
        );
        
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals('0', $coupon->meta['isStackable']);
    }
    
    /**
     * Test activating coupons
     */
    public function test_activate_coupons() {
        // Create coupon and manually deactivate it
        $result = $this->create_test_coupon();
        $coupon_code = $result['coupons'][0]['coupon_code'];
        $coupon_id = $result['coupons'][0]['id'];
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'gf_addon_feed',
            array('is_active' => 0),
            array('id' => $coupon_id)
        );
        
        // Activate via update
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'activate',
            array('is_active' => 1)
        );
        
        // Verify activation
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals(1, $coupon->is_active);
    }
    
    /**
     * Test deactivating coupons
     */
    public function test_deactivate_coupons() {
        // Create active coupon
        $result = $this->create_test_coupon();
        $coupon_code = $result['coupons'][0]['coupon_code'];
        
        // Deactivate
        $update_result = $this->plugin->update_coupons(
            array($coupon_code),
            'deactivate',
            array('is_active' => 0)
        );
        
        // Verify deactivation
        $coupon = $this->get_coupon_by_code($coupon_code);
        $this->assertEquals(0, $coupon->is_active);
    }
    
    /**
     * Test bulk updates
     */
    public function test_bulk_updates() {
        // Create multiple coupons
        $coupon_codes = array();
        for ($i = 1; $i <= 5; $i++) {
            $result = $this->create_test_coupon(array(
                'coupon_prefix' => "BULK{$i}_",
                'amount_value' => '10'
            ));
            $coupon_codes[] = $result['coupons'][0]['coupon_code'];
        }
        
        // Update all at once
        $update_result = $this->plugin->update_coupons(
            $coupon_codes,
            'discount',
            array(
                'amount_type' => 'percentage',
                'amount_value' => '30'
            )
        );
        
        // Verify all were updated
        $this->assertCount(5, $update_result['results']);
        foreach ($update_result['results'] as $result) {
            $this->assertEquals('success', $result['status']);
        }
        
        // Check database
        foreach ($coupon_codes as $code) {
            $this->assertCouponExists($code, array(
                'couponAmount' => '30'
            ));
        }
    }
    
    /**
     * Test updating non-existent coupon
     */
    public function test_update_nonexistent_coupon() {
        $update_result = $this->plugin->update_coupons(
            array('NONEXISTENT_CODE'),
            'discount',
            array(
                'amount_type' => 'percentage',
                'amount_value' => '20'
            )
        );
        
        $this->assertEquals('error', $update_result['results'][0]['status']);
        $this->assertEquals('Coupon not found', $update_result['results'][0]['message']);
    }
    
    /**
     * Test CSV parsing
     */
    public function test_csv_parsing() {
        // Test with header
        $csv_with_header = "coupon_code,other_field\nTEST1,value1\nTEST2,value2\nTEST3,value3";
        $codes = $this->invokeMethod($this->plugin, 'parse_csv_for_codes', array($csv_with_header));
        $this->assertEquals(array('TEST1', 'TEST2', 'TEST3'), $codes);
        
        // Test without header
        $csv_without_header = "COUPON1\nCOUPON2\nCOUPON3";
        $codes = $this->invokeMethod($this->plugin, 'parse_csv_for_codes', array($csv_without_header));
        $this->assertEquals(array('COUPON1', 'COUPON2', 'COUPON3'), $codes);
        
        // Test with empty lines and duplicates
        $csv_messy = "CODE1\n\nCODE2\nCODE1\n\nCODE3\n";
        $codes = $this->invokeMethod($this->plugin, 'parse_csv_for_codes', array($csv_messy));
        $this->assertEquals(array('CODE1', 'CODE2', 'CODE3'), $codes);
    }
    
    /**
     * Test database integrity after updates
     */
    public function test_database_integrity_after_updates() {
        // Create coupons
        $coupon_codes = array();
        for ($i = 1; $i <= 10; $i++) {
            $result = $this->create_test_coupon(array(
                'coupon_prefix' => "UPDATE{$i}_"
            ));
            $coupon_codes[] = $result['coupons'][0]['coupon_code'];
        }
        
        // Perform various updates
        $half = array_slice($coupon_codes, 0, 5);
        $other_half = array_slice($coupon_codes, 5);
        
        $this->plugin->update_coupons($half, 'discount', array(
            'amount_type' => 'flat',
            'amount_value' => '25'
        ));
        
        $this->plugin->update_coupons($other_half, 'usage', array(
            'usage_limit' => 100
        ));
        
        $this->plugin->update_coupons($coupon_codes, 'dates', array(
            'start_date' => '2024-01-01',
            'expiry_date' => '2024-12-31'
        ));
        
        // Check database integrity
        $this->assertDatabaseIntegrity();
    }
    
    /**
     * Helper method to invoke private methods for testing
     */
    protected function invokeMethod($object, $methodName, array $parameters = array()) {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
} 