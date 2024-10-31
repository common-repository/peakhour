<?php

/**
 * The following code is a derivative work of the code from the Fastly.com  wordpress plugin project,
 * which is licensed GPLv2. This code therefore is also licensed under the terms
 * of the GNU Public License, version 2.
 *
 * Collects Cache Tags related post.
 *
 * Attempts to find all Cache Tags that are related to an individual post.
 */
class Peakhour_Related_Cache_Tags
{

    /**
     * The post ID from which relationships are determined.
     *
     * @var string The post ID from which relationships are determined.
     */
    var $_post_id = 0;

    /**
     * The WP_Post object from which relationships are determined.
     *
     * @var null|WP_Post The WP_Post object from which relationships are determined.
     */
    var $_post = null;

    /**
     * Collection of Cache Tags
     *
     * @var array
     */
    var $_collection = array();

    private $options;

    /**
     * Construct the object.
     *
     * @param  int $identifier - postID
     */
    public function __construct($identifier)
    {
        $this->options = Peakhour_Settings::instance()->get_settings();

        // Cast Object to string in special cases
        if ($identifier instanceof WP_Post) {
            $identifier = $identifier->ID;
        }

        // Pull the post object from the $identifiers array and setup a standard post object.
        $this->set_post_id($identifier);
        $this->set_post(get_post($identifier));
        // Insert identifier
        $this->_collection[] = 'p-' . $identifier;
    }

    /**
     * Determine all cache tags
     *
     * @return array Related cache tags
     */
    public function locate_all()
    {
        // Collect and store tags
        $this->locate_cache_taxonomies($this->get_post_id());
        $this->locate_author_cache_tag($this->get_post_id());
        $this->include_always_purged_types();

//        $num = count($this->_collection);
        // Split tags for multiple requests if needed
//        if ($num >= PEAKHOUR_MAX_HEADER_KEY_SIZE) {
//            $parts = $num / PEAKHOUR_MAX_HEADER_KEY_SIZE;
//            $additional = ($parts > (int)$parts) ? 1 : 0;
//            $parts = (int)$parts + (int)$additional;
//            $chunks = ceil($num/$parts);
//            $this->_collection = array_chunk($this->_collection, $chunks);
//        } else {
//        $this->_collection = array($this->_collection);
//        }

        return $this->_collection;
    }

    /**
     * Includes types that get purged always (for custom themes)
     */
    public function include_always_purged_types()
    {
        $always_purged = $this->get_always_purged_types();
        $this->_collection = array_merge($this->_collection, $always_purged);
    }

    /**
     * Fetches types that get purged always (for custom themes)
     *
     * @return array Keys that always get purged.
     */
    public function get_always_purged_types()
    {
//        $always_purged_tags = Purgely_Settings::get_setting('always_purged_tags');
//        $always_purged_tags = explode(',', $always_purged_tags);

        if ($this->options["purge-homepage"]) {
            $always_purged_templates = array(
                'tm-post',
                'tm-home',
                'tm-front_page',
                'tm-feed',
                'holos',
                'tm-404'
            );
        } else {
            $always_purged_templates = array(
                   'tm-post',
                   'tm-feed',
                   'holos',
                   'tm-404'
               );
        }

//        $always_purged = array_merge($always_purged_templates, $always_purged_tags);
//        $always_purged = array_merge($always_purged_templates, $always_purged_tags);
        return $always_purged_templates;
    }

    /**
     * Get the term link pages for all terms associated with a post in a particular taxonomy.
     *
     * @param  int $post_id Post ID.
     */
    public function locate_cache_taxonomies($post_id)
    {

        $taxonomies = apply_filters('peakhour_taxonomy_tags', (array)get_taxonomies());

        foreach ($taxonomies as $taxonomy) {
            $this->locate_cache_taxonomy_single($post_id, $taxonomy);
        }
    }

    /**
     * Locate single taxonomy terms for post_id
     *
     * @param $post_id
     * @param $taxonomy
     */
    public function locate_cache_taxonomy_single($post_id, $taxonomy)
    {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));

        if (is_array($terms)) {
            foreach ($terms as $term) {
                if ($term) {
                    $tag = 't-' . $term;
                    $this->_collection[] = $tag;
                }
            }
        }
    }

    /**
     * Get author tag
     *
     * @param  int $post_id The post ID to search for related author information.
     */
    public function locate_author_cache_tag($post_id)
    {

        if ($post = $this->get_post($post_id)) {
            $post->post_author;
            $tag = 'a-' . absint($post->post_author);
            $this->_collection[] = $tag;
        }
    }

    /**
     * Get the main post ID.
     *
     * @return int    The main post ID.
     */
    public function get_post_id()
    {
        return $this->_post_id;
    }

    /**
     * Set the main post ID.
     *
     * @param  int $post_id The main post ID.
     * @return void
     */
    public function set_post_id($post_id)
    {
        $this->_post_id = $post_id;
    }

    /**
     * Get the main post object.
     *
     * @return WP_Post|false    The main post object.
     */
    public function get_post()
    {
        if ($this->_post) {
            return $this->_post;
        }

        $post = get_post($this->get_post_id());

        if (!$post) {
            return false;
        } else {
            $this->set_post($post);
            return $post;
        }
    }

    /**
     * Set the main post object.
     *
     * @param  WP_Post $post The main post object.
     * @return void
     */
    public function set_post($post)
    {
        $this->_post = $post;
    }
}
