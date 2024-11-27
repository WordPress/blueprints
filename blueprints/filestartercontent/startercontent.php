<?php
/*
Plugin Name: Short Theme Starter Content Example
Description: as part of a Blueprint example, this plugin adds a starter content to a Playground instans. 
Author: Birgit Pauli-Haack
Version: 0.0.1
*/
add_action( 'after_setup_theme', function() {
    add_theme_support( 'starter-content', array(
        'posts' => array(
            'homepage-section' => array(
                'post_type'    => 'page',
                'post_title'   => _x( 'Subscriptions', 'Theme starter content', 'your-text-domain' ),
                'post_content' => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Our Subscriptions</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"top":"var:preset|spacing|10"}}}} -->
<p class="has-text-align-center" style="margin-top:var(--wp--preset--spacing--10);font-size:1.125rem">We offer two subscription levels: </p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><strong><em>Free</em> at $0</strong> - Access to 5 exclusive <em>Études Articles</em> per month.</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><strong><em>Connoisseur</em> at $12</strong> - Access to 20 exclusive <em>Études Articles</em> per month.<br></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->'
            ),
        ),
    ) );
} );
