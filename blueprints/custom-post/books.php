<?php
/*
 * Plugin Name:       Books
 * Plugin URI:        https://icodeforapurpose.com
 * Description:       Create the Book Custom post Type
 * Version:           0.1.
 * Requires at least: 6.0
 * Requires PHP:      7.2
 * Author:            Birgit Pauli-Haack
 * Author URI:        https://profiles.wordpress.org/bph
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       gbt
 * Domain Path:       /languages
 */


// Register Custom Post Type
function register_books() {

	$labels = array(
		'name'                  => _x( 'Books', 'Post Type General Name', 'gbt' ),
		'singular_name'         => _x( 'Book', 'Post Type Singular Name', 'gbt' ),
		'menu_name'             => __( 'Books', 'gbt' ),
		'name_admin_bar'        => __( 'Books', 'gbt' ),
		'archives'              => __( 'Book Archives', 'gbt' ),
		'attributes'            => __( 'Book Attributes', 'gbt' ),
		'parent_item_colon'     => __( 'Parent book', 'gbt' ),
		'all_items'             => __( 'All Books', 'gbt' ),
		'add_new_item'          => __( 'Add New Book', 'gbt' ),
		'add_new'               => __( 'Add New Book', 'gbt' ),
		'new_item'              => __( 'New Book', 'gbt' ),
		'edit_item'             => __( 'Edit Book', 'gbt' ),
		'update_item'           => __( 'Update Book', 'gbt' ),
		'view_item'             => __( 'View Book', 'gbt' ),
		'view_items'            => __( 'View Books', 'gbt' ),
		'search_items'          => __( 'Search Books', 'gbt' ),
		'not_found'             => __( 'Not found', 'gbt' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'gbt' ),
		'featured_image'        => __( 'Featured Image', 'gbt' ),
		'set_featured_image'    => __( 'Set featured image', 'gbt' ),
		'remove_featured_image' => __( 'Remove featured image', 'gbt' ),
		'use_featured_image'    => __( 'Use as featured image', 'gbt' ),
		'insert_into_item'      => __( 'Insert into item', 'gbt' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'gbt' ),
		'items_list'            => __( 'Items list', 'gbt' ),
		'items_list_navigation' => __( 'Items list navigation', 'gbt' ),
		'filter_items_list'     => __( 'Filter items list', 'gbt' ),
	);
	$args = array(
		'label'                 => __( 'Book', 'gbt' ),
		'description'           => __( 'Books', 'gbt' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor'),
		'taxonomies'            => array( 'genre', ' publisher' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-book-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
	);
	register_post_type( 'gbtbooks', $args );

}
add_action( 'init', 'register_books', 0 );
