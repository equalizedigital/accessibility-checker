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
	update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s' ) );
	update_option( 'edac_post_types', [ 'post', 'page' ] );
	update_option( 'edac_simplified_summary_position', 'after' );

	// Sanitize the input.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is not required.
	$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is not required.
	$checked = isset( $_POST['checked'] ) ? array_map( 'sanitize_text_field', $_POST['checked'] ) : [];

	// Redirect: Don't do redirects when multiple plugins are bulk activated.
	if ( 'activate-selected' === $action && count( $checked ) > 1 ) {
		return;
	}

	Accessibility_Statement::add_page();
}
