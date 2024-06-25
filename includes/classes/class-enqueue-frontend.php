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


		// Don't load on admin pages or in an iframe that is running a pageScan.
		if (
			is_admin() ||
			(
				isset( $_GET['edac_pageScanner'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'1' === $_GET['edac_pageScanner'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		) {
			return;
		}

		// Don't load on the frontend if we don't have a post to work with.
		global $post;
		$post_id = is_object( $post ) ? $post->ID : null;

		if ( null === $post_id ) {
			return;
		}

		// Don't load in a customizer preview or user can't edit the page. A filter
		// can override the edit requirement to allow anyone to see it.
		if (
			is_customize_preview() ||
			(
				/**
				 * Filter the visibility of the frontend highlighter.
				 *
				 * 'edac_filter_frontend_highlighter_visibility' is a filter that can be used
				 * to allow users without edit permissions on the post to see the frontend
				 * highlighter. You can use the filter to perform additional permission checks
				 * on who can see it.
				 *
				 * @since 1.14.0
				 *
				 * @param bool $visibility The visibility of the frontend highlighter. Default is false, return true to show the frontend highlighter.
				 */
				! apply_filters( 'edac_filter_frontend_highlighter_visibility', false ) &&
				! ( $post_id && current_user_can( 'edit_post', $post_id ) )
			)
		) {
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
					'postID'         => $post_id,
					'nonce'          => wp_create_nonce( 'ajax-nonce' ),
					'edacUrl'        => esc_url_raw( get_site_url() ),
					'ajaxurl'        => admin_url( 'admin-ajax.php' ),
					'loggedIn'       => is_user_logged_in(),
					'appCssUrl'      => EDAC_PLUGIN_URL . 'build/css/frontendHighlighterApp.css?ver=' . EDAC_VERSION,
					'widgetPosition' => get_option( 'edac_frontend_highlighter_position', 'right' ),
				]
			);

		}
	}
}
