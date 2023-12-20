<?php
/**
 * Class file for site health info.
 *
 * @since 1.7.1 TODO: Update to the correct version.
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
	 * @since 1.7.1 TODO: Update to the correct version.
	 * @param array $information The debug information.
	 * @return array
	 */
	public function get_data( $information ) {
		return array_merge( $information, $this->get_edac_data() );
	}

    /**
	 * Gets all of the data for the Site Health.
	 *
	 * @since 1.7.1 TODO: Update to the correct version.
	 * @return array
	 */
	private function get_edac_data() {
		$collectors = array(
			'edac_general'   => new General(),
			//'edac_tables'    => new Tables(),
		);

		$information = array();
		foreach ( $collectors as $key => $class ) {
			$information[ $key ] = $class->get();
		}

		// Allow extensions to add their own debug information that's specific to EDAC.
		return apply_filters( 'edac_debug_information', $information );
	}
}
