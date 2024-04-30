<?php
/**
 * Class file for enqueueing frontend styles and scripts.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Class that initializes and handles enqueueing styles and scripts for the frontend.
 */
class Enqueue_Frontend {


	/**
	 * Constructor
	 */
	public function __construct() {
	}


	/**
	 * Enqueue the scripts and styles.
	 */
	public static function enqueue() {
		self::maybe_enqueue_frontend_highlighter();
	}

	/**
	 * Enqueue the frontend highlighter.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_frontend_highlighter() {

		// This loads on all pages, so bail as early as possible. Do checks that don't require DB calls first.


		// Don't load on admin pages in iframe that is running a pageScan.
		if (
			is_admin() ||
			(
				isset( $_GET['edac_pageScanner'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'1' === $_GET['edac_pageScanner'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		) {
			return;
		}

		// Don't load on customizer pages or if the user is not able to edit this page.
		global $post;
		$post_id = is_object( $post ) ? $post->ID : null;

		if ( null === $post_id ) {
			return;
		}

		// Dont load if the user is not able to edit this page or if we are in a customizer preview.
		if ( is_customize_preview() || ! ( $post_id && current_user_can( 'edit_post', $post_id ) ) ) {
			return;
		}


		// Don't load if this pagetype is not setup to be scanned.
		$post_types        = get_option( 'edac_post_types' );
		$current_post_type = get_post_type();
		$active            = ( is_array( $post_types ) && in_array( $current_post_type, $post_types, true ) );


		if ( $active ) {


			wp_enqueue_style( 'edac-frontend-highlighter-app', plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/css/frontendHighlighterApp.css', false, EDAC_VERSION, 'all' );
			wp_enqueue_script( 'edac-frontend-highlighter-app', plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/frontendHighlighterApp.bundle.js', false, EDAC_VERSION, false );

			wp_localize_script(
				'edac-frontend-highlighter-app',
				'edacFrontendHighlighterApp',
				[
					'postID'    => $post_id,
					'nonce'     => wp_create_nonce( 'ajax-nonce' ),
					'edacUrl'   => esc_url_raw( get_site_url() ),
					'ajaxurl'   => admin_url( 'admin-ajax.php' ),
					'loggedIn'  => is_user_logged_in(),
					'appCssUrl' => EDAC_PLUGIN_URL . 'build/css/frontendHighlighterApp.css?ver=' . EDAC_VERSION,
				]
			);

		}
	}
}
