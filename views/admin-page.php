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
    <h1><?php _e('GravityForms Coupon Manager', 'gf-coupon-generator'); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This tool allows you to bulk generate and update coupons for GravityForms.', 'gf-coupon-generator'); ?></p>
    </div>
    
    <!-- Tab Navigation -->
    <h2 class="nav-tab-wrapper">
        <a href="#generate" class="nav-tab nav-tab-active" data-tab="generate"><?php _e('Generate Coupons', 'gf-coupon-generator'); ?></a>
        <a href="#update" class="nav-tab" data-tab="update"><?php _e('Update Existing Coupons', 'gf-coupon-generator'); ?></a>
    </h2>
    
    <!-- Generate Tab Content -->
    <div id="generate-tab" class="tab-content active">
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
            <div id="generate-results-message"></div>
            
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
    
    <!-- Update Tab Content -->
    <div id="update-tab" class="tab-content" style="display:none;">
        <form id="gf-coupon-update-form" method="post" enctype="multipart/form-data">
            <div class="form-container">
                <div class="form-section">
                    <h2><?php _e('Update Existing Coupons', 'gf-coupon-generator'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="update_csv_file"><?php _e('CSV File', 'gf-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="update_csv_file" id="update_csv_file" accept=".csv" required>
                                <p class="description"><?php _e('Upload a CSV file with coupon codes (one column with header "coupon_code").', 'gf-coupon-generator'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="update_action"><?php _e('Update Action', 'gf-coupon-generator'); ?></label>
                            </th>
                            <td>
                                <select name="update_action" id="update_action" required>
                                    <option value=""><?php _e('-- Select Action --', 'gf-coupon-generator'); ?></option>
                                    <option value="discount"><?php _e('Change Discount Amount/Type', 'gf-coupon-generator'); ?></option>
                                    <option value="dates"><?php _e('Update Dates', 'gf-coupon-generator'); ?></option>
                                    <option value="usage"><?php _e('Update Usage Limit', 'gf-coupon-generator'); ?></option>
                                    <option value="stackable"><?php _e('Update Stackable Setting', 'gf-coupon-generator'); ?></option>
                                    <option value="deactivate"><?php _e('Deactivate Coupons', 'gf-coupon-generator'); ?></option>
                                    <option value="activate"><?php _e('Activate Coupons', 'gf-coupon-generator'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Dynamic update fields based on action -->
                <div id="update-fields-container" style="display:none;">
                    <!-- Discount fields -->
                    <div id="discount-fields" class="update-fields" style="display:none;">
                        <div class="form-section">
                            <h3><?php _e('New Discount Settings', 'gf-coupon-generator'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="new_amount_type"><?php _e('New Discount Type', 'gf-coupon-generator'); ?></label>
                                    </th>
                                    <td>
                                        <select name="new_amount_type" id="new_amount_type">
                                            <option value="percentage"><?php _e('Percentage (%)', 'gf-coupon-generator'); ?></option>
                                            <option value="flat"><?php _e('Flat Amount ($)', 'gf-coupon-generator'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="new_amount_value"><?php _e('New Discount Value', 'gf-coupon-generator'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" name="new_amount_value" id="new_amount_value" value="">
                                        <p class="description" id="new_discount_description"><?php _e('Amount of the discount (without % or $ symbol).', 'gf-coupon-generator'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Date fields -->
                    <div id="dates-fields" class="update-fields" style="display:none;">
                        <div class="form-section">
                            <h3><?php _e('New Date Settings', 'gf-coupon-generator'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="new_start_date"><?php _e('New Start Date', 'gf-coupon-generator'); ?></label>
                                    </th>
                                    <td>
                                        <input type="date" name="new_start_date" id="new_start_date">
                                        <p class="description"><?php _e('Leave blank to keep existing start date.', 'gf-coupon-generator'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="new_expiry_date"><?php _e('New Expiry Date', 'gf-coupon-generator'); ?></label>
                                    </th>
                                    <td>
                                        <input type="date" name="new_expiry_date" id="new_expiry_date">
                                        <p class="description"><?php _e('Leave blank to keep existing expiry date.', 'gf-coupon-generator'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Usage limit fields -->
                    <div id="usage-fields" class="update-fields" style="display:none;">
                        <div class="form-section">
                            <h3><?php _e('New Usage Limit', 'gf-coupon-generator'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="new_usage_limit"><?php _e('New Usage Limit', 'gf-coupon-generator'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" name="new_usage_limit" id="new_usage_limit" min="0" value="1">
                                        <p class="description"><?php _e('Enter 0 for unlimited usage.', 'gf-coupon-generator'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Stackable fields -->
                    <div id="stackable-fields" class="update-fields" style="display:none;">
                        <div class="form-section">
                            <h3><?php _e('Stackable Setting', 'gf-coupon-generator'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="new_is_stackable"><?php _e('Stackable', 'gf-coupon-generator'); ?></label>
                                    </th>
                                    <td>
                                        <select name="new_is_stackable" id="new_is_stackable">
                                            <option value="1"><?php _e('Yes - Can be combined with other coupons', 'gf-coupon-generator'); ?></option>
                                            <option value="0"><?php _e('No - Cannot be combined', 'gf-coupon-generator'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="submit-section">
                    <button type="submit" id="update-coupons-btn" class="button button-primary button-large">
                        <?php _e('Update Coupons', 'gf-coupon-generator'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </div>
        </form>
        
        <div id="update-results-container" style="display:none;">
            <h2><?php _e('Update Results', 'gf-coupon-generator'); ?></h2>
            <div id="update-results-message"></div>
            
            <table class="widefat" id="update-results-table">
                <thead>
                    <tr>
                        <th><?php _e('Coupon Code', 'gf-coupon-generator'); ?></th>
                        <th><?php _e('Status', 'gf-coupon-generator'); ?></th>
                        <th><?php _e('Message', 'gf-coupon-generator'); ?></th>
                    </tr>
                </thead>
                <tbody id="update-results-list">
                    <!-- Results will be populated here -->
                </tbody>
            </table>
            
            <p>
                <button type="button" id="export-update-results-btn" class="button button-secondary">
                    <?php _e('Export Results as CSV', 'gf-coupon-generator'); ?>
                </button>
            </p>
        </div>
    </div>
</div> 