=== Coupon Manager for GravityForms ===
Contributors: jordanburch101
Tags: gravity forms, coupons, bulk, discount, woocommerce
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 8.1
Stable tag: 0.0.4
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Bulk generate and manage coupons for the Gravity Forms Coupons Add-On. Create hundreds of coupons at once and update existing coupons in batches.

== Description ==

Coupon Manager for GravityForms is a powerful WordPress plugin that extends the functionality of the Gravity Forms Coupons Add-On by allowing you to bulk generate and update coupons efficiently.

= Key Features =

**Generate Coupons**
* Bulk generate multiple coupons at once (up to 1000)
* Customize coupon codes with prefixes
* Set discount types (percentage or flat amount)
* Configure usage limits and expiration dates
* Export generated coupons to CSV

**Update Existing Coupons**
* Upload CSV file with coupon codes to update
* Batch update various coupon properties:
  * Discount Amount/Type: Change from percentage to flat amount or vice versa
  * Dates: Update start and expiry dates
  * Usage Limits: Modify how many times coupons can be used
  * Stackable Setting: Allow or disallow combining with other coupons
  * Activation Status: Activate or deactivate coupons in bulk
* Data integrity checks to ensure only existing coupons are updated
* Detailed results showing success/failure for each coupon
* Export update results to CSV

= Requirements =

* WordPress 5.0+
* Gravity Forms plugin (latest version recommended)
* Gravity Forms Coupons Add-On (official add-on from Gravity Forms)
* PHP 8.1+

= Security Features =

* Permission checks ensure only users with proper capabilities can use the plugin
* All inputs are properly sanitized
* Database queries use prepared statements
* AJAX requests are protected with nonces

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/coupon-manager/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Forms > Coupon Manager in the admin menu

== Frequently Asked Questions ==

= What is required to use this plugin? =

You need WordPress 5.0+, Gravity Forms plugin, and the official Gravity Forms Coupons Add-On installed and activated.

= How many coupons can I generate at once? =

You can generate up to 1000 coupons in a single batch operation.

= Can I update existing coupons? =

Yes! You can upload a CSV file with coupon codes and batch update various properties like discount amounts, dates, usage limits, and activation status.

= What format should my CSV file be in? =

The CSV file should have a header row with "coupon_code" and then list one coupon code per row. Empty rows are ignored and duplicates are removed automatically.

= Is this plugin secure? =

Yes, the plugin includes comprehensive security measures including permission checks, input sanitization, prepared database statements, and nonce protection for AJAX requests.

== Screenshots ==

1. Main plugin interface showing generate and update tabs
2. Coupon generation form with all available options
3. CSV upload interface for updating existing coupons
4. Results table showing generated coupons
5. Update results with success/failure status for each coupon

== Changelog ==

= 1.0.0 =
* Initial release
* Bulk coupon generation functionality
* Batch coupon update functionality
* CSV import/export capabilities
* Comprehensive security measures
* User-friendly admin interface

== Upgrade Notice ==

= 1.0.0 =
Initial release of Coupon Manager for GravityForms. Adds powerful bulk coupon management capabilities to your Gravity Forms setup. 