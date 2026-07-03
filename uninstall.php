<?php
/**
 * Uninstall handler — removes every trace of the plugin.
 *
 * Deactivation keeps options; only uninstall deletes them.
 *
 * @package Trigv
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Cancel any queued dispatches.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( '', array(), 'trigv' );
}

// Delete all plugin options.
delete_option( 'trigv_settings' );
delete_option( 'trigv_trigger_settings' );
delete_option( 'trigv_log' );

// Multisite: clean each site.
if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		delete_option( 'trigv_settings' );
		delete_option( 'trigv_trigger_settings' );
		delete_option( 'trigv_log' );
		restore_current_blog();
	}
}
