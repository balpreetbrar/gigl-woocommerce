<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package GIGLDelivery
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * -------------------------------------------------------
 * Delete Custom Post Type Data (gigl)
 * -------------------------------------------------------
 */

// Get all GIGL post IDs (memory efficient).
$gigl_post_ids = get_posts(
	array(
		'post_type'      => 'gigl',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

// Permanently delete each post.
if ( ! empty( $gigl_post_ids ) ) {
	foreach ( $gigl_post_ids as $gigl_post_id ) {
		wp_delete_post( $gigl_post_id, true );
	}
}

/**
 * -------------------------------------------------------
 * Delete Plugin Options
 * -------------------------------------------------------
 */

// Delete single site options.
delete_option( 'gigl_settings' );
delete_option( 'gigl_version' );
delete_option( 'gigl_api_mode' );
delete_option( 'gigl_sender_details' );

// Delete site-wide options (for multisite compatibility).
delete_site_option( 'gigl_settings' );
delete_site_option( 'gigl_version' );
delete_site_option( 'gigl_api_mode' );
delete_site_option( 'gigl_sender_details' );

/**
 * -------------------------------------------------------
 * Clear Scheduled Cron Jobs (If Any)
 * -------------------------------------------------------
 */

$gigl_timestamp = wp_next_scheduled( 'gigl_cron_event' );

if ( $gigl_timestamp ) {
	wp_unschedule_event( $gigl_timestamp, 'gigl_cron_event' );
}

/**
 * -------------------------------------------------------
 * Flush Rewrite Rules (If CPT was registered)
 * -------------------------------------------------------
 */

flush_rewrite_rules();
