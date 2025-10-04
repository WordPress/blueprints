<?php
/*
Plugin Name: Fancy Dashboard Widget
Description: A stylish WordPress dashboard widget with recent posts, user stats, and quick links.
Version: 1.0.0
Author: Muryam
License: GPL-2.0+
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Fancy_Dashboard_Widget {
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'),1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    // Add the dashboard widget
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'fancy_dashboard_widget',
            'Fancy Dashboard Overview',
            array($this, 'render_widget_content')
        );
          // Globalize the metaboxes array, this holds all the widgets for wp-admin.
    	global $wp_meta_boxes;
    	
    	// Get the regular dashboard widgets array 
    	// (which already has our new widget but appended at the end).
    	$default_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
    	
    	// Backup and delete our new dashboard widget from the end of the array.
    	$gt_dashboard_backup = array( 'fancy_dashboard_widget' => $default_dashboard['fancy_dashboard_widget'] );
    	unset( $default_dashboard['fancy_dashboard_widget'] );
     
    	// Merge the two arrays together so our widget is at the beginning.
    	$sorted_dashboard = array_merge( $gt_dashboard_backup, $default_dashboard );
     
    	// Save the sorted array back into the original metaboxes. 
    	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
    }

    // Enqueue Tailwind CSS
    public function enqueue_styles($hook) {
        if ('index.php' !== $hook) {
            return;
        }
        wp_enqueue_style(
            'fancy-dashboard-widget-css',
            'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
            array(),
            '2.2.19'
        );
    }

    // Render the widget content
    public function render_widget_content() {
        $recent_posts = wp_get_recent_posts(array('numberposts' => 5, 'post_status' => 'publish'));
        $user_count = count_users();
        $current_user = wp_get_current_user();
        ?>
        <div class="p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4 text-gray-800">Welcome, <?php echo esc_html($current_user->display_name); ?>!</h2>
            
            <!-- Quick Stats -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Quick Stats</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-blue-100 rounded-lg">
                        <p class="text-sm text-gray-600">Total Posts</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo wp_count_posts()->publish; ?></p>
                    </div>
                    <div class="p-4 bg-green-100 rounded-lg">
                        <p class="text-sm text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo $user_count['total_users']; ?></p>
                    </div>
                    <div class="p-4 bg-purple-100 rounded-lg">
                        <p class="text-sm text-gray-600">Comments</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo wp_count_comments()->total_comments; ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Posts -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Recent Posts</h3>
                <ul class="list-disc list-inside">
                    <?php foreach ($recent_posts as $post): ?>
                        <li class="text-gray-600 hover:text-blue-500">
                            <a href="<?php echo esc_url(get_permalink($post['ID'])); ?>">
                                <?php echo esc_html($post['post_title']); ?>
                            </a>
                            <span class="text-sm text-gray-500">
                                (<?php echo esc_html(get_the_date('M j, Y', $post['ID'])); ?>)
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Quick Links</h3>
                <div class="flex space-x-4">
                    <a href="<?php echo admin_url('post-new.php'); ?>" 
                       class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                        New Post
                    </a>
                    <a href="<?php echo admin_url('users.php'); ?>" 
                       class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
                        Manage Users
                    </a>
                    <a href="<?php echo admin_url('edit-comments.php'); ?>" 
                       class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 transition">
                        View Comments
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
new Fancy_Dashboard_Widget();
?>
