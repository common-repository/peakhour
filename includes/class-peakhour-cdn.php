<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


class Peakhour_CDN
{
    private static $instance;

    private $postIdsProcessed = [];

    private static $cache_tags_collection;
    private static $cache_tags_header;

    private $options;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->options = Peakhour_Settings::instance()->get_settings();

        // Initialize the tag collector.
        $this::$cache_tags_header = new Peakhour_Cache_Tags_Header();

      // Initialize the cache control header.
//        $this::$cache_control_header = new Peakhour_Surrogate_Control_Header();

        // Add the cache tags.
        add_action('wp', array($this, 'set_standard_tags'), 100);

        // Send the cache tags.
        add_action('wp', array($this, 'send_cache_tags'), 101);


        // purging
        add_action('switch_theme', array($this, 'purge_all'));
        add_action('customize_save', array($this, 'purge_all')); // customise theme
        add_action('autoptimize_action_cachepurged', array($this, 'purge_all')); // for people who have that plugin
        add_action('trashed_post', array($this, 'purge'), 10, 1);
        add_action('deleted_post', array($this, 'purge'), 10, 1);
        add_action('future_to_publish', array($this, 'purge'), 10, 1);
        add_action('save_post', array($this, 'purge'), 10, 1);
        add_action('edit_post', array($this, 'purge'), 10, 1);
        add_action('delete_attachment', array($this, 'purge'), 10, 1);
        add_action('edited_terms', array($this, 'purge_term'), 10, 1); //why does no one else purge on edit category!
        add_action('delete_term', array($this, 'purge_term'), 10, 1); //why does no one else purge on edit category!
    }

    function purge_term( $term_id ) {
        $this->log_message('term edited/deleted, ' . $term_id);

        $collection = ['t-' . $term_id];

        Peakhour_API::instance()->purgeTags($collection);

    }

    function purge( $post_id ) {
        if (in_array($this->getPostId($post_id), $this->postIdsProcessed)) {
             return;
        }

        array_push($this->postIdsProcessed, $this->getPostId($post_id));
        if (!in_array(get_post_status($post_id), array('publish', 'trash', 'draft'))) {
            return;
        }
        
        $related_collection_object = new Peakhour_Related_Cache_Tags($post_id);
        if ( $this->options['aggressive-purging'] )
            $related_collection_object->locate_all();
        else
            $related_collection_object->include_always_purged_types();

        Peakhour_API::instance()->purgeTags($related_collection_object->_collection);
    }

    function purge_all() {
        Peakhour_API::instance()->purge_all();
    }

    function log_message($message) {
        if ($this->options['logging'])
            error_log($message);
    }

    /**
     * Set all the cache tags for the requests.
     *
     * @return void
     */
    public function set_standard_tags()
    {

        if (is_user_logged_in()) {
            return;
        }

        global $wp_query;
        $tag_collection = new Peakhour_Cache_Tag_Collection($wp_query);

        $this::$cache_tags_collection = $tag_collection;

        $tags = $tag_collection->get_tags();
        $this::$cache_tags_header->add_tags($tags);
    }

    /**
     * Send the currently registered cache tags.
     *
     * This function takes all of the cache tags that are currently recorded and flattens them into a single header
     * and sends the header. Any other tags need to be set by 3rd party code before "init", 101.
     *
     * This function does allow for a filtering of the tags before they are sent, to allow for the tags to be
     * de-registered when and if necessary.
     *
     * @return void
     */
    public function send_cache_tags()
    {

        if (is_user_logged_in()) {
            return;
        }

        $tags_header = $this::$cache_tags_header;
        $tags = apply_filters('peakhour_cache_tags', $tags_header->get_tags());
        $tags_header->set_tags($tags);

        $tags_header->send_header();
    }

    private function getPostId($post)
    {
        if ($post instanceof WP_Post) {
            return $post->ID;
        }
        return $post;
    }

}

Peakhour_CDN::instance();
