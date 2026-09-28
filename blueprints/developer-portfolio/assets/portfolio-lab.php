<?php
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<style>
        html, body, #page, .site-content, .wp-site-blocks, .is-root-container { 
            margin: 0 !important; 
            padding: 0 !important; 
            background-color: #090d16 !important; 
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; 
            color: #f8fafc !important; 
            -webkit-font-smoothing: antialiased; 
        } 

        header, 
        .wp-block-template-part {
            background-color: #ffffff !important;
            background: #ffffff !important;
            color: #0f172a !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }

        header {
            padding: 16px 32px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            min-height: 60px !important;
        }

        header nav,
        header .wp-block-navigation,
        header .wp-block-navigation-item {
            display: none !important;
        }

        .wp-block-site-title,
        .wp-block-site-title a {
            color: #0f172a !important;
            background: transparent !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            font-size: 1.125rem !important;
            line-height: 1.2 !important;
            display: inline-block !important;
            margin: 0 !important;
        }

        .entry-title, 
        .wp-block-post-title {
            display: none !important;
        }

        .wp-block-group.alignfull { 
            margin-top: 0 !important; 
            margin-bottom: 0 !important; 
        } 
        
        h1, h2, h3, h4, .wp-block-heading { 
            font-family: "Plus Jakarta Sans", sans-serif !important; 
            letter-spacing: -0.02em; 
            color: #ffffff !important; 
        } 

        .ui-lab-card-link { 
            text-decoration: none !important; 
            color: inherit !important; 
            display: block; 
            border-radius: 12px; 
        } 

        .ui-lab-card { 
            transition: border-color 0.3s ease, box-shadow 0.3s ease; 
            border: 1px solid #1e293b !important; 
            background: #0f172a !important; 
            height: 100%; 
            cursor: pointer; 
        } 

        .ui-card-image-wrap { 
            overflow: hidden; 
            border-radius: 8px; 
            background-color: #020617; 
            position: relative; 
            min-height: 180px; 
        } 

        .ui-lab-card img { 
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
            display: block; 
            width: 100%; 
            height: auto; 
            object-fit: cover;
        } 

        .ui-lab-card-link:hover .ui-lab-card { 
            border-color: #38bdf8 !important; 
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 0 15px rgba(56, 189, 248, 0.2); 
        } 

        .ui-lab-card-link:hover img { 
            transform: scale(1.06); 
        } 

        .ui-card-cta { 
            transition: color 0.2s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            font-weight: 600; 
            font-size: 0.875rem; 
            color: #38bdf8; 
            margin-top: 16px; 
        } 

        .ui-lab-card-link:hover .ui-card-cta { 
            color: #7dd3fc; 
        } 

        .ui-card-badges { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 6px; 
            margin-bottom: 12px; 
        } 

        .ui-badge { 
            font-size: 0.6875rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            padding: 3px 8px; 
            border-radius: 4px; 
            background: rgba(30, 41, 59, 0.8); 
            color: #38bdf8; 
            border: 1px solid #0284c7; 
        }
    </style>';
});

add_action('init', function() {
    // Eliminar la Sample Page inicial si existe
    $sample_page = get_page_by_title('Sample Page');
    if ($sample_page) {
        wp_delete_post($sample_page->ID, true);
    }

    // Evitar recrear la página si ya existe
    $existing_id = get_option('portfolio_page_id');
    if ($existing_id && get_post($existing_id)) {
        return;
    }

    $html = '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"6%","right":"6%"}},"color":{"background":"#090d16","text":"#f8fafc"}},"layout":{"type":"constrained"}} --><div class="wp-block-group alignfull has-text-color has-background" style="color:#f8fafc;background-color:#090d16;padding-top:80px;padding-right:6%;padding-bottom:80px;padding-left:6%"><!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} --><div class="wp-block-group"><!-- wp:paragraph {"style":{"color":{"text":"#38bdf8"},"typography":{"fontStyle":"normal","fontWeight":"700","letterSpacing":"0.1em"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#38bdf8;font-weight:700;letter-spacing:0.1em;text-transform:uppercase">Full-Stack Engineer &amp; Open Source</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"3rem","fontWeight":"800","lineHeight":"1.15"}}} --><h1 class="wp-block-heading" style="font-size:3rem;font-weight:800;line-height:1.15;color:#ffffff">Featured Projects &amp; Products</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.6"},"color":{"text":"#cbd5e1"}}} --><p class="has-text-color" style="color:#cbd5e1;font-size:1.125rem;line-height:1.6">Explore open-source tools, SaaS platforms, and developer utilities built for scale.</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column --><div class="wp-block-column"><a href="https://github.com" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/01-project-analytics.webp" alt="Real-time Analytics Dashboard" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">Next.js</span><span class="ui-badge">Tailwind</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">01. PulseMetrics SaaS</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Privacy-first real-time web analytics engine with sub-millisecond response times.</p><!-- /wp:paragraph --><div class="ui-card-cta">View Repository &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><a href="https://github.com" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/02-project-saas.webp" alt="SaaS Management Platform" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">React</span><span class="ui-badge">TypeScript</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">02. CloudDeck Hub</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Multi-cloud infrastructure management dashboard for serverless deployments.</p><!-- /wp:paragraph --><div class="ui-card-cta">View Repository &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column --><div class="wp-block-column"><a href="https://github.com" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/03-project-api.webp" alt="API Gateway Infrastructure" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">Go</span><span class="ui-badge">GraphQL</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">03. FastAPI Gateway</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">High-throughput API gateway with built-in rate limiting and JWT auth.</p><!-- /wp:paragraph --><div class="ui-card-cta">View Repository &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><a href="https://github.com" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/04-project-design.webp" alt="Design Token Engine" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">CSS</span><span class="ui-badge">Web Components</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">04. Tokens Studio</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Automated CSS token compiler for synchronizing Figma tokens into code.</p><!-- /wp:paragraph --><div class="ui-card-cta">View Repository &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column --><div class="wp-block-column"><a href="https://github.com" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/05-project-cli.webp" alt="CLI Developer Utilities" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">Rust</span><span class="ui-badge">CLI</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">05. DevSync CLI</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Lightning-fast command line interface for synchronizing local dev databases.</p><!-- /wp:paragraph --><div class="ui-card-cta">View Repository &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><a href="https://github.com" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/06-project-docs.webp" alt="Technical Documentation Engine" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">Markdown</span><span class="ui-badge">Astro</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">06. DocuLite Framework</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Zero-config static site generator specifically tailored for software documentation.</p><!-- /wp:paragraph --><div class="ui-card-cta">View Repository &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group>';

    $id = wp_insert_post([
        'post_title'   => 'Developer Showcase',
        'post_content' => $html,
        'post_status'  => 'publish',
        'post_type'    => 'page'
    ]);

    if ($id && !is_wp_error($id)) {
        update_option('page_on_front', $id);
        update_option('show_on_front', 'page');
        update_option('portfolio_page_id', $id);
    }
});