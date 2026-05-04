<?php
/**
 * Plugin Name: Playground Welcome
 * Description: Replaces the default Hello World post and Sample Page with welcome content for WordPress Playground.
 * Version: 1.1.0
 * Author: Playground
 * License: GPL-2.0+
 * Text Domain: playground-welcome
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Playground_Welcome {

    private $content_created_option = 'playground_welcome_content_created';

    public function __construct() {
        add_action('init', [$this, 'init']);
    }

    public function init() {
        load_plugin_textdomain('playground-welcome', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->maybe_setup_content();
    }

    private function maybe_setup_content() {
        if (get_option($this->content_created_option)) {
            return;
        }

        add_filter('wp_kses_allowed_html', [__CLASS__, 'allow_svg_tags']);

        $post = get_post(1);
        if ($post) {
            wp_update_post([
                'ID' => $post->ID,
                'post_title' => __('Welcome to Your WordPress', 'playground-welcome'),
                'post_content' => self::get_welcome_post_content(),
                'post_name' => 'welcome-to-your-wordpress',
            ]);
        }

        $sample_page = get_post(2);
        if ($sample_page) {
            wp_update_post([
                'ID' => $sample_page->ID,
                'post_title' => __('About this WordPress', 'playground-welcome'),
                'post_content' => self::get_about_page_content(),
                'post_name' => 'about',
            ]);
        }

        update_option($this->content_created_option, true);
    }

    public static function get_welcome_post_path() {
        $locale = determine_locale();
        $lang = substr($locale, 0, 2);
        $plugin_dir = plugin_dir_path(__FILE__);

        $localized_file = $plugin_dir . $lang . '/welcome-post.html';
        if (file_exists($localized_file)) {
            return $localized_file;
        }

        return $plugin_dir . 'welcome-post.html';
    }

    public static function get_welcome_post_content() {
        $path = self::get_welcome_post_path();
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return '';
    }

    public static function get_about_page_path() {
        $locale = determine_locale();
        $lang = substr($locale, 0, 2);
        $plugin_dir = plugin_dir_path(__FILE__);

        $localized_file = $plugin_dir . $lang . '/about.html';
        if (file_exists($localized_file)) {
            return $localized_file;
        }

        return $plugin_dir . 'about.html';
    }

    public static function get_about_page_content() {
        $path = self::get_about_page_path();
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return '';
    }

    public static function allow_svg_tags($tags) {
        $tags['svg'] = [
            'viewbox' => true,
            'xmlns' => true,
            'width' => true,
            'height' => true,
            'aria-hidden' => true,
            'focusable' => true,
            'style' => true,
        ];
        $tags['path'] = [
            'd' => true,
            'fill-rule' => true,
            'clip-rule' => true,
        ];
        return $tags;
    }
}

new Playground_Welcome();
