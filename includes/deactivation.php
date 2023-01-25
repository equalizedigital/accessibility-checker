<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

function edac_deactivation() {
	delete_option('edac_activation_date');
}
