<?php
/**
 * Class file for site health info.
 *
 * @since 1.9.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

/**
 * Class that handles site health info.
 */
class Information {

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
	}
	
	/**
	 * Initialize class hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_filter( 'debug_information', array( $this, 'get_data' ) );
	}

	/**
	 * Gets the array of sections for the Site Health.
	 *
	 * @since 1.9.0
	 * @param array $information The debug information.
	 * @return array
	 */
	public function get_data( $information ) {
		return array_merge( $information, $this->get_edac_data() );
	}

	/**
	 * Gets all of the data for the Site Health.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	private function get_edac_data() {
		$collectors = array(
			'edac_free' => new Free(),
		);

		if ( defined( 'EDACP_VERSION' ) ) {
			$collectors['edac_pro'] = new Pro();
		}

		if ( defined( 'EDACAH_VERSION' ) ) {
			$collectors['edac_audit_history'] = new Audit_History();
		}

		$information = array();
		foreach ( $collectors as $key => $class ) {
			$information[ $key ] = $class->get();
		}

		// Allow extensions to add their own debug information that's specific to EDAC.
		return apply_filters( 'edac_debug_information', $information );
	}
}
