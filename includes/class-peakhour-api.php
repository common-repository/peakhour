<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


class Peakhour_API
{
    private static $instance;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private $resources_url;
    private $wildcard_resources_url;
    private $tags_url;
    private $options;

    public function __construct() {
        $this->options = Peakhour_Settings::instance()->get_settings();
        $endpoint = $this->options['api-endpoint'];
        $domain = $this->options['domain'];
        $this->resources_url = "$endpoint/domains/$domain/services/rp/cdn/resources";
        $this->wildcard_resources_url = "$endpoint/domains/$domain/services/rp/cdn/wildcard";
        $this->tags_url = "$endpoint/domains/$domain/services/rp/cdn/tag";
    }

    function make_request_body( $urls ) {
        $paths = array();

        foreach ( $urls as $url ) {
            $parsed = parse_url( $url );
            $path = ( isset( $parsed['path'] ) ? $parsed['path'] : null );
            if ( empty( $path ) ) {
                $path = '/';
            }
            if ( isset( $parsed['query'] ) ) {
                $path = $path . '?' . $parsed['query'];
            }
            array_push($paths, $path );
        }

        $body = array( "paths" => $paths );

        return json_encode( $body );
    }

    function make_headers() {
        global $wp;
        $request_url = home_url(add_query_arg(array(), $wp->request));
        $ref = wp_get_referer();
        $headers = array(
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->options['api-key'],
            'Peakhour-Request-Url' => $request_url,
            'Peakhour-Referrer-Url' => $ref,
            'Peakhour-Php-Ver' => PHP_VERSION
        );
        return $headers;
    }

    function log_response( $response ) {
        if ( is_wp_error( $response )) {
            $this->log_message('Error requesting Peakhour.io API: ' . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );
            $this->log_message('Peakhour.io API result: ' . $response_code . ' ' . $response_body);
        }
    }

    function log_message( $message ) {
        if ( $this->options['logging'] )
            error_log($message);
    }

    function do_request($url, $method, $body) {
        $args = array(
            'body' => $body,
            'headers' => $this->make_headers(),
            'method' => $method,
            'user-agent' => "WordPress/" . get_bloginfo('version') . " peakhour-wordpress/" . Peakhour::version() . "; " . get_bloginfo('url')
        );
        $response = wp_remote_request($url, $args);
        $this->log_response($response);
        return $response;
    }

    function do_resources_request($method, $urls) {
        $body = $this->make_request_body($urls);
        return $this->do_request($this->resources_url, $method, $body);
    }

    public function purge_url($url) {
        $response = $this->do_resources_request('DELETE', [$url]);
        return $this->parse_response($response);
    }

    public function purge($urls) {
        if ( $this->options['auto-purging'] )
            $this->do_resources_request( 'DELETE', $urls );
        else
            $this->log_message( 'Purging disabled' );
    }

    public function purgeTags($tags)
    {
        if ( $this->options['auto-purging'] )
            $this->do_request($this->tags_url,'DELETE', json_encode(array('tags' => $tags)));
        else
            $this->log_message( 'Purging disabled' );
    }


    public function purge_all() {
        if ( $this->options['auto-purging'] )
            $this->do_request($this->resources_url, 'DELETE', '{"paths":[]}');
        else
            $this->log_message('Purging disabled');
    }

    public function purge_all_now() {
        $response = $this->do_request($this->resources_url, 'DELETE', '{"paths":[]}');
        return $this->parse_response($response);
    }

    public function check_connection() {
        $response = $this->do_request($this->resources_url, 'OPTIONS', null);
        return $this->parse_response($response);
    }

    function parse_response($response) {
        if ( is_wp_error( $response ) ) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        if ( $response_code == 403 ) {
            return array('success' => false, 'message' => "Access denied");
        } elseif ( $response_code == 401 ) {
            return array('success' => false, 'message' => "Authorization failed");
        } elseif ( $response_code != 200 && $response_code != 202 ) {
            return array('success' => false, 'message' => "Invalid status code: $response_code");
        } 

        return array('success' => true, 'message' => 'Success!', 'body' => $response_body);
    }
}
