<?php
/**
 * Accessibility Checker plugin file.
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
