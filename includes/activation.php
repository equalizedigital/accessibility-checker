<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Accessibility_Statement;

/**
 * Activation
 *
 * @return void
 */
function edac_activation() {
	// set options.
	\EDAC\Admin\Options::set( 'activation_date', gmdate( 'Y-m-d H:i:s' ) );
	\EDAC\Admin\Options::set( 'post_types', array( 'post', 'page' ) );
	\EDAC\Admin\Options::set( 'simplified_summary_position', 'after' );

	// Sanitize the input.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is not required.
	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is not required.
	$checked = isset( $_POST['checked'] ) ? array_map( 'sanitize_text_field', $_POST['checked'] ) : array();

	// Redirect: Don't do redirects when multiple plugins are bulk activated.
	if ( 'activate-selected' === $action && count( $checked ) > 1 ) {
		return;
	}

	Accessibility_Statement::add_page();
}
