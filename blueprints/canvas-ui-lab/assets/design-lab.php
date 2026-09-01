<?php
add_action('wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<style>
        html, body, #page, .site-content, .wp-site-blocks, .is-root-container { 
            margin: 0 !important; 
            padding: 0 !important; 
            background-color: #0b0f19 !important; 
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important; 
            color: #f8fafc !important; 
            -webkit-font-smoothing: antialiased; 
        } 

        /* Header blanco con elementos uno al lado del otro */
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
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 32px !important;
            min-height: 60px !important;
        }

        header .wp-block-group,
        header .is-layout-flex,
        header nav,
        header ul {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 32px !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            list-style: none !important;
        }

        /* Ocultar solo el enlace duplicado de la galería */
        .wp-block-navigation-item:has(a[href*="design-system-gallery"]),
        .wp-block-navigation-item:nth-child(1) {
            display: none !important;
        }

        .wp-block-site-title,
        .wp-block-site-title a,
        .wp-block-navigation .wp-block-navigation-item__label,
        .wp-block-navigation a {
            color: #0f172a !important;
            background: transparent !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            font-size: 1rem !important;
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
            border: 1px solid #334155 !important; 
            background: #1e293b !important; 
            height: 100%; 
            cursor: pointer; 
        } 

        .ui-card-image-wrap { 
            overflow: hidden; 
            border-radius: 8px; 
            background-color: #0f172a; 
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
            border-color: #818cf8 !important; 
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 0 15px rgba(99, 102, 241, 0.2); 
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
            color: #818cf8; 
            margin-top: 16px; 
        } 

        .ui-lab-card-link:hover .ui-card-cta { 
            color: #a5b4fc; 
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
            background: rgba(51, 65, 85, 0.6); 
            color: #cbd5e1; 
            border: 1px solid #475569; 
        }
    </style>';
});

add_action('init', function() {
    $existing_id = get_option('design_lab_page_id');
    if ($existing_id && get_post($existing_id)) {
        wp_delete_post($existing_id, true);
    }

    $html = '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px","left":"6%","right":"6%"}},"color":{"background":"#0b0f19","text":"#f8fafc"}},"layout":{"type":"constrained"}} --><div class="wp-block-group alignfull has-text-color has-background" style="color:#f8fafc;background-color:#0b0f19;padding-top:80px;padding-right:6%;padding-bottom:80px;padding-left:6%"><!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} --><div class="wp-block-group"><!-- wp:paragraph {"style":{"color":{"text":"#a5b4fc"},"typography":{"fontStyle":"normal","fontWeight":"700","letterSpacing":"0.1em"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#a5b4fc;font-weight:700;letter-spacing:0.1em;text-transform:uppercase">Design System &amp; UI Kit</p><!-- /wp:paragraph --><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"3rem","fontWeight":"800","lineHeight":"1.15"}}} --><h1 class="wp-block-heading" style="font-size:3rem;font-weight:800;line-height:1.15;color:#ffffff">Component Gallery</h1><!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.6"},"color":{"text":"#cbd5e1"}}} --><p class="has-text-color" style="color:#cbd5e1;font-size:1.125rem;line-height:1.6">Click any card below to explore the official external documentation for each component.</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:spacer {"height":"40px"} --><div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column --><div class="wp-block-column"><a href="https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-json/" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/01-design-tokens.webp" alt="Design Tokens and Color Palette UI" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">CSS3</span><span class="ui-badge">WordPress</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">01. Design Tokens</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Centralized system for color variables and typographic scale management.</p><!-- /wp:paragraph --><div class="ui-card-cta">Read documentation &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_transitions/Using_CSS_transitions" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/02-micro-interactions.webp" alt="Micro-Interactions Design" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">CSS3</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">02. Micro-Interactions</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Subtle hover and focus feedback powered by fluid easing curves.</p><!-- /wp:paragraph --><div class="ui-card-cta">Read documentation &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column --><div class="wp-block-column"><a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_grid_layout" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/03-responsive-layout.webp" alt="Responsive Layout" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">CSS3</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">03. Responsive Layout</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Adaptive structures using CSS Grid and Flexbox for mobile and desktop views.</p><!-- /wp:paragraph --><div class="ui-card-cta">Read documentation &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Animations_API" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/04-component-motion.webp" alt="Component Motion" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">CSS3</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">04. Component Motion</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Advanced transitions designed to enhance interactive user experience.</p><!-- /wp:paragraph --><div class="ui-card-cta">Read documentation &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --></div><!-- /wp:columns --><!-- wp:spacer {"height":"24px"} --><div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide"><!-- wp:column --><div class="wp-block-column"><a href="https://www.w3.org/WAI/standards-guidelines/wcag/" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/05-accessibility-patterns.webp" alt="Accessibility Patterns and Inclusive Design" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">WCAG</span><span class="ui-badge">CSS3</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">05. Accessibility Patterns</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Accessible WCAG 2.1 patterns featuring full keyboard navigation and ARIA support.</p><!-- /wp:paragraph --><div class="ui-card-cta">Read documentation &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><a href="https://developer.wordpress.org/block-editor/how-to-guides/themes/" target="_blank" rel="noopener noreferrer" class="ui-lab-card-link"><!-- wp:group {"className":"ui-lab-card","style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} --><div class="wp-block-group ui-lab-card" style="border-radius:12px;padding:24px"><div class="ui-card-image-wrap"><!-- wp:image {"aspectRatio":"16/9","scale":"cover"} --><figure class="wp-block-image"><img src="/wp-content/uploads/06-theme-architecture.webp" alt="Theme Architecture" style="aspect-ratio:16/9;object-fit:cover"/></figure><!-- /wp:image --></div><!-- wp:spacer {"height":"20px"} --><div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div><!-- /wp:spacer --><div class="ui-card-badges"><span class="ui-badge">WordPress</span></div><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700"}}} --><h3 class="wp-block-heading" style="font-size:1.25rem;font-weight:700;color:#ffffff">06. Theme Architecture</h3><!-- /wp:heading --><!-- wp:paragraph {"style":{"color":{"text":"#cbd5e1"},"typography":{"lineHeight":"1.55"}},"fontSize":"small"} --><p class="has-text-color has-small-font-size" style="color:#cbd5e1;line-height:1.55">Dynamic block theme architecture engineered for scalable design systems.</p><!-- /wp:paragraph --><div class="ui-card-cta">Read documentation &rarr;</div></div><!-- /wp:group --></a></div><!-- /wp:column --></div><!-- /wp:columns --></div><!-- /wp:group>';

    $id = wp_insert_post([
        'post_title'   => 'Design System Gallery',
        'post_content' => $html,
        'post_status'  => 'publish',
        'post_type'    => 'page'
    ]);

    if ($id) {
        update_option('page_on_front', $id);
        update_option('show_on_front', 'page');
        update_option('design_lab_page_id', $id);
    }
});