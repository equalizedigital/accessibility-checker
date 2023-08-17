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
	// text to remove.
	$remove_text   = array();
	$remove_text[] = __( 'permalink of ', 'edac' );
	$remove_text[] = __( 'permalink to ', 'edac' );
	$remove_text[] = __( '&nbsp;', 'edac' );

	$string1 = strtolower( $string1 );
	$string1 = str_ireplace( $remove_text, '', $string1 );
	$string1 = strip_tags( $string1 );
	$string1 = trim( $string1, " \t\n\r\0\x0B\xC2\xA0" );
	$string1 = html_entity_decode( $string1 );

	$string2 = strtolower( $string2 );
	$string2 = str_ireplace( $remove_text, '', $string2 );
	$string2 = strip_tags( $string2 );
	$string2 = trim( $string2, " \t\n\r\0\x0B\xC2\xA0" );
	$string2 = html_entity_decode( $string2 );

	if ( $string1 === $string2 ) {
		return 1;
	} else {
		return 0;
	}

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
	$css_array = array(); // master array to hold all values.
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
 * Check if plugin is installed
 *
 * @param string $plugin_slug Slug of the plugin.
 *
 * @return bool
 */
function edac_check_plugin_active( $plugin_slug ) {
	if ( is_plugin_active( $plugin_slug ) ) {
		return true;
	}

	return false;
}

/**
 * Convert cardinal number into ordinal number
 *
 * @param int $number Number to make ordinal.
 * @return string
 */
function edac_ordinal( $number ) {
	$ends = array( 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' );
	if ( ( ( $number % 100 ) >= 11 ) && ( ( $number % 100 ) <= 13 ) ) {
		return $number . 'th';
	} else {
		return $number . $ends[ $number % 10 ];
	}
}

/**
 * Log
 *
 * @param mixed $message Log Message.
 * @return void
 */
function edac_log( $message ) {
	$edac_log = dirname( __DIR__ ) . '/edac_log.log';
	if ( is_array( $message ) ) {
		$message = print_r( $message, true );
	}
	if ( file_exists( $edac_log ) ) {
		$file = fopen( $edac_log, 'a' );
		fwrite( $file, $message . "\n" );
	} else {
		$file = fopen( $edac_log, 'w' );
		fwrite( $file, $message . "\n" );
	}
	fclose( $file );
}

/**
 * Remove child nodes with simple dom
 *
 * @param simple_html_dom_node $parent_node The parent node.
 * @return string
 */
function edac_simple_dom_remove_child( simple_html_dom_node $parent_node ) {
	$parent_node->innertext = '';

	$error = $parent_node->save();
	return $error;
}

/**
 * Remove element from multidimensional array
 *
 * @param array  $array The multidimensional array.
 * @param string $key The key of the element.
 * @param string $value The value of the element.
 * @return array
 */
function edac_remove_element_with_value( $array, $key, $value ) {
	foreach ( $array as $sub_key => $sub_array ) {
		if ( $sub_array[ $key ] === $value ) {
			unset( $array[ $sub_key ] );
		}
	}
	return $array;
}

/**
 * Filter a multi-demensional array
 *
 * @param array  $array The multi-dimensional array.
 * @param string $index The index of the element.
 * @param string $value of the array.
 * @return array
 */
function edac_filter_by_value( $array, $index, $value ) {
	if ( is_array( $array ) && count( $array ) > 0 ) {
		foreach ( array_keys( $array ) as $key ) {
			$temp[ $key ] = $array[ $key ][ $index ];

			if ( $temp[ $key ] === $value ) {
				$newarray[ $key ] = $array[ $key ];
			}
		}
	}

	if ( isset( $newarray ) && is_array( $newarray ) && count( $newarray ) ) {
		return array_values( $newarray );
	} else {
		return null;
	}
	
}

/**
 * Check if Gutenberg is Active
 *
 * @return boolean
 */
function edac_is_gutenberg_active() {
	$gutenberg    = false;
	$block_editor = false;

	if ( has_filter( 'replace_editor', 'gutenberg_init' ) ) {
		// Gutenberg is installed and activated.
		$gutenberg = true;
	}

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

	$use_block_editor = ( get_option( 'classic-editor-replace' ) === 'no-replace' );

	return $use_block_editor;
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

		$days_active = abs( round( $diff / 86400 ) );
	} else {
		$days_active = null;
	}
	return $days_active;
}

/**
 * Custom Post Types
 *
 * @return array
 */
function edac_custom_post_types() {
	$args = array(
		'public'   => true,
		'_builtin' => false,
	);

	$output   = 'names'; // names or objects, note names is the default.
	$operator = 'and'; // 'and' or 'or'

	$post_types = get_post_types( $args, $output, $operator );

	return $post_types;
}

/**
 * Available Post Types
 *
 * @return array
 */
function edac_post_types() {

	$post_types = array( 'post', 'page' );

	// filter post types.
	if ( has_filter( 'edac_filter_post_types' ) ) {
		$post_types = apply_filters( 'edac_filter_post_types', $post_types );
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
 * Processes all EDAC actions sent via POST and GET by looking for the 'edac-action'
 * request and running do_action() to call the function
 *
 * @return void
 */
function edac_process_actions() {

	// if this fails, check_admin_referer() will automatically print a "failed" page and die.
	if ( ! empty( $_POST ) && isset( $_POST['edac_download_sysinfo_nonce'] ) && check_admin_referer( 'edac_download_sysinfo', 'edac_download_sysinfo_nonce' ) ) {

		if ( isset( $_POST['edac-action'] ) ) {
			do_action( 'edac_' . $_POST['edac-action'], $_POST );
		}

		if ( isset( $_GET['edac-action'] ) ) {
			do_action( 'edac_' . $_GET['edac-action'], $_GET );
		}
	}

}

/**
 * String Get HTML
 *
 * @param string $str
 * @param boolean $lowercase
 * @param boolean $forceTagsClosed
 * @param string $target_charset
 * @param boolean $stripRN
 * @param string $defaultBRText
 * @param string $defaultSpanText
 * @return string
 */
function edac_str_get_html (
	$str,
	$lowercase = true,
	$forceTagsClosed = true,
	$target_charset = DEFAULT_TARGET_CHARSET,
	$stripRN = true,
	$defaultBRText = DEFAULT_BR_TEXT,
	$defaultSpanText = DEFAULT_SPAN_TEXT){
	$dom = new EDAC_Dom(
		null,
		$lowercase,
		$forceTagsClosed,
		$target_charset,
		$stripRN,
		$defaultBRText,
		$defaultSpanText
	);

	if (empty($str) || strlen($str) > MAX_FILE_SIZE) {
		$dom->clear();
		return false;
	}

	return $dom->load($str, $lowercase, $stripRN);
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
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $table_name)) {
        // Invalid table name
        return null;
    }

    // Verify that the table actually exists in the database.
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Table does not exist
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
		} else {
			return $value;
		}
	} else {
		return $value;
	}
}


/**
 * Upcoming meetups in json format
 *
 * @param string $meetup
 * @param integer $count
 * @return json
 */
function edac_get_upcoming_meetups_json( $meetup, $count = 5 ) {

    $key = 'upcoming_meetups__' . sanitize_title( $meetup ) . '_' . intval( $count );
    $output = get_transient( $key );
    if( false === $output ) {

        $query_args = array(
            'sign' => 'true',
            'photo-host' => 'public',
            'page' => intval( $count ),
        );

        $request_uri = 'https://api.meetup.com/' . sanitize_title( $meetup ) . '/events';
        $request = wp_remote_get( add_query_arg( $query_args, $request_uri ) );

        if( is_wp_error( $request ) || '200' != wp_remote_retrieve_response_code( $request ) )
            return;

        $output = json_decode( wp_remote_retrieve_body( $request ) );
        if( empty( $output ) )
            return;

	
        set_transient( $key, $output, DAY_IN_SECONDS );
    }

    return $output;
}


function edac_get_upcoming_meetups_html( $meetup, $count = 5 ) {

	$json = edac_get_upcoming_meetups_json( $meetup, $count );

	if(empty($json)){
		return;
	}

	$allowed_tags = array(
		'div' => array(),
		'p' => array(),
		'span' => array(),
		'br' => array(),
		'hr' => array(),
		'strong' => array(),
		'b' => array(),
		'em' => array(),
		'i' => array(),
	);

	$html = '<ul class="edac-upcoming-meetup-list">';
	
	foreach($json as $event){

		$desc = wp_kses( $event->description, $allowed_tags );

		$html .= '
		<li class="edac-upcoming-meetup-item">
			<details>
				<summary>
					<div class="edac-upcoming-meetup-item-name">' . esc_html( $event->name ) . '</div>
					<div class="edac-upcoming-meetup-item-time edac-timestamp-to-local">' . (intval( $event->time ) / 1000) . '</div>
				</summary>
				<div class="edac-upcoming-meetup-item-desc"> ' . $desc  . '</div>			
			</details>
			<a class="edac-upcoming-meetup-item-link" href="' . esc_url($event->link)  . '">Attend Free</a>
		</li>';
	}

	$html .= '</ul>';

	return $html;
			
}


/**
 * Calculate the issue density
 *
 * @param [type] $issue_count
 * @param [type] $element_count
 * @param [type] $content_length
 * @return void
 */
function edac_get_issue_density( $issue_count, $element_count, $content_length){

	$element_weight = .8;
	$content_weight = .2;

	$elements_per_error = $issue_count / $element_count;
	$chars_per_error = $issue_count / $content_length;
	
	$score = (($elements_per_error * $element_weight) + ($chars_per_error * $content_weight));
	
	return round($score * 100,2);

}
	

/**
 * Get info from a dom that we need for calculating density
 *
 * @param [type] $dom
 * @return void
 */
function edac_get_body_density_data( $dom ){

	$body_element = $dom->find('body', 0);

	//Remove the elements we shouldn't count
	foreach ($body_element->find('.edac-highlight-panel,#wpadminbar,style,script') as $element) {
		$element->remove();
	}
	
	if($body_element){
	
		$body_elements_count = edac_count_dom_descendants($body_element);
		
		$body_content = preg_replace("/[^A-Za-z0-9]/", '', $body_element->plaintext);
		
		return [
			$body_elements_count,
			strlen($body_content),
		];
	
	}	

	return false;
}

/**
 * Recursively count elements in a dom
 *
 * @param [type] $element
 * @return void
 */
function edac_count_dom_descendants($dom_elements) {
    $count = 0;

    foreach ($dom_elements->children() as $child) {
        $count++; // Count the current child
        $count += edac_count_dom_descendants($child); // Recursively count descendants
    }

    return $count;
}