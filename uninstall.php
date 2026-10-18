<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// check if the delte data option is checked. If not, don't delete data.
$edac_delete_data = get_option( 'edac_delete_data' );
if ( true === (bool) $edac_delete_data ) {

	// drop database.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Using direct query for table drop in uninstall script, caching not required for one time operation.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'accessibility_checker' ) );

	// delete options.
	$edac_options     = [
		'edac_db_version',
		'edac_activation_date',
		'edac_simplified_summary_position',
		'edac_post_types',
		'edac_add_footer_accessibility_statement',
		'edac_accessibility_policy_page',
		'edac_include_accessibility_statement_link',
		'edac_frontend_highlighter_position',
		'edac_delete_data',
		'edac_review_notice',
		'edac_authorization_password', // legacy option, remove in 1.29.0.
		'edac_authorization_username', // legacy option, remove in 1.29.0.
		'edac_gaad_notice_dismiss',
		'edac_gaad_notice_dismiss_2026',
		'edac_black_friday_2023_notice_dismiss',
		'edac_black_friday_2024_notice_dismiss',
		'edac_black_friday_2025_notice_dismiss',
	];
	$edac_fix_options = [
		'edac_add_label_to_unlabeled_form_fields',
		'edac_add_label_to_unlabelled_form_fields',
		'edac_fix_add_file_size_and_type_to_linked_files',
		'edac_fix_add_label_to_unlabelled_form_fields',
		'edac_fix_add_lang_and_dir',
		'edac_fix_add_missing_or_empty_page_title',
		'edac_fix_add_read_more_title',
		'edac_fix_add_read_more_title_screen_reader_only',
		'edac_fix_add_skip_link',
		'edac_fix_add_skip_link_always_visible',
		'edac_fix_add_skip_link_nav_target_id',
		'edac_fix_add_skip_link_target',
		'edac_fix_add_skip_link_target_id',
		'edac_fix_block_pdf_uploads',
		'edac_fix_comment_label',
		'edac_fix_disable_skip_link_styles',
		'edac_fix_focus_outline',
		'edac_fix_focus_outline_color',
		'edac_fix_force_link_underline',
		'edac_fix_meta_viewport_scalable',
		'edac_fix_prevent_links_opening_in_new_windows',
		'edac_fix_prevent_links_opening_new_windows',
		'edac_fix_remove_tabindex',
		'edac_fix_remove_title_if_preferred_accessible_name',
		'edac_fix_search_label',
		'edac_fix_new_window_warning',
	];

	// Capability system options. The free plugin owns the capability system for
	// the whole Accessibility Checker family, so its uninstall is the single
	// place the assignments and bookkeeping are torn down. Add-ons never remove
	// capabilities on their own deactivation or uninstall.
	$edac_capability_options = [
		'edac_capability_role_map',
		'edac_capability_defaults_seeded',
		'edac_synced_capabilities_edac_capability_role_map',
		'edac_capability_migration_version_edac_capability_role_map',
		// Legacy "Ignore Permissions" roles option. Historically only Pro's
		// uninstall deleted it, so a free-only site uninstalling with Delete Data
		// on would leave it (and the capability grants it seeded) behind. Delete it
		// here too - whichever plugin uninstalls last actually clears it (PRO-1286).
		'edacp_ignore_user_roles',
		// Legacy options from the removed per-user grant subsystem. Never shipped
		// in a release, but delete them defensively so a dev/staging site that ran
		// an intermediate build does not leave orphaned rows behind.
		'edac_capability_user_grants',
		'edac_synced_user_grants_edac_capability_role_map',
	];

	// Build the exact set of capabilities the permission system manages from its
	// own bookkeeping BEFORE those options are deleted. Using this rather than a
	// broad "edac_*" match means orphaned capabilities from an add-on uninstalled
	// earlier are still cleaned up, while unrelated edac_* capabilities that are
	// not part of this system (e.g. edac_upload_pdf) are left untouched.
	$edac_managed_caps = [];
	$edac_role_map     = get_option( 'edac_capability_role_map', [] );
	if ( is_array( $edac_role_map ) ) {
		$edac_managed_caps = array_merge( $edac_managed_caps, array_keys( $edac_role_map ) );
	}
	$edac_seeded = get_option( 'edac_capability_defaults_seeded', [] );
	if ( is_array( $edac_seeded ) ) {
		$edac_managed_caps = array_merge( $edac_managed_caps, $edac_seeded );
	}
	$edac_managed_caps = array_values( array_unique( array_filter( array_map( 'strval', $edac_managed_caps ) ) ) );

	$edac_options_to_clear = array_merge( $edac_options, $edac_fix_options, $edac_capability_options );

	foreach ( $edac_options_to_clear as $edac_option ) {
		delete_option( $edac_option );
		delete_site_option( $edac_option );
	}

	// Remove the managed capabilities from every role.
	if ( $edac_managed_caps ) {
		$edac_wp_roles = wp_roles();
		if ( $edac_wp_roles instanceof WP_Roles ) {
			foreach ( $edac_wp_roles->role_objects as $edac_role ) {
				foreach ( $edac_managed_caps as $edac_capability ) {
					$edac_role->remove_cap( $edac_capability );
				}
			}
		}
	}
}
