<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Deactivation
 *
 * @return void
 */
function edac_deactivation() {
	\EDAC\Admin\Options::delete( 'activation_date' );
}
