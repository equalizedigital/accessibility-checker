<?php
/**
 * Gets the audit history information.
 *
 * @since 1.9.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

/**
 * Loads audit history information into Site Health
 *
 * @since 1.9.0
 */
class Audit_History {

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
			'label'  => __( 'Accessibility Checker &mdash; Audit History', 'accessibility-checker' ),
			'fields' => [
				'version'                => [
					'label' => 'Version',
					'value' => EDACAH_VERSION,
				],
				'ignores_db_table_count' => [
					'label' => 'DB Table Count',
					'value' => edac_database_table_count( 'accessibility_checker_audit_history' ),
				],
			],
		];
	}
}
