<?php

/**
 * Compare strings
 *
 * @param string $string1
 * @param string $string2
 * @return boolean
 */
function edac_compare_strings($string1, $string2)
{
	// text to remove
	$removeText = array();
	$removeText[] = __('permalink of ', 'edac');
	$removeText[] = __('permalink to ', 'edac');
	$removeText[] = __('&nbsp;', 'edac');

	$string1 = strtolower($string1);
	$string1 = str_ireplace($removeText, '', $string1);
	$string1 = strip_tags($string1);
	$string1 = trim($string1, " \t\n\r\0\x0B\xC2\xA0");
	$string1 = html_entity_decode($string1);

	$string2 = strtolower($string2);
	$string2 = str_ireplace($removeText, '', $string2);
	$string2 = strip_tags($string2);
	$string2 = trim($string2, " \t\n\r\0\x0B\xC2\xA0");
	$string2 = html_entity_decode($string2);

	if ($string1 == $string2) {
		return 1;
	} else {
		return 0;
	}

}

/**
 * Parse CSS
 *
 * @param string $css
 * @return array
 */
function edac_parse_css($css)
{	
	$css = str_replace('@charset "UTF-8";','',$css);
	$css = preg_replace("%/\*(?:(?!\*/).)*\*/%s", " ", $css);
	$css_array = array(); // master array to hold all values
	$element = explode('}', $css);
	foreach ($element as $element) {
		// get the name of the CSS element
		$a_name = explode('{', $element);
		$name = $a_name[0];
		// get all the key:value pair styles
		$a_styles = explode(';', $element);
		// remove element name from first property element
		$a_styles[0] = str_replace($name . '{', '', $a_styles[0]);
		// loop through each style and split apart the key from the value
		$count = count($a_styles);
		//$counter = 0;
		for ($a = 0; $a < $count; $a++) {
			if ($a_styles[$a] != '') {
				$a_styles[$a] = str_ireplace('https://', '//', $a_styles[$a]);
				$a_styles[$a] = str_ireplace('http://', '//', $a_styles[$a]);
				$a_key_value = explode(':', $a_styles[$a]);
				// build the master css array
				if (array_key_exists(1, $a_key_value)) {
					//$css_array[trim($counter . $name)][trim(strtolower($a_key_value[0]))] = trim($a_key_value[1]);
					$css_array[trim($name)][trim(strtolower($a_key_value[0]))] = trim($a_key_value[1]);
				}

			}
			//$counter++;
		}
	}
	return $css_array;
}

/**
 * Check if plugin is installed by getting all plugins from the plugins dir
 *
 * @param $plugin_slug
 *
 * @return bool
 */
function edac_check_plugin_installed( $plugin_slug ) {
	$installed_plugins = get_plugins();

	return array_key_exists( $plugin_slug, $installed_plugins ) || in_array( $plugin_slug, $installed_plugins, true );
}

/**
 * Check if plugin is installed
 *
 * @param string $plugin_slug
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
 * @param int $number
 * @return string
 */
function edac_ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}

/**
 * Log
 *
 * @param mixed $message
 * @return void
 */
function edac_log($message){
	$edac_log = dirname(__DIR__) . '/edac_log.log';
	if (is_array($message)) {
		$message = print_r($message, true);
	}
	if (file_exists($edac_log)) {
		$file = fopen($edac_log, 'a');
		fwrite($file, $message . "\n");
	} else {
		$file = fopen($edac_log, 'w');
		fwrite($file, $message . "\n");
	}
	fclose($file);
}

/**
 * Remove child nodes with simple dom
 *
 * @param $parentNode
 * @return string
 */
function edac_simple_dom_remove_child(simple_html_dom_node $parentNode) {
	$parentNode->innertext = '';
	$error = $parentNode->save();
	return $error;
}

/**
 * remove element from multidimensional array
 *
 * @param array $array
 * @param $key
 * @param $value
 * @return array
 */
function edac_remove_element_with_value($array, $key, $value){
	foreach($array as $subKey => $subArray){
		 if($subArray[$key] == $value){
			  unset($array[$subKey]);
		 }
	}
	return $array;
}

/**
 * Filter a multi-demensional array
 *
 * @param array $array
 * @param string $index
 * @param string $value
 * @return void
 */
function edac_filter_by_value($array, $index, $value){
	if(is_array($array) && count($array)>0) 
	{
		foreach(array_keys($array) as $key){
			$temp[$key] = $array[$key][$index];
			
			if ($temp[$key] == $value){
				$newarray[$key] = $array[$key];
			}
		}
	  }
  return array_values($newarray);
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
 * @return void
 */
function edac_days_active(){
	$activation_date = get_option('edac_activation_date');
	if($activation_date){
		$diff = strtotime($activation_date) - strtotime(date('Y-m-d H:i:s'));
		$days_active = abs(round($diff / 86400));
	}else{
		$days_active = null;
	}
	return $days_active;
}

/**
 * Custom Post Types
 *
 * @return array
 */
function edac_custom_post_types(){
	$args = array(
		'public'   => true,
		'_builtin' => false,
	);

	$output = 'names'; // names or objects, note names is the default
	$operator = 'and'; // 'and' or 'or'

	$post_types = get_post_types( $args, $output, $operator ); 

	return $post_types;
}

/**
 * Available Post Types
 *
 * @return array
 */
function edac_post_types(){

	$post_types = ['post','page'];

	// filter post types
	if(has_filter('edac_filter_post_types')) {
		$post_types = apply_filters('edac_filter_post_types', $post_types);
	}

	// remove duplicates
	$post_types = array_unique($post_types);

	// validate post types
	foreach ($post_types as $key => $post_type) {
		if(!post_type_exists($post_type)) unset($post_types[$key]);
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
	if ( isset( $_POST['edac-action'] ) ) {
		do_action( 'edac_' . $_POST['edac-action'], $_POST );
	}

	if ( isset( $_GET['edac-action'] ) ) {
		do_action( 'edac_' . $_GET['edac-action'], $_GET );
	}
}