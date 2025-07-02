<?php
/**
 * Mock classes for GravityForms and GFCoupons
 * These simulate the basic structure needed for our plugin to load
 */

// Mock GFForms class
if (!class_exists('GFForms')) {
    class GFForms {
        public static $version = '2.7.0';

        public static function get_page() {
            return isset($_GET['page']) ? $_GET['page'] : '';
        }
    }
}

// Mock GFCoupons class
if (!class_exists('GFCoupons')) {
    class GFCoupons {
        public static $version = '3.2.0';
        private static $instance = null;

        public static function get_instance() {
            if (self::$instance == null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function get_feeds($form_id = null) {
            global $wpdb;

            $where = "addon_slug = 'gravityformscoupons' AND is_active = 1";
            if ($form_id) {
                $where .= $wpdb->prepare(" AND form_id = %d", $form_id);
            }

            $results = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}gf_addon_feed WHERE {$where}"
            );

            $feeds = array();
            foreach ($results as $result) {
                $feed = array(
                    'id' => $result->id,
                    'form_id' => $result->form_id,
                    'is_active' => $result->is_active,
                    'meta' => json_decode($result->meta, true)
                );
                $feeds[] = $feed;
            }

            return $feeds;
        }
    }
}

// Mock GFAPI class
if (!class_exists('GFAPI')) {
    class GFAPI {
        public static function get_forms($active = true, $trash = false) {
            // Return mock forms for testing
            return array(
                array(
                    'id' => 1,
                    'title' => 'Test Form 1',
                    'is_active' => true
                ),
                array(
                    'id' => 2,
                    'title' => 'Test Form 2',
                    'is_active' => true
                )
            );
        }

        public static function get_form($form_id) {
            $forms = self::get_forms();
            foreach ($forms as $form) {
                if ($form['id'] == $form_id) {
                    return $form;
                }
            }
            return false;
        }
    }
}
