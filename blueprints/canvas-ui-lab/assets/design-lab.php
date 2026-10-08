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
    // 1. Verificar si la página ya existe para no sobrescribirla ni borrarla en peticiones posteriores
    $existing_id = get_option('canvas_ui_lab_page_id');
    if ($existing_id && get_post($existing_id)) {
        return; // Preserva ediciones del usuario y peticiones REST de Gutenberg
    }

    // 2. Eliminar Sample Page solo en el primer arranque
    $sample_page = get_page_by_path('sample-page');
    if ($sample_page) {
        wp_delete_post($sample_page->ID, true);
    }

    // 3. Definir el contenido HTML visual completo del diseño Canvas UI Lab
    $html = '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"5%","right":"5%"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:60px;padding-right:5%;padding-bottom:60px;padding-left:5%">
    <!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"2.5rem"}}} -->
    <h1 class="wp-block-heading" style="font-size:2.5rem">Canvas UI Lab</h1>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"style":{"color":{"text":"#64748b"}}} -->
    <p class="has-text-color" style="color:#64748b">Explora componentes y layouts interactivos optimizados para WordPress Playground.</p>
    <!-- /wp:paragraph -->

    <!-- wp:spacer {"height":"30px"} -->
    <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:columns {"align":"wide"} -->
    <div class="wp-block-columns alignwide">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large"} -->
            <figure class="wp-block-image size-large"><img src="/wp-content/mu-plugins/assets/images/preview-1.webp" alt="UI Component 1"/></figure>
            <!-- /wp:image -->
            <!-- wp:heading {"level":3} -->
            <h3>Design Systems</h3>
            <!-- /wp:heading -->
            <!-- wp:paragraph -->
            <p>Sistemas de diseño estructurados y componentes reutilizables en bloques nativos.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:image {"sizeSlug":"large"} -->
            <figure class="wp-block-image size-large"><img src="/wp-content/mu-plugins/assets/images/preview-2.webp" alt="UI Component 2"/></figure>
            <!-- /wp:image -->
            <!-- wp:heading {"level":3} -->
            <h3>Interactive Modules</h3>
            <!-- /wp:heading -->
            <!-- wp:paragraph -->
            <p>Módulos dinámicos desarrollados con la Interactivity API de WordPress.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->';

    // 4. Crear la página de inicio una sola vez
    $id = wp_insert_post([
        'post_title'   => 'Canvas UI Lab',
        'post_content' => $html,
        'post_status'  => 'publish',
        'post_type'    => 'page'
    ]);

    // 5. Configurar la página de portada y guardar la opción para evitar recreaciones futuras
    if ($id && !is_wp_error($id)) {
        update_option('page_on_front', $id);
        update_option('show_on_front', 'page');
        update_option('canvas_ui_lab_page_id', $id);
    }
});