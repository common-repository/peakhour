<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Peakhour_Settings_Page
{
    private static $instance;

    public static $host_regex = "/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/";

    public static function instance() {
        if ( is_null( self::$instance )) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ));
        add_action( 'admin_init', array( $this, 'init_settings' ));
        add_action( 'wp_ajax_purge_url', array( $this, 'purge_url' ));
        add_action( 'wp_ajax_purge_all_now', array( $this, 'purge_all_now' ));
        add_action( 'wp_ajax_test_peakhour_api_connection', array( $this, 'test_peakhour_api_connection' ));

    }

    function add_admin_menu() {
        add_menu_page(
            'Peakhour.io Settings',
            'Peakhour.io',
            'manage_options',
            'peakhour',
            array($this, 'render_options_page')
        );
    }

    function init_settings() {
        register_setting(
            'peakhour-settings',
            'peakhour-settings',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'peakhour-settings-section',
            'Connection Settings',
            array( $this, 'render_settings_section' ),
            'peakhour-settings'
        );

        add_settings_field(
            'api-key',
            'Peakhour API key',
            array( $this, 'render_api_key' ),
            'peakhour-settings',
            'peakhour-settings-section'
        );

        add_settings_field(
            'domain',
            'Domain name',
            array( $this, 'render_domain' ),
            'peakhour-settings',
            'peakhour-settings-section'
        );

        add_settings_field(
            'test_connection',
            '',
            array( $this, 'render_test_connection' ),
            'peakhour-settings',
            'peakhour-settings-section'
        );

        add_settings_section(
            'peakhour-cdn-settings-section',
            'CDN Settings',
            array( $this, 'render_cdn_settings_section' ),
            'peakhour-settings'
        );

        add_settings_field(
            'auto-purging',
            'Automatic purging',
            array($this, 'render_auto_purging'),
            'peakhour-settings',
            'peakhour-cdn-settings-section'
        );

        add_settings_field(
            'purge-homepage',
            'Purge homepage',
            array($this, 'render_purge_homepage'),
            'peakhour-settings',
            'peakhour-cdn-settings-section'
        );

        add_settings_section(
            'peakhour-manual-purging-section',
            'Manual Purging',
            array( $this, 'render_manual_purging_section' ),
            'peakhour-settings'
        );

        add_settings_field(
            'purge_all',
            'Flush entire cache',
            array($this, 'render_purge_all'),
            'peakhour-settings',
            'peakhour-manual-purging-section'
        );

        add_settings_field(
            'purge_url',
            'Flush url',
            array($this, 'render_purge_url'),
            'peakhour-settings',
            'peakhour-manual-purging-section'
        );

        
        /*
                add_settings_field(
                    'auto-adding',
                    'Automatic adding',
                    array( $this, 'render_auto_adding' ),
                    'peakhour-settings',
                    'peakhour-cdn-settings-section'
                );
        */

        add_settings_section(
            'peakhour-advanced-settings-section',
            'Advanced Settings',
            array($this, 'render_advanced_settings_section'),
            'peakhour-settings'
        );

        add_settings_field(
            'api-endpoint',
            'Peakhour API endpoint',
            array($this, 'render_api_endpoint'),
            'peakhour-settings',
            'peakhour-advanced-settings-section'
        );

        add_settings_field(
            'logging',
            'Enable plugin logging',
            array($this, 'render_logging'),
            'peakhour-settings',
            'peakhour-advanced-settings-section'
        );

        add_settings_field(
            'query-string',
            'Strip versions from static files',
            array($this, 'render_strip_query_string'),
            'peakhour-settings',
            'peakhour-advanced-settings-section'
        );

        add_settings_field(
            'aggressive-purging',
            'Make sure we do not serve any stale content by purging aggressively',
            array($this, 'render_aggressive_purging'),
            'peakhour-settings',
            'peakhour-advanced-settings-section'
        );
    }

    function render_settings_section() {
        esc_html_e('Please enter your Peakhour.io account details. ', 'peakhour');
    }

    function render_cdn_settings_section() {
        esc_html_e('Configure integration with Peakhour.io CDN. ', 'peakhour');
    }
    
    function render_advanced_settings_section() {
    }

    function render_manual_purging_section() {
        esc_html_e('Manually purge resources that aren\'t flushed automatically', 'peakhour');
    }

    public function render_api_endpoint() {
        $options = Peakhour_Settings::instance()->get_settings();
        ?>
        <input type='text' name='peakhour-settings[api-endpoint]' value="<?php echo esc_attr( $options['api-endpoint'] ) ?>">
        <?php
    }

    public function render_api_key() {
        $options = Peakhour_Settings::instance()->get_settings();
        ?>
        <input type='text' name='peakhour-settings[api-key]' value="<?php echo esc_attr( $options['api-key'] ) ?>">
        <p class="description">
            <?php
            esc_html_e( 'Your API key. ', 'peakhour' );
            ?>
        </p>
        <?php
    }

    public function render_domain() {
        $options = Peakhour_Settings::instance()->get_settings();
        ?>
        <input type='text' name='peakhour-settings[domain]' value="<?php echo esc_attr( $options['domain'] ) ?>">
        <p class="description">
            <?php
            esc_html_e( 'Domain name as registered in Peakhour.io. Do not include http:// etc ', 'peakhour' );
            ?>
        </p>
        <?php
    }

    public function render_test_connection() {
        $options = Peakhour_Settings::instance()->get_settings();
        //only show the button if we have saved our settings.
        if (!($options['domain'] && $options['api-key']))
            return;
        ?>
        <input type='button' class='button button-secondary' id="test-connection-btn" value="Test connection"/>
        <em class="description" id="test-connection-response" style='padding-top: 5px; display: inline-block'>
            <strong>
                <?php
                ?>
                Save any changes below before testing
            </strong>
        </em>
        <script type='text/javascript'>
            var url = "<?php echo admin_url('admin-ajax.php'); ?>";
            jQuery(document).ready(function ($) {
                jQuery('#test-connection-btn').click(function () {
                    $.ajax({
                        method: 'GET',
                        url: url,
                        data: {action: 'test_peakhour_api_connection'},
                        success: function (response) {
                            document.getElementById('test-connection-response').innerHTML = response.message;
                        },
                        dataType: 'json'
                    });
                });
            });
        </script>
        <?php
    }

    function render_purge_all() {

        $options = Peakhour_Settings::instance()->get_settings();
        //only show the button if we have saved our settings.
        if (!($options['domain'] && $options['api-key']))
            return;
        ?>
        <input type='button' class='button button-secondary' id="purge-all-btn" value="Purge Everything Now!"/>
        <em class="description" id="purge-all-response" style='padding-top: 5px; display: inline-block'>
            <strong>
                <?php
                ?>
                Use wisely. This will impact on website performance as your entire site will have
                to be recached. To be more selective use the url purge below.
            </strong>
        </em>
        <script type='text/javascript'>
            var url = "<?php echo admin_url('admin-ajax.php'); ?>";
            jQuery(document).ready(function ($) {
                jQuery('#purge-all-btn').click(function () {
                    $.ajax({
                        method: 'GET',
                        url: url,
                        data: {action: 'purge_all_now'},
                        success: function (response) {
                            document.getElementById('purge-all-response').innerHTML = response.message;
                        },
                        dataType: 'json'
                    });
                });
            });
        </script>
        <?php
    }

    public function render_purge_url() {
        ?>
        <input type='text' name='url' id="url" style="width:500px"><input type='button' class='button button-secondary' id="purge-url-btn" value="Purge Url"/><br/>

        <p class="description" id="purge-url-response" style='padding-top: 5px; display: inline-block'>
            <?php
            esc_html_e( 'Enter a url to purge.', 'peakhour' );
            ?>
        </p>
        <script type='text/javascript'>
            var url = "<?php echo admin_url('admin-ajax.php'); ?>";
            jQuery(document).ready(function ($) {
                jQuery('#purge-url-btn').click(function () {
                    $.ajax({
                        method: 'POST',
                        url: url,
                        data: {action: 'purge_url', url: document.getElementById('url').value},
                        success: function (response) {
                            document.getElementById('purge-url-response').innerHTML = response.body;
                        },
                        dataType: 'json'
                    });
                });
            });
        </script>
        <?php
    }

    function render_purge_homepage() {
        $options = Peakhour_Settings::instance()->get_settings();
        ?>
        <input type='checkbox' name='peakhour-settings[purge-homepage]' value='1'
            <?php
            checked('1', $options['purge-homepage']);
            ?>
        >
        <p class="description">
            <?php
            esc_html_e('Always purge homepage on changes (untick if homepage is standalone)');
            ?>
        </p>
        <?php
    }


    function render_auto_purging() {
        $options = Peakhour_Settings::instance()->get_settings();
        ?>
        <input type='checkbox' name='peakhour-settings[auto-purging]' value='1'
            <?php
            checked('1', $options['auto-purging']);
            ?>
        >
        <p class="description">
            <?php
            esc_html_e('Automatically purge modified/trashed pages from Peakhour.io CDN. ');
            ?>
        </p>
        <?php
    }

    function render_aggressive_purging() {
        $options = Peakhour_Settings::instance()->get_settings();
        ?>
        <input type='checkbox' name='peakhour-settings[aggressive-purging]' value='1'
            <?php
            checked('1', $options['aggressive-purging']);
            ?>
        >
        <p class="description">
            <?php
            esc_html_e('Content might be purged unnecessarily with this option when making changes, but it ensures that stale content will not be served');
            ?>
        </p>
        <?php
    }

    function render_strip_query_string() {
        $options = Peakhour_Settings::instance()->get_settings();

        ?>
        <input type='checkbox' name='peakhour-settings[query-string]' value='1'
            <?php
            checked('1', (isset($options['query-string']) ? $options['query-string'] : 1));
            ?>
        >
        <p class="description">
            <?php
            esc_html_e('Strip versioning from static resources for better caching');
            ?>
        </p>
        <?php
    }

    function render_logging() {
        $options = Peakhour_Settings::instance()->get_settings();

        ?>
        <input type='checkbox' name='peakhour-settings[logging]' value='1'
            <?php
            checked('1', $options['logging']);
            ?>
        >
        <?php
    }

    function render_options_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        settings_errors();

        ?>

        <div class="wrap">
            <h1>Peakhour.io</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('peakhour-settings');
                do_settings_sections('peakhour-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    function sanitize_host($input) {
        $sanitized = sanitize_text_field($input);
        $sanitized = strtolower($sanitized);
        if (preg_match(Peakhour_Settings_Page::$host_regex, $sanitized)) {
            return apply_filters('sanitize_host', $sanitized, $input);
        } else {
            add_settings_error('Domain name', 'domain', esc_attr($sanitized) . ' is not a valid host', 'error');
            return apply_filters('sanitize_host', '', $input);
        }
    }

    function sanitize($input) {
        $new_input = array();

        if (isset($input['api-endpoint'])) {
            $new_input['api-endpoint'] = sanitize_text_field($input['api-endpoint']);
        } else {
            $new_input['api-endpoint'] = '';
        }

        if (isset($input['api-key'])) {
            $new_input['api-key'] = sanitize_text_field($input['api-key']);
        } else {
            $new_input['api-key'] = '';
        }

        if (isset($input['domain'])) {
            $new_input['domain'] = $this->sanitize_host($input['domain']);

        } else {
            $new_input['domain'] = '';
        }

        if (isset($input['auto-purging'])) {
            $new_input['auto-purging'] = (int)$input['auto-purging'];
        } else {
            $new_input['auto-purging'] = 0;
        }

        if (isset($input['auto-adding'])) {
            $new_input['auto-adding'] = (int)$input['auto-adding'];
        } else {
            $new_input['auto-adding'] = 0;
        }

        if (isset($input['purge-homepage'])) {
            $new_input['purge-homepage'] = (int)$input['purge-homepage'];
        } else {
            $new_input['purge-homepage'] = 0;
        }

        if (isset($input['logging'])) {
            $new_input['logging'] = (int)$input['logging'];
        } else {
            $new_input['logging'] = 0;
        }

        if (isset($input['query-string'])) {
            $new_input['query-string'] = (int)$input['query-string'];
        } else {
            $new_input['query-string'] = 0;
        }

        if (isset($input['aggressive-purging'])) {
            $new_input['aggressive-purging'] = (int)$input['aggressive-purging'];
        } else {
            $new_input['aggressive-purging'] = 0;
        }



        return $new_input;
    }


    function purge_all_now() {
        $result = Peakhour_API::instance()->purge_all_now();
        echo json_encode($result);
        die ();
    }

    function purge_url() {
        $path = sanitize_text_field( $_POST['url'] );
        if (empty($path)) {
            echo json_encode(['body' => 'Please enter a path to flush', 'success' => True]);
        } else if (preg_match('/^[\*\/]+$/', $path) && $path != '/') { //are there only * and / in there?
            echo json_encode(['body' => 'Please include characters other than * and /', 'success' => True]);
        } else {
            $result = Peakhour_API::instance()->purge_url($path);
            echo json_encode($result);
        }
        die ();
    }


    function test_peakhour_api_connection() {
        $result = Peakhour_API::instance()->check_connection();
        echo json_encode($result);
        die ();
    }


}

Peakhour_Settings_Page::instance();
