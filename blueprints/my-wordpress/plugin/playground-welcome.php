<?php
/**
 * Plugin Name: Playground Welcome
 * Description: A welcome dialog for WordPress Playground that lets you set your name and import RSS feed content.
 * Version: 1.0.0
 * Author: Playground
 * License: GPL-2.0+
 * Text Domain: playground-welcome
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Playground_Welcome {

    private $option_name = 'playground_welcome_completed';
    private $content_created_option = 'playground_welcome_content_created';

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_ajax_playground_welcome_save', [$this, 'handle_save']);
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
                'post_title' => __('Welcome to your WordPress', 'playground-welcome'),
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
            'fill' => true,
            'aria-hidden' => true,
            'focusable' => true,
            'style' => true,
        ];
        $tags['path'] = [
            'd' => true,
            'fill' => true,
            'fill-rule' => true,
            'clip-rule' => true,
        ];
        return $tags;
    }

    public function add_admin_page() {
        add_submenu_page(
            'tools.php',
            __('Welcome', 'playground-welcome'),
            __('Welcome', 'playground-welcome'),
            'manage_options',
            'playground-welcome',
            [$this, 'render_page']
        );
    }

    public function enqueue_styles($hook) {
        if ($hook !== 'tools_page_playground-welcome') {
            return;
        }

        wp_enqueue_style(
            'playground-welcome',
            plugin_dir_url(__FILE__) . 'playground-welcome.css',
            ['wp-components'],
            '1.0.0'
        );

        wp_enqueue_script(
            'playground-welcome',
            plugin_dir_url(__FILE__) . 'playground-welcome.js',
            ['wp-components', 'wp-dom-ready', 'wp-element'],
            '1.0.0',
            true
        );

        wp_localize_script(
            'playground-welcome',
            'playgroundWelcomeSettings',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'homeUrl' => home_url('/'),
                'nonce' => wp_create_nonce('playground_welcome_nonce'),
                'strings' => [
                    'title' => __('Welcome to your WordPress', 'playground-welcome'),
                    'intro' => __("This is a private WordPress that's free and needs no account. It's stored in your browser and will be here when you come back.", 'playground-welcome'),
                    'displayNameLabel' => __("What's your name?", 'playground-welcome'),
                    'importTitle' => __('Import content from a website', 'playground-welcome'),
                    'feedUrlLabel' => __('Website URL', 'playground-welcome'),
                    'feedUrlHelp' => __("Enter a site URL and we'll find and import its RSS feed.", 'playground-welcome'),
                    'maxItemsLabel' => __('Maximum posts to import', 'playground-welcome'),
                    'fivePosts' => __('5 posts', 'playground-welcome'),
                    'tenPosts' => __('10 posts', 'playground-welcome'),
                    'twentyPosts' => __('20 posts', 'playground-welcome'),
                    'fiftyPosts' => __('50 posts', 'playground-welcome'),
                    'continue' => __('Continue', 'playground-welcome'),
                    'importing' => __('Importing...', 'playground-welcome'),
                    'notNow' => __('Not now', 'playground-welcome'),
                    'errorMessage' => __('An error occurred. Please try again.', 'playground-welcome'),
                ],
            ]
        );
    }

    public function render_page() {
        ?>
        <div id="playground-welcome-root"></div>
        <?php
    }

    public function handle_save() {
        if (!wp_verify_nonce($_POST['nonce'], 'playground_welcome_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'playground-welcome')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'playground-welcome')]);
        }

        $messages = [];

        $display_name = sanitize_text_field($_POST['display_name'] ?? '');
        if (!empty($display_name)) {
            $user_id = get_current_user_id();
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
            ]);
            /* translators: %s: user's display name */
            update_option('blogname', sprintf(__("%s's WordPress", 'playground-welcome'), $display_name));
            /* translators: %s: user's display name */
            $messages[] = sprintf(__('Name updated to "%s"', 'playground-welcome'), $display_name);
        }

        $feed_url = trim($_POST['feed_url'] ?? '');
        $max_items = intval($_POST['max_items'] ?? 10);

        if (!empty($feed_url)) {
            if (!preg_match('~^https?://~i', $feed_url)) {
                $feed_url = 'https://' . $feed_url;
            }
            $feed_url = esc_url_raw($feed_url);
            if (empty($feed_url)) {
                wp_send_json_error(['message' => __('Please enter a valid website URL.', 'playground-welcome')]);
            }
            $import_result = $this->import_feed($feed_url, $max_items);
            if ($import_result['success']) {
                $messages[] = $import_result['message'];
            } else {
                wp_send_json_error(['message' => $import_result['message']]);
            }
        }

        update_option($this->option_name, true);

        $final_message = !empty($messages)
            ? implode('. ', $messages) . '. ' . __('Redirecting to your site...', 'playground-welcome')
            : __('Setup complete! Redirecting to your site...', 'playground-welcome');

        wp_send_json_success(['message' => $final_message]);
    }

    private function import_feed($feed_url, $max_items) {
        include_once ABSPATH . WPINC . '/feed.php';

        $feed = fetch_feed($feed_url);

        if (is_wp_error($feed)) {
            return [
                'success' => false,
                /* translators: %s: error message */
                'message' => sprintf(__('Could not fetch feed: %s', 'playground-welcome'), $feed->get_error_message())
            ];
        }

        $items = $feed->get_items(0, $max_items);

        if (empty($items)) {
            return [
                'success' => false,
                'message' => __('No items found in the feed.', 'playground-welcome')
            ];
        }

        $imported = 0;
        $current_user_id = get_current_user_id();

        foreach ($items as $item) {
            $title = $item->get_title();
            $content = $item->get_content();
            $date = $item->get_date('Y-m-d H:i:s');
            $permalink = $item->get_permalink();

            $existing = get_posts([
                'post_type' => 'post',
                'meta_key' => '_playground_feed_source',
                'meta_value' => $permalink,
                'posts_per_page' => 1
            ]);

            if (!empty($existing)) {
                continue;
            }

            /* translators: %s: URL of the original article */
            $content .= "\n\n<p><em>" . sprintf(__('Originally published at <a href="%1$s">%2$s</a>', 'playground-welcome'), esc_url($permalink), esc_html(parse_url($permalink, PHP_URL_HOST))) . "</em></p>";

            $post_id = wp_insert_post([
                'post_title' => sanitize_text_field($title),
                'post_content' => wp_kses_post($content),
                'post_status' => 'publish',
                'post_author' => $current_user_id,
                'post_date' => $date ?: current_time('mysql'),
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_playground_feed_source', $permalink);
                $imported++;
            }
        }

        return [
            'success' => true,
            /* translators: %d: number of imported posts */
            'message' => sprintf(_n('Imported %d post from feed', 'Imported %d posts from feed', $imported, 'playground-welcome'), $imported)
        ];
    }
}

new Playground_Welcome();
