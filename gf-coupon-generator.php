<?php
/**
 * Plugin Name: GravityForms Coupon Generator
 * Description: Bulk generate coupons for GravityForms Coupons addon
 * Version: 1.0.0
 * Author: Jordan Burch
 * Author URI: https://github.com/Jordanburch101
 * Text Domain: gf-coupon-generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GF_COUPON_GEN_VERSION', '1.0.0');
define('GF_COUPON_GEN_PATH', plugin_dir_path(__FILE__));
define('GF_COUPON_GEN_URL', plugin_dir_url(__FILE__));

// Main plugin class
class GF_Coupon_Generator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check if GravityForms is active
        add_action('admin_init', array($this, 'check_dependencies'));
        
        // Use GravityForms own hooks for adding menu items
        add_action('gform_addon_navigation', array($this, 'add_menu_item'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Ajax handlers
        add_action('wp_ajax_generate_gf_coupons', array($this, 'generate_coupons_ajax'));
    }
    
    public function check_dependencies() {
        if (!class_exists('GFForms') || !class_exists('GFCoupons')) {
            add_action('admin_notices', array($this, 'admin_notice_missing_gf'));
        }
    }
    
    public function admin_notice_missing_gf() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('GF Coupon Generator requires both GravityForms and GravityForms Coupons addon to be installed and activated.', 'gf-coupon-generator'); ?></p>
        </div>
        <?php
    }
    
    public function add_menu_item($menus) {
        $menus[] = array(
            'name' => 'gf_coupon_generator',
            'label' => __('Coupon Generator', 'gf-coupon-generator'),
            'callback' => array($this, 'render_admin_page'),
            'permission' => 'gravityforms_edit_forms'
        );
        
        return $menus;
    }
    
    public function enqueue_admin_assets($hook) {
        // Updated hook check for GF pages
        if (is_admin() && isset($_GET['page']) && $_GET['page'] == 'gf_coupon_generator') {
            wp_enqueue_style(
                'gf-coupon-generator-css',
                GF_COUPON_GEN_URL . 'assets/css/admin.css',
                array(),
                GF_COUPON_GEN_VERSION
            );
            
            wp_enqueue_script(
                'gf-coupon-generator-js',
                GF_COUPON_GEN_URL . 'assets/js/admin.js',
                array('jquery'),
                GF_COUPON_GEN_VERSION,
                true
            );
            
            wp_localize_script('gf-coupon-generator-js', 'gfCouponGen', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gf_coupon_generator_nonce'),
            ));
        }
    }
    
    public function render_admin_page() {
        include GF_COUPON_GEN_PATH . 'views/admin-page.php';
    }
    
    public function generate_coupons_ajax() {
        check_ajax_referer('gf_coupon_generator_nonce', 'nonce');
        
        if (!current_user_can('gravityforms_edit_forms')) {
            wp_send_json_error('Permission denied');
        }
        
        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        $coupon_prefix = isset($_POST['coupon_prefix']) ? sanitize_text_field($_POST['coupon_prefix']) : '';
        $coupon_length = isset($_POST['coupon_length']) ? intval($_POST['coupon_length']) : 8;
        $amount_type = isset($_POST['amount_type']) ? sanitize_text_field($_POST['amount_type']) : 'percentage';
        $amount_value = isset($_POST['amount_value']) ? sanitize_text_field($_POST['amount_value']) : '0';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $expiry_date = isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : '';
        $usage_limit = isset($_POST['usage_limit']) ? intval($_POST['usage_limit']) : 1;
        $is_stackable = isset($_POST['is_stackable']) ? intval($_POST['is_stackable']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        // Validate inputs
        if (empty($form_id)) {
            wp_send_json_error('Form ID is required');
        }
        
        if ($quantity < 1 || $quantity > 1000) {
            wp_send_json_error('Quantity must be between 1 and 1000');
        }
        
        $results = $this->generate_coupons($form_id, $coupon_prefix, $coupon_length, $amount_type, 
                                          $amount_value, $start_date, $expiry_date, $usage_limit, 
                                          $is_stackable, $quantity);
        
        wp_send_json_success($results);
    }
    
    public function generate_coupons($form_id, $coupon_prefix, $coupon_length, $amount_type, 
                                    $amount_value, $start_date, $expiry_date, $usage_limit, 
                                    $is_stackable, $quantity) {
        global $wpdb;
        
        $results = array(
            'success' => 0,
            'failed' => 0,
            'coupons' => array()
        );
        
        for ($i = 0; $i < $quantity; $i++) {
            // Generate coupon code
            $random_part = $this->generate_random_code($coupon_length);
            $coupon_code = $coupon_prefix . $random_part;
            
            // Initial coupon name - will be updated after insertion
            $coupon_name = 'Coupon';
            
            // Format amount value based on type (add $ for flat amounts)
            $amount_display = ($amount_type === 'flat') ? '$' . $amount_value : $amount_value;
            
            // Prepare meta data - match the exact structure GF Coupons expects
            $meta = array(
                'gravityForm' => $form_id,
                'couponName' => $coupon_name,
                'couponCode' => $coupon_code,
                'couponAmountType' => $amount_type,
                'couponAmount' => $amount_display,
                'startDate' => $start_date,
                'endDate' => $expiry_date, // Always include endDate field, even if empty
                'usageLimit' => (string)$usage_limit, // Convert to string to match GF format
                'isStackable' => (string)$is_stackable // Convert to string to match GF format
            );
            
            // Insert into database
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'gf_addon_feed',
                array(
                    'form_id' => $form_id,
                    'is_active' => 1,
                    'feed_order' => 0,
                    'meta' => json_encode($meta),
                    'addon_slug' => 'gravityformscoupons'
                )
            );
            
            if ($inserted) {
                $feed_id = $wpdb->insert_id;
                
                // Now update the name with the ID
                $updated_name = "Coupon - #{$feed_id}";
                $meta['couponName'] = $updated_name;
                
                // Update the record with the new name
                $wpdb->update(
                    $wpdb->prefix . 'gf_addon_feed',
                    array('meta' => json_encode($meta)),
                    array('id' => $feed_id)
                );
                
                $results['success']++;
                $results['coupons'][] = array(
                    'id' => $feed_id,
                    'coupon_code' => $coupon_code
                );
            } else {
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    private function generate_random_code($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        return $code;
    }
}

// Initialize plugin
function gf_coupon_generator_init() {
    return GF_Coupon_Generator::get_instance();
}

// Start the plugin
gf_coupon_generator_init(); 