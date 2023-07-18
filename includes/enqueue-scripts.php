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
	wp_enqueue_style( 'edac', plugin_dir_url( __DIR__ ) . 'assets/css/accessibility-checker-admin.css', array(), EDAC_VERSION, 'all' );
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

	if ( is_array( $post_types ) && count( $post_types ) && ( in_array( $current_post_type, $post_types, true ) || in_array( $page, $pages, true ) ) || ( $pagenow !== 'site-editor.php' ) ) {

		global $post;
		$post_id = is_object( $post ) ? $post->ID : null;
		wp_enqueue_script( 'edac', plugin_dir_url( __DIR__ ) . 'assets/js/accessibility-checker-admin-min.js', array( 'jquery' ), EDAC_VERSION, false );

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
			// Load the app in scan mode on the scan tab in the settings
			edac_enqueue_scripts( 'full-scan' );

		
		}   
	}

}

/**
 * Enqueue Styles
 */
function edac_enqueue_styles() {
	wp_enqueue_style( 'edac-app', plugin_dir_url( __DIR__ ) . 'build/accessibility-checker-app/css/main.css', false, EDAC_VERSION, 'all' );
}

/**
 * Enqueue Scripts
 */
function edac_enqueue_scripts( $mode = '' ) {
	
	global $post;
	$post_id = is_object( $post ) ? $post->ID : null;

	if ( '' === $mode ) {
		// Load with ui by default.
		$mode = 'ui';
	}
	
	if ( 'full-scan' === $mode ||
		( 
			( 'ui' === $mode || 'editor-scan' === $mode ) &&
			$post_id && current_user_can( 'edit_post', $post_id ) && 
			! is_customize_preview() 
		)
	) {

		wp_enqueue_script( 'edac-app', plugin_dir_url( __DIR__ ) . 'build/accessibility-checker-app/main.bundle.js', false, EDAC_VERSION, false );

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

		$pending_full_scan = false;
		$pro = edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' );
		if ( $pro ) {

			global $wpdb;
						
			// if a scheduled or fullscan has been completed after the last recorded js fullscan, we need to run a new js fullscan.
			$row = $wpdb->get_row( 
				$wpdb->prepare(
					"SELECT scheduled_date_gmt FROM {$wpdb->actionscheduler_actions} WHERE 
				(hook=%s or hook=%s) and status=%s and scheduled_date_gmt > %s
				ORDER BY  scheduled_date_gmt  DESC LIMIT 1",
					'edacp_spawn_schedules_hook', 
					'edacp_scan_content_hook',
					ActionScheduler_Store::STATUS_COMPLETE,
					get_option(
						'edacp_fullscan_start',
						time()
					)
				)
			);

			
			if ( ! IS_NULL( $row ) ) {
				$pending_full_scan = true;
			}       
		}
		
		wp_localize_script(
			'edac-app',
			'edac_script_vars',
			array(
				'postID'   => $post_id,
				'nonce'    => wp_create_nonce( 'ajax-nonce' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'edacApiUrl'   => esc_url_raw( rest_url() . 'accessibility-checker/v1' ),
				'edacpApiUrl'  => esc_url_raw( rest_url() . 'accessibility-checker-pro/v1' ),
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'loggedIn' => is_user_logged_in(),
				'active'   => $active,
				'mode'     => $mode,
				'pendingFullScan' => $pending_full_scan,
				'scanUrl' => get_preview_post_link(
					$post_id, 
					array(
						'edac-action' => 'js-scan',
					)
				),
			)
		);

	}       

}
