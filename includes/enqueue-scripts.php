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
	wp_enqueue_style( 'edac', plugin_dir_url( __DIR__ ) . 'build/admin/css/accessibility-checker-admin.css', array(), EDAC_VERSION, 'all' );
}

/**
 * Enqueue Admin Scripts
 */
function edac_admin_enqueue_scripts() {

	global $pagenow;
	$post_types        = get_option( 'edac_post_types' );
	$current_post_type = get_post_type();
	$page              = isset( $_GET['page'] ) ? $_GET['page'] : null;
	$pages             = array(
		'accessibility_checker',
		'accessibility_checker_settings',
		'accessibility_checker_issues',
		'accessibility_checker_ignored',
	);

	if ( is_array( $post_types ) && count( $post_types ) && ( in_array( $current_post_type, $post_types, true ) || in_array( $page, $pages, true ) ) || ( 'site-editor.php' !== $pagenow ) ) {

		global $post;
		$post_id = is_object( $post ) ? $post->ID : null;
		wp_enqueue_script( 'edac', plugin_dir_url( __DIR__ ) . 'build/admin/admin.bundle.js', array( 'jquery' ), EDAC_VERSION, false );

		wp_localize_script(
			'edac',
			'edac_script_vars',
			array(
				'postID' => $post_id,
				'nonce'  => wp_create_nonce( 'ajax-nonce' ),
			)
		);

		if ( 'post.php' === $pagenow ) {
			// Load the app in scan mode when editing a post/page
			edac_enqueue_scripts( 'editor-scan' );
		}   

	
		if (
			'accessibility-checker_page_accessibility_checker_settings' === get_current_screen()->id 
			&& isset( $_GET['tab'] ) && 'scan' === $_GET['tab']
		) {
			// Load the app in scan mode on the scan tab in the settings.
			edac_enqueue_scripts( 'full-scan' );

		
		}   
	}

}

/**
 * Enqueue Styles
 */
function edac_enqueue_styles() {
	wp_enqueue_style( 'edac-app', plugin_dir_url( __DIR__ ) . 'build/app/css/main.css', false, EDAC_VERSION, 'all' );
}

/**
 * Enqueue scripts
 *
 * @param string $mode 
 * @return void
 */
function edac_enqueue_scripts( $mode = '' ) {
	
	global $post;
	$post_id = is_object( $post ) ? $post->ID : null;
	$pro = edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID === true;
	$has_pending_scans = false;
	
	$headers = array(
		'Content-Type' => 'application/json',
		'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ),
	);

	if ( '' === $mode ) {
		if ( ( current_user_can( 'edit_post', $post_id ) && ! is_customize_preview() )
		) {  
			$mode = 'ui';
		} else {
			return;
		}
	}
	
	if ( ( 'full-scan' === $mode && $pro )
			||
			( 'ui' === $mode || 'editor-scan' === $mode  
			 &&
			$post_id && current_user_can( 'edit_post', $post_id ) )
	) {

		wp_enqueue_script( 'edac-app', plugin_dir_url( __DIR__ ) . 'build/app/main.bundle.js', false, EDAC_VERSION, false );

		$active = null;
	
		if ( 'ui' === $mode ) {
			// We are on ui/preview page. Set $active true to have the scanner show.
			$post_types        = get_option( 'edac_post_types' );
			$current_post_type = get_post_type();
			if ( is_array( $post_types ) && in_array( $current_post_type, $post_types, true ) ) {
				$active = true;
			} else {
				$active = false;
			}
		}


	
	
		if ( $pro ) {

			if ( 'full-scan' === $mode ) {
				$has_pending_scans = true;
				
			} else {
				$scans = new \EDACP\Scans();
				$all_pendings = array_merge(
					$scans->get_never_scanned(),
					$scans->get_pending()
				);
		
				if ( count( $all_pendings ) ) {
					$has_pending_scans = true;
				}           
			}       


			$username = get_option( 'edacp_authorization_username' );
			$password = get_option( 'edacp_authorization_password' );   
			if ( $username && $password ) {
				$headers['Authorization'] = 'Basic ' . base64_encode( "$username:$password" );
			}       
		} else {
			
			$server_headers = getallheaders();
			if ( isset( $server_headers['Authorization'] ) ) {
				$headers['Authorization'] = 'None';
			}       
		}
	
	
		wp_localize_script(
			'edac-app',
			'edac_script_vars',
			array(
				'postID'   => $post_id,
				'nonce'    => wp_create_nonce( 'ajax-nonce' ),
				'edacUrl'   => esc_url_raw( get_site_url() ),
				'edacApiUrl'   => esc_url_raw( rest_url() . 'accessibility-checker/v1' ),
				'edacHeaders' => $headers,
				'edacpApiUrl'  => $pro ? esc_url_raw( rest_url() . 'accessibility-checker-pro/v1' ) : '',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'loggedIn' => is_user_logged_in(),
				'active'   => $active,
				'mode'     => $mode,
				'pendingFullScan' => $has_pending_scans,
				'scanUrl' => get_preview_post_link(
					$post_id, 
					array(
						'edac-action' => 'js-scan',
						'edac-preview-nonce' => wp_create_nonce( 'edac-preview_nonce' ),
					)
				),
			)
		);

	}       

}
