<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Peakhour_Settings
{
    private static $instance;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        if (get_option('peakhour-settings') == false) {
            $default_domain = '';
            $blog_url = get_bloginfo('url');
            if ($blog_url != '') {
                $default_domain = parse_url(get_bloginfo('url', PHP_URL_HOST));
            }

            $default_options = array(
                'api-endpoint' => 'https://www.peakhour.io/api/v1',
                'api-key' => '',
                'domain' => $default_domain,
                'auto-adding' => 1,
                'auto-purging' => 1,
                'purge-homepage' => 0,
                'log-all-requests' => 0,
                'log-error-requests' => 0,
                'logging' => 0,
                'query-string' => 1,
                'aggressive-purging' => 1
            );

            update_option('peakhour-settings', $default_options);
        }
    }

    public function get_settings()
    {
        $settings = get_option('peakhour-settings');
        if (!array_key_exists("aggressive-purging", $settings)) {
            $settings['aggressive-purging'] = 0;
        }
        if (!array_key_exists("purge-homepage", $settings)) {
            $settings['purge-homepage'] = 0;
        }
        return $settings;
    }

    public function get_setting($setting)
    {
        return get_settings()[$setting];
    }
}

Peakhour_Settings::instance();
