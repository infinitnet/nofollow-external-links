<?php
/**
 * Plugin Name: Nofollow External Links
 * Description: Add nofollow attribute to all external links
 * Version: 1.0
 * Author: Infinitnet
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

    public function add_nofollow_to_links($content) {
        if (empty($content)) {
            return $content;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($this->is_external_link($href)) {
                $this->add_nofollow_attribute($link);
            }
        }

        $content = $dom->saveHTML();
        return $content;
    }

    private function is_external_link($url) {
        $home_url = parse_url(home_url(), PHP_URL_HOST);
        $link_host = parse_url($url, PHP_URL_HOST);
        return $link_host && $link_host !== $home_url && !empty($url);
    }

    private function add_nofollow_attribute($link) {
        $rel = $link->getAttribute('rel');
        $rel_values = empty($rel) ? array() : explode(' ', $rel);

        if (!in_array('nofollow', $rel_values)) {
            $rel_values[] = 'nofollow';
            $link->setAttribute('rel', implode(' ', array_unique($rel_values)));
        }
    }
}

new External_Link_Nofollow();
