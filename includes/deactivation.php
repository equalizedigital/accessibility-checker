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
	delete_option( 'edac_activation_date' );
}
