<?php
/**
 * Plugin Name: Nofollow External Links
 * Description: Add nofollow attribute to all external links
 * Version: 1.0.2
 * Requires at least: 6.2
 * Author: Infinitnet
 * Author URI: https://infinitnet.io/
 * License: GPLv3
 * Text Domain: nofollow-external-links
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class External_Link_Nofollow {
    public function __construct() {
        add_action('wp', array($this, 'init'), 999);
    }

    public function init() {
        if (!is_admin()) {
            add_filter('the_content', array($this, 'add_nofollow_to_links'), 999);
        }
    }

    /**
     * Add rel="nofollow" to external links.
     *
     * Only <a> start tags are changed; every other byte of the content is kept as is.
     * Script, style, textarea, title and comment contents are not parsed as tags.
     * Needs WP_HTML_Tag_Processor (WordPress 6.2+); without it the content is returned unchanged.
     */
    public function add_nofollow_to_links($content) {
        if (!is_string($content) || $content === '' || stripos($content, '<a') === false) {
            return $content;
        }
        if (!class_exists('WP_HTML_Tag_Processor')) {
            return $content;
        }

        $processor = new WP_HTML_Tag_Processor($content);
        $changed = false;

        while ($processor->next_tag(array('tag_name' => 'a'))) {
            $href = $processor->get_attribute('href');
            if (!is_string($href) || !$this->is_external_link($href)) {
                continue;
            }

            $rel = $processor->get_attribute('rel');
            $rel_values = is_string($rel) ? preg_split('/\s+/', trim($rel), -1, PREG_SPLIT_NO_EMPTY) : array();
            // Link types are ASCII case-insensitive, so rel="NoFollow" already counts.
            if (in_array('nofollow', array_map('strtolower', $rel_values), true)) {
                continue;
            }

            $rel_values[] = 'nofollow';
            $processor->set_attribute('rel', implode(' ', array_unique($rel_values)));
            $changed = true;
        }

        return $changed ? $processor->get_updated_html() : $content;
    }

    private function is_external_link($url) {
        if (empty($url)) {
            return false;
        }
        $home_host = parse_url(home_url(), PHP_URL_HOST);
        $link_host = parse_url($url, PHP_URL_HOST);
        if (!$link_host) {
            return false;
        }
        // Host names are case-insensitive.
        return strtolower($link_host) !== strtolower((string) $home_host);
    }
}

new External_Link_Nofollow();
