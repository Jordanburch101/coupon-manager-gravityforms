<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get all active GF forms
$forms = array();
if (class_exists('GFAPI')) {
    $gf_forms = GFAPI::get_forms(true);
    foreach ($gf_forms as $form) {
        $forms[$form['id']] = $form['title'];
    }
}
?>

<div class="wrap gf-coupon-generator">
    <h1><?php _e('GravityForms Coupon Generator', 'gf-coupon-generator'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This tool allows you to bulk generate coupons for GravityForms. Configure the options below and click "Generate Coupons".', 'gf-coupon-generator'); ?></p>
    </div>
    
    <form id="gf-coupon-generator-form" method="post">
        <div class="form-container">
            <div class="form-section">
                <h2><?php _e('Basic Settings', 'gf-coupon-generator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="form_id"><?php _e('Target Form', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select name="form_id" id="form_id" required>
                                <option value=""><?php _e('-- Select Form --', 'gf-coupon-generator'); ?></option>
                                <?php foreach ($forms as $id => $title): ?>
                                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($title); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Select the form these coupons will be applied to.', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="quantity"><?php _e('Quantity', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="quantity" id="quantity" min="1" max="1000" value="10" required>
                            <p class="description"><?php _e('Number of coupons to generate (max 1000).', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="form-section">
                <h2><?php _e('Coupon Settings', 'gf-coupon-generator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="coupon_prefix"><?php _e('Coupon Prefix', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="coupon_prefix" id="coupon_prefix" value="">
                            <p class="description"><?php _e('Optional prefix for coupon codes. Example: "SUMMER-" would generate "SUMMER-x7dQ2p".', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="coupon_length"><?php _e('Coupon Code Length', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="coupon_length" id="coupon_length" min="4" max="32" value="8" required>
                            <p class="description"><?php _e('Length of the random part of the coupon code (excluding prefix).', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="amount_type"><?php _e('Discount Type', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <select name="amount_type" id="amount_type" required>
                                <option value="percentage"><?php _e('Percentage (%)', 'gf-coupon-generator'); ?></option>
                                <option value="flat"><?php _e('Flat Amount', 'gf-coupon-generator'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="amount_value"><?php _e('Discount Value', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="amount_value" id="amount_value" value="10" required>
                            <p class="description" id="discount_description"><?php _e('Amount of the discount (without % symbol).', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="usage_limit"><?php _e('Usage Limit', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="usage_limit" id="usage_limit" min="0" value="1">
                            <p class="description"><?php _e('Number of times each coupon can be used. Enter 0 for unlimited.', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="is_stackable"><?php _e('Stackable', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="is_stackable" id="is_stackable" value="1">
                            <label for="is_stackable"><?php _e('Allow this coupon to be combined with other coupons', 'gf-coupon-generator'); ?></label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="form-section">
                <h2><?php _e('Date Settings', 'gf-coupon-generator'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="start_date"><?php _e('Start Date', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="start_date" id="start_date" value="<?php echo date('Y-m-d'); ?>">
                            <p class="description"><?php _e('Date when coupons become valid. Leave blank for no start date restriction.', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="expiry_date"><?php _e('Expiry Date', 'gf-coupon-generator'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="expiry_date" id="expiry_date" value="">
                            <p class="description"><?php _e('Date when coupons expire. Leave blank for no expiration.', 'gf-coupon-generator'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="submit-section">
                <button type="submit" id="generate-coupons-btn" class="button button-primary button-large">
                    <?php _e('Generate Coupons', 'gf-coupon-generator'); ?>
                </button>
                <span class="spinner"></span>
            </div>
        </div>
    </form>
    
    <div id="results-container" style="display:none;">
        <h2><?php _e('Generated Coupons', 'gf-coupon-generator'); ?></h2>
        <div class="notice notice-success">
            <p id="success-message"></p>
        </div>
        
        <table class="widefat" id="coupons-table">
            <thead>
                <tr>
                    <th><?php _e('ID', 'gf-coupon-generator'); ?></th>
                    <th><?php _e('Coupon Code', 'gf-coupon-generator'); ?></th>
                </tr>
            </thead>
            <tbody id="coupons-list">
                <!-- Results will be populated here -->
            </tbody>
        </table>
        
        <p>
            <button type="button" id="export-csv-btn" class="button button-secondary">
                <?php _e('Export as CSV', 'gf-coupon-generator'); ?>
            </button>
        </p>
    </div>
</div> 