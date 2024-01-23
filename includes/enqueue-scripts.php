<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Enqueue Admin Styles
 */
function edac_admin_enqueue_styles() {
	wp_enqueue_style( 'edac', plugin_dir_url( __DIR__ ) . 'build/css/admin.css', array(), EDAC_VERSION, 'all' );
}

/**
 * Enqueue Admin Scripts
 */
function edac_admin_enqueue_scripts() {

	global $pagenow;
	$post_types        = get_option( 'edac_post_types' );
	$current_post_type = get_post_type();
	$page              = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$pages             = array(
		'accessibility_checker',
		'accessibility_checker_settings',
		'accessibility_checker_issues',
		'accessibility_checker_ignored',
	);

	if ( is_array( $post_types ) && count( $post_types ) && ( in_array( $current_post_type, $post_types, true ) || in_array( $page, $pages, true ) ) || ( 'site-editor.php' !== $pagenow ) ) {

		global $post;
		$post_id = is_object( $post ) ? $post->ID : null;
		wp_enqueue_script( 'edac', plugin_dir_url( __DIR__ ) . 'build/admin.bundle.js', array( 'jquery' ), EDAC_VERSION, false );

		wp_localize_script(
			'edac',
			'edac_script_vars',
			array(
				'postID'     => $post_id,
				'nonce'      => wp_create_nonce( 'ajax-nonce' ),
				'edacApiUrl' => esc_url_raw( rest_url() . 'accessibility-checker/v1' ),
				'restNonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		if ( 'post.php' === $pagenow ) {
		

			// Is this posttype setup to be checked?
			$post_types        = get_option( 'edac_post_types' );
			$current_post_type = get_post_type();
			if ( is_array( $post_types ) && in_array( $current_post_type, $post_types, true ) ) {
				$active = true;
			} else {
				$active = false;
			}

	
			$pro = edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID;
	
			if ( WP_DEBUG || strpos( EDAC_VERSION, '-beta' ) !== false ) {
				$debug = true;
			} else {
				$debug = false;
			}
		
			// Force debug off.
			$debug = false;

			wp_enqueue_script( 'edac-editor-app', plugin_dir_url( __DIR__ ) . 'build/editorApp.bundle.js', false, EDAC_VERSION, false );
	
			wp_localize_script(
				'edac-editor-app',
				'edac_editor_app',
				array(
					'postID'     => $post_id,
					'edacUrl'    => esc_url_raw( get_site_url() ),
					'edacApiUrl' => esc_url_raw( rest_url() . 'accessibility-checker/v1' ),
					'baseurl'    => plugin_dir_url( __DIR__ ),
					'active'     => $active,
					'pro'        => $pro,
					'authOk'     => false === (bool) get_option( 'edac_password_protected', false ),
					'debug'      => $debug,
					'scanUrl'    => get_preview_post_link(
						$post_id, 
						array( 'edac_pageScanner' => 1 )
					),
				)
			);
	
	



		}   
	}
}


/**
 * Enqueue scripts
 *
 * @return void
 */
function edac_enqueue_scripts() {

	
	// Handle loading the frontend-highlighter-app.

	// Don't load on admin pages in iframe that is running a pageScan.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( is_admin() || isset( $_GET['edac_pageScanner'] ) && '1' === $_GET['edac_pageScanner'] ) {
		return;
	}


	// Don't load on customizer pages or if the user is not able to edit this page.
	global $post;
	$post_id = is_object( $post ) ? $post->ID : null;

	if ( null === $post_id ) {
		return;
	}

	if ( is_customize_preview() || ! ( $post_id && current_user_can( 'edit_post', $post_id ) ) ) {
		return;
	}
	

	// Don't load if this pagetype is not setup to be scanned.
	$post_types        = get_option( 'edac_post_types' );
	$current_post_type = get_post_type();
	if ( is_array( $post_types ) && in_array( $current_post_type, $post_types, true ) ) {
		$active = true;
	} else {
		$active = false;
	}


	if ( $active ) {

	
		wp_enqueue_style( 'edac-frontend-highlighter-app', plugin_dir_url( __DIR__ ) . 'build/css/frontendHighlighterApp.css', false, EDAC_VERSION, 'all' );
		wp_enqueue_script( 'edac-frontend-highlighter-app', plugin_dir_url( __DIR__ ) . 'build/frontendHighlighterApp.bundle.js', false, EDAC_VERSION, false );

		wp_localize_script(
			'edac-frontend-highlighter-app',
			'edac_frontend_highlighter_app',
			array(
				'postID'    => $post_id,
				'nonce'     => wp_create_nonce( 'ajax-nonce' ),
				'edacUrl'   => esc_url_raw( get_site_url() ),
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'loggedIn'  => is_user_logged_in(),
				'appCssUrl' => EDAC_PLUGIN_URL . 'build/css/frontendHighlighterApp.css?ver=' . EDAC_VERSION,
			)
		);

	}
}
