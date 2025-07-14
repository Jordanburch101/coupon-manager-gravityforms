<?php
/**
 * PHPUnit bootstrap file for GF Coupon Generator
 */

// Ensure we're running in CLI
if (php_sapi_name() !== 'cli') {
    die('This file can only be run from the command line.');
}

$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration
if (!isset($_SERVER['WP_TESTS_PHPUNIT_POLYFILLS_PATH'])) {
    $_SERVER['WP_TESTS_PHPUNIT_POLYFILLS_PATH'] = dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php, have you run tests/bin/install-wp-tests.sh?" . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested and its dependencies
 */
function _manually_load_plugin() {
    // First, we need to mock GravityForms classes since they're premium
    require_once dirname(__DIR__) . '/tests/mocks/class-gf-mocks.php';

    // Load our plugin
    require dirname(__DIR__) . '/coupon-manager-for-gravityforms.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Include our test case classes
require_once dirname(__FILE__) . '/class-gf-coupon-test-case.php';
