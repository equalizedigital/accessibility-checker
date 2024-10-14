<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

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

		return html_entity_decode( $content );
	};

	return $prepare_strings( $string1 ) === $prepare_strings( $string2 );
}

/**
 * Parse CSS
 *
 * @param string $css String to parse.
 * @return array
 */
function edac_parse_css( $css ) {
	$css       = str_replace( '@charset "UTF-8";', '', $css );
	$css       = preg_replace( '%/\*(?:(?!\*/).)*\*/%s', ' ', $css );
	$css_array = []; // master array to hold all values.
	$element   = explode( '}', $css );
	foreach ( $element as $element ) {
		// get the name of the CSS element.
		$a_name = explode( '{', $element );
		$name   = $a_name[0];
		// get all the key:value pair styles.
		$a_styles = explode( ';', $element );
		// remove element name from first property element.
		$a_styles[0] = str_replace( $name . '{', '', $a_styles[0] );
		// loop through each style and split apart the key from the value.
		$count = count( $a_styles );
		for ( $a = 0; $a < $count; $a++ ) {
			if ( '' !== $a_styles[ $a ] ) {
				$a_styles[ $a ] = str_ireplace( 'https://', '//', $a_styles[ $a ] );
				$a_styles[ $a ] = str_ireplace( 'http://', '//', $a_styles[ $a ] );
				$a_key_value    = explode( ':', $a_styles[ $a ] );
				// build the master css array.
				if ( array_key_exists( 1, $a_key_value ) ) {
					$css_array[ trim( $name ) ][ trim( strtolower( $a_key_value[0] ) ) ] = trim( $a_key_value[1] );
				}
			}
		}
	}
	return $css_array;
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
 * Remove child nodes with simple dom
 *
 * @param simple_html_dom_node $parent_node The parent node.
 * @return string
 */
function edac_simple_dom_remove_child( simple_html_dom_node $parent_node ) {
	$parent_node->innertext = '';

	return $parent_node->save();
}

/**
 * Remove element from multidimensional array
 *
 * @param array  $items The multidimensional array.
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
 * Filter a multi-demensional array
 *
 * @param array  $items The multi-dimensional array.
 * @param string $index The index of the element.
 * @param string $value of the array.
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
 * Check if Gutenberg is Active
 *
 * @return boolean
 */
function edac_is_gutenberg_active() {
	$gutenberg    = false;
	$block_editor = false;

	$gutenberg = has_filter( 'replace_editor', 'gutenberg_init' );

	if ( version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' ) ) {
		// Block editor.
		$block_editor = true;
	}

	if ( ! $gutenberg && ! $block_editor ) {
		return false;
	}

	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
		return true;
	}

	return ( get_option( 'classic-editor-replace' ) === 'no-replace' );
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
 * Documentation Link.
 *
 * @param array $rule to get link from.
 * @return string markup for link.
 */
function edac_documentation_link( $rule ) {
	global $wp_version;
	$days_active = edac_days_active();

	if ( ! $rule['info_url'] || ! isset( $rule['slug'] ) ) {
		return '';
	}

	return add_query_arg(
		[
			'utm_source'       => 'accessibility-checker',
			'utm_medium'       => 'software',
			'utm_term'         => esc_attr( $rule['slug'] ),
			'utm_content'      => 'content-analysis',
			'utm_campaign'     => 'wordpress-general',
			'php_version'      => PHP_VERSION,
			'platform'         => 'wordpress',
			'platform_version' => $wp_version,
			'software'         => 'free',
			'software_version' => EDAC_VERSION,
			'days_active'      => $days_active,
		],
		$rule['info_url']
	);
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
 * String Get HTML
 *
 * @param string  $str string to parse.
 * @param boolean $lowercase lowercase.
 * @param boolean $force_tags_closed force tags closed.
 * @param string  $target_charset target charset.
 * @param boolean $strip_rn strip rn.
 * @param string  $default_br_text default br text.
 * @param string  $default_span_text default span text.
 * @return object|false
 */
function edac_str_get_html(
	$str,
	$lowercase = true,
	$force_tags_closed = true,
	$target_charset = DEFAULT_TARGET_CHARSET,
	$strip_rn = true,
	$default_br_text = DEFAULT_BR_TEXT,
	$default_span_text = DEFAULT_SPAN_TEXT
) {
	$dom = new EDAC_Dom(
		null,
		$lowercase,
		$force_tags_closed,
		$target_charset,
		$strip_rn,
		$default_br_text,
		$default_span_text
	);

	if ( empty( $str ) || strlen( $str ) > MAX_FILE_SIZE ) {
		$dom->clear();
		return false;
	}

	return $dom->load( $str, $lowercase, $strip_rn );
}

/**
 * Remove elements from the dom by css_selector
 *
 * @param simple_html_dom $dom .
 * @param array           $css_selectors array .
 * @return simple_html_dom
 */
function edac_remove_elements( $dom, $css_selectors = [] ) {

	if ( $dom ) {

		foreach ( $css_selectors as $css_selector ) {
			$elements = $dom->find( $css_selector );
			foreach ( $elements as $element ) {
				if ( null !== $element ) {
					$element->remove();
				}
			}
		}
	}

	return $dom;
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

	return $table_name;
}

/**
 * Replace CSS Variables with Value
 *
 * @param string $value string to replace.
 * @param array  $css_array css array.
 * @return string
 */
function edac_replace_css_variables( $value, $css_array ) {

	if ( stripos( $value, 'var(--' ) !== false ) {

		// replace strings.
		$value = str_replace( 'var(', '', $value );
		$value = str_replace( ')', '', $value );
		$value = str_replace( 'calc(', '', $value );

		// explode and loop through css vars.
		$values = explode( ',', $value );
		if ( is_array( $css_array ) ) {
			foreach ( $values as $value ) {

				// check for index in array.
				if ( ! isset( $css_array[':root'] ) ) {
					continue;
				}

				// check if is a css variable.
				if ( substr( $value, 0, 2 ) === '--' && array_key_exists( $value, $css_array[':root'] ) ) {
					$found_value = $css_array[':root'][ $value ];

					// if value found break loop.
					if ( $found_value ) {
						break;
					}
				} else {

					// if not a variable return value.
					$found_value = $value;
				}
			}
		}

		if ( ! empty( $found_value ) ) {
			return $found_value;
		}
	}
	return $value;
}

/**
 * Generates a nonce that expires after a specified number of seconds.
 *
 * @param string $secret secret.
 * @param int    $timeout_seconds The number of seconds after which the nonce expires.
 * @return string
 */
function edac_generate_nonce( $secret, $timeout_seconds = 120 ) {

	$length      = 10;
	$chars       = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
	$ll          = strlen( $chars ) - 1;
	$salt        = '';
	$salt_length = 0;

	while ( $salt_length < $length ) {
		$salt       .= $chars[ wp_rand( 0, $ll ) ];
		$salt_length = strlen( $salt );
	}

	$time     = time();
	$max_time = $time + $timeout_seconds;

	return $salt . ',' . $max_time . ',' . sha1( $salt . $secret . $max_time );
}

/**
 * Verifies if the nonce is valid and not expired.
 *
 * @param string $secret secret.
 * @param string $nonce nonce.
 * @return boolean
 */
function edac_is_valid_nonce( $secret, $nonce ) {
	if ( ! is_string( $nonce ) ) {
		return false;
	}
	$a = explode( ',', $nonce );
	if ( count( $a ) !== 3 ) {
		return false;
	}
	$salt     = $a[0];
	$max_time = (int) $a[1];
	$hash     = $a[2];
	$back     = sha1( $salt . $secret . $max_time );
	if ( $back !== $hash ) {
		return false;
	}
	if ( time() > $max_time ) {
		return false;
	}
	return true;
}

/**
 * Upcoming meetups in json format
 *
 * @param string  $meetup meetup name.
 * @param integer $count number of meetups to return.
 * @return json
 */
function edac_get_upcoming_meetups_json( $meetup, $count = 5 ) {

	$key    = 'upcoming_meetups__' . sanitize_title( $meetup ) . '__' . (int) $count;
	$output = get_transient( $key );

	if ( false === $output ) {

		$query_args = [
			'sign'       => 'true',
			'photo-host' => 'public',
			'page'       => (int) $count,
		];

		$request_uri = 'https://api.meetup.com/' . sanitize_title( $meetup ) . '/events';
		$request     = wp_remote_get( add_query_arg( $query_args, $request_uri ) ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- wp_remote_get needed to be compatible with all environments.

		if ( is_wp_error( $request ) || 200 !== (int) wp_remote_retrieve_response_code( $request ) ) {
			return;
		}

		$output = json_decode( wp_remote_retrieve_body( $request ) );
		if ( empty( $output ) ) {
			return;
		}

		set_transient( $key, $output, DAY_IN_SECONDS );
	}

	return $output;
}


/**
 * Upcoming meetups in html
 *
 * @param  string  $meetup meetup name.
 * @param  integer $count number of meetups to return.
 * @param  string  $heading heading level.
 * @return json
 */
function edac_get_upcoming_meetups_html( $meetup, $count = 5, $heading = '3' ) {

	$json = edac_get_upcoming_meetups_json( $meetup, $count );

	if ( empty( $json ) ) {
		return;
	}

	$html = '<ul class="edac-upcoming-meetup-list">';

	foreach ( $json as $event ) {
		$link_text = esc_html__( 'Attend Free', 'accessibility-checker' );

		$html .= '
		<li class="edac-upcoming-meetup-item edac-mb-3">
			<h' . esc_html( $heading ) . ' class="edac-upcoming-meetup-item-name">' . esc_html( $event->name ) . '</h' . esc_html( $heading ) . '>
			<div class="edac-upcoming-meetup-item-time edac-timestamp-to-local">' . ( (int) $event->time / 1000 ) . '</div>
			<a aria-label="' . esc_attr( $link_text . ': ' . $event->name ) . '" class="edac-upcoming-meetup-item-link" href="' . esc_url( $event->link ) . '">' . $link_text . '</a>
		</li>';
	}

	$html .= '</ul>';

	return $html;
}

/**
 * Return the first X number of root p or div tags from html
 *
 * @param  object  $html object to parse.
 * @param  integer $paragraph_count number of paragraphs to return.
 * @return string|boolean
 */
function edac_truncate_html_content( $html, $paragraph_count = 1 ) {

	$allowed_tags = [
		'div'    => [],
		'p'      => [],
		'span'   => [],
		'br'     => [],
		'hr'     => [],
		'strong' => [],
		'b'      => [],
		'em'     => [],
		'i'      => [],
	];

	$html = wp_kses( $html, $allowed_tags );

	// Create a new DOMDocument instance.
	$dom = new DOMDocument();
	$dom->loadHTML( $html );

	// Find the <body> element.
	$body_element = $dom->getElementsByTagName( 'body' )->item( 0 );

	if ( $body_element ) {

		$content = [];

		// Loop through the child nodes of the <body> element.
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMDocument uses camelCase.
		foreach ( $body_element->childNodes as $child_node ) {
			if ( 'p' === $child_node->nodeName || 'div' === $child_node->nodeName ) {
				$content[] = '<p>' . $child_node->textContent . '</p>';
			}
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		if ( count( $content ) > 0 ) {
			return implode(
				PHP_EOL,
				array_slice( $content, 0, $paragraph_count )
			);
		}
	}

	return false;
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
 * Get info from html that we need for calculating density
 *
 * @param string $html html to parse.
 * @return boolean|array
 */
function edac_get_body_density_data( $html ) {

	if ( $html && trim( $html ) !== '' ) {

		$density_dom = new simple_html_dom();
		$density_dom->load( $html );

		$body_element = $density_dom->find( 'body', 0 );

		if ( ! $body_element ) {
			return false;
		}

		// Remove the elements we shouldn't count.
		foreach ( $body_element->find( '.edac-highlight-panel,#wpadminbar,style,script' ) as $element ) {
			$element->remove();
		}

		if ( $body_element ) {

			$body_elements_count = edac_count_dom_descendants( $body_element );

			$body_content = preg_replace( '/[^A-Za-z0-9]/', '', $body_element->plaintext );

			return [
				$body_elements_count,
				strlen( $body_content ),
			];

		}
	}

	return false;
}


/**
 * Recursively count elements in a dom
 *
 * @param object $dom_elements dom elements.
 * @return int
 */
function edac_count_dom_descendants( $dom_elements ) {
	$count = 0;

	foreach ( $dom_elements->children() as $child ) {
		++$count;
		$count += edac_count_dom_descendants( $child ); // Recursively count descendants.
	}

	return $count;
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
 * Add a scheme to file it looks like a url but doesn't have one.
 *
 * @since 1.10.1
 *
 * @param  string $file The filename.
 * @param  string $site_protocol The site protocol. Default is 'https'.
 *
 * @return string The filename unchanged if it doesn't look like a URL, or with a scheme added if it does.
 */
function edac_url_add_scheme_if_not_existing( string $file, string $site_protocol = '' ): string {

	// if it starts with some valid scheme return unchanged.
	$valid_schemes = [ 'http', 'https', 'ftp', 'ftps', 'mailto', 'tel', 'file', 'data', 'irc', 'ssh', 'sftp' ];
	$start_of_file = substr( $file, 0, 6 );
	foreach ( $valid_schemes as $scheme ) {
		if ( str_starts_with( $start_of_file, $scheme ) ) {
			return $file;
		}
	}

	// if it starts with / followed by any alphanumeric assume it's a relative url.
	if ( preg_match( '/^\/[a-zA-Z0-9]/', $file ) ) {
		return $file;
	}

	// by this point it doesn't seem like a url or a relative path so make it into one.
	$file_location = ltrim( $file, '/' );
	$site_scheme   = ( ! empty( $site_protocol ) )
		? $site_protocol
		: ( is_ssl() ? 'https' : 'http' );

	return "{$site_scheme}://{$file_location}";
}

/**
 * Requests the headers of a URL to check if it exists.
 *
 * @since 1.10.1
 *
 * @param string $url the url to check.
 * @param int    $timeout the timeout in seconds. Default is 5.
 * @return bool
 */
function edac_url_exists( string $url, int $timeout = 5 ): bool {

	$response = wp_remote_head(
		$url,
		[
			'timeout' => $timeout,
		]
	);

	if (
		is_wp_error( $response ) ||
		( // Check if the response code is not in the 2xx range.
			wp_remote_retrieve_response_code( $response ) < 200 ||
			wp_remote_retrieve_response_code( $response ) > 299
		)
	) {
		return false;
	}

	return true;
}

/**
 * Get a file from local or remote source as a binary file handle.
 *
 * @since 1.10.1
 *
 * @param string $filename The file location, either local or a remote URL.
 * @return resource|bool The file binary string or false if the file could not be opened.
 */
function edac_get_file_opened_as_binary( string $filename ) {
	if (
		str_starts_with( $filename, 'http' ) ||
		preg_match( '/^\/[a-zA-Z0-9]/', $filename )
	) {
		$file = $filename;
	} else {
		$file       = edac_url_add_scheme_if_not_existing( $filename );
		$url_exists = edac_url_exists( $file );
	}

	// if this url doesn't exist, return false.
	if ( isset( $url_exists ) && false === $url_exists ) {
		return false;
	}

	try {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- path validated above.
		$fh = fopen( $file, 'rb' );
	} catch ( Exception $e ) {
		return false;
	}

	return $fh;
}

/**
 * Generate a summary statistic list item.
 *
 * @since 1.14.0
 *
 * @param string $item_class     The base CSS class for the list item.
 * @param int    $count     The count of items to display.
 * @param string $label      The translated label with count included.
 *
 * @return string The generated HTML list item.
 */
function edac_generate_summary_stat( string $item_class, int $count, string $label ): string {
	$has_error_class = ( $count > 0 ) ? ' has-errors' : '';

	return '
        <li class="edac-summary-stat ' . $item_class . $has_error_class . '" aria-label="' . $label . '">
            <div class="edac-panel-number">
                ' . $count . '
            </div>
            <div class="edac-panel-number-label">' . $label . '</div>
        </li>';
}

/**
 * Check if an element has an extension that matches the provided list.
 *
 * @since 1.15.0
 *
 * @param string $item A file path or URL to check.
 * @param array  $extensions An array of extensions to check for.
 *
 * @return bool True if the item has an extension that matches the list, false otherwise.
 */
function edac_is_item_using_matching_extension( string $item, array $extensions ): bool {
	$extension = pathinfo( $item, PATHINFO_EXTENSION );
	return in_array( '.' . $extension, $extensions, true );
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
		'software'         => defined( 'EDACP_KEY_VALID' ) && EDACP_KEY_VALID ? 'pro' : 'free',
		'software_version' => defined( 'EDACP_VERSION' ) ? EDACP_VERSION : EDAC_VERSION,
		'days_active'      => $days_active,
	];

	$query_args = array_merge( $query_defaults, $query_args );

	switch ( $type ) {
		case 'help':
			$base_link = trailingslashit( 'https://a11ychecker.com/help' . $args['help_id'] ?? '' );
			break;
		case 'pro':
		default:
			$base_link = 'https://equalizedigital.com/accessibility-checker/pricing/';
			break;
	}
	return add_query_arg( $query_args, $base_link );
}
