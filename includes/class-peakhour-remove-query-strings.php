<?php

class Peakhour_Remove_Query_Strings
{
    private static $instance;
    private $options;

    public static function instance() {
        if ( is_null( self::$instance )) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->options = Peakhour_Settings::instance()->get_settings();

        add_filter('script_loader_src', array($this, 'remove_query_strings'), 15, 1);
        add_filter('style_loader_src', array($this, 'remove_query_strings'), 15, 1);

//        add_filter('script_loader_src', array($this, 'remove_query_strings_2'), 15, 1);
//        add_filter('style_loader_src', array($this, 'remove_query_strings_2'), 15, 1);

    }

    function remove_query_strings($src)
    {
        $val = isset($this->options['query-string']) ? $this->options['query-string'] : 1;
        if ($val) {
            if (strpos($src, '?ver=') or strpos($src, '&ver=')) {
                $src = remove_query_arg('ver', $src);
            }
            if (strpos($src, '?v=') or strpos($src, '&v=')) {
                $src = remove_query_arg('v', $src);
            }
        }
        return $src;
    }
}

Peakhour_Remove_Query_Strings::instance();
