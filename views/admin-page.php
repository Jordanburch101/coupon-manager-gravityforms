<?php
/**
 * Admin page template for GravityForms Coupon Generator.
 *
 * @package GF_Coupon_Generator
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get all active GF forms.
$forms = array();
if ( class_exists( 'GFAPI' ) ) {
	$gf_forms = GFAPI::get_forms( true );
	foreach ( $gf_forms as $form ) {
		$forms[ $form['id'] ] = $form['title'];
	}
}
?>

<div class="wrap gf-coupon-generator">
	<h1><?php esc_html_e( 'GravityForms Coupon Manager', 'gf-coupon-generator' ); ?></h1>
	
	<div class="notice notice-info">
		<p><?php esc_html_e( 'This tool allows you to bulk generate and update coupons for GravityForms.', 'gf-coupon-generator' ); ?></p>
	</div>
	
	<!-- Tab Navigation -->
	<h2 class="nav-tab-wrapper">
		<a href="#generate" class="nav-tab nav-tab-active" data-tab="generate"><?php esc_html_e( 'Generate Coupons', 'gf-coupon-generator' ); ?></a>
		<a href="#update" class="nav-tab" data-tab="update"><?php esc_html_e( 'Update Existing Coupons', 'gf-coupon-generator' ); ?></a>
	</h2>
	
	<!-- Generate Tab Content -->
	<div id="generate-tab" class="tab-content active">
		<form id="gf-coupon-generator-form" method="post">
			<div class="form-container">
				<div class="form-section">
					<h2><?php esc_html_e( 'Basic Settings', 'gf-coupon-generator' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="form_id"><?php esc_html_e( 'Target Form', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<select name="form_id" id="form_id" required>
									<option value=""><?php esc_html_e( '-- Select Form --', 'gf-coupon-generator' ); ?></option>
									<?php foreach ( $forms as $id => $title ) : ?>
										<option value="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Select the form these coupons will be applied to.', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="quantity"><?php esc_html_e( 'Quantity', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="number" name="quantity" id="quantity" min="1" max="1000" value="10" required>
								<p class="description"><?php esc_html_e( 'Number of coupons to generate (max 1000).', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="form-section">
					<h2><?php esc_html_e( 'Coupon Settings', 'gf-coupon-generator' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="coupon_prefix"><?php esc_html_e( 'Coupon Prefix', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="text" name="coupon_prefix" id="coupon_prefix" value="">
								<p class="description"><?php esc_html_e( 'Optional prefix for coupon codes. Example: "SUMMER-" would generate "SUMMER-x7dQ2p".', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="coupon_length"><?php esc_html_e( 'Coupon Code Length', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="number" name="coupon_length" id="coupon_length" min="4" max="32" value="8" required>
								<p class="description"><?php esc_html_e( 'Length of the random part of the coupon code (excluding prefix).', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="amount_type"><?php esc_html_e( 'Discount Type', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<select name="amount_type" id="amount_type" required>
									<option value="percentage"><?php esc_html_e( 'Percentage (%)', 'gf-coupon-generator' ); ?></option>
									<option value="flat"><?php esc_html_e( 'Flat Amount', 'gf-coupon-generator' ); ?></option>
								</select>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="amount_value"><?php esc_html_e( 'Discount Value', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="text" name="amount_value" id="amount_value" value="10" required>
								<p class="description" id="discount_description"><?php esc_html_e( 'Amount of the discount (without % symbol).', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="usage_limit"><?php esc_html_e( 'Usage Limit', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="number" name="usage_limit" id="usage_limit" min="0" value="1">
								<p class="description"><?php esc_html_e( 'Number of times each coupon can be used. Enter 0 for unlimited.', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="is_stackable"><?php esc_html_e( 'Stackable', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="is_stackable" id="is_stackable" value="1">
								<label for="is_stackable"><?php esc_html_e( 'Allow this coupon to be combined with other coupons', 'gf-coupon-generator' ); ?></label>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="form-section">
					<h2><?php esc_html_e( 'Date Settings', 'gf-coupon-generator' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="start_date"><?php esc_html_e( 'Start Date', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="date" name="start_date" id="start_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
								<p class="description"><?php esc_html_e( 'Date when coupons become valid. Leave blank for no start date restriction.', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="expiry_date"><?php esc_html_e( 'Expiry Date', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="date" name="expiry_date" id="expiry_date" value="">
								<p class="description"><?php esc_html_e( 'Date when coupons expire. Leave blank for no expiration.', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="submit-section">
					<button type="submit" id="generate-coupons-btn" class="button button-primary button-large">
						<?php esc_html_e( 'Generate Coupons', 'gf-coupon-generator' ); ?>
					</button>
					<span class="spinner"></span>
				</div>
			</div>
		</form>
		
		<div id="results-container" style="display:none;">
			<h2><?php esc_html_e( 'Generated Coupons', 'gf-coupon-generator' ); ?></h2>
			<div id="generate-results-message"></div>
			
			<table class="widefat" id="coupons-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'gf-coupon-generator' ); ?></th>
						<th><?php esc_html_e( 'Coupon Code', 'gf-coupon-generator' ); ?></th>
					</tr>
				</thead>
				<tbody id="coupons-list">
					<!-- Results will be populated here -->
				</tbody>
			</table>
			
			<p>
				<button type="button" id="export-csv-btn" class="button button-secondary">
					<?php esc_html_e( 'Export as CSV', 'gf-coupon-generator' ); ?>
				</button>
			</p>
		</div>
	</div>
	
	<!-- Update Tab Content -->
	<div id="update-tab" class="tab-content" style="display:none;">
		<form id="gf-coupon-update-form" method="post" enctype="multipart/form-data">
			<div class="form-container">
				<div class="form-section">
					<h2><?php esc_html_e( 'Update Existing Coupons', 'gf-coupon-generator' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="update_csv_file"><?php esc_html_e( 'CSV File', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<input type="file" name="update_csv_file" id="update_csv_file" accept=".csv" required>
								<p class="description"><?php esc_html_e( 'Upload a CSV file with coupon codes (one column with header "coupon_code").', 'gf-coupon-generator' ); ?></p>
							</td>
						</tr>
						
						<tr>
							<th scope="row">
								<label for="update_action"><?php esc_html_e( 'Update Action', 'gf-coupon-generator' ); ?></label>
							</th>
							<td>
								<select name="update_action" id="update_action" required>
									<option value=""><?php esc_html_e( '-- Select Action --', 'gf-coupon-generator' ); ?></option>
									<option value="discount"><?php esc_html_e( 'Change Discount Amount/Type', 'gf-coupon-generator' ); ?></option>
									<option value="dates"><?php esc_html_e( 'Update Dates', 'gf-coupon-generator' ); ?></option>
									<option value="usage"><?php esc_html_e( 'Update Usage Limit', 'gf-coupon-generator' ); ?></option>
									<option value="stackable"><?php esc_html_e( 'Update Stackable Setting', 'gf-coupon-generator' ); ?></option>
									<option value="deactivate"><?php esc_html_e( 'Deactivate Coupons', 'gf-coupon-generator' ); ?></option>
									<option value="activate"><?php esc_html_e( 'Activate Coupons', 'gf-coupon-generator' ); ?></option>
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
							<h3><?php esc_html_e( 'New Discount Settings', 'gf-coupon-generator' ); ?></h3>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="new_amount_type"><?php esc_html_e( 'New Discount Type', 'gf-coupon-generator' ); ?></label>
									</th>
									<td>
										<select name="new_amount_type" id="new_amount_type">
											<option value="percentage"><?php esc_html_e( 'Percentage (%)', 'gf-coupon-generator' ); ?></option>
											<option value="flat"><?php esc_html_e( 'Flat Amount ($)', 'gf-coupon-generator' ); ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="new_amount_value"><?php esc_html_e( 'New Discount Value', 'gf-coupon-generator' ); ?></label>
									</th>
									<td>
										<input type="text" name="new_amount_value" id="new_amount_value" value="">
										<p class="description" id="new_discount_description"><?php esc_html_e( 'Amount of the discount (without % or $ symbol).', 'gf-coupon-generator' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
					</div>
					
					<!-- Date fields -->
					<div id="dates-fields" class="update-fields" style="display:none;">
						<div class="form-section">
							<h3><?php esc_html_e( 'New Date Settings', 'gf-coupon-generator' ); ?></h3>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="new_start_date"><?php esc_html_e( 'New Start Date', 'gf-coupon-generator' ); ?></label>
									</th>
									<td>
										<input type="date" name="new_start_date" id="new_start_date">
										<p class="description"><?php esc_html_e( 'Leave blank to keep existing start date.', 'gf-coupon-generator' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<label for="new_expiry_date"><?php esc_html_e( 'New Expiry Date', 'gf-coupon-generator' ); ?></label>
									</th>
									<td>
										<input type="date" name="new_expiry_date" id="new_expiry_date">
										<p class="description"><?php esc_html_e( 'Leave blank to keep existing expiry date.', 'gf-coupon-generator' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
					</div>
					
					<!-- Usage limit fields -->
					<div id="usage-fields" class="update-fields" style="display:none;">
						<div class="form-section">
							<h3><?php esc_html_e( 'New Usage Limit', 'gf-coupon-generator' ); ?></h3>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="new_usage_limit"><?php esc_html_e( 'New Usage Limit', 'gf-coupon-generator' ); ?></label>
									</th>
									<td>
										<input type="number" name="new_usage_limit" id="new_usage_limit" min="0" value="1">
										<p class="description"><?php esc_html_e( 'Enter 0 for unlimited usage.', 'gf-coupon-generator' ); ?></p>
									</td>
								</tr>
							</table>
						</div>
					</div>
					
					<!-- Stackable fields -->
					<div id="stackable-fields" class="update-fields" style="display:none;">
						<div class="form-section">
							<h3><?php esc_html_e( 'Stackable Setting', 'gf-coupon-generator' ); ?></h3>
							<table class="form-table">
								<tr>
									<th scope="row">
										<label for="new_is_stackable"><?php esc_html_e( 'Stackable', 'gf-coupon-generator' ); ?></label>
									</th>
									<td>
										<select name="new_is_stackable" id="new_is_stackable">
											<option value="1"><?php esc_html_e( 'Yes - Can be combined with other coupons', 'gf-coupon-generator' ); ?></option>
											<option value="0"><?php esc_html_e( 'No - Cannot be combined', 'gf-coupon-generator' ); ?></option>
										</select>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				
				<div class="submit-section">
					<button type="submit" id="update-coupons-btn" class="button button-primary button-large">
						<?php esc_html_e( 'Update Coupons', 'gf-coupon-generator' ); ?>
					</button>
					<span class="spinner"></span>
				</div>
			</div>
		</form>
		
		<div id="update-results-container" style="display:none;">
			<h2><?php esc_html_e( 'Update Results', 'gf-coupon-generator' ); ?></h2>
			<div id="update-results-message"></div>
			
			<table class="widefat" id="update-results-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Coupon Code', 'gf-coupon-generator' ); ?></th>
						<th><?php esc_html_e( 'Status', 'gf-coupon-generator' ); ?></th>
						<th><?php esc_html_e( 'Message', 'gf-coupon-generator' ); ?></th>
					</tr>
				</thead>
				<tbody id="update-results-list">
					<!-- Results will be populated here -->
				</tbody>
			</table>
			
			<p>
				<button type="button" id="export-update-results-btn" class="button button-secondary">
					<?php esc_html_e( 'Export Results as CSV', 'gf-coupon-generator' ); ?>
				</button>
			</p>
		</div>
	</div>
</div> 