<?php
/*
  Plugin Name: Peakhour
  Plugin URI: https://peakhour.io/
  Description: Integrate your Wordpress installation with peakhour.io website accelerator
  Version: 1.0.3
  Author: Peakhour
  License: BSD-3-Clause
*/

if (!defined('WPINC')) {
    die;
}

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define("PEAKHOUR_MIN_PHP", "5.4");
define("PEAKHOUR_MIN_WP", "4.6.2");

class Peakhour
{
    private static $instance;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function version()
    {
        return '1.0.3';
    }

    public function __construct()
    {

        if (version_compare(PHP_VERSION, PEAKHOUR_MIN_PHP, '<')) {
            $this->too_old('Your PHP version is too old, you need at least ' . PEAKHOUR_MIN_PHP);
        }

        if (version_compare($GLOBALS['wp_version'], PEAKHOUR_MIN_WP, '<')) {
            $this->too_old('Your wordpress version is too old, you need at least ' . PEAKHOUR_MIN_WP);
        }

        $includes_dir = plugin_dir_path(__FILE__) . 'includes';

        require_once $includes_dir . '/class-peakhour-settings.php';
        require_once $includes_dir . '/class-peakhour-api.php';
        require_once $includes_dir . '/class-peakhour-cache-tag-collection.php';
        require_once $includes_dir . '/class-peakhour-header.php';
        require_once $includes_dir . '/class-peakhour-cache-tags-header.php';
        require_once $includes_dir . '/class-peakhour-related-cache-tags.php';
        require_once $includes_dir . '/class-peakhour-remove-query-strings.php';
        require_once $includes_dir . '/class-peakhour-cdn.php';


        if (is_admin()) {
            require_once $includes_dir . '/class-peakhour-settings-page.php';
        }
    }

    public function too_old( $message ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        deactivate_plugins(plugin_basename(__FILE__), true);
        wp_die('<p>' . $message . '! Die</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
    }
}

Peakhour::instance();
