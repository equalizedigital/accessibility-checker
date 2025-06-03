<?php
/**
 * Gets the pro information.
 *
 * @since 1.9.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

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

		// Get only the pro fixes.
		$fixes = array_filter(
			FixesManager::get_instance()->get_fixes_settings(),
			function ( $fix ) {
				return $fix['is_pro'];
			}
		);
		// remove the is_pro flag, this isn't needed in the output.
		foreach ( $fixes as $key => $fix ) {
			unset( $fixes[ $key ]['is_pro'] );
		}

		return [
			'label'  => __( 'Accessibility Checker &mdash; Pro', 'accessibility-checker' ),
			'fields' => [
				'version'                => [
					'label' => __( 'Version', 'accessibility-checker' ),
					'value' => defined( 'EDACP_VERSION' ) ? esc_html( EDACP_VERSION ) : __( 'Unset', 'accessibility-checker' ),
				],
				'database_version'       => [
					'label' => __( 'Database Version', 'accessibility-checker' ),
					'value' => defined( 'EDACP_DB_VERSION' ) ? esc_html( EDACP_DB_VERSION ) : __( 'Unset', 'accessibility-checker' ),
				],
				'license_status'         => [
					'label' => __( 'License Status', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edacp_license_status' ) ),
				],
				'authorization_username' => [
					'label' => __( 'Authorization Username', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edacp_authorization_username' ) ? get_option( 'edacp_authorization_username' ) : __( 'Unset', 'accessibility-checker' ) ),
				],
				'authorization_password' => [
					'label' => __( 'Authorization Password', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edacp_authorization_password' ) ? get_option( 'edacp_authorization_password' ) : __( 'Unset', 'accessibility-checker' ) ),
				],
				'scan_id'                => [
					'label' => __( 'Scan ID', 'accessibility-checker' ),
					'value' => esc_html( get_transient( 'edacp_scan_id' ) ),
				],
				'scan_total'             => [
					'label' => __( 'Scan Total', 'accessibility-checker' ),
					'value' => absint( get_transient( 'edacp_scan_total' ) ),
				],
				'simplified_sum_heading' => [
					'label' => __( 'Simplified Sum Heading', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edacp_simplified_summary_heading' ) ),
				],
				'ignore_permissions'     => [
					'label' => __( 'Ignore Permissions', 'accessibility-checker' ),
					'value' => esc_html( get_option( 'edacp_ignore_user_roles' ) ? implode( ', ', get_option( 'edacp_ignore_user_roles' ) ) : __( 'None', 'accessibility-checker' ) ),
				],
				'ignores_db_table_count' => [
					'label' => __( 'Ignores DB Table Count', 'accessibility-checker' ),
					'value' => absint( edac_database_table_count( 'accessibility_checker_global_ignores' ) ),
				],
				'fixes'                  => [
					'label' => __( 'Fixes', 'accessibility-checker' ),
					'value' => esc_html( wp_json_encode( $fixes ) ),
				],
			],
		];
	}
}
