<?php

/**
 * Get system info
 *
 * @since       1.1.0
 * @global      object $wpdb Used to query the database using the WordPress Database API
 * @return      string $return A string containing the info to output
 */

/**
 * Display the system info tab
 *
 * @return void
 */
function edac_sysinfo_display(){
	?>
	<form action="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=system_info' ) ); ?>" method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="edac-sysinfo"><?php echo edac_tools_sysinfo_get(); ?></textarea>
		<p class="submit">
			<input type="hidden" name="edac-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'edac-download-sysinfo', false ); ?>
		</p>
	</form>
	<?php
}

function edac_tools_sysinfo_get() {
	global $wpdb;

	if( !class_exists( 'Browser' ) )
		require_once EDAC_PLUGIN_DIR . 'includes/classes/browser.php';

	$browser = new Browser();

	// Get theme info
	$theme_data   = wp_get_theme();
	$theme        = $theme_data->Name . ' ' . $theme_data->Version;
	$parent_theme = $theme_data->Template;
	if ( ! empty( $parent_theme ) ) {
		$parent_theme_data = wp_get_theme( $parent_theme );
		$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
	}

	// Try to identify the hosting provider
	$host = edac_get_host();

	$return  = '### Begin System Info (Generated ' . date( 'Y-m-d H:i:s' ) . ') ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	$return  = apply_filters( 'edac_sysinfo_after_site_info', $return );

	// Can we determine the site's host?
	if( $host ) {
		$return .= "\n" . '-- Hosting Provider' . "\n\n";
		$return .= 'Host:                     ' . $host . "\n";

		$return  = apply_filters( 'edac_sysinfo_after_host_info', $return );
	}

	// The local users' browser information, handled by the Browser class
	$return .= "\n" . '-- User Browser' . "\n\n";
	$return .= $browser;

	$return  = apply_filters( 'edac_sysinfo_after_user_browser', $return );

	$locale = get_locale();

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . ( !empty( $locale ) ? $locale : 'en_US' ) . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	if ( $parent_theme !== $theme ) {
		$return .= 'Parent Theme:             ' . $parent_theme . "\n";
	}
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if( get_option( 'show_on_front' ) == 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH:                  ' . ABSPATH . "\n";

	// Make sure wp_remote_post() is working
	$request['cmd'] = '_notify-validate';

	$params = array(
		'sslverify'     => false,
		'timeout'       => 60,
		'user-agent'    => 'EDAC/' . EDAC_VERSION,
		'body'          => $request
	);

	$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

	if( !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
		$WP_REMOTE_POST = 'wp_remote_post() works';
	} else {
		$WP_REMOTE_POST = 'wp_remote_post() does not work';
	}

	$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
	$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	//$return .= 'Admin AJAX:               ' . ( edac_test_ajax_works() ? 'Accessible' : 'Inaccessible' ) . "\n";
	$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Unset' ) . "\n";
	$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

	$return  = apply_filters( 'edac_sysinfo_after_wordpress_config', $return );

	// EDAC configuration
	$return .= "\n" . '-- Accessibility Checker Configuration' . "\n\n";
	$return .= 'Version:                  ' . EDAC_VERSION . "\n";
	$return .= 'Database Version:         ' . get_option('edac_db_version') . "\n";
	$return .= 'Policy Page:              ' . ( get_option('edac_accessibility_policy_page') ? get_option('edac_accessibility_policy_page')."\n" : "Unset\n" );
	$return .= 'Activation Date:          ' . get_option('edac_activation_date') . "\n";
	$return .= 'Footer Statement:         ' . ( get_option('edac_add_footer_accessibility_statement') ? "Enabled\n" : "Disabled\n" );
	$return .= 'Authorization Username:   ' . ( get_option('edac_authorization_username') ? get_option('edac_authorization_username')."\n" : "Unset\n" );
	$return .= 'Authorization Password:   ' . ( get_option('edac_authorization_password') ? get_option('edac_authorization_password')."\n" : "Unset\n" );
	$return .= 'Delete Data:              ' . ( get_option('edac_delete_data') ? "Enabled\n" : "Disabled\n" );
	$return .= 'Include Statement Link:   ' . ( get_option('edac_include_accessibility_statement_link') ? "Enabled\n" : "Disabled\n" );
	$return .= 'Post Types:               ' . implode(', ', get_option('edac_post_types'))."\n";
	$return .= 'Simplified Sum Position:  ' . get_option('edac_simplified_summary_position')."\n";
	$return .= 'Simplified Sum Prompt:    ' . get_option('edac_simplified_summary_prompt')."\n";
	$return .= 'Post Count:               ' . edac_get_posts_count()."\n";
	$return .= 'Error Count:              ' . edac_get_error_count()."\n";
	$return .= 'Warning Count:            ' . edac_get_warning_count()."\n";
	$return .= 'DB Table Count:           ' . edac_database_table_count('accessibility_checker')."\n";

	if(edac_check_plugin_active('accessibility-checker-pro/accessibility-checker-pro.php')){
		
		$return .= "\n" . '-- Accessibility Checker Pro Configuration' . "\n\n";
		$return .= 'Version:                  ' . EDACP_VERSION . "\n";
		$return .= 'Database Version:         ' . get_option('edacp_db_version') . "\n";
		$return .= 'License Status:           ' . get_option('edacp_license_status') . "\n";
		$return .= 'Scan ID:                  ' . get_transient( 'edacp_scan_id' ) . "\n";
		$return .= 'Scan Total:               ' . get_transient( 'edacp_scan_total' ) . "\n";
		$return .= 'Simplified Sum Heading:   ' . get_option('edacp_simplified_summary_heading')."\n";
		$return .= 'Background Scan Schedule: ' . get_option('edacp_background_scan_schedule') . "\n";
		$next_scan = as_next_scheduled_action( 'edacp_schedule_scan_hook' );
		if($next_scan){
			$return .= 'Next Background Scan: ' . date('F j, Y g:i a', $next_scan). "\n";
		}
		$return .= 'Ignore Permissions:       ' . ( get_option('edacp_ignore_user_roles') ? implode(', ', get_option('edacp_ignore_user_roles'))."\n" : "None\n" );
		$return .= 'Ignores DB Table Count:   ' . edac_database_table_count('accessibility_checker_global_ignores')."\n";
		$return .= 'Logs DB Table Count:      ' . edac_database_table_count('accessibility_checker_logs')."\n";
	}

	$return  = apply_filters( 'edac_sysinfo_after_edac_config', $return );

	// Templates
	$dir = get_stylesheet_directory() . '/edac_templates/*';
	if( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
		$return .= "\n" . '-- EDAC Template Overrides' . "\n\n";

		foreach( glob( $dir ) as $file ) {
			$return .= 'Filename:                 ' . basename( $file ) . "\n";
		}

		$return  = apply_filters( 'edac_sysinfo_after_edac_templates', $return );
	}

	// Get plugins that have an update
	$updates = get_plugin_updates();

	// Must-use plugins
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if( count( $muplugins ) > 0 ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach( $muplugins as $plugin => $plugin_data ) {
			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}

		$return = apply_filters( 'edac_sysinfo_after_wordpress_mu_plugins', $return );
	}

	// WordPress active plugins
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach( $plugins as $plugin_path => $plugin ) {
		if( !in_array( $plugin_path, $active_plugins ) )
			continue;

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return  = apply_filters( 'edac_sysinfo_after_wordpress_plugins', $return );

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach( $plugins as $plugin_path => $plugin ) {
		if( in_array( $plugin_path, $active_plugins ) )
			continue;

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return  = apply_filters( 'edac_sysinfo_after_wordpress_plugins_inactive', $return );

	if( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if( !array_key_exists( $plugin_base, $active_plugins ) )
				continue;

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$plugin  = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$return  = apply_filters( 'edac_sysinfo_after_wordpress_ms_plugins', $return );
	}

	// Server configuration
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	$return  = apply_filters( 'edac_sysinfo_after_webserver_config', $return );

	// PHP configs
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";
	$return .= 'PHP Arg Separator:        ' . edac_get_php_arg_separator_output() . "\n";

	$return  = apply_filters( 'edac_sysinfo_after_php_config', $return );

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return  = apply_filters( 'edac_sysinfo_after_php_ext', $return );

	// Session stuff
	$return .= "\n" . '-- Session Configuration' . "\n\n";
	//$return .= 'EDAC Use Sessions:         ' . ( defined( 'edac_USE_PHP_SESSIONS' ) && edac_USE_PHP_SESSIONS ? 'Enforced' : ( EDAC()->session->use_php_sessions() ? 'Enabled' : 'Disabled' ) ) . "\n";
	$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

	// The rest of this is only relevant is session is enabled
	if( isset( $_SESSION ) ) {
		$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
		$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
		$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
		$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
		$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
	}

	$return  = apply_filters( 'edac_sysinfo_after_session_config', $return );

	$return .= "\n" . '### End System Info ###';

	return $return;
}

/**
 * Get user host
 * Returns the webhost this site is using if possible
 *
 * @return mixed string $host if detected, false otherwise
 */
function edac_get_host() {
	$host = false;

	if( defined( 'WPE_APIKEY' ) ) {
		$host = 'WP Engine';
	} elseif( defined( 'PAGELYBIN' ) ) {
		$host = 'Pagely';
	} elseif( DB_HOST == 'localhost:/tmp/mysql5.sock' ) {
		$host = 'ICDSoft';
	} elseif( DB_HOST == 'mysqlv5' ) {
		$host = 'NetworkSolutions';
	} elseif( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
		$host = 'iPage';
	} elseif( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
		$host = 'IPower';
	} elseif( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
		$host = 'MediaTemple Grid';
	} elseif( strpos( DB_HOST, '.pair.com' ) !== false ) {
		$host = 'pair Networks';
	} elseif( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
		$host = 'Rackspace Cloud';
	} elseif( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
		$host = 'SysFix.eu Power Hosting';
	} elseif( isset( $_SERVER['SERVER_NAME'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ), 'Flywheel' ) !== false ) {
		$host = 'Flywheel';
	} else {

		// Adding a general fallback for data gathering.
		if ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$server_name = sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) );
		}

		$host = 'DBH: ' . DB_HOST . ', SRV: ' . $server_name;
	}

	return $host;
}

/**
 * Checks whether AJAX is disabled.
 *
 * @return bool True when AJAX is disabled, false otherwise.
 */
/* function edac_is_ajax_disabled() {
	return apply_filters( 'edac_is_ajax_disabled', false );
} */

/**
 * Get PHP Arg Separator Output
 *
 * @return string Arg separator output
 */
function edac_get_php_arg_separator_output() {
	return ini_get( 'arg_separator.output' );
}

/**
 * Generates a System Info download file
 *
 * @return void
 */
function edac_tools_sysinfo_download() {

	if( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="edac-system-info.txt"' );

	echo wp_strip_all_tags( $_POST['edac-sysinfo'] );

	die();
}

/**
 * Get Post Count by available custom post types
 *
 * @return mixed
 */
function edac_get_posts_count(){

	$output = [];

	$post_types = get_option('edac_post_types');
	if($post_types){
		foreach ($post_types as $post_type) {

			$counts = wp_count_posts($post_type);
			
			if($counts){
				foreach ($counts as $key => $value) {
					if($value == 0){
						unset($counts->{$key});
					}
				}
			}

			if($counts){
				$array = [];
				foreach ($counts as $key => $value) {
					$array[] = $key.' = '.$value;
				}
				if($array){
					$output[] = $post_type.': '.implode(', ',$array);
				}
			}
			
		}
	}

	if($output){
		return implode(', ',$output);
	}else{
		return false;
	}
	

}

/**
 * Get Raw Global Error Count
 *
 * @param int $post_id
 * @return array
 */
function edac_get_error_count(){
	global $wpdb;

	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker where siteid = %d and ruletype = %s";
	$summary['errors'] = intval($wpdb->get_var($wpdb->prepare($query, get_current_blog_id(), 'error')));

	return $summary['errors'];
}

/**
 * Get Raw Global Warning Count
 *
 * @param int $post_id
 * @return array
 */
function edac_get_warning_count(){
	global $wpdb;

	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker where siteid = %d and ruletype = %s";
	$summary['errors'] = intval($wpdb->get_var($wpdb->prepare($query, get_current_blog_id(), 'warning')));

	return $summary['errors'];
}

/**
 * Get Database Table Count
 *
 * @param [type] $table
 * @return int
 */
function edac_database_table_count($table){

	global $wpdb;
    $table_name = $wpdb->prefix . $table;
    $count_query = "select count(*) from $table_name";
    $num = $wpdb->get_var($count_query);

    return  $num;

}