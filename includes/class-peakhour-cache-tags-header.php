<?php

/**
 * The following code is a derivative work of the code from the Fastly.com  wordpress plugin project,
 * which is licensed GPLv2. This code therefore is also licensed under the terms
 * of the GNU Public License, version 2.
 *
 * Set the Cache Tags header for a request.
 *
 * This class gathers, sanitizes and sends all of the Cache Tags for a request.
 */
class Peakhour_Cache_Tags_Header extends Peakhour_Header
{

    /**
     * Header name.
     *
     * @var string
     */
    protected $_header_name = 'X-Cache-Tags';

    /**
     * The lists that will compose the X-Cache-Tags header value.
     *
     * @var array    List of Cache Tags.
     */
    protected $_tags = array();

    /**
     * Add multiple tags to the list.
     *
     * @param  string $tags The tags to add to the list.
     */
    public function add_tags($tags)
    {
        $current_tags = $this->get_tags();

        // Combine tags.
        $tags = array_merge($current_tags, $tags);

        // De-dupe tags.
        $tags = array_unique($tags);

        // Retag the tags.
        $tags = array_values($tags);

        $this->set_tags($tags);
    }

    /**
     * Add a tag to the list.
     *
     * @param  string $tag The tag to add to the list.
     * @return array       The full list of tags.
     */
    public function add_tag($tag)
    {
        $tags = $this->get_tags();
        $tags[] = $tag;

        $this->set_tags($tags);
        return $tags;
    }

    /**
     * Return the value of the header, overwritten from parent for Tags special case
     * Also test header size, if too big, set tag that will always be purged
     *
     * @return string The header value.
     */
    public function get_value()
    {
        $tags_string = $this->prepare_tags();
        $header_string = $this->_header_name . ': ' . $tags_string;
        $header_size_bytes = mb_strlen($header_string, '8bit');
//        if ($header_size_bytes >= FASTLY_MAX_HEADER_SIZE) {
//            // Set to be always purged
//            $siteId = false;
//            if(is_multisite()) {
//                $siteId = get_current_blog_id();
//            } elseif($sitecode = Purgely_Settings::get_setting('sitecode')) {
//                $siteId = $sitecode;
//            }
//            if($siteId) {
//                return $siteId . '-' . 'holos';
//            }
//            return 'holos';
//        }
        return $tags_string;
    }

    /**
     * Prepare the tags into a header value string.
     *
     * @return string Space delimited list of sanitized tags.
     */
    public function prepare_tags()
    {
        $tags = $this->get_tags();

        $tags = array_map(array($this, 'sanitize_tag'), $tags);
        return rtrim(implode(',', $tags), ' ');
    }

    /**
     * Sanitize a cache tag.
     *
     * @param  string $tag The unsanitized tag.
     * @return string The sanitized tag.
     */
    public function sanitize_tag($tag)
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag);
    }

    /**
     * Set the tags for the Cache Tags header.
     *
     * @param  array $tags The tags for the header.
     * @return void
     */
    public function set_tags($tags)
    {
        $this->_tags = $tags;
    }

    /**
     * Key the list of Cache Tags.
     *
     * @return array The list of Cache Tags.
     */
    public function get_tags()
    {
        return $this->_tags;
    }
}
