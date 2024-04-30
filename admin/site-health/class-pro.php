<?php
/**
 * Gets the pro information.
 *
 * @since 1.9.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

/**
 * Loads pro information into Site Health
 *
 * @since 1.9.0
 */
class Pro {

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
			'label'  => __( 'Accessibility Checker &mdash; Pro', 'accessibility-checker' ),
			'fields' => [
				'version'                => [
					'label' => 'Version',
					'value' => defined( 'EDACP_VERSION' ) ? esc_html( EDACP_VERSION ) : 'Unset',
				],
				'database_version'       => [
					'label' => 'Database Version',
					'value' => defined( 'EDACP_DB_VERSION' ) ? esc_html( EDACP_DB_VERSION ) : 'Unset',
				],
				'license_status'         => [
					'label' => 'License Status',
					'value' => esc_html( get_option( 'edacp_license_status' ) ),
				],
				'authorization_username' => [
					'label' => 'Authorization Username',
					'value' => esc_html( get_option( 'edacp_authorization_username' ) ? get_option( 'edacp_authorization_username' ) : 'Unset' ),
				],
				'authorization_password' => [
					'label' => 'Authorization Password',
					'value' => esc_html( get_option( 'edacp_authorization_password' ) ? get_option( 'edacp_authorization_password' ) : 'Unset' ),
				],
				'scan_id'                => [
					'label' => 'Scan ID',
					'value' => esc_html( get_transient( 'edacp_scan_id' ) ),
				],
				'scan_total'             => [
					'label' => 'Scan Total',
					'value' => absint( get_transient( 'edacp_scan_total' ) ),
				],
				'simplified_sum_heading' => [
					'label' => 'Simplified Sum Heading',
					'value' => esc_html( get_option( 'edacp_simplified_summary_heading' ) ),
				],
				'ignore_permissions'     => [
					'label' => 'Ignore Permissions',
					'value' => esc_html( get_option( 'edacp_ignore_user_roles' ) ? implode( ', ', get_option( 'edacp_ignore_user_roles' ) ) : 'None' ),
				],
				'ignores_db_table_count' => [
					'label' => 'Ignores DB Table Count',
					'value' => absint( edac_database_table_count( 'accessibility_checker_global_ignores' ) ),
				],
			],
		];
	}
}
