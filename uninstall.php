<?php
/**
 * Uninstall GEO AI Woo
 *
 * Fired when the plugin is uninstalled.
 *
 * @package GeoAiWoo
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'geo_ai_woo_settings' );

// Delete transients.
delete_transient( 'geo_ai_woo_llms' );
delete_transient( 'geo_ai_woo_llms_full' );

// Clear scheduled events.
wp_clear_scheduled_hook( 'geo_ai_woo_regenerate_llms' );

// Delete post meta for all posts.
global $wpdb;
$meta_keys    = array(
	'_geo_ai_woo_description',
	'_geo_ai_woo_keywords',
	'_geo_ai_woo_exclude',
	'_geo_ai_woo_auto_description',
);
$placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_keys
	)
);

// Flush rewrite rules.
flush_rewrite_rules();
