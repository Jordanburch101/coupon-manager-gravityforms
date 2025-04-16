# GravityForms Coupon Generator

A WordPress plugin that allows you to bulk generate coupons for GravityForms.

## Description

This plugin provides an easy way to generate multiple coupons at once for the GravityForms Coupons addon. It adds rows directly to the `wp_gf_addon_feed` table with the necessary coupon configuration.

## Requirements

- WordPress 5.0+
- GravityForms 2.4+
- GravityForms Coupons Add-on

## Installation

1. Upload the `gf-coupon-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Forms > Coupon Generator to use the plugin

## Features

- Generate multiple coupons in one click
- Configure coupon settings:
  - Target form
  - Coupon prefix
  - Coupon code length
  - Discount type (percentage or flat amount)
  - Discount value
  - Start date
  - Expiry date
  - Usage limit
  - Stackable option
- Export generated coupons to CSV

## Usage

1. Go to Forms > Coupon Generator in the WordPress admin
2. Select the target form for the coupons
3. Configure coupon settings:
   - Quantity: number of coupons to generate
   - Coupon prefix (optional): prefix to add to all coupon codes
   - Coupon length: length of the random part of the code
   - Discount type: percentage or flat amount
   - Discount value: amount of the discount
   - Usage limit: how many times each coupon can be used
   - Stackable: whether the coupon can be combined with others
   - Start date/Expiry date: validity period for coupons
4. Click "Generate Coupons"
5. View the generated coupons and export to CSV if needed

## Support

For support or feature requests, please create an issue on the GitHub repository.

## License

This plugin is licensed under the GPL v2 or later. 