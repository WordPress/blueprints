<?php
add_action('wp_head', function() {
    echo '<style>
        /* Custom styling and layout overrides */
        html, body, #page, .site-content, .wp-site-blocks, .is-root-container { 
            margin: 0 !important; 
            padding: 0 !important; 
        } 

        .entry-title, .wp-block-post-title { 
            display: none !important; 
        }

        .wp-block-navigation,
        .wp-block-navigation-item {
            display: none !important;
        }

        .wp-block-site-title {
            text-align: center !important;
            margin: 0 auto !important;
        }

        .wp-block-site-title a {
            text-decoration: none !important;
            pointer-events: none !important;
        }
    </style>';
});

add_action('init', function() {
    // 1. Check if the page has already been initialized
    $existing_id = get_option('canvas_ui_lab_page_id');
    if ($existing_id && get_post($existing_id)) {
        // Exit early to preserve page edits, revisions, and prevent Gutenberg REST API failures
        return;
    }

    // 2. Remove default Sample Page on first initialization only
    $sample_page = get_page_by_path('sample-page');
    if ($sample_page) {
        wp_delete_post($sample_page->ID, true);
    }

    // 3. Define initial page content
    $html = '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"5%","right":"5%"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group alignfull" style="padding-top:60px;padding-right:5%;padding-bottom:60px;padding-left:5%"><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"2.5rem"}}} --><h1 class="wp-block-heading" style="font-size:2.5rem">Canvas UI Lab Showcase</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Welcome to the Canvas UI Lab environment. Explore component designs, interactive layouts, and custom block configurations.</p><!-- /wp:paragraph --></div><!-- /wp:group>';

    // 4. Create the front page once
    $id = wp_insert_post([
        'post_title'   => 'Canvas UI Lab Home',
        'post_content' => $html,
        'post_status'  => 'publish',
        'post_type'    => 'page'
    ]);

    // 5. Store page ID and configure static front page
    if ($id && !is_wp_error($id)) {
        update_option('page_on_front', $id);
        update_option('show_on_front', 'page');
        update_option('canvas_ui_lab_page_id', $id);
    }
});