<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// check if the delte data option is checked. If not, don't delete data.
$delete_data = get_option( 'edac_delete_data' );
if ( true === (bool) $delete_data ) {

	// drop database.
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Using direct query for table drop in uninstall script, caching not required for one time operation.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'accessibility_checker' ) );

	// delete options.
	$options     = [
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
		'edac_authorization_password',
		'edac_authorization_username',
		'edac_gaad_notice_dismiss',
		'edac_black_friday_2023_notice_dismiss',
	];
	$fix_options = [
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
		'edac_fix_meta-viewport-scalable',
		'edac_fix_prevent_links_opening_in_new_windows',
		'edac_fix_prevent_links_opening_new_windows',
		'edac_fix_prevent-links-opening-new-windows',
		'edac_fix_remove_tabindex',
		'edac_fix_remove_title_if_preferred_accessible_name',
		'edac_fix_search_label',
	];

	$options_to_clear = array_merge( $options, $fix_options );

	foreach ( $options_to_clear as $option ) {
		delete_option( $option );
		delete_site_option( $option );
	}
}
