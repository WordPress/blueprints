<?php
/**
 * Plugin Name: Example Custom Post Types
 * Description: Registers custom post types and ACF field groups for demonstrating the Custom Fields Snapshots plugin.
 * Version: 1.0
 * Author: Alex Georgiev
 */

/**
 * Register ACF field groups for various post types.
 */
function create_custom_post_types() {
	register_post_type(
		'product',
		array(
			'labels'       => array(
				'name'          => 'Products',
				'singular_name' => 'Product',
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_menu' => true,
		)
	);

	register_post_type(
		'event',
		array(
			'labels'       => array(
				'name'          => 'Events',
				'singular_name' => 'Event',
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_menu' => true,
		)
	);

	register_post_type(
		'testimonial',
		array(
			'labels'       => array(
				'name'          => 'Testimonials',
				'singular_name' => 'Testimonial',
			),
			'public'       => false,
			'has_archive'  => false,
			'show_ui'      => true,
			'show_in_menu' => true,
		)
	);

	register_post_type(
		'team_member',
		array(
			'labels'       => array(
				'name'          => 'Team Members',
				'singular_name' => 'Team Member',
			),
			'public'       => false,
			'has_archive'  => false,
			'show_ui'      => true,
			'show_in_menu' => true,
		)
	);
}

add_action( 'init', 'create_custom_post_types' );

/**
 * Register custom post types.
 */
function add_acf_field_groups() {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		acf_add_local_field_group(
			array(
				'key'      => 'group_blog_post_details',
				'title'    => 'Blog Post Details',
				'fields'   => array(
					array(
						'key'   => 'field_post_subtitle',
						'label' => 'Post Subtitle',
						'name'  => 'post_subtitle',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_post_excerpt',
						'label' => 'Post Excerpt',
						'name'  => 'post_excerpt',
						'type'  => 'textarea',
					),
					array(
						'key'   => 'field_post_reading_time',
						'label' => 'Estimated Reading Time (minutes)',
						'name'  => 'post_reading_time',
						'type'  => 'number',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'post',
						),
					),
				),
			)
		);

		acf_add_local_field_group(
			array(
				'key'      => 'group_page_details',
				'title'    => 'Page Details',
				'fields'   => array(
					array(
						'key'   => 'field_page_header',
						'label' => 'Page Header',
						'name'  => 'page_header',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_page_summary',
						'label' => 'Page Summary',
						'name'  => 'page_summary',
						'type'  => 'textarea',
					),
					array(
						'key'   => 'field_page_cta',
						'label' => 'Call to Action Button',
						'name'  => 'page_cta',
						'type'  => 'link',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'page',
						),
					),
				),
			)
		);

		acf_add_local_field_group(
			array(
				'key'      => 'group_product_details',
				'title'    => 'Product Details',
				'fields'   => array(
					array(
						'key'   => 'field_product_price',
						'label' => 'Price',
						'name'  => 'product_price',
						'type'  => 'number',
					),
					array(
						'key'   => 'field_product_sku',
						'label' => 'SKU',
						'name'  => 'product_sku',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_product_stock',
						'label' => 'Stock Quantity',
						'name'  => 'product_stock',
						'type'  => 'number',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'product',
						),
					),
				),
			)
		);

		acf_add_local_field_group(
			array(
				'key'      => 'group_event_details',
				'title'    => 'Event Details',
				'fields'   => array(
					array(
						'key'   => 'field_event_date',
						'label' => 'Event Date',
						'name'  => 'event_date',
						'type'  => 'date_picker',
					),
					array(
						'key'   => 'field_event_location',
						'label' => 'Location',
						'name'  => 'event_location',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_event_capacity',
						'label' => 'Capacity',
						'name'  => 'event_capacity',
						'type'  => 'number',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'event',
						),
					),
				),
			)
		);

		acf_add_local_field_group(
			array(
				'key'      => 'group_testimonial_details',
				'title'    => 'Testimonial Details',
				'fields'   => array(
					array(
						'key'   => 'field_testimonial_author',
						'label' => 'Author',
						'name'  => 'testimonial_author',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_testimonial_company',
						'label' => 'Company',
						'name'  => 'testimonial_company',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_testimonial_rating',
						'label' => 'Rating',
						'name'  => 'testimonial_rating',
						'type'  => 'number',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'testimonial',
						),
					),
				),
			)
		);

		acf_add_local_field_group(
			array(
				'key'      => 'group_team_member_details',
				'title'    => 'Team Member Details',
				'fields'   => array(
					array(
						'key'   => 'field_team_member_position',
						'label' => 'Position',
						'name'  => 'team_member_position',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_team_member_email',
						'label' => 'Email',
						'name'  => 'team_member_email',
						'type'  => 'email',
					),
					array(
						'key'   => 'field_team_member_bio',
						'label' => 'Biography',
						'name'  => 'team_member_bio',
						'type'  => 'textarea',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'team_member',
						),
					),
				),
			)
		);
	}
}

add_action( 'acf/init', 'add_acf_field_groups' );
