<?php
/**
 * Gets the free information.
 *
 * @since 1.9.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

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
		return [
			'label'  => __( 'Accessibility Checker &mdash; Free', 'accessibility-checker' ),
			'fields' => [
				'version'                 => [
					'label' => 'Version',
					'value' => esc_html( EDAC_VERSION ),
				],
				'database_version'        => [
					'label' => 'Database Version',
					'value' => esc_html( EDAC_DB_VERSION ),
				],
				'policy_page'             => [
					'label' => 'Policy Page',
					'value' => esc_html( get_option( 'edac_accessibility_policy_page' ) ? get_option( 'edac_accessibility_policy_page' ) : "Unset\n" ),
				],
				'activation_date'         => [
					'label' => 'Activation Date',
					'value' => esc_html( get_option( 'edac_activation_date' ) ),
				],
				'footer_statement'        => [
					'label' => 'Footer Statement',
					'value' => esc_html( get_option( 'edac_add_footer_accessibility_statement' ) ? 'Enabled' : 'Disabled' ),
				],
				'delete_data'             => [
					'label' => 'Delete Data',
					'value' => esc_html( get_option( 'edac_delete_data' ) ? 'Enabled' : 'Disabled' ),
				],
				'include_statement_link'  => [
					'label' => 'Include Statement Link',
					'value' => esc_url( get_option( 'edac_include_accessibility_statement_link' ) ? 'Enabled' : 'Disabled' ),
				],
				'post_types'              => [
					'label' => 'Post Types',
					'value' => esc_html( get_option( 'edac_post_types' ) ? implode( ', ', get_option( 'edac_post_types' ) ) : 'Unset' ),
				],
				'simplified_sum_position' => [
					'label' => 'Simplified Sum Position',
					'value' => esc_html( get_option( 'edac_simplified_summary_position' ) ),
				],
				'simplified_sum_prompt'   => [
					'label' => 'Simplified Sum Prompt',
					'value' => esc_html( get_option( 'edac_simplified_summary_prompt' ) ),
				],
				'post_count'              => [
					'label' => 'Post Count',
					'value' => esc_html( edac_get_posts_count() ),
				],
				'error_count'             => [
					'label' => 'Error Count',
					'value' => absint( edac_get_error_count() ),
				],
				'warning_count'           => [
					'label' => 'Warning Count',
					'value' => absint( edac_get_warning_count() ),
				],
				'db_table_count'          => [
					'label' => 'DB Table Count',
					'value' => absint( edac_database_table_count( 'accessibility_checker' ) ),
				],
			],
		];
	}
}
