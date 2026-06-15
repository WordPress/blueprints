<?php

require '/wordpress/wp-load.php';

/**
 * Media Database Setup Script
 *
 * This script creates attachment records in the database for media files that are
 * assumed to already exist in wp-content/uploads. It also updates thumbnail references
 * on posts by matching post_type + title + post_date (not by ID).
 *
 * Run via WP-CLI: wp eval-file database-media.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

/**
 * Media files to register as attachments.
 * Each entry contains the file path (relative to uploads), title, date, mime type,
 * and optionally the parent post identifier (type+title+date).
 */
$attachments = array(
	array(
		'file'      => 'woocommerce-placeholder.png',
		'title'     => 'woocommerce-placeholder',
		'date'      => '2024-11-07 06:51:51',
		'mime_type' => 'image/png',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_eefvayeefvayeefv.jpeg',
		'title'     => 'Gemini_Generated_Image_eefvayeefvayeefv',
		'date'      => '2024-11-07 06:56:52',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/davies-designs-studio-dufGTlUAGJ0-unsplash.jpg',
		'title'     => 'davies-designs-studio-dufGTlUAGJ0-unsplash',
		'date'      => '2024-11-07 08:05:56',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'wp_template', 'title' => 'Single Posts', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/alexandra-gorn-9rmnzkmydSY-unsplash-scaled.jpg',
		'title'     => 'alexandra-gorn-9rmnzkmydSY-unsplash',
		'date'      => '2024-11-07 08:07:52',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'wp_template', 'title' => 'Single Posts', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/jon-tyson-fIVD9x3JvDo-unsplash.jpg',
		'title'     => 'jon-tyson-fIVD9x3JvDo-unsplash',
		'date'      => '2024-11-07 08:08:33',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'wp_template', 'title' => 'Single Posts', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/pariwat-pannium-S8daAB_nJSg-unsplash-scaled.jpg',
		'title'     => 'pariwat-pannium-S8daAB_nJSg-unsplash',
		'date'      => '2024-11-07 08:10:13',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'wp_template', 'title' => 'Single Posts', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/Joburg-Gold.jpeg',
		'title'     => 'Joburg Gold',
		'date'      => '2024-11-07 08:17:33',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Joburg Gold', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_x69a3kx69a3kx69a.jpeg',
		'title'     => 'Gemini_Generated_Image_x69a3kx69a3kx69a',
		'date'      => '2024-11-07 08:23:10',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_oqcrsooqcrsooqcr.jpeg',
		'title'     => 'Gemini_Generated_Image_oqcrsooqcrsooqcr',
		'date'      => '2024-11-07 08:25:26',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Kiwi Black', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_sn9i0xsn9i0xsn9i.jpeg',
		'title'     => 'Gemini_Generated_Image_sn9i0xsn9i0xsn9i',
		'date'      => '2024-11-07 08:26:00',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_12p0db12p0db12p0.jpeg',
		'title'     => 'Gemini_Generated_Image_12p0db12p0db12p0',
		'date'      => '2024-11-07 08:28:03',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'London Fog', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_adbrbladbrbladbr.jpeg',
		'title'     => 'Gemini_Generated_Image_adbrbladbrbladbr',
		'date'      => '2024-11-07 08:29:11',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_ee8fukee8fukee8f.jpeg',
		'title'     => 'Gemini_Generated_Image_ee8fukee8fukee8f',
		'date'      => '2024-11-07 08:36:17',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Kiwi Black', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_ycs91bycs91bycs9.jpeg',
		'title'     => 'Gemini_Generated_Image_ycs91bycs91bycs9',
		'date'      => '2024-11-07 08:38:15',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_2bk9lm2bk9lm2bk9.jpeg',
		'title'     => 'Gemini_Generated_Image_2bk9lm2bk9lm2bk9',
		'date'      => '2024-11-07 09:17:27',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Composite Product', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_ltl1y0ltl1y0ltl1.jpeg',
		'title'     => 'Gemini_Generated_Image_ltl1y0ltl1y0ltl1',
		'date'      => '2024-11-07 09:28:34',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_bt4wbpbt4wbpbt4w.jpeg',
		'title'     => 'Gemini_Generated_Image_bt4wbpbt4wbpbt4w',
		'date'      => '2024-11-07 09:31:16',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_wvkhqjwvkhqjwvkh.jpeg',
		'title'     => 'Gemini_Generated_Image_wvkhqjwvkhqjwvkh',
		'date'      => '2024-11-07 09:38:47',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Variable Product Selection', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/Gemini_Generated_Image_q1vqd1q1vqd1q1vq.jpeg',
		'title'     => 'Gemini_Generated_Image_q1vqd1q1vqd1q1vq',
		'date'      => '2024-11-07 09:40:57',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Variable Product Selection', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/gotta-go-fast.gif',
		'title'     => 'gotta-go-fast',
		'date'      => '2024-11-20 12:41:17',
		'mime_type' => 'image/gif',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Rich Bait', 'date' => '2024-11-20' ),
	),
	array(
		'file'      => '2024/11/BrewCommerce_Hero-1024x1024.jpeg',
		'title'     => 'BrewCommerce_Hero',
		'date'      => '2025-02-14 12:43:50',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'wp_template', 'title' => 'Single Posts', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2025/03/Brew_Cog.jpeg',
		'title'     => 'Brew_Cog',
		'date'      => '2025-03-28 12:47:03',
		'mime_type' => 'image/jpeg',
		'parent'    => array( 'post_type' => 'product', 'title' => 'Group Product Switcher - Worker', 'date' => '2025-03-20' ),
	),
	array(
		'file'      => '2025/03/coffee-bean-solid.png',
		'title'     => 'coffee-bean-solid',
		'date'      => '2025-03-28 12:53:20',
		'mime_type' => 'image/png',
		'parent'    => null,
	),
	array(
		'file'      => '2025/03/cropped-coffee-bean-solid.png',
		'title'     => 'cropped-coffee-bean-solid.png',
		'date'      => '2025-03-28 12:53:29',
		'mime_type' => 'image/png',
		'parent'    => null,
	),
	array(
		'file'      => '2025/03/simple_bundle.png',
		'title'     => 'simple_bundle',
		'date'      => '2025-03-31 19:02:39',
		'mime_type' => 'image/png',
		'parent'    => null,
	),
	array(
		'file'      => '2025/04/Gemini_Generated_Image_1zhc831zhc831zhc.jpeg',
		'title'     => 'Gemini_Generated_Image_1zhc831zhc831zhc',
		'date'      => '2025-04-09 13:29:49',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2025/04/BrewCommerce_1kg_Bundle.jpeg',
		'title'     => 'BrewCommerce_1kg_Bundle',
		'date'      => '2025-04-09 13:35:37',
		'mime_type' => 'image/jpeg',
		'parent'    => null,
	),
	array(
		'file'      => '2025/07/BrewCommerceLogo_v2.png',
		'title'     => 'BrewCommerceLogo_v2',
		'date'      => '2025-07-17 17:32:52',
		'mime_type' => 'image/png',
		'parent'    => null,
	),
	array(
		'file'      => '2025/07/BrewCommerceLogo_v2-w.png',
		'title'     => 'BrewCommerceLogo_v2-w',
		'date'      => '2025-07-17 17:35:10',
		'mime_type' => 'image/png',
		'parent'    => null,
	),
	array(
		'file'      => '2025/11/BC_single.webp',
		'title'     => 'BC_single',
		'date'      => '2025-11-14 06:29:48',
		'mime_type' => 'image/png',
		'parent'    => null,
	),
	array(
		'file'      => '2024/11/BC_Bundles.webp',
		'title'     => 'BC_Bundles',
		'date'      => '2025-11-14 06:30:17',
		'mime_type' => 'image/png',
		'parent'    => array( 'post_type' => 'wp_template', 'title' => 'Single Posts', 'date' => '2024-11-07' ),
	),
	array(
		'file'      => '2024/11/BC_Subs.webp',
		'title'     => 'BC_Subs',
		'date'      => '2025-11-14 06:33:45',
		'mime_type' => 'image/png',
		'parent'    => array( 'post_type' => 'wp_template', 'title' => 'Single Posts', 'date' => '2024-11-07' ),
	),
);

/**
 * Product prices: Set prices for WooCommerce products after import.
 * WooCommerce stores prices in postmeta with _price and _regular_price keys.
 * Products are identified by post_type + title + date prefix.
 */
$product_prices = array(
	// Joburg Gold - variable coffee product
	array(
		'post'  => array( 'post_type' => 'product', 'title' => 'Joburg Gold', 'date' => '2024-11-07' ),
		'price' => '10',
	),
	array(
		'post'  => array( 'post_type' => 'product_variation', 'title' => 'Joburg Gold - 1kg', 'date' => '2024-11-07' ),
		'price' => '25',
	),
	array(
		'post'  => array( 'post_type' => 'product_variation', 'title' => 'Joburg Gold - 250g', 'date' => '2024-11-07' ),
		'price' => '10',
	),
	// Kiwi Black - variable coffee product
	array(
		'post'  => array( 'post_type' => 'product', 'title' => 'Kiwi Black', 'date' => '2024-11-07' ),
		'price' => '10',
	),
	array(
		'post'  => array( 'post_type' => 'product_variation', 'title' => 'Kiwi Black - 250g', 'date' => '2024-11-07' ),
		'price' => '10',
	),
	array(
		'post'  => array( 'post_type' => 'product_variation', 'title' => 'Kiwi Black - 1kg', 'date' => '2024-11-07' ),
		'price' => '25',
	),
	// London Fog - variable coffee product
	array(
		'post'  => array( 'post_type' => 'product', 'title' => 'London Fog', 'date' => '2024-11-07' ),
		'price' => '10',
	),
	array(
		'post'  => array( 'post_type' => 'product_variation', 'title' => 'London Fog - 250g', 'date' => '2024-11-07' ),
		'price' => '10',
	),
	array(
		'post'  => array( 'post_type' => 'product_variation', 'title' => 'London Fog - 1kg', 'date' => '2024-11-07' ),
		'price' => '25',
	),
	// Coffee bundles
	array(
		'post'  => array( 'post_type' => 'product', 'title' => 'Product Bundle (250g)', 'date' => '2024-11-07' ),
		'price' => '20',
	),
	array(
		'post'  => array( 'post_type' => 'product', 'title' => 'Product Bundle (1kg)', 'date' => '2025-04-09' ),
		'price' => '50',
	),
);

/**
 * Thumbnail mappings: which posts get which attachment as their featured image.
 * Posts are identified by post_type + title + date prefix.
 * Attachments are identified by their title.
 */
$thumbnail_mappings = array(
	// Coffee products
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Joburg Gold', 'date' => '2024-11-07' ),
		'attachment' => 'Joburg Gold',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Kiwi Black', 'date' => '2024-11-07' ),
		'attachment' => 'Gemini_Generated_Image_ee8fukee8fukee8f',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'London Fog', 'date' => '2024-11-07' ),
		'attachment' => 'Gemini_Generated_Image_12p0db12p0db12p0',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Product Bundle (250g)', 'date' => '2024-11-07' ),
		'attachment' => 'Gemini_Generated_Image_2bk9lm2bk9lm2bk9',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Product Bundle (1kg)', 'date' => '2025-04-09' ),
		'attachment' => 'BrewCommerce_1kg_Bundle',
	),
	// Other products (still need thumbnails even if not priced)
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Composite Product', 'date' => '2024-11-07' ),
		'attachment' => 'Gemini_Generated_Image_2bk9lm2bk9lm2bk9',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Weight', 'date' => '2024-11-07' ),
		'attachment' => 'Brew_Cog',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Variable Product Selection', 'date' => '2024-11-07' ),
		'attachment' => 'Gemini_Generated_Image_q1vqd1q1vqd1q1vq',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Rich Bait', 'date' => '2024-11-20' ),
		'attachment' => 'gotta-go-fast',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Group Product Switcher - Worker', 'date' => '2025-03-20' ),
		'attachment' => 'Brew_Cog',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Single Brew Collection', 'date' => '2025-03-20' ),
		'attachment' => 'Gemini_Generated_Image_q1vqd1q1vqd1q1vq',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Complex Combos', 'date' => '2025-03-20' ),
		'attachment' => 'Gemini_Generated_Image_ltl1y0ltl1y0ltl1',
	),
	array(
		'post'       => array( 'post_type' => 'product', 'title' => 'Triple Pack', 'date' => '2025-03-28' ),
		'attachment' => 'Gemini_Generated_Image_q1vqd1q1vqd1q1vq',
	),
);

/**
 * Find a post by post_type, title, and date prefix.
 */
function find_post_by_identifier( $identifier ) {
	global $wpdb;

	$post_type = $identifier['post_type'];
	$title     = $identifier['title'];
	$date      = $identifier['date'];

	$post = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_type = %s
			AND post_title = %s
			AND post_date LIKE %s
			LIMIT 1",
			$post_type,
			$title,
			$date . '%'
		)
	);

	return $post ? $post->ID : null;
}

/**
 * Find an attachment by its title.
 */
function find_attachment_by_title( $title ) {
	global $wpdb;

	$attachment = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_title = %s
			LIMIT 1",
			$title
		)
	);

	return $attachment ? $attachment->ID : null;
}

/**
 * Generate basic attachment metadata for an image file.
 */
function generate_attachment_metadata( $file, $mime_type ) {
	$upload_dir = wp_upload_dir();
	$file_path  = $upload_dir['basedir'] . '/' . $file;

	$metadata = array(
		'file' => $file,
	);

	if ( file_exists( $file_path ) && strpos( $mime_type, 'image/' ) === 0 ) {
		$image_size = @getimagesize( $file_path );
		if ( $image_size ) {
			$metadata['width']  = $image_size[0];
			$metadata['height'] = $image_size[1];
		}

		$metadata['filesize'] = @filesize( $file_path );
		$metadata['sizes']    = array();

		// WordPress will regenerate thumbnails if needed via wp_generate_attachment_metadata()
	}

	return $metadata;
}

/**
 * Create an attachment post and its metadata.
 */
function create_attachment( $attachment_data ) {
	global $wpdb;

	$file      = $attachment_data['file'];
	$title     = $attachment_data['title'];
	$date      = $attachment_data['date'];
	$mime_type = $attachment_data['mime_type'];
	$parent    = $attachment_data['parent'];

	// Check if attachment already exists
	$existing = find_attachment_by_title( $title );
	if ( $existing ) {
		echo "Attachment already exists: {$title} (ID: {$existing})\n";
		return $existing;
	}

	// Find parent post ID if specified
	$parent_id = 0;
	if ( $parent ) {
		$parent_id = find_post_by_identifier( $parent );
		if ( ! $parent_id ) {
			echo "Warning: Parent post not found for attachment {$title}\n";
			$parent_id = 0;
		}
	}

	// Build the attachment URL
	$upload_dir = wp_upload_dir();
	$guid       = $upload_dir['baseurl'] . '/' . $file;

	// Generate slug from title
	$slug = sanitize_title( $title );

	// Insert the attachment post
	$attachment_id = wp_insert_post(
		array(
			'post_author'    => 1,
			'post_date'      => $date,
			'post_date_gmt'  => $date,
			'post_title'     => $title,
			'post_status'    => 'inherit',
			'comment_status' => 'open',
			'ping_status'    => 'closed',
			'post_name'      => $slug,
			'post_parent'    => $parent_id,
			'guid'           => $guid,
			'post_type'      => 'attachment',
			'post_mime_type' => $mime_type,
		)
	);

	if ( is_wp_error( $attachment_id ) ) {
		echo "Error creating attachment {$title}: " . $attachment_id->get_error_message() . "\n";
		return null;
	}

	// Add _wp_attached_file meta
	update_post_meta( $attachment_id, '_wp_attached_file', $file );

	// Generate and save attachment metadata
	$metadata = generate_attachment_metadata( $file, $mime_type );
	update_post_meta( $attachment_id, '_wp_attachment_metadata', $metadata );

	echo "Created attachment: {$title} (ID: {$attachment_id})\n";

	return $attachment_id;
}

/**
 * Set a post's featured image (thumbnail).
 */
function set_post_thumbnail_by_identifier( $post_identifier, $attachment_title ) {
	$post_id       = find_post_by_identifier( $post_identifier );
	$attachment_id = find_attachment_by_title( $attachment_title );

	if ( ! $post_id ) {
		echo "Warning: Post not found - {$post_identifier['post_type']}: {$post_identifier['title']}\n";
		return false;
	}

	if ( ! $attachment_id ) {
		echo "Warning: Attachment not found - {$attachment_title}\n";
		return false;
	}

	update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
	echo "Set thumbnail for {$post_identifier['title']} to {$attachment_title}\n";

	return true;
}

/**
 * Set a product's price and essential WooCommerce metadata.
 * WooCommerce requires both _price and _regular_price to display prices.
 * Also sets stock status and other flags so products are purchasable.
 */
function set_product_price( $post_identifier, $price ) {
	$post_id = find_post_by_identifier( $post_identifier );

	if ( ! $post_id ) {
		echo "Warning: Product not found - {$post_identifier['post_type']}: {$post_identifier['title']}\n";
		return false;
	}

	// Price metadata
	update_post_meta( $post_id, '_price', $price );
	update_post_meta( $post_id, '_regular_price', $price );

	// Stock and inventory
	update_post_meta( $post_id, '_stock_status', 'instock' );
	update_post_meta( $post_id, '_manage_stock', 'no' );
	update_post_meta( $post_id, '_backorders', 'no' );

	// Product type flags
	update_post_meta( $post_id, '_virtual', 'no' );
	update_post_meta( $post_id, '_downloadable', 'no' );
	update_post_meta( $post_id, '_sold_individually', 'no' );

	echo "Set price for {$post_identifier['title']} to €{$price}\n";

	return true;
}

/**
 * Create the homepage with dynamic image ID references.
 */
function create_homepage() {
	// Get the attachment IDs we need
	$hero_id       = find_attachment_by_title( 'BrewCommerce_Hero' );
	$bc_single_id  = find_attachment_by_title( 'BC_single' );
	$bc_bundles_id = find_attachment_by_title( 'BC_Bundles' );
	$bc_subs_id    = find_attachment_by_title( 'BC_Subs' );

	if ( ! $hero_id ) {
		echo "Warning: BrewCommerce_Hero attachment not found, homepage may not display correctly\n";
		$hero_id = 0;
	}
	if ( ! $bc_single_id ) {
		echo "Warning: BC_single attachment not found\n";
		$bc_single_id = 0;
	}
	if ( ! $bc_bundles_id ) {
		echo "Warning: BC_Bundles attachment not found\n";
		$bc_bundles_id = 0;
	}
	if ( ! $bc_subs_id ) {
		echo "Warning: BC_Subs attachment not found\n";
		$bc_subs_id = 0;
	}

	$upload_dir = wp_upload_dir();
	$base_url   = $upload_dir['baseurl'];

	// Homepage content with dynamic IDs
	$content = '<!-- wp:cover {"url":"' . $base_url . '/2024/11/BrewCommerce_Hero-1024x1024.jpeg","id":' . $hero_id . ',"alt":"BrewCommerce\'s Hero Image","hasParallax":true,"dimRatio":0,"isUserOverlayColor":true,"minHeight":739,"contentPosition":"center right","isDark":false,"sizeSlug":"large","metadata":{"categories":["woo-commerce","intro"],"patternName":"woocommerce-blocks/just-arrived-full-hero","name":"Just Arrived Full Hero"},"align":"full","style":{"color":[]}} -->
<div class="wp-block-cover alignfull is-light has-parallax has-custom-content-position is-position-center-right" style="min-height:739px"><div role="img" aria-label="BrewCommerce\'s Hero Image" class="wp-block-cover__image-background wp-image-' . $hero_id . ' size-large has-parallax" style="background-position:50% 50%;background-image:url(' . $base_url . '/2024/11/BrewCommerce_Hero-1024x1024.jpeg)"></div><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"padding":{"right":"60px","left":"60px","top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}},"border":{"width":"0px","style":"none","radius":"2em"},"color":{"background":"#4c244fcf"}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group has-background" style="border-style:none;border-width:0px;border-radius:2em;color:#FFF;background-color:#4c244fcf;padding-top:var(--wp--preset--spacing--40);padding-right:60px;padding-bottom:var(--wp--preset--spacing--40);padding-left:60px"><!-- wp:heading -->
<h2 class="wp-block-heading" id="just-arrived">Find your brew</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|foreground"}}}},"textColor":"foreground"} -->
<p class="has-foreground-color has-text-color has-link-color">Explore our exclusive range of coffee, roasted in a mythical kitchen that doesn\'t exist.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons"><!-- wp:button {"fontSize":"large","fontFamily":"roboto-slab"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-roboto-slab-font-family has-large-font-size has-custom-font-size wp-element-button" href="/shop/">Shop now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"metadata":{"name":"Product Gallery","categories":["woo-commerce","featured-selling"],"patternName":"woocommerce-blocks/product-query-product-gallery"},"align":"full","className":" preview-opacity","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull preview-opacity" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","align":"wide"} -->
<h2 class="wp-block-heading alignwide has-text-align-center">Bestsellers</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":{"product_cat":[21]},"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"aspectRatio":"3/4"} -->
<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"center","level":6,"isLink":true,"style":{"typography":{"textTransform":"capitalize"},"spacing":{"margin":{"top":"12px","bottom":"8px"}}}} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Featured Category Triple","categories":["woo-commerce","featured-selling"],"patternName":"woocommerce-blocks/featured-category-triple"},"align":"full","className":" preview-opacity","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull preview-opacity" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"metadata":{"categories":["woo-commerce"],"patternName":"woocommerce-blocks/featured-category-triple","name":"Featured Category Triple"},"align":"wide","style":{"spacing":{"blockGap":{"top":"0px","left":"0px"},"padding":{"right":"0","left":"0","top":"0","bottom":"0"},"margin":{"top":"0px","bottom":"0px"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:0px;margin-bottom:0px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"' . $base_url . '/2025/11/BC_single.webp","id":' . $bc_single_id . ',"dimRatio":30,"overlayColor":"black","isUserOverlayColor":true,"contentPosition":"bottom center","isDark":false,"sizeSlug":"large","className":"is-light has-white-color","style":{"spacing":{"padding":{"bottom":"56px"}}}} -->
<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center has-white-color" style="padding-bottom:56px"><img class="wp-block-cover__image-background wp-image-' . $bc_single_id . ' size-large" alt="" src="' . $base_url . '/2025/11/BC_single.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-30 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":4,"style":{"spacing":{"margin":{"bottom":"24px"}},"color":{"background":"#4c244fa8"}}} -->
<h4 class="wp-block-heading has-text-align-center has-background" style="background-color:#4c244fa8;margin-bottom:24px"><a href="/product-category/beans/" data-type="product_cat" data-id="21">All the beans</a></h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-align-center has-link-color" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><a href="/shop/" data-type="link" data-id="/shop/">Shop Now</a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"' . $base_url . '/2024/11/BC_Bundles.webp","id":' . $bc_bundles_id . ',"dimRatio":30,"overlayColor":"black","isUserOverlayColor":true,"contentPosition":"bottom center","isDark":false,"sizeSlug":"large","className":"is-light has-white-color","style":{"spacing":{"padding":{"bottom":"56px"}},"border":{"top":{"width":"0px","style":"none"},"right":{"color":"#a4a4a4","style":"dotted","width":"3px"},"bottom":{"width":"0px","style":"none"},"left":{"color":"#a4a4a4","style":"dotted","width":"3px"}}}} -->
<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center has-white-color" style="border-top-style:none;border-top-width:0px;border-right-color:#a4a4a4;border-right-style:dotted;border-right-width:3px;border-bottom-style:none;border-bottom-width:0px;border-left-color:#a4a4a4;border-left-style:dotted;border-left-width:3px;padding-bottom:56px"><img class="wp-block-cover__image-background wp-image-' . $bc_bundles_id . ' size-large" alt="" src="' . $base_url . '/2024/11/BC_Bundles.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-30 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":4,"style":{"spacing":{"margin":{"bottom":"24px"}},"color":{"background":"#4c244fa8"}}} -->
<h4 class="wp-block-heading has-text-align-center has-background" style="background-color:#4c244fa8;margin-bottom:24px"><a href="/product-category/beans/product-bundle/" data-type="product_cat" data-id="31">Product Bundles</a></h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-align-center has-link-color" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><a href="/shop/" data-type="link" data-id="/shop/">Shop Now</a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:cover {"url":"' . $base_url . '/2024/11/BC_Subs.webp","id":' . $bc_subs_id . ',"dimRatio":30,"overlayColor":"black","isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0.53},"contentPosition":"bottom center","isDark":false,"sizeSlug":"large","className":"is-light has-white-color","style":{"spacing":{"padding":{"bottom":"56px"}}}} -->
<div class="wp-block-cover is-light has-custom-content-position is-position-bottom-center has-white-color" style="padding-bottom:56px"><img class="wp-block-cover__image-background wp-image-' . $bc_subs_id . ' size-large" alt="" src="' . $base_url . '/2024/11/BC_Subs.webp" style="object-position:50% 53%" data-object-fit="cover" data-object-position="50% 53%"/><span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim-30 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":4,"style":{"spacing":{"margin":{"bottom":"24px"}},"color":{"background":"#4c244fa8"}}} -->
<h4 class="wp-block-heading has-text-align-center has-background" style="background-color:#4c244fa8;margin-bottom:24px"><a href="/shop/" data-type="page" data-id="6">Subscriptions</a></h4>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"padding":{"top":"0","bottom":"0"},"margin":{"top":"0","bottom":"0"}}}} -->
<p class="has-text-align-center has-link-color" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><a href="/shop/" data-type="link" data-id="/shop/">Shop Now</a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Reviews","categories":["reviews"],"patternName":"ff"},"align":"full","className":" preview-opacity","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center","wideSize":"900px"}} -->
<div class="wp-block-group alignfull preview-opacity" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","align":"wide"} -->
<h2 class="wp-block-heading alignwide has-text-align-center">Reviews</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|10","right":"var:preset|spacing|10"}}}} -->
<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--10)"><!-- wp:group {"layout":{"type":"constrained","contentSize":"380px","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","orientation":"vertical"}} -->
<div class="wp-block-group">

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":3,"className":"is-testimonial-name","style":{"typography":{"lineHeight":"1"}},"fontSize":"small"} -->
<h3 class="wp-block-heading has-text-align-center is-testimonial-name has-small-font-size" style="line-height:1">Jane Script</h3>
<!-- /wp:heading --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"0"} -->
<div style="height:0" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph {"align":"center","className":"is-testimonial-review"} -->
<p class="has-text-align-center is-testimonial-review">"I was told I\'d get a free coffee for writing this review."</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|10","right":"var:preset|spacing|10"}}}} -->
<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--10)"><!-- wp:group {"layout":{"type":"constrained","contentSize":"380px","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","orientation":"vertical"}} -->
<div class="wp-block-group">

<!-- wp:group {"style":{"spacing":{"blockGap":"6px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":4,"className":"is-testimonial-name","style":{"typography":{"lineHeight":"1"}},"fontSize":"small"} -->
<h4 class="wp-block-heading has-text-align-center is-testimonial-name has-small-font-size" style="line-height:1">Sven Addict</h4>
<!-- /wp:heading --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"0"} -->
<div style="height:0" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph {"align":"center","className":"is-testimonial-review"} -->
<p class="has-text-align-center is-testimonial-review">I recently purchased coffee from <a href="https://www.terbodore.com/">Terbodore</a> in the Midlands of KZN in South Africa. <br>As in, real coffee. It\'s amazing."</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"Product Collection: Featured Products 5 Columns","categories":["woo-commerce","featured-selling"],"patternName":"woocommerce-blocks/product-collection-featured-products-5-columns"},"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))"><!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">Shop new arrivals</h3>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":5,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":5},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."},"align":"wide"} -->
<div class="wp-block-woocommerce-product-collection alignwide"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"isDescendentOfQueryLoop":true,"aspectRatio":"1"} -->
<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","fontSize":"small"} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->

<!-- wp:buttons {"align":"wide","layout":{"type":"flex","verticalAlignment":"center","justifyContent":"center"}} -->
<div class="wp-block-buttons alignwide"><!-- wp:button {"textAlign":"center"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-text-align-center wp-element-button" href="/shop/">Shop All</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->';

	// Check if homepage already exists
	$existing = get_page_by_path( 'home' );
	if ( $existing ) {
		echo "Homepage already exists (ID: {$existing->ID}), updating content...\n";
		wp_update_post(
			array(
				'ID'           => $existing->ID,
				'post_content' => $content,
			)
		);
		$page_id = $existing->ID;
	} else {
		// Create the homepage
		$page_id = wp_insert_post(
			array(
				'post_title'   => 'Home',
				'post_name'    => 'home',
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			)
		);

		if ( is_wp_error( $page_id ) ) {
			echo "Error creating homepage: " . $page_id->get_error_message() . "\n";
			return null;
		}

		echo "Created homepage (ID: {$page_id})\n";
	}

	// Set as front page
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_id );
	echo "Set homepage as front page\n";

	return $page_id;
}

// Main execution
echo "=== Creating Attachment Records ===\n\n";

foreach ( $attachments as $attachment_data ) {
	create_attachment( $attachment_data );
}

echo "\n=== Setting Post Thumbnails ===\n\n";

foreach ( $thumbnail_mappings as $mapping ) {
	set_post_thumbnail_by_identifier( $mapping['post'], $mapping['attachment'] );
}

echo "\n=== Setting Product Prices ===\n\n";

foreach ( $product_prices as $price_data ) {
	set_product_price( $price_data['post'], $price_data['price'] );
}

echo "\n=== Creating Homepage ===\n\n";

create_homepage();

echo "\n=== Setting Site Logo ===\n\n";

$logo_id = find_attachment_by_title( 'BrewCommerceLogo_v2-w' );
if ( $logo_id ) {
	update_option( 'site_logo', $logo_id );
	set_theme_mod( 'custom_logo', $logo_id );
	echo "Set site logo to BrewCommerceLogo_v2-w (ID: {$logo_id})\n";
} else {
	echo "Warning: Site logo attachment not found\n";
}

echo "\n=== Updating WooCommerce Page Settings ===\n\n";

// After WXR import, page IDs change. Update WooCommerce to use the correct pages.
$wc_pages = array(
	'woocommerce_shop_page_id'     => 'shop',
	'woocommerce_cart_page_id'     => 'cart',
	'woocommerce_checkout_page_id' => 'checkout',
	'woocommerce_myaccount_page_id' => 'my-account',
);

foreach ( $wc_pages as $option_name => $page_slug ) {
	$page = get_page_by_path( $page_slug );
	if ( $page ) {
		update_option( $option_name, $page->ID );
		echo "Set {$option_name} to page '{$page_slug}' (ID: {$page->ID})\n";
	} else {
		echo "Warning: Page '{$page_slug}' not found for {$option_name}\n";
	}
}

// Update WooCommerce placeholder image ID
$placeholder = find_attachment_by_title( 'woocommerce-placeholder' );
if ( $placeholder ) {
	update_option( 'woocommerce_placeholder_image', $placeholder );
	echo "Set woocommerce_placeholder_image to ID: {$placeholder}\n";
}

echo "\n=== Done ===\n";
