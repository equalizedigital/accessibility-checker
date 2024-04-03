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
		return array(
			'label'  => __( 'Accessibility Checker &mdash; Pro', 'accessibility-checker' ),
			'fields' => array(
				'version'                => array(
					'label' => 'Version',
					'value' => defined( 'EDACP_VERSION' ) ? esc_html( EDACP_VERSION ) : 'Unset',
				),
				'database_version'       => array(
					'label' => 'Database Version',
					'value' => defined( 'EDACP_DB_VERSION' ) ? esc_html( EDACP_DB_VERSION ) : 'Unset',
				),
				'license_status'         => array(
					'label' => 'License Status',
					'value' => get_option( 'edacp_license_status' ),
				),
				'authorization_username' => array(
					'label' => 'Authorization Username',
					'value' => ( get_option( 'edacp_authorization_username' ) ? get_option( 'edacp_authorization_username' ) : 'Unset' ),
				),
				'authorization_password' => array(
					'label' => 'Authorization Password',
					'value' => ( get_option( 'edacp_authorization_password' ) ? get_option( 'edacp_authorization_password' ) : 'Unset' ),
				),
				'scan_id'                => array(
					'label' => 'Scan ID',
					'value' => get_transient( 'edacp_scan_id' ),
				),
				'scan_total'             => array(
					'label' => 'Scan Total',
					'value' => get_transient( 'edacp_scan_total' ),
				),
				'simplified_sum_heading' => array(
					'label' => 'Simplified Sum Heading',
					'value' => get_option( 'edacp_simplified_summary_heading' ),
				),
				'ignore_permissions'     => array(
					'label' => 'Ignore Permissions',
					'value' => ( get_option( 'edacp_ignore_user_roles' ) ? implode( ', ', get_option( 'edacp_ignore_user_roles' ) ) : 'None' ),
				),
				'ignores_db_table_count' => array(
					'label' => 'Ignores DB Table Count',
					'value' => edac_database_table_count( 'accessibility_checker_global_ignores' ),
				),
			),
		);
	}
}
