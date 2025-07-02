# GravityForms Coupon Manager

A WordPress plugin that allows bulk generation and updating of coupons for the Gravity Forms Coupons Add-On.

## Features

### Generate Coupons
- Bulk generate multiple coupons at once
- Customize coupon codes with prefixes
- Set discount types (percentage or flat amount)
- Configure usage limits and expiration dates
- Export generated coupons to CSV

### Update Existing Coupons (NEW)
- Upload CSV file with coupon codes to update
- Batch update various coupon properties:
  - **Discount Amount/Type**: Change from percentage to flat amount or vice versa
  - **Dates**: Update start and expiry dates
  - **Usage Limits**: Modify how many times coupons can be used
  - **Stackable Setting**: Allow or disallow combining with other coupons
  - **Activation Status**: Activate or deactivate coupons in bulk
- Data integrity checks to ensure only existing coupons are updated
- Detailed results showing success/failure for each coupon
- Export update results to CSV

## Requirements

- WordPress 5.0+
- Gravity Forms plugin (latest version recommended)
- Gravity Forms Coupons Add-On (official add-on from Gravity Forms)
- PHP 8.1+

## Installation

1. Upload the plugin files to `/wp-content/plugins/gf-coupon-manager/`
2. Activate the plugin through the WordPress admin
3. Navigate to Forms > Coupon Manager in the admin menu

## Usage

### Generating New Coupons

1. Go to the "Generate Coupons" tab
2. Select the target form
3. Configure coupon settings (prefix, discount type, amount, etc.)
4. Click "Generate Coupons"
5. Export the generated coupon codes to CSV if needed

### Updating Existing Coupons

1. Go to the "Update Existing Coupons" tab
2. Prepare a CSV file with one column:
   - Header: `coupon_code`
   - List coupon codes one per row
3. Upload the CSV file
4. Select the update action:
   - Change Discount Amount/Type
   - Update Dates
   - Update Usage Limit
   - Update Stackable Setting
   - Activate/Deactivate Coupons
5. Fill in the new values based on your selected action
6. Click "Update Coupons"
7. Review the results and export if needed

### Example: Changing Multiple Coupons from 100% to $30 Discount

1. Create a CSV file with coupon codes:
   ```
   coupon_code
   SUMMER-abc123
   SUMMER-def456
   SUMMER-ghi789
   ```

2. Upload the CSV file
3. Select "Change Discount Amount/Type" as the update action
4. Choose "Flat Amount ($)" as the new discount type
5. Enter "30" as the new discount value
6. Click "Update Coupons"

## CSV Format

The CSV file for updating coupons should have:
- First row: `coupon_code` (header)
- Subsequent rows: One coupon code per row
- The plugin will ignore empty rows and remove duplicates

## Security Features

- Permission checks ensure only users with `gravityforms_edit_forms` capability can use the plugin
- All inputs are properly sanitized
- Database queries use prepared statements
- AJAX requests are protected with nonces

## Support

For issues or feature requests, please contact the plugin author. 