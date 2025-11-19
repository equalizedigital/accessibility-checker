<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * A class that handles lazyload filter.
 * This class allows for disabling lazyload when highlighting elements.
 * 
 * @since 1.8.0
 */
class Lazyload_Filter {

	/**
	 * Initialize WordPress hooks.
	 */
	public function init_hooks() {
		add_filter( 'perfmatters_lazyload', [ $this, 'perfmatters' ] );
	}

	/**
	 * Add a filter for lazyloading images using the perfmatters_lazyload hook.
	 *
	 * @param bool $lazyload Whether to lazyload images.
	 * @return bool Whether to lazyload images.
	 */
	public function perfmatters( $lazyload ) {
		if (
			! isset( $_GET['edac_nonce'] ) 
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['edac_nonce'] ) ), 'edac_highlight' )
		) {
			return $lazyload;
		}
		if ( isset( $_GET['edac'] ) ) {
			return false;
		}
		return $lazyload;
	}
}
