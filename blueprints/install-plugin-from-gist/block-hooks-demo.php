<?php
/**
 * Plugin Name:       Block Hooks Demo
 * Description:       Block Hooks API experiments.
 * Version:           0.1.0
 * Requires at least: 6.5
 */

defined( 'ABSPATH' ) || exit;

/* EXAMPLE 1 */

function add_like_button_block_before_post_title_block( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {
	
	// Only apply this hook on the single template.
	if ( ! $context instanceof WP_Block_Template || ! property_exists( $context, 'slug' ) || 'single' !== $context->slug ) {
		return $hooked_block_types;
	}

	// Insert the Like Button block before the Post Title block.
	if ( 'before' === $relative_position && 'core/post-title' === $anchor_block_type ) {
		$hooked_block_types[] = 'ockham/like-button';
	}

		// // Remove the Like Button block after the Post Content block.
		// if ( 'after' === $relative_position && 'core/post-content' === $anchor_block_type ) {
		// 	$hooked_block_types = array_filter( $hooked_block_types, function( $block ) {
		// 		return 'ockham/like-button' !== $block;
		// 	} );
		// }

	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'add_like_button_block_before_post_title_block', 12, 4 );

function remove_hooked_like_button_block_after_post_content( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context  ) {
	
	// Has the hooked block been suppressed by a previous filter?
	if ( is_null( $parsed_hooked_block ) ) {
		return $parsed_hooked_block;
	}

	// Remove any Like Button blocks hooked after Post Content.
	if ( 'core/post-content' === $parsed_anchor_block['blockName'] ) {
		return null;
	}

	return $parsed_hooked_block;
}
// Priority is set to 15.
add_filter( 'hooked_block_ockham/like-button', 'remove_hooked_like_button_block_after_post_content', 15, 5 );


/* EXAMPLE 2 */

function add_copyright_date_block_to_footer( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {

	if ( 
		// Hook the block in footer patterns.
		( is_array( $context ) && isset( $context[ 'blockTypes' ] ) && in_array( 'core/template-part/footer', $context[ 'blockTypes' ] ) ) ||
		// Hook the block in footer template parts.
		( $context instanceof WP_Block_Template && property_exists( $context, 'slug' ) && 'footer' === $context->slug )
	) {
		if ( 
			'core/site-title' === $anchor_block_type &&
			'after' === $relative_position
		) {
			$hooked_block_types[] = 'create-block/copyright-date-block';
		}
	}

	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'add_copyright_date_block_to_footer', 10, 4 );

function modify_hooked_copyright_date_block_in_footer( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context  ) {

	// Has the hooked block been suppressed by a previous filter?
	if ( is_null( $parsed_hooked_block ) ) {
		return $parsed_hooked_block;
	}

	// Only apply the updated attributes if the block is hooked after a Site Title block.
	if (
		isset( $parsed_anchor_block['blockName'] ) && 
		'core/site-title' === $parsed_anchor_block['blockName'] &&
		'after' === $relative_position
	) {

		$parsed_hooked_block['attrs'] = array(
			'startingYear'     => '2019',
			'showStartingYear' => true,
			'fontSize'         => 'small',
			'textColor'        => 'contrast-2', // Color slug preset by the theme.
		);
	}

	return $parsed_hooked_block;
}
add_filter( 'hooked_block_create-block/copyright-date-block', 'modify_hooked_copyright_date_block_in_footer', 10, 5 );


/* EXAMPLE 3 */

function add_paragraph_after_post_content_on_posts( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {

	if ( 
		// Hook the block on single templates.
		( $context instanceof WP_Block_Template && property_exists( $context, 'slug' ) && 'single' === $context->slug )
	) {
		if ( 
			'core/post-content' === $anchor_block_type &&
			'after' === $relative_position
		) {
			$hooked_block_types[] = 'core/paragraph';
		}
	}

	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'add_paragraph_after_post_content_on_posts', 10, 4 );

function modify_hooked_paragraph_block_after_post_content( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context  ) {

	// Has the hooked block been suppressed by a previous filter?
	if ( is_null( $parsed_hooked_block ) ) {
		return $parsed_hooked_block;
	}

	// Only apply the updated attributes if the block is hooked after a Post Content block.
	if ( 
		'core/post-content' === $parsed_anchor_block['blockName'] &&
		'after' === $relative_position
	) {

		// Set the font size and the alignment of the text in the Paragraph block.
		$parsed_hooked_block['attrs'] = array(
			'align'    => 'right',
			'fontSize' => 'small'
		);
		$parsed_hooked_block['innerContent'] = array( 
			'<p class="has-text-align-right has-small-font-size"><a href="#">' . __( 'Back to top' ) . '</a></p>' 
		);
		
		// Wrap the Paragraph block in a Group block with a contrained layout and top margin.
		return array(
			'blockName' => 'core/group',
			'attrs'     => array(
				"layout" => array(
					"type" => "constrained"
				),
				'style' => array(
					'spacing' => array(
						'margin' => array(
							'top' => 'var:preset|spacing|40'
						)
					)
				)
			),
			'innerBlocks' => array( $parsed_hooked_block ),
			'innerContent' => array(
				'<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--40)">',
				null,
				'</div>'
			),
		);
	}

	return $parsed_hooked_block;
}
add_filter( 'hooked_block_core/paragraph', 'modify_hooked_paragraph_block_after_post_content', 10, 5 );


/* EXAMPLE 4 */

function add_loginout_block_to_header_navigation( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {

	// Is $context a Navigation menu?
	if ( ! $context instanceof WP_Post || 'wp_navigation' !== $context->post_type ) {
		return $hooked_block_types;
	}

	// Check if the menu title contains "header", "heading", or "primary". Abort otherwise.
	if ( 
		false === stripos( $context->post_title, 'header' ) && 
		false === stripos( $context->post_title, 'heading' ) &&
		false === stripos( $context->post_title, 'primary' )
	) {
		return $hooked_block_types;
	}
	
	if ( 'last_child' === $relative_position && 'core/navigation' === $anchor_block_type ) {
		$hooked_block_types[] = 'core/loginout';
	}

	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'add_loginout_block_to_header_navigation', 10, 4 );