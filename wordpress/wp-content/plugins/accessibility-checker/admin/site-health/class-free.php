<?php
/**
 * Gets the free information.
 *
 * @since 1.9.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Loads free information into Site Health
 *
 * @since 1.9.0
 */
class Free {

	/**
	 * General constructor.
	 */
	public function __construct() {
	}

	/**
	 * Gets the site health section.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	public function get() {
		// Get only the non-pro fixes.
		$fixes = array_filter(
			FixesManager::get_instance()->get_fixes_settings(),
			function ( $fix ) {
				return ! $fix['is_pro'];
			}
		);
		// remove the is_pro flag, this isn't needed in the output.
		foreach ( $fixes as $key => $fix ) {
			unset( $fixes[ $key ]['is_pro'] );
		}

		return [
			'label'  => __( 'Accessibility Checker &mdash; Free', 'accessibility-checker' ),
			'fields' => [
				'version'                 => [
					'label' => __( 'Version', 'accessibility-checker' ),
					'value' => esc_html( EDAC_VERSION ),
				],
				'database_version'        => [
					'label' => __( 'Database Version', 'accessibility-checker' ),
					'value' => esc_html( EDAC_DB_VERSION ),
				],
				'policy_page'             => [
					'label' => __( 'Policy Page', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edac_accessibility_policy_page' ) ? get_option( 'edac_accessibility_policy_page' ) : __( 'Unset', 'accessibility-checker' ) ),
				],
				'activation_date'         => [
					'label' => __( 'Activation Date', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edac_activation_date' ) ),
				],
				'footer_statement'        => [
					'label' => __( 'Footer Statement', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edac_add_footer_accessibility_statement' ) ? __( 'Enabled', 'accessibility-checker' ) : __( 'Disabled', 'accessibility-checker' ) ),
				],
				'delete_data'             => [
					'label' => __( 'Delete Data', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edac_delete_data' ) ? __( 'Enabled', 'accessibility-checker' ) : __( 'Disabled', 'accessibility-checker' ) ),
				],
				'include_statement_link'  => [
					'label' => __( 'Include Statement Link', 'accessibility-checker' ),
					'value' => esc_url( get_option( 'edac_include_accessibility_statement_link' ) ? __( 'Enabled', 'accessibility-checker' ) : __( 'Disabled', 'accessibility-checker' ) ),
				],
				'post_types'              => [
					'label' => __( 'Post Types', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edac_post_types' ) ? implode( ', ', get_option( 'edac_post_types' ) ) : __( 'Unset', 'accessibility-checker' ) ),
				],
				'simplified_sum_position' => [
					'label' => __( 'Simplified Sum Position', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edac_simplified_summary_position' ) ),
				],
				'simplified_sum_prompt'   => [
					'label' => __( 'Simplified Sum Prompt', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edac_simplified_summary_prompt' ) ),
				],
				'post_count'              => [
					'label' => __( 'Post Count', 'accessibility-checker' ),
					'value' => esc_html( edac_get_posts_count() ),
				],
				'error_count'             => [
					'label' => __( 'Error Count', 'accessibility-checker' ),
					'value' => absint( edac_get_error_count() ),
				],
				'warning_count'           => [
					'label' => __( 'Warning Count', 'accessibility-checker' ),
					'value' => absint( edac_get_warning_count() ),
				],
				'db_table_count'          => [
					'label' => __( 'DB Table Count', 'accessibility-checker' ),
					'value' => absint( edac_database_table_count( 'accessibility_checker' ) ),
				],
				'fixes'                   => [
					'label' => __( 'Fixes', 'accessibility-checker' ),
					'value' => esc_html( wp_json_encode( $fixes ) ),
				],
			],
		];
	}
}
