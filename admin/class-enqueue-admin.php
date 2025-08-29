<?php
/**
 * Class file for Admin enqueueing styles and scripts.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\OptIn\Email_Opt_In;

/**
 * Class that initializes and handles enqueueing styles and scripts for the admin.
 */
class Enqueue_Admin {


	/**
	 * Constructor
	 */
	public function __construct() {
	}


	/**
	 * Enqueue the scripts and styles.
	 */
	public static function enqueue() {
		self::enqueue_styles();
		self::maybe_enqueue_admin_and_editor_app_scripts();
		self::maybe_enqueue_email_opt_in_script();
	}

	/**
	 * Enqueue the admin styles.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		wp_enqueue_style( 'edac', plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/css/admin.css', [], EDAC_VERSION, 'all' );
	}

	/**
	 * Enqueue the admin and editorApp scripts.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_admin_and_editor_app_scripts() {

		global $pagenow;
		$post_types        = Settings::get_scannable_post_types();
		$current_post_type = get_post_type();
		$page              = self::get_current_page_slug();
		$enabled_pages     = apply_filters(
			'edac_filter_admin_scripts_slugs',
			[
				'accessibility_checker',
				'accessibility_checker_settings',
				'accessibility_checker_issues',
				'accessibility_checker_ignored',
			]
		);

		if (
			(
				is_array( $post_types ) &&
				count( $post_types ) &&
				(
					in_array( $current_post_type, $post_types, true ) ||
					in_array( $page, $enabled_pages, true )
				)
			) ||
			'site-editor.php' !== $pagenow
		) {

			global $post;
			$post_id = is_object( $post ) ? $post->ID : null;
			wp_enqueue_script( 'edac', plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/admin.bundle.js', [ 'jquery' ], EDAC_VERSION, false );
			wp_set_script_translations( 'edac', 'accessibility-checker', plugin_dir_path( EDAC_PLUGIN_FILE ) . 'languages' );

			wp_localize_script(
				'edac',
				'edac_script_vars',
				[
					'postID'     => $post_id,
					'nonce'      => wp_create_nonce( 'ajax-nonce' ),
					'edacApiUrl' => esc_url_raw( rest_url() . 'accessibility-checker/v1' ),
					'restNonce'  => wp_create_nonce( 'wp_rest' ),
					'proUrl'     => esc_url_raw( edac_generate_link_type( [ 'utm-content' => '__name__' ] ) ),
				]
			);

			if ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) {

				// Is this posttype setup to be checked?
				$post_types        = Settings::get_scannable_post_types();
				$current_post_type = get_post_type();
				$active            = ( is_array( $post_types ) && in_array( $current_post_type, $post_types, true ) );

				$pro = defined( 'EDACP_VERSION' ) && EDAC_KEY_VALID;

				if ( EDAC_DEBUG || strpos( EDAC_VERSION, '-beta' ) !== false ) {
					$debug = true; // @codeCoverageIgnore
				} else {
					$debug = false;
				}

				wp_enqueue_script( 'edac-editor-app', plugin_dir_url( EDAC_PLUGIN_FILE ) . 'build/editorApp.bundle.js', false, EDAC_VERSION, false );
				wp_set_script_translations( 'edac-editor-app', 'accessibility-checker', plugin_dir_path( EDAC_PLUGIN_FILE ) . 'languages' );

				// If this is the frontpage or homepage, preview URLs won't work. Use the live URL.
				if ( (int) get_option( 'page_on_front' ) === $post_id || (int) get_option( 'page_for_posts' ) === $post_id ) {
					$scan_url = add_query_arg( 'edac_pageScanner', 1, get_permalink( $post_id ) );
				} else {
					$post_view_link = apply_filters(
						'edac_get_origin_url_for_virtual_page',
						get_preview_post_link( $post_id ),
						$post_id
					);

					$scan_url = add_query_arg(
						[
							'edac_pageScanner' => 1,
						],
						$post_view_link
					);
				}

				wp_localize_script(
					'edac-editor-app',
					'edac_editor_app',
					[
						'postID'       => $post_id,
						'edacUrl'      => esc_url_raw( get_site_url() ),
						'edacApiUrl'   => esc_url_raw( rest_url() . 'accessibility-checker/v1' ),
						'baseurl'      => plugin_dir_url( __DIR__ ),
						'active'       => $active,
						'pro'          => $pro,
						'debug'        => $debug,
						'scanUrl'      => $scan_url,
						'maxAltLength' => max( 1, absint( apply_filters( 'edac_max_alt_length', 300 ) ) ),
						'version'      => EDAC_VERSION,
						'restNonce'    => wp_create_nonce( 'wp_rest' ),
					]
				);

			}
		}
	}

	/**
	 * Enqueue the email opt-in script on the welcome page.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_email_opt_in_script() {

		$page = self::get_current_page_slug();
		if ( 'accessibility_checker' !== $page ) {
			return;
		}

		$user_already_opted_in = (bool) get_user_meta( get_current_user_id(), Email_Opt_In::EDAC_USER_OPTIN_META_KEY, true );
		if ( $user_already_opted_in ) {
			return;
		}

		$email_opt_in = new Email_Opt_In();
		$email_opt_in->enqueue_scripts();
	}

	/**
	 * Gets the current admin page slug.
	 *
	 * @since 1.31.0
	 * @return string|null The current page slug or null if not set.
	 */
	private static function get_current_page_slug(): ?string {
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	}
}
