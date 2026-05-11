<?php
/**
 * Remove Personal RAG data when the plugin is deleted.
 *
 * @package Personal_RAG
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'personal_rag_vectors',
	$wpdb->prefix . 'personal_rag_chunks',
	$wpdb->prefix . 'personal_rag_sources',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

delete_option( 'personal_rag_db_version' );
