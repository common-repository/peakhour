<?php

/**
  * The following code is a derivative work of the code from the Fastly.com  wordpress plugin project,
  * which is licensed GPLv2. This code therefore is also licensed under the terms
  * of the GNU Public License, version 2.
  *
  * Collects all Cache Tags to add to an individual response.
 */

class Peakhour_Cache_Tag_Collection
{

    const TEMPLATE_KEY_PREFIX = 'tm-';

    /**
     * The cache tag values.
     *
     * @var array The cache tags that will be set.
     */
    private $_tags = array();

    /**
     * Template types
     * @var array
     */
    static public $types = array(
        'single',
        'preview',
        'front_page',
        'page',
        'archive',
        'date',
        'year',
        'month',
        'day',
        'time',
        'author',
        'category',
        'tag',
        'tax',
        'search',
        'feed',
        'comment_feed',
        'trackback',
        'home',
        '404',
        'paged',
        'admin',
        'attachment',
        'singular',
        'robots',
        'posts_page',
        'post_type_archive',
    );

    public $custom_ttl = false;

    /**
     * Construct the object.
     *
     * @param  WP_Query $wp_query The main query object.
     */
    public function __construct($wp_query)
    {
        // Register the tags that need to be set for the current request, starting with post IDs.
        $tags = $this->_add_tag_post_ids($wp_query);
//        error_log("tags = " . print_r($tags));

        // Get the query type.
        $template_tag = $this->_add_tag_query_type($wp_query);

        // Get all taxonomy terms and author info if on a single post.
        $term_tags = array();

        if ($wp_query->is_single()) {
//            error_log("is single");
            $taxonomies = apply_filters('peakhour_taxonomy_tags', (array)get_taxonomies());

            foreach ($taxonomies as $taxonomy) {
                if (!$wp_query->post) continue;
                $term_tags = array_merge($term_tags, $this->_add_tag_terms_single($wp_query->post->ID, $taxonomy));
            }

            // Get author information.
            $term_tags = array_merge($term_tags, $this->_add_tag_author($wp_query->post));

        } else {
//            error_log("is not single");
            if ($wp_query->is_category() || $wp_query->is_tag() || $wp_query->is_tax()) {
                $term_tags = $this->_add_tag_terms_taxonomy();
            } else if ($wp_query->is_author()) {
                $queried_object = get_queried_object();
                $term_tags = ['a-' . absint($queried_object)];
            }
        }

        // Merge, de-dupe, and prune empties.
        $tags = array_merge(
            $tags,
            $template_tag,
            $term_tags
        );

        $tags = array_unique($tags);
        $tags = array_filter($tags);

        // If there is always purge tag existing, remove all others
//        $always_purged = Peakhour_Related_Cache_Tags::get_always_purged_types();
//        foreach($always_purged as $k) {
//            if (in_array($k, $template_tag)) {
//                $tags = $template_tag;
//                break;
//            }
//        }

        $this->set_tags($tags);
    }

    /**
     * Add a tag for each post ID to all pages that include the post.
     *
     * @param  WP_Query $wp_query The main query.
     * @return array       $tags        The "post-{ID}" tags.
     */
    private function _add_tag_post_ids($wp_query)
    {
        $tags = array();

        foreach ($wp_query->posts as $post) {
            $tags[] = 'p-' . absint($post->ID);
        }

        return $tags;
    }

    /**
     * Determine the type of WP template being displayed.
     *
     * @param WP_Query $wp_query The query object to inspect.
     * @return array $tag The template tag.
     */
    private function _add_tag_query_type($wp_query)
    {
        $template_type = '';
        $tag = '';

        /**
         * This function has the potential to be called in the admin context. Unfortunately, in the admin context,
         * $wp_query, is not a WP_Query object. Bad things happen when call_user_func is applied below. As such, lets' be
         * cautious and make sure that the $wp_query object is indeed a WP_Query object.
         */
        if (is_a($wp_query, 'WP_Query')) {
            // List of all "is" calls.
            $types = $this::$types;

            /**
             * Foreach "is" call, if it is a callable function, call and see if it returns true. If it does, we know what type
             * of template we are currently on. Break the loop and return that value.
             */
            foreach ($types as $type) {
                $callable = array($wp_query, 'is_' . $type);
                if (method_exists($wp_query, 'is_' . $type) && is_callable($callable)) {
                    if (true === call_user_func($callable)) {
                        $template_type = $type;
                        break;
                    }
                }
            }
        }

        // Only set the tag if it exists.
        if (!empty($template_type)) {
            $tag = self::TEMPLATE_KEY_PREFIX . $template_type;
        }

//        $this->set_custom_ttl($template_type);

        return (array)$tag;
    }

//    public function set_custom_ttl($template_type)
//    {
//        $custom_ttls = Peakhour_Settings::get_setting('custom_ttl_templates');
//        $ttl = isset($custom_ttls[$template_type]) ? $custom_ttls[$template_type] : false;
//        $this->custom_ttl = (int)$ttl;
//    }
//
//    public function get_custom_ttl()
//    {
//        return $this->custom_ttl;
//    }

    /**
     * Get the term tags for every term associated with a post.
     *
     * @param  int $post_id Post ID.
     * @param  string $taxonomy The taxonomy to look for associated terms.
     * @return array              The term slug/taxonomy combos for the post.
     */
    private function _add_tag_terms_single($post_id, $taxonomy)
    {
        $tags = array();
        $terms = get_the_terms($post_id, $taxonomy);

        if ($terms) {
            foreach ($terms as $term) {
                if (isset($term->term_id)) {
                    $tags[] = 't-' . $term->term_id;
                }
            }
        }

        return $tags;
    }

    /**
     * Get the term tags for taxonomies.
     *
     * @return array The taxonomy combos for the post.
     */
    private function _add_tag_terms_taxonomy()
    {
        $tags = array();

        $queried_object = get_queried_object();
        // archive page? author page? single post?

        if (!empty($queried_object->term_id) && !empty($queried_object->taxonomy)) {
            $tags[] = 't-' . absint($queried_object->term_id);
        }

        return $tags;
    }


    /**
     * Get author related to this post.
     *
     * @param  WP_Post $post The post object to search for related author information.
     * @return array               The related author tag.
     */
    private function _add_tag_author($post)
    {
        $author = absint($post->post_author);
        $tag = array();

        if ($author > 0) {
            $tag[] = 'a-' . absint($author);
        }

        return $tag;
    }

    /**
     * Set the tags variable.
     *
     * @param  array $tags Array of Peakhour_Cache_Tag objects.
     * @return void
     */
    public function set_tags($tags)
    {
        $this->_tags = $tags;
    }

    /**
     * Set an individual tag.
     *
     * @param  Peakhour_Cache_Tags_Header $tag Peakhour_Cache_Tag object.
     * @return void
     */
    public function set_tag($tag)
    {
        $tags = $this->get_tags();
        $tags[] = $tag;

        $this->set_tags($tags);
    }

    /**
     * Get all of the tags to be sent in the headers.
     *
     * @return array    Array of Peakhour_Cache_Tag objects
     */
    public function get_tags()
    {
        return $this->_tags;
    }
}
