<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compare strings
 *
 * @param string $string1 String to compare.
 * @param string $string2 String to compare.
 * @return boolean
 */
function edac_compare_strings( $string1, $string2 ) {
	/**
	 * Prepare strings for our comparison.
	 *
	 * @param string $content String to prepare.
	 * @return string
	 */
	$prepare_strings = function ( $content ) {
		// Text to remove.
		$remove_text = [
			__( 'permalink of ', 'accessibility-checker' ),
			__( 'permalink to ', 'accessibility-checker' ),
			__( '&nbsp;', 'accessibility-checker' ),
		];

		$content = strtolower( $content );
		$content = str_ireplace( $remove_text, '', $content );
		$content = wp_strip_all_tags( $content );
		$content = trim( $content, " \t\n\r\0\x0B\xC2\xA0" );

		return html_entity_decode( $content, ENT_QUOTES | ENT_HTML5 );
	};

	return $prepare_strings( $string1 ) === $prepare_strings( $string2 );
}

/**
 * Check if plugin is installed by getting all plugins from the plugins dir
 *
 * @param string $plugin_slug Slug of the plugin.
 * @return bool
 */
function edac_check_plugin_installed( $plugin_slug ) {
	$installed_plugins = get_plugins();

	return array_key_exists( $plugin_slug, $installed_plugins ) || in_array( $plugin_slug, $installed_plugins, true );
}

/**
 * Convert cardinal number into ordinal number
 *
 * @param int|string $number Number to make ordinal.
 * @return string
 */
function edac_ordinal( $number ) {

	$number = (int) $number;

	if ( class_exists( 'NumberFormatter' ) ) {
		return (
			new NumberFormatter(
				get_locale(),
				NumberFormatter::ORDINAL
			)
		)->format( $number );

	} else {
		if ( $number % 100 >= 11 && $number % 100 <= 13 ) {
			$ordinal = $number . 'th';
		} else {
			switch ( $number % 10 ) {
				case 1:
					$ordinal = $number . 'st';
					break;
				case 2:
					$ordinal = $number . 'nd';
					break;
				case 3:
					$ordinal = $number . 'rd';
					break;
				default:
					$ordinal = $number . 'th';
					break;
			}
		}
		return $ordinal;

	}
}

/**
 * Remove element from multi-dimensional array
 *
 * @param array  $items The multi-dimensional array.
 * @param string $key The key of the element.
 * @param string $value The value of the element.
 * @return array
 */
function edac_remove_element_with_value( $items, $key, $value ) {
	foreach ( $items as $sub_key => $sub_array ) {
		if ( $sub_array[ $key ] === $value ) {
			unset( $items[ $sub_key ] );
		}
	}
	return $items;
}

/**
 * Filter a multi-dimensional array
 *
 * @param array  $items The multi-dimensional array.
 * @param string $index The index of the element.
 * @param string $value The element value to match.
 * @return array
 */
function edac_filter_by_value( $items, $index, $value ) {
	if ( is_array( $items ) && count( $items ) > 0 ) {
		foreach ( array_keys( $items ) as $key ) {
			$temp[ $key ] = $items[ $key ][ $index ];

			if ( $temp[ $key ] === $value ) {
				$newarray[ $key ] = $items[ $key ];
			}
		}
	}

	if ( isset( $newarray ) && is_array( $newarray ) && count( $newarray ) ) {
		return array_values( $newarray );
	}
	return [];
}

/**
 * Get days plugin has been active
 *
 * @return int
 */
function edac_days_active() {
	$activation_date = get_option( 'edac_activation_date' );
	if ( $activation_date ) {
		$diff = strtotime( $activation_date ) - strtotime( gmdate( 'Y-m-d H:i:s' ) );
		return abs( round( $diff / 86400 ) );
	}
	return 0;
}

/**
 * Custom Post Types
 *
 * @return array
 */
function edac_custom_post_types() {
	$args = [
		'public'   => true,
		'_builtin' => false,
	];

	$output   = 'names'; // names or objects, note names is the default.
	$operator = 'and'; // Options 'and' or 'or'.

	return get_post_types( $args, $output, $operator );
}

/**
 * Available Post Types
 *
 * @return array
 */
function edac_post_types() {
	/**
	 * Filter the post types that the plugin will check.
	 *
	 * @since 1.4.0
	 *
	 * @param array $post_types post types.
	 */
	$post_types = apply_filters( 'edac_filter_post_types', [ 'post', 'page' ] );

	if ( ! is_array( $post_types ) ) {
		$post_types = [ $post_types ];
	}

	// remove duplicates.
	$post_types = array_unique( $post_types );

	// validate post types.
	foreach ( $post_types as $key => $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			unset( $post_types[ $key ] );
		}
	}

	return $post_types;
}

/**
 * Retrieve a human readable post type label.
 *
 * @param string $post_type Post type slug.
 * @return string
 */
function edac_get_post_type_label( string $post_type ): string {
	$post_type = sanitize_key( (string) $post_type );

	if ( '' === $post_type ) {
		return '';
	}

	$post_type_object = get_post_type_object( $post_type );

	if ( $post_type_object instanceof \WP_Post_Type && ! empty( $post_type_object->labels->name ) ) {
		return $post_type_object->labels->name;
	}

	return ucfirst( $post_type );
}

/**
 * This function validates a table name against WordPress naming conventions and checks its existence in the database.
 *
 * The function first checks if the provided table name only contains alphanumeric characters, underscores, or hyphens.
 * If not, it returns null.
 *
 * After that, it checks if a table with that name actually exists in the database using the SHOW TABLES LIKE query.
 * If the table doesn't exist, it also returns null.
 *
 * If both checks are passed, it returns the valid table name.
 *
 * @param string $table_name The name of the table to be validated.
 *
 * @return string|null The validated table name, or null if the table name is invalid or the table does not exist.
 */
function edac_get_valid_table_name( $table_name ) {
	global $wpdb;
	static $found_table_name;

	if ( isset( $found_table_name ) ) {
		return $found_table_name;
	}

	// Check if table name only contains alphanumeric characters, underscores, or hyphens.
	if ( ! preg_match( '/^[a-zA-Z0-9_\-]+$/', $table_name ) ) {
		// Invalid table name.
		return null;
	}

	// Verify that the table actually exists in the database.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
		// Table does not exist.
		return null;
	}

	$found_table_name = $table_name;
	return $found_table_name;
}

/**
 * Upcoming meetups in json format
 *
 * @param string  $meetup meetup name.
 * @param integer $count number of meetups to return.
 * @return array
 */
function edac_get_upcoming_meetups_json( $meetup, $count = 5 ) {

	if ( empty( $meetup ) || ! is_string( $meetup ) ) {
		return [];
	}

	// Min of 1 and max of 25.
	$count = absint( max( 1, min( 25, $count ) ) );

	// Sanitize meetup name for both cache key and GraphQL query to prevent injection.
	$sanitized_meetup = sanitize_title( $meetup );

	$key          = '_upcoming_meetups__' . $sanitized_meetup . '__' . (int) $count;
	$stale_key    = $key . '__stale';
	$cached_value = get_transient( $key );

	if ( false !== $cached_value ) {
		return is_array( $cached_value ) ? $cached_value : [];
	}

	$output = [];

	$request_uri = 'https://api.meetup.com/gql-ext';
	$query       = '
	query Group {
		groupByUrlname(urlname: "' . $sanitized_meetup . '") {
			events(first: ' . (int) $count . ') {
				totalCount
				edges {
					node {
						dateTime
						eventUrl
						id
						title
					}
				}
			}
		}
	}';

	$request = wp_remote_post(
		$request_uri,
		[
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'timeout' => 10, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Timeout set for external request.
			'body'    => wp_json_encode(
				[
					'query' => $query,
				]
			),
		]
	);

	if ( ! is_wp_error( $request ) && 200 === (int) wp_remote_retrieve_response_code( $request ) ) {
		$response_body = json_decode( wp_remote_retrieve_body( $request ) );

		$edges = null;
		if ( is_object( $response_body ) && isset( $response_body->data->groupByUrlname->events->edges ) ) {
			$edges = $response_body->data->groupByUrlname->events->edges;
		}

		if ( is_array( $edges ) ) {
			foreach ( $edges as $edge ) {
				if ( ! isset( $edge->node ) ) {
					continue;
				}

				$event = $edge->node;

				// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL response uses camelCase.
				if ( empty( $event->title ) || empty( $event->dateTime ) || empty( $event->eventUrl ) || empty( $event->id ) ) {
					continue;
				}

				$timestamp = strtotime( (string) $event->dateTime );
				if ( false === $timestamp ) {
					continue;
				}

				$event_data       = new stdClass();
				$event_data->name = (string) $event->title;
				$event_data->time = $timestamp * 1000; // Convert to milliseconds to match old format.
				$event_data->link = (string) $event->eventUrl;
				$event_data->id   = (string) $event->id;
				// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase.

				$output[] = $event_data;
			}
		}
	}

	if ( ! empty( $output ) ) {
		set_transient( $key, $output, DAY_IN_SECONDS );
		update_option( $stale_key, $output, false );
		return $output;
	}

	$stale_value = get_option( $stale_key );
	if ( is_array( $stale_value ) && ! empty( $stale_value ) ) {
		// Serve stale data for a short window while retrying upstream requests periodically.
		set_transient( $key, $stale_value, HOUR_IN_SECONDS );
		return $stale_value;
	}

	return [];
}

/**
 * Upcoming meetups in html
 *
 * @param  string  $meetup meetup name.
 * @param  integer $count number of meetups to return.
 * @param  string  $heading heading level.
 * @return string
 */
function edac_get_upcoming_meetups_html( $meetup, $count = 5, $heading = '3' ) {

	$json = edac_get_upcoming_meetups_json( $meetup, $count );

	if ( empty( $json ) ) {
		return '';
	}

	$html = '<ul class="edac-upcoming-meetup-list">';

	foreach ( $json as $event ) {
		$link_text = esc_html__( 'Attend Free', 'accessibility-checker' );

		$html .= '
		<li class="edac-upcoming-meetup-item edac-mb-3">
			<h' . esc_html( $heading ) . ' class="edac-upcoming-meetup-item-name">' . esc_html( $event->name ) . '</h' . esc_html( $heading ) . '>
			<div class="edac-upcoming-meetup-item-time edac-timestamp-to-local">' . (string) ( (int) $event->time / 1000 ) . '</div>
			<a aria-label="' . esc_attr( $link_text . ': ' . $event->name ) . '" class="edac-upcoming-meetup-item-link" href="' . esc_url( $event->link ) . '">' . $link_text . '</a>
		</li>';
	}

	$html .= '</ul>';

	return $html;
}

/**
 * Calculate the issue density
 *
 * @param  int $issue_count number of issues.
 * @param  int $element_count number of elements.
 * @param  int $content_length length of content.
 * @return int
 */
function edac_get_issue_density( $issue_count, $element_count, $content_length ) {

	if ( $element_count < 1 || $content_length < 1 ) {
		return 0;
	}

	$element_weight = .8;
	$content_weight = .2;

	$error_elements_percentage = $issue_count / $element_count;
	$error_content_percentage  = $issue_count / $content_length;

	$score = (
		( $error_elements_percentage * $element_weight ) +
		( $error_content_percentage * $content_weight )
	);

	return round( $score * 100, 2 );
}

/**
 * Get simplified summary
 *
 * @param integer $post Post ID.
 * @return void
 */
function edac_get_simplified_summary( $post = null ) {
	if ( null === $post ) {
		$post = get_the_ID();
	}

	if ( null === $post ) {
		return;
	}

	echo wp_kses_post(
		( new \EDAC\Inc\Simplified_Summary() )->simplified_summary_markup( $post )
	);
}

/**
 * Get Post Count by available custom post types
 *
 * @return mixed
 */
function edac_get_posts_count() {

	$output = [];

	$post_types = Settings::get_scannable_post_types();
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
				$array = [];
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
	}
	return false;
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
		$stored_errors = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i WHERE siteid = %d AND ruletype = %s', $table_name, get_current_blog_id(), 'error' ) );

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
		$stored_warnings = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i WHERE siteid = %d AND ruletype = %s', $table_name, get_current_blog_id(), 'warning' ) );

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

/**
 * Generate a summary statistic list item.
 *
 * @since 1.14.0
 *
 * @param string $item_class The base CSS class for the list item.
 * @param int    $count      The count of items to display.
 * @param string $label      The translated label with count included.
 * @param string $icon_name  Icon name passed to edac_icon(). Default 'error'.
 *
 * @return string The generated HTML list item.
 */
function edac_generate_summary_stat( string $item_class, int $count, string $label, string $icon_name = 'error' ): string {
	$has_error_class = ( $count > 0 ) ? ' has-errors' : '';

	return '
		<li class="edac-summary-stat ' . $item_class . $has_error_class . '">
			<span class="screen-reader-text">' . $count . ' ' . $label . '</span>
			' . edac_icon( $icon_name ) . '
			<div class="edac-panel-number" aria-hidden="true">
				' . $count . '
			</div>
			<div class="edac-panel-number-label" aria-hidden="true">' . $label . '</div>
		</li>';
}

/**
 * Generate links to pro page with some params.
 *
 * @param array  $query_args A list of key value pairs to add as query vars to the link.
 * @param string $type The type of link to generate. Default is 'pro'.
 * @param array  $args Additional arguments to pass on the link.
 * @return string
 */
function edac_generate_link_type( $query_args = [], $type = 'pro', $args = [] ): string {

	if ( ! is_array( $query_args ) ) {
		$query_args = [];
	}

	if ( ! is_array( $args ) ) {
		$args = [];
	}

	$date_now        = new DateTime( gmdate( 'Y-m-d H:i:s' ) );
	$activation_date = new DateTime( get_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s' ) ) );
	$interval        = $date_now->diff( $activation_date );
	$days_active     = $interval->days;
	$query_defaults  = [
		'utm_source'       => 'accessibility-checker',
		'utm_medium'       => 'software',
		'utm_campaign'     => 'wordpress-general',
		'php_version'      => PHP_VERSION,
		'platform'         => 'wordpress',
		'platform_version' => $GLOBALS['wp_version'],
		'software'         => edac_is_pro() ? 'pro' : 'free',
		'software_version' => defined( 'EDACP_VERSION' ) ? EDACP_VERSION : EDAC_VERSION,
		'days_active'      => $days_active,
	];

	// Add the ref parameter if one is set via filter.
	$ref = apply_filters( 'edac_filter_generate_link_type_ref', '' );
	if ( ! empty( $ref ) && is_string( $ref ) ) {
		$query_args['ref'] = $ref;
	}

	$query_args = array_merge( $query_defaults, $query_args );

	switch ( $type ) {
		case 'help':
			$base_link = trailingslashit( 'https://a11ychecker.com/help' . $args['help_id'] ?? '' );
			break;
		case 'custom': // phpcs:ignore -- intentially only breaking inside the condition because if it's not set we want to hit default.
			if ( ! empty( $args['base_link'] ) ) {
				$base_link = $args['base_link'];
				break;
			}
		case 'pro':
		default:
			$base_link = 'https://equalizedigital.com/accessibility-checker/pricing/';
			break;
	}
	return add_query_arg( $query_args, $base_link );
}

/**
 * Echo or return a link with some utms.
 *
 * This is just a simplified wrapper around `edac_generate_link_type` to generate a link with UTM parameters.
 *
 * @param string $base_url the base URL to which UTM parameters will be added.
 * @param string $campaign the UTM campaign name, optional.
 * @param string $content the UTM content name, optional.
 * @param bool   $directly_echo whether to echo the link or return it. Default is true.
 *
 * @return void|string
 */
function edac_link_wrapper( $base_url, $campaign = '', $content = '', $directly_echo = true ) {
	if ( empty( $base_url ) || ! is_string( $base_url ) ) {
		return;
	}

	$params = [];
	if ( ! empty( $campaign ) ) {
		$params['utm_campaign'] = $campaign;
	}

	if ( ! empty( $content ) ) {
		$params['utm_content'] = $content;
	}

	$link = edac_generate_link_type(
		$params,
		'custom',
		[ 'base_link' => $base_url ]
	);

	if ( ! $directly_echo ) {
		return $link;
	}

	echo esc_url( $link );
}

/**
 * Check if WooCommerce is enabled.
 *
 * This just checks for existence of the main WooCommerce function and class.
 *
 * @return bool
 */
function edac_is_woocommerce_enabled() {
	return function_exists( 'WC' ) && class_exists( 'WooCommerce' );
}

/**
 * Check if a given post id is the WooCommerce checkout page.
 *
 * @param int $post_id The post ID to check.
 * @return bool
 */
function edac_check_if_post_id_is_woocommerce_checkout_page( $post_id ) {
	if ( ! edac_is_woocommerce_enabled() ) {
		return false;
	}

	return wc_get_page_id( 'checkout' ) === $post_id;
}

/**
 * Parse HTML content to extract image or SVG elements
 *
 * @param string $html The HTML content to parse.
 * @return array Array containing 'img' (string) and 'svg' (string) keys.
 */
function edac_parse_html_for_media( $html ) {
	if ( empty( $html ) ) {
		return [
			'img' => null,
			'svg' => null,
		];
	}

	// Decode HTML entities before processing.
	$decoded_html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5 );

	// Early return if no media tags found.
	if ( stripos( $decoded_html, '<img' ) === false && stripos( $decoded_html, '<svg' ) === false ) {
		return [
			'img' => null,
			'svg' => null,
		];
	}

	// More specific img tag regex pattern.
	if ( preg_match( '/<img[^>]+src=([\'"])(.*?)\1[^>]*>/i', $decoded_html, $matches ) ) {
		return [
			'img' => $matches[2] ?? null,
			'svg' => null,
		];
	}

	// SVG pattern remains the same.
	if ( preg_match( '/<svg[^>]*>.*?<\/svg>/is', $decoded_html, $matches ) ) {
		return [
			'img' => null,
			'svg' => $matches[0],
		];
	}

	return [
		'img' => null,
		'svg' => null,
	];
}

/**
 * Remove corrected posts
 *
 * @param int    $post_ID The ID of the post.
 * @param string $type    The type of the post.
 * @param int    $pre     The flag indicating the removal stage (1 for before validation php based rules, 2 for after validation).
 * @param string $ruleset    The type of the ruleset to correct (php or js). For backwards compatibility, defaults to 'php'.
 *
 * @return void
 */
function edac_remove_corrected_posts( $post_ID, $type, $pre = 1, $ruleset = 'php' ) {  // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- $ruleset is for backwards compatibility.
	global $wpdb;

	$rules = edac_register_rules();

	if ( 0 === count( $rules ) ) {
		return;
	}

	$sql = 1 === $pre
		? "UPDATE {$wpdb->prefix}accessibility_checker SET recordcheck = %d WHERE siteid = %d AND postid = %d AND type = %s"
		: "DELETE FROM {$wpdb->prefix}accessibility_checker WHERE recordcheck = %d AND siteid = %d AND postid = %d AND type = %s";

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for adding data to database, caching not required for one time operation.
	$wpdb->query(
		$wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			0,
			get_current_blog_id(),
			$post_ID,
			$type
		)
	);
}

/**
 * Generate a landmark link with proper URL and ARIA label
 *
 * @param string $landmark The landmark type (e.g., "header", "navigation", "main").
 * @param string $landmark_selector The CSS selector for the landmark.
 * @param int    $post_id The post ID to link to.
 * @param string $css_class Optional CSS class for the link. Default 'edac-details-rule-records-record-landmark-link'.
 * @param bool   $target_blank Whether to open link in new window. Default true.
 *
 * @return string The HTML for the landmark link or just the landmark text if no selector.
 */
function edac_generate_landmark_link( $landmark, $landmark_selector, $post_id, $css_class = 'edac-details-rule-records-record-landmark-link', $target_blank = true ) {
	if ( empty( $landmark ) ) {
		return '';
	}
	$landmark = ucwords( $landmark );
	$landmark = esc_html( $landmark );

	// If we have both landmark and selector, create a link.
	if ( ! empty( $landmark_selector ) ) {
		$link = apply_filters(
			'edac_get_origin_url_for_virtual_page',
			get_the_permalink( $post_id ),
			$post_id
		);

		$landmark_url = add_query_arg(
			[
				'edac_landmark' => base64_encode( $landmark_selector ),
				'edac_nonce'    => wp_create_nonce( 'edac_highlight' ),
			],
			$link
		);

		// translators: %s is the landmark type (e.g., "Header", "Navigation", "Main").
		$landmark_aria_label = sprintf( __( 'View %s landmark on website, opens a new window', 'accessibility-checker' ), $landmark );

		$target_attr = $target_blank ? ' target="_blank"' : '';

		return sprintf(
			'<a href="%s" class="%s"%s aria-label="%s">%s</a>',
			esc_url( $landmark_url ),
			esc_attr( $css_class ),
			$target_attr,
			esc_attr( $landmark_aria_label ),
			$landmark
		);
	}

	// If we only have landmark text, return it formatted.
	return $landmark;
}

/**
 * Check if a post is a virtual page.
 *
 * This function checks if a post is a virtual page using the pro plugin's
 * VirtualItemType:POST_TYPE constant.
 *
 * @param int $post_id The post ID to check.
 * @return bool True if the post is a virtual page, false otherwise.
 */
function edac_is_virtual_page( $post_id ) {
	if ( class_exists( '\EqualizeDigital\AccessibilityCheckerPro\VirtualContent\PostType\VirtualItemType' ) ) {
		$post_type     = get_post_type( $post_id );
		$pro_post_type = \EqualizeDigital\AccessibilityCheckerPro\VirtualContent\PostType\VirtualItemType::POST_TYPE;
		return $pro_post_type === $post_type;
	}

	return false;
}

/**
 * Check if the Pro version of the plugin is active.
 *
 * @return bool True if Pro version is active, false otherwise.
 */
function edac_is_pro() {
	return defined( 'EDACP_VERSION' ) && defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID;
}

/**
 * Get current timestamp in UTC for database storage.
 *
 * @since 1.35.0
 * @return string MySQL datetime string in UTC.
 */
function edac_get_current_utc_datetime(): string {
	return gmdate( 'Y-m-d H:i:s' );
}

/**
 * Format a UTC datetime string for display in WordPress timezone.
 *
 * Uses the WordPress configured date and time format settings.
 *
 * @since 1.35.0
 * @param string $utc_datetime MySQL datetime string in UTC format.
 * @return string Formatted datetime string in WordPress timezone, or empty string if invalid.
 */
function edac_format_datetime_from_utc( string $utc_datetime ): string {
	if ( ! $utc_datetime || '0000-00-00 00:00:00' === $utc_datetime ) {
		return '';
	}

	$timestamp = strtotime( $utc_datetime . ' UTC' );
	if ( false === $timestamp ) {
		return '';
	}

	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );
	$format      = $date_format . ' ' . $time_format;

	return wp_date( $format, $timestamp );
}

/**
 * Determine the icon name to display for the readability panel.
 *
 * PHP equivalent of the JS getPanelIcon() helper used in the readability sidebar panel.
 *
 * Logic:
 *  - No content or grade is 0 → 'warning'
 *  - Reading level is passing (below 9th grade) → 'check'
 *  - Reading level is failing:
 *    - No simplified summary → 'warning'
 *    - Summary exists and grade is valid (> 0 and not failed) → 'check'
 *    - Summary grade failed → 'warning'
 *  - Default → 'warning'
 *
 * @since 1.38.0
 *
 * @param bool   $has_content        Whether the post has enough content to calculate a grade.
 * @param int    $post_grade         Flesch-Kincaid grade level of the post content. 0 means not calculable.
 * @param bool   $post_grade_failed  True when the post grade is above 9th grade (reading level not 'below').
 * @param string $summary_text       The simplified summary text, if any.
 * @param int    $summary_grade      Flesch-Kincaid grade level of the simplified summary.
 * @param bool   $summary_grade_failed True when the simplified summary grade is above 9th grade.
 * @return string Icon name: 'check' or 'warning'.
 */
function edac_get_readability_panel_icon( bool $has_content, int $post_grade, bool $post_grade_failed, string $summary_text, int $summary_grade, bool $summary_grade_failed ): string {
	if ( ! $has_content || 0 === $post_grade ) {
		return 'warning';
	}

	if ( ! $post_grade_failed ) {
		return 'check';
	}

	// Reading level is above 9th grade – evaluate the simplified summary.
	if ( ! $summary_text ) {
		return 'warning';
	}

	if ( $summary_grade > 0 && ! $summary_grade_failed ) {
		return 'check';
	}

	if ( $summary_grade_failed ) {
		return 'warning';
	}

	return 'warning';
}

/**
 * Render an icon as an HTML string.
 *
 * PHP equivalent of the Icon JS component. Returns a <span> wrapping the
 * requested SVG with the appropriate BEM classes and ARIA attributes.
 *
 * Supported icon names: check, warning, error, info.
 * Supported types (controls color via CSS): success, warning, error, info.
 * When $type is omitted the type is derived automatically from $name.
 *
 * @since 1.38.0
 *
 * @param string $name       Icon name: 'check', 'warning', 'error', or 'info'. Default 'check'.
 * @param string $type       Optional. BEM modifier type for color: 'success', 'warning', 'error', 'info'.
 *                           When empty, derived automatically from $name.
 * @param bool   $aria_hidden Whether to add aria-hidden="true". Automatically set to false when
 *                           $aria_label is provided. Default true.
 * @param string $aria_label  Accessible label. When provided, aria-hidden is set to false and
 *                           aria-label is added to the wrapper span.
 * @param string $css_class   Additional CSS classes to append to the wrapper span.
 * @return string The rendered HTML, or an empty string for an unknown icon name.
 */
function edac_icon( string $name = 'check', string $type = '', bool $aria_hidden = true, string $aria_label = '', string $css_class = '' ): string {

	$svgs = [
		'check'     => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">'
						. '<path d="M7.5 10.625L9.375 12.5L12.5 8.125" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>'
						. '<rect x="2.625" y="2.625" width="14.75" height="14.75" rx="2.375" stroke="currentColor" stroke-width="1.25"/>'
						. '</svg>',
		'warning'   => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">'
						. '<path d="M9.99997 7.5V10.625M2.24747 13.4383C1.52581 14.6883 2.42831 16.25 3.87081 16.25H16.1291C17.5708 16.25 18.4733 14.6883 17.7525 13.4383L11.6241 2.815C10.9025 1.565 9.09747 1.565 8.37581 2.815L2.24747 13.4383ZM9.99997 13.125H10.0058V13.1317H9.99997V13.125Z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>'
						. '</svg>',
		'error'     => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">'
						. '<path d="M10 7.5V10.625M17.5 10C17.5 10.9849 17.306 11.9602 16.9291 12.8701C16.5522 13.7801 15.9997 14.6069 15.3033 15.3033C14.6069 15.9997 13.7801 16.5522 12.8701 16.9291C11.9602 17.306 10.9849 17.5 10 17.5C9.01509 17.5 8.03982 17.306 7.12987 16.9291C6.21993 16.5522 5.39314 15.9997 4.6967 15.3033C4.00026 14.6069 3.44781 13.7801 3.0709 12.8701C2.69399 11.9602 2.5 10.9849 2.5 10C2.5 8.01088 3.29018 6.10322 4.6967 4.6967C6.10322 3.29018 8.01088 2.5 10 2.5C11.9891 2.5 13.8968 3.29018 15.3033 4.6967C16.7098 6.10322 17.5 8.01088 17.5 10ZM10 13.125H10.0067V13.1317H10V13.125Z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>'
						. '</svg>',
		'info'      => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">'
						. '<path d="M9.375 9.375L9.40917 9.35833C9.51602 9.30495 9.63594 9.2833 9.75472 9.29596C9.8735 9.30862 9.98616 9.35505 10.0794 9.42976C10.1726 9.50446 10.2424 9.60432 10.2806 9.71749C10.3189 9.83066 10.3238 9.95242 10.295 10.0683L9.705 12.4317C9.67595 12.5476 9.68078 12.6695 9.71891 12.7828C9.75704 12.8961 9.82687 12.9961 9.92011 13.071C10.0134 13.1458 10.1261 13.1923 10.245 13.205C10.3639 13.2177 10.4839 13.196 10.5908 13.1425L10.625 13.125M17.5 10C17.5 10.9849 17.306 11.9602 16.9291 12.8701C16.5522 13.7801 15.9997 14.6069 15.3033 15.3033C14.6069 15.9997 13.7801 16.5522 12.8701 16.9291C11.9602 17.306 10.9849 17.5 10 17.5C9.01509 17.5 8.03982 17.306 7.12987 16.9291C6.21993 16.5522 5.39314 15.9997 4.6967 15.3033C4.00026 14.6069 3.44781 13.7801 3.0709 12.8701C2.69399 11.9602 2.5 10.9849 2.5 10C2.5 8.01088 3.29018 6.10322 4.6967 4.6967C6.10322 3.29018 8.01088 2.5 10 2.5C11.9891 2.5 13.8968 3.29018 15.3033 4.6967C16.7098 6.10322 17.5 8.01088 17.5 10ZM10 6.875H10.0067V6.88167H10V6.875Z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>'
						. '</svg>',
		'dismissed' => '<svg width="20" height="20" viewBox="0 0 37 37" fill="none" xmlns="http://www.w3.org/2000/svg">'
						. '<path d="M13.875 19.6562L17.3437 23.125L23.125 15.0312M32.375 18.5C32.375 20.3221 32.0161 22.1263 31.3188 23.8097C30.6215 25.4931 29.5995 27.0227 28.3111 28.3111C27.0227 29.5995 25.4931 30.6215 23.8097 31.3188C22.1263 32.0161 20.3221 32.375 18.5 32.375C16.6779 32.375 14.8737 32.0161 13.1903 31.3188C11.5069 30.6215 9.97731 29.5995 8.68889 28.3111C7.40048 27.0227 6.37846 25.4931 5.68117 23.8097C4.98389 22.1263 4.625 20.3221 4.625 18.5C4.625 14.8201 6.08683 11.291 8.68889 8.68889C11.291 6.08683 14.8201 4.625 18.5 4.625C22.1799 4.625 25.709 6.08683 28.3111 8.68889C30.9132 11.291 32.375 14.8201 32.375 18.5Z" stroke="currentColor" stroke-width="2.775" stroke-linecap="round" stroke-linejoin="round"/>'
						. '</svg>',
	];

	if ( ! isset( $svgs[ $name ] ) ) {
		return '';
	}

	// Auto-derive type from name when not explicitly provided.
	if ( '' === $type ) {
		$type_map = [
			'check'     => 'success',
			'warning'   => 'warning',
			'error'     => 'error',
			'info'      => 'info',
			'dismissed' => 'dismissed',
		];
		$type     = $type_map[ $name ] ?? '';
	}

	// Build CSS class string.
	$classes    = array_filter( [ 'edac-icon', $type ? 'edac-icon--' . $type : '', $css_class ] );
	$class_attr = implode( ' ', $classes );

	// Build ARIA attributes.
	// When an aria-label is supplied the icon must be announced, so aria-hidden is forced off.
	$resolved_aria_hidden = $aria_label ? false : $aria_hidden;
	$aria_attrs           = 'aria-hidden="' . ( $resolved_aria_hidden ? 'true' : 'false' ) . '"';
	if ( $aria_label ) {
		$aria_attrs .= ' aria-label="' . esc_attr( $aria_label ) . '"';
	}

	return '<span class="' . esc_attr( $class_attr ) . '" ' . $aria_attrs . '>'
		. $svgs[ $name ]
		. '</span>';
}
