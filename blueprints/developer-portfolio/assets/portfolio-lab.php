<?php
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;600;700&display=swap" rel="stylesheet">';
    echo '<style>
        html, body, #page, .site-content, .wp-site-blocks, .is-root-container { 
            margin: 0 !important; 
            padding: 0 !important; 
            background-color: #030712 !important; 
            font-family: "Fira Code", monospace !important; 
            color: #4ade80 !important; 
            -webkit-font-smoothing: antialiased; 
        } 

        header, .wp-block-template-part {
            background-color: #0b0f19 !important;
            border-bottom: 2px solid #22c55e !important;
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.2) !important;
            padding: 14px 32px !important;
        }

        .wp-block-site-title a {
            color: #22c55e !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            text-decoration: none !important;
        }

        .wp-block-navigation,
        .wp-block-navigation-item {
            display: none !important;
        }

        .entry-title, .wp-block-post-title { display: none !important; }

        .terminal-card-link {
            text-decoration: none !important;
            color: inherit !important;
            display: block;
        }

        .terminal-card {
            background: #080d1a !important;
            border: 1px solid #1e293b !important;
            border-left: 4px solid #06b6d4 !important;
            border-radius: 4px !important;
            padding: 20px !important;
            transition: all 0.25s ease-in-out !important;
            position: relative;
            overflow: hidden;
        }

        .terminal-card-link:hover .terminal-card {
            border-color: #22c55e !important;
            border-left-color: #22c55e !important;
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.25) !important;
            transform: translateY(-2px);
        }

        .status-led {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #22c55e;
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 8px #22c55e;
        }

        .terminal-badge {
            font-size: 0.75rem;
            color: #06b6d4;
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            padding: 2px 8px;
            border-radius: 2px;
            text-transform: uppercase;
        }

        .cmd-prompt {
            color: #38bdf8;
            font-weight: bold;
        }
    </style>';
});

add_action('init', function() {
    $sample_page = get_page_by_path('sample-page');
    if ($sample_page) {
        wp_delete_post($sample_page->ID, true);
    }

    $existing_id = get_option('portfolio_page_id');
    if ($existing_id && get_post($existing_id)) {
        return;
    }

    $html = '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"5%","right":"5%"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group alignfull" style="padding-top:60px;padding-right:5%;padding-bottom:60px;padding-left:5%"><!-- wp:paragraph --><p><span class="cmd-prompt">root@system:~#</span> ./init_portfolio.sh --verbose</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"2.5rem"}}} --><h1 class="wp-block-heading" style="font-size:2.5rem;color:#22c55e">&gt; DEVELOPER_PORTFOLIO.SYS</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#94a3b8"}}} --><p class="has-text-color" style="color:#94a3b8">[STATUS: ONLINE] Select a module to review system logs and project architecture.</p><!-- /wp:paragraph --><!-- wp:spacer {"height":"30px"} --><div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column --><div class="wp-block-column"><a href="https://github.com" target="_blank" rel="noopener noreferrer" class="terminal-card-link"><div class="terminal-card"><div style="margin-bottom:12px"><span class="status-led"></span><span class="terminal-badge">MODULE // 01</span></div><h3 style="color:#ffffff;margin:0 0 10px 0;font-size:1.2rem">&gt; CORE_PROJECTS</h3><p style="color:#94a3b8;font-size:0.875rem;margin:0 0 14px 0">Full-stack web applications and custom engine modules.</p><span style="color:#22c55e;font-size:0.85rem">[EXECUTE_INSPECT] &rarr;</span></div></a></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><a href="https://developer.wordpress.org" target="_blank" rel="noopener noreferrer" class="terminal-card-link"><div class="terminal-card"><div style="margin-bottom:12px"><span class="status-led"></span><span class="terminal-badge">MODULE // 02</span></div><h3 style="color:#ffffff;margin:0 0 10px 0;font-size:1.2rem">&gt; API_ENDPOINT_LAB</h3><p style="color:#94a3b8;font-size:0.875rem;margin:0 0 14px 0">Custom REST API architecture and GraphQL schemas.</p><span style="color:#22c55e;font-size:0.85rem">[EXECUTE_INSPECT] &rarr;</span></div></a></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group>';

    $id = wp_insert_post([
        'post_title'   => 'Developer Portfolio Terminal',
        'post_content' => $html,
        'post_status'  => 'publish',
        'post_type'    => 'page'
    ]);

    if ($id) {
        update_option('page_on_front', $id);
        update_option('show_on_front', 'page');
        update_option('portfolio_page_id', $id);
    }
});