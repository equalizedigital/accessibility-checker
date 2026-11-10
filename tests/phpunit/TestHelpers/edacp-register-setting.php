<?php
/**
 * Test double for the Pro setting registration callback.
 *
 * @package Accessibility_Checker
 */

if ( ! function_exists( 'edacp_register_setting' ) ) {
	/**
	 * Record that the Pro callback ran.
	 */
	function edacp_register_setting(): void {
		$GLOBALS['edac_test_pro_setting_registered'] = true;
	}
}
