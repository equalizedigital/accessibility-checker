<?php
/**
 * Class file for UI ajax requests
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class that handles UI-related ajax requests.
 */
class UI_Ajax {

	/**
	 * Constructor function for the class.
	 */
	public function __construct() {
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax', [ $this, 'dismiss_welcome_cta' ] );
		add_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax', [ $this, 'dismiss_dashboard_cta' ] );
	}

	/**
	 * Handle AJAX request to dismiss Welcome CTA
	 *
	 * @return void
	 */
	public function dismiss_welcome_cta() {

		update_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true );

		wp_send_json( 'success' );
	}

	/**
	 * Handle AJAX request to dismiss dashboard CTA
	 *
	 * @return void
	 */
	public function dismiss_dashboard_cta() {

		update_user_meta( get_current_user_id(), 'edac_dashboard_cta_dismissed', true );

		wp_send_json( 'success' );
	}
}
