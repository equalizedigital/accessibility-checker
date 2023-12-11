<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 * @since   1.1.0
 * @global  object $wpdb Used to query the database using the WordPress Database API
 * @return  string $return A string containing the info to output
 */

/**
 * Display the system info tab
 *
 * @return void
 */
function edac_sysinfo_display() {

	?>
	<form action="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=system_info' ) ); ?>" method="post" dir="ltr">
		<?php wp_nonce_field( 'edac_download_sysinfo', 'edac_download_sysinfo_nonce' ); ?>
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="edac-sysinfo"><?php echo esc_html( edac_tools_sysinfo_get() ); ?></textarea>
		<p class="submit">
			<input type="hidden" name="edac-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'edac-download-sysinfo', false ); ?>
		</p>
	</form>
	<?php
}

/**
 * Get System Info
 *
 * @return string
 */
function edac_tools_sysinfo_get() {
	global $wpdb;

	// Get theme info.
	$theme_data = wp_get_theme();
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$theme = $theme_data->Name . ' ' . $theme_data->Version;
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$parent_theme = $theme_data->Template;
	if ( ! empty( $parent_theme ) ) {
		$parent_theme_data = wp_get_theme( $parent_theme );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$parent_theme = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
	}

	// Try to identify the hosting provider.
	$host = edac_get_host();

	$return = '### Begin System Info (Generated ' . gmdate( 'Y-m-d H:i:s' ) . ') ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	$return = apply_filters( 'edac_sysinfo_after_site_info', $return );

	// Can we determine the site's host?
	if ( $host ) {
		$return .= "\n" . '-- Hosting Provider' . "\n\n";
		$return .= 'Host:                     ' . $host . "\n";

		$return = apply_filters( 'edac_sysinfo_after_host_info', $return );
	}

	// The local users' browser information, handled by the Browser class.
	$return .= "\n" . '-- User Browser' . "\n\n";
	if ( class_exists( 'Browser' ) ) {
		$browser = new Browser();
		$return .= 'Platform:                  ' . $browser->getPlatform() . "\n";
		$return .= 'Browser Name:              ' . $browser->getBrowser() . "\n";
		$return .= 'Browser Version:           ' . $browser->getVersion() . "\n";
		$return .= 'Browser User Agent:        ' . $browser->getUserAgent() . "\n";
	} else {
		$return .= 'Platform:                   N/A' . "\n";
		$return .= 'Browser Name:               N/A' . "\n";
		$return .= 'Browser Version:            N/A' . "\n";
		$return .= 'Browser User Agent:         N/A' . "\n";
	}

	$return = apply_filters( 'edac_sysinfo_after_user_browser', $return );

	$locale = get_locale();

	// WordPress configuration.
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	if ( $parent_theme !== $theme ) {
		$return .= 'Parent Theme:             ' . $parent_theme . "\n";
	}
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'.
	if ( get_option( 'show_on_front' ) === 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id  = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( 0 !== $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( 0 !== $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH:                  ' . ABSPATH . "\n";

	// Make sure wp_remote_post() is working.
	$request['cmd'] = '_notify-validate';

	$params = array(
		'sslverify'  => false,
		'timeout'    => 3,
		'user-agent' => 'EDAC/' . EDAC_VERSION,
		'body'       => $request,
	);

	$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

	if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
		$wp_remote_post = 'wp_remote_post() works';
	} else {
		$wp_remote_post = 'wp_remote_post() does not work';
	}

	$return .= 'Remote Post:              ' . $wp_remote_post . "\n";
	$return .= 'Table Prefix:             Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Unset' ) . "\n";
	$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

	$return = apply_filters( 'edac_sysinfo_after_wordpress_config', $return );

	// EDAC configuration.
	$return .= "\n" . '-- Accessibility Checker Configuration' . "\n\n";
	$return .= 'Version:                  ' . EDAC_VERSION . "\n";
	$return .= 'Database Version:         ' . get_option( 'edac_db_version' ) . "\n";
	$return .= 'Policy Page:              ' . ( get_option( 'edac_accessibility_policy_page' ) ? get_option( 'edac_accessibility_policy_page' ) . "\n" : "Unset\n" );
	$return .= 'Activation Date:          ' . get_option( 'edac_activation_date' ) . "\n";
	$return .= 'Footer Statement:         ' . ( get_option( 'edac_add_footer_accessibility_statement' ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Delete Data:              ' . ( get_option( 'edac_delete_data' ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Include Statement Link:   ' . ( get_option( 'edac_include_accessibility_statement_link' ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Post Types:               ' . ( get_option( 'edac_post_types' ) ? implode( ', ', get_option( 'edac_post_types' ) ) . "\n" : "Unset\n" );
	$return .= 'Simplified Sum Position:  ' . get_option( 'edac_simplified_summary_position' ) . "\n";
	$return .= 'Simplified Sum Prompt:    ' . get_option( 'edac_simplified_summary_prompt' ) . "\n";
	$return .= 'Post Count:               ' . edac_get_posts_count() . "\n";
	$return .= 'Error Count:              ' . edac_get_error_count() . "\n";
	$return .= 'Warning Count:            ' . edac_get_warning_count() . "\n";
	$return .= 'DB Table Count:           ' . edac_database_table_count( 'accessibility_checker' ) . "\n";

	if ( is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ) {

		$return   .= "\n" . '-- Accessibility Checker Pro Configuration' . "\n\n";
		$return   .= 'Version:                  ' . EDACP_VERSION . "\n";
		$return   .= 'Database Version:         ' . get_option( 'edacp_db_version' ) . "\n";
		$return   .= 'License Status:           ' . get_option( 'edacp_license_status' ) . "\n";
		$return   .= 'Authorization Username:   ' . ( get_option( 'edacp_authorization_username' ) ? get_option( 'edacp_authorization_username' ) . "\n" : "Unset\n" );
		$return   .= 'Authorization Password:   ' . ( get_option( 'edacp_authorization_password' ) ? get_option( 'edacp_authorization_password' ) . "\n" : "Unset\n" );
		$return   .= 'Scan ID:                  ' . get_transient( 'edacp_scan_id' ) . "\n";
		$return   .= 'Scan Total:               ' . get_transient( 'edacp_scan_total' ) . "\n";
		$return   .= 'Simplified Sum Heading:   ' . get_option( 'edacp_simplified_summary_heading' ) . "\n";
		$return   .= 'Background Scan Schedule: ' . get_option( 'edacp_background_scan_schedule' ) . "\n";
		$next_scan = as_next_scheduled_action( 'edacp_schedule_scan_hook' );
		if ( $next_scan ) {
			$return .= 'Next Background Scan: ' . gmdate( 'F j, Y g:i a', $next_scan ) . "\n";
		}
		$return .= 'Ignore Permissions:       ' . ( get_option( 'edacp_ignore_user_roles' ) ? implode( ', ', get_option( 'edacp_ignore_user_roles' ) ) . "\n" : "None\n" );
		$return .= 'Ignores DB Table Count:   ' . edac_database_table_count( 'accessibility_checker_global_ignores' ) . "\n";
		$return .= 'Logs DB Table Count:      ' . edac_database_table_count( 'accessibility_checker_logs' ) . "\n";
	}

	$return = apply_filters( 'edac_sysinfo_after_edac_config', $return );

	// Templates.
	$dir = get_stylesheet_directory() . '/edac_templates/*';
	if ( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
		$return .= "\n" . '-- EDAC Template Overrides' . "\n\n";

		foreach ( glob( $dir ) as $file ) {
			$return .= 'Filename:                 ' . basename( $file ) . "\n";
		}

		$return = apply_filters( 'edac_sysinfo_after_edac_templates', $return );
	}

	// Get plugins that have an update.
	$updates = get_plugin_updates();

	// Must-use plugins
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if ( count( $muplugins ) > 0 ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach ( $muplugins as $plugin => $plugin_data ) {
			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}

		$return = apply_filters( 'edac_sysinfo_after_wordpress_mu_plugins', $return );
	}

	// WordPress active plugins.
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins        = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! in_array( $plugin_path, $active_plugins, true ) ) {
			continue;
		}

		$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'edac_sysinfo_after_wordpress_plugins', $return );

	// WordPress inactive plugins.
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( in_array( $plugin_path, $active_plugins, true ) ) {
			continue;
		}

		$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'edac_sysinfo_after_wordpress_plugins_inactive', $return );

	if ( is_multisite() ) {
		// WordPress Multisite active plugins.
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$plugin  = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$return = apply_filters( 'edac_sysinfo_after_wordpress_ms_plugins', $return );
	}

	// Server configuration.
	$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) : 'Unknown';

	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $server_software . "\n";

	$return = apply_filters( 'edac_sysinfo_after_webserver_config', $return );

	// PHP configs.
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";
	$return .= 'PHP Arg Separator:        ' . edac_get_php_arg_separator_output() . "\n";

	$return = apply_filters( 'edac_sysinfo_after_php_config', $return );

	// PHP extensions and such.
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return = apply_filters( 'edac_sysinfo_after_php_ext', $return );

	// Session stuff.
	$return .= "\n" . '-- Session Configuration' . "\n\n";
	$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

	// The rest of this is only relevant is session is enabled.
	if ( isset( $_SESSION ) ) {
		$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
		$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
		$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
		$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
		$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
	}

	$return = apply_filters( 'edac_sysinfo_after_session_config', $return );

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

	if ( defined( 'WPE_APIKEY' ) ) {
		$host = 'WP Engine';
	} elseif ( defined( 'PAGELYBIN' ) ) {
		$host = 'Pagely';
	} elseif ( defined( 'WPCOM_IS_VIP_ENV' ) ) {
		$host = 'WordPress VIP';
	} elseif ( DB_HOST === 'localhost:/tmp/mysql5.sock' ) {
		$host = 'ICDSoft';
	} elseif ( DB_HOST === 'mysqlv5' ) {
		$host = 'NetworkSolutions';
	} elseif ( strpos( DB_HOST, 'ipagemysql.com' ) !== false ) {
		$host = 'iPage';
	} elseif ( strpos( DB_HOST, 'ipowermysql.com' ) !== false ) {
		$host = 'IPower';
	} elseif ( strpos( DB_HOST, '.gridserver.com' ) !== false ) {
		$host = 'MediaTemple Grid';
	} elseif ( strpos( DB_HOST, '.pair.com' ) !== false ) {
		$host = 'pair Networks';
	} elseif ( strpos( DB_HOST, '.stabletransit.com' ) !== false ) {
		$host = 'Rackspace Cloud';
	} elseif ( strpos( DB_HOST, '.sysfix.eu' ) !== false ) {
		$host = 'SysFix.eu Power Hosting';
	} elseif ( isset( $_SERVER['SERVER_NAME'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ), 'Flywheel' ) !== false ) {
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

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// if this fails, check_admin_referer() will automatically print a "failed" page and die.
	if ( ! empty( $_POST ) && isset( $_POST['edac_download_sysinfo_nonce'] ) && check_admin_referer( 'edac_download_sysinfo', 'edac_download_sysinfo_nonce' ) ) {

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="edac-system-info.txt"' );

		if ( isset( $_POST['edac-sysinfo'] ) ) {
			echo esc_html( wp_strip_all_tags( $_POST['edac-sysinfo'] ) );
		}

		die();

	}
}

/**
 * Get Post Count by available custom post types
 *
 * @return mixed
 */
function edac_get_posts_count() {

	$output = array();

	$post_types = get_option( 'edac_post_types' );
	if ( $post_types ) {
		foreach ( $post_types as $post_type ) {

			$counts = wp_count_posts( $post_type );

			if ( $counts ) {
				foreach ( $counts as $key => $value ) {
					// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					if ( 0 == $value ) {
						unset( $counts->{$key} );
					}
				}
			}

			if ( $counts ) {
				$array = array();
				foreach ( $counts as $key => $value ) {
					$array[] = $key . ' = ' . $value;
				}
				if ( $array ) {
					$output[] = $post_type . ': ' . implode( ', ', $array );
				}
			}
		}
	}

	if ( $output ) {
		return implode( ', ', $output );
	} else {
		return false;
	}
}

/**
 * Get Raw Global Error Count
 *
 * @return array
 */
function edac_get_error_count() {
	global $wpdb;

	// Define a unique cache key for our data.
	$cache_key     = 'edac_errors_' . get_current_blog_id();
	$stored_errors = wp_cache_get( $cache_key );

	// Check if the result exists in the cache.
	if ( false === $stored_errors ) {
		// If not, perform the database query.
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$stored_errors = intval( $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i WHERE siteid = %d AND ruletype = %s', $table_name, get_current_blog_id(), 'error' ) ) );

		// Save the result in the cache for future use.
		wp_cache_set( $cache_key, $stored_errors );
	}

	return $stored_errors;
}

/**
 * Get Raw Global Warning Count
 *
 * @return array Array of.
 */
function edac_get_warning_count() {
	global $wpdb;

	// Define a unique cache key for our data.
	$cache_key       = 'edac_warnings_' . get_current_blog_id();
	$stored_warnings = wp_cache_get( $cache_key );

	// Check if the result exists in the cache.
	if ( false === $stored_warnings ) {
		// If not, perform the database query.
		$table_name = $wpdb->prefix . 'accessibility_checker';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$stored_warnings = intval( $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i WHERE siteid = %d AND ruletype = %s', $table_name, get_current_blog_id(), 'warning' ) ) );

		// Save the result in the cache for future use.
		wp_cache_set( $cache_key, $stored_warnings );
	}

	return $stored_warnings;
}


/**
 * Get Database Table Count
 *
 * @param string $table Database table.
 * @return int
 */
function edac_database_table_count( $table ) {
	global $wpdb;

	// Create a unique cache key based on the table's name.
	$cache_key = 'edac_table_count_' . $table;

	// Try to get the count from the cache first.
	$count = wp_cache_get( $cache_key );

	if ( false === $count ) {
		// If the count is not in the cache, perform the database query.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i', $wpdb->prefix . $table ) );

		// Save the count to the cache for future use.
		wp_cache_set( $cache_key, $count );
	}

	return $count;
}
