<?php

/**
 *
 * @link              https://a11ychecker.com
 * @since             1.0.0
 * @package           Accessibility_Checker
 *
 * @wordpress-plugin
 * Plugin Name:       Accessibility Checker
 * Plugin URI:        https://a11ychecker.com
 * Description:       Audit and check your website for accessibility before you hit publish. In-post accessibility scanner and guidance.
 * Version:           1.2.7
 * Author:            Equalize Digital
 * Author URI:        https://equalizedigital.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       edac
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Freemius
 */
if ( ! function_exists( 'edac_fs' ) ) {
    // Create a helper function for easy SDK access.
    function edac_fs() {
        global $edac_fs;

        if ( ! isset( $edac_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $edac_fs = fs_dynamic_init( array(
                'id'                  => '7322',
                'slug'                => 'accessibility-checker',
                'type'                => 'plugin',
                'public_key'          => 'pk_5146b841819550deb8874ca70bc89',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'accessibility_checker',
                    'first-path'     => 'admin.php?page=accessibility_checker',
                    'account'        => false,
                    'contact'        => false,
                    'support'        => false,
                ),
            ) );
        }

        return $edac_fs;
    }

    // Init Freemius.
    edac_fs();
    // Signal that SDK was initiated.
    do_action( 'edac_fs_loaded' );
}

/**
 * Setup constants.
 */

// Current plugin version
if ( ! defined( 'EDAC_VERSION' ) ) {
	define( 'EDAC_VERSION', '1.2.7' );
}

// Current database version
if ( ! defined( 'EDAC_DB_VERSION' ) ) {
	define( 'EDAC_DB_VERSION', '1.0.2' );
}

// Plugin Folder Path
if ( ! defined( 'EDAC_PLUGIN_DIR' ) ) {
	define( 'EDAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL
if ( ! defined( 'EDAC_PLUGIN_URL' ) ) {
	define( 'EDAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin Root File
if ( ! defined( 'EDAC_PLUGIN_FILE' ) ) {
	define( 'EDAC_PLUGIN_FILE', __FILE__ );
}

// Accessibility New Window Warning Plugin Active
if ( ! defined( 'EDAC_ANWW_ACTIVE' ) ) {
	if(is_plugin_active('accessibility-new-window-warnings/accessibility-new-window-warnings.php')){
		define( 'EDAC_ANWW_ACTIVE', true );
	}else{
		define( 'EDAC_ANWW_ACTIVE', false );
	}
}

// Enable EDAC_DEBUG mode
if ( ! defined( 'EDAC_DEBUG' ) ) {
	define( 'EDAC_DEBUG', false );
}

// SVG Icons
define( 'EDAC_SVG_IGNORE_ICON', '<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 20010904//EN"
 "http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
 width="568.000000pt" height="568.000000pt" viewBox="0 0 568.000000 568.000000"
 preserveAspectRatio="xMidYMid meet">

<g transform="translate(0.000000,568.000000) scale(0.100000,-0.100000)"
fill="#000000" stroke="none">
<path d="M2558 5585 c-289 -49 -525 -173 -735 -387 -166 -168 -277 -338 -363
-557 -89 -224 -118 -380 -130 -696 -17 -428 -40 -640 -106 -964 -86 -426 -235
-825 -399 -1072 -37 -55 -104 -136 -155 -187 -103 -102 -135 -160 -125 -222
12 -73 68 -126 200 -191 l79 -39 27 27 c15 16 691 842 1504 1837 l1477 1809
-38 54 c-292 424 -793 662 -1236 588z"/>
<path d="M4508 5323 c-36 -43 -930 -1138 -1988 -2433 -1057 -1295 -1931 -2364
-1942 -2376 -18 -21 -18 -21 112 -127 71 -59 134 -107 140 -107 5 0 883 1070
1952 2377 1068 1308 1970 2412 2005 2454 l63 76 -130 107 c-72 58 -134 106
-139 106 -5 0 -38 -35 -73 -77z"/>
<path d="M3013 2494 l-1102 -1349 912 -3 c585 -2 971 1 1077 8 590 39 965 171
995 350 10 62 -22 120 -125 222 -164 163 -300 406 -406 726 -132 397 -207 783
-237 1221 l-12 173 -1102 -1348z"/>
<path d="M2220 939 c0 -32 73 -142 135 -204 112 -112 241 -165 400 -165 159 0
288 53 400 165 63 62 135 171 135 204 0 8 -150 11 -535 11 -385 0 -535 -3
-535 -11z"/>
</g>
</svg>');

/**
 * Plugin Activation & Deactivation
 */
register_activation_hook( __FILE__, 'edac_activation');
register_deactivation_hook( __FILE__, 'edac_deactivation' );

/**
 * Define file path and basename
 */
$edac_plugin_directory = __FILE__;
$edac_plugin_basename = plugin_basename( __FILE__ );

/**
 * add simple dom support (need to over ride max file size, if clashes with another install of simple dom there the max file size will be dependednt upon that installation)
 */
if(!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 6000000);
if(!class_exists ('simple_html_dom')) {
	include_once(plugin_dir_path( __FILE__ ).'includes/simplehtmldom/simple_html_dom.php');
	include_once(plugin_dir_path( __FILE__ ).'includes/classes/class_edac_dom.php');
}

/**
 * Include TextStatistics
 */
include_once(plugin_dir_path( __FILE__ ).'includes/TextStatistics/Maths.php');
include_once(plugin_dir_path( __FILE__ ).'includes/TextStatistics/Pluralise.php');
include_once(plugin_dir_path( __FILE__ ).'includes/TextStatistics/Resource.php');
include_once(plugin_dir_path( __FILE__ ).'includes/TextStatistics/Syllables.php');
include_once(plugin_dir_path( __FILE__ ).'includes/TextStatistics/Text.php');
include_once(plugin_dir_path( __FILE__ ).'includes/TextStatistics/TextStatistics.php');
use DaveChild\TextStatistics as TS;

/**
 * Import Resources
 */
require_once(plugin_dir_path( __FILE__ ).'includes/activation.php');
require_once(plugin_dir_path( __FILE__ ).'includes/deactivation.php');
require_once(plugin_dir_path( __FILE__ ).'includes/helper-functions.php');
require_once(plugin_dir_path( __FILE__ ).'includes/enqueue-scripts.php');
require_once(plugin_dir_path( __FILE__ ).'includes/meta-boxes.php');
require_once(plugin_dir_path( __FILE__ ).'includes/options-page.php');
require_once(plugin_dir_path( __FILE__ ).'includes/validate.php');
require_once(plugin_dir_path( __FILE__ ).'includes/insert.php');
require_once(plugin_dir_path( __FILE__ ).'includes/purge.php');
require_once(plugin_dir_path( __FILE__ ).'includes/system-info.php');

/**
 * Filters and Actions
 */
add_action( 'admin_enqueue_scripts', 'edac_admin_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'edac_admin_enqueue_styles' );
add_action( 'admin_init', 'edac_update_database', 10 );
add_action( 'add_meta_boxes', 'edac_register_meta_boxes' );
add_action( 'admin_menu','edac_add_options_page' );
add_action( 'admin_init', 'edac_register_setting' );
add_action( 'admin_head','edac_post_on_load' );
add_filter( 'save_post', 'edac_save_post', 10, 3 );
add_action( 'wp_ajax_edac_summary_ajax', 'edac_summary_ajax' );
add_action( 'wp_ajax_edac_details_ajax', 'edac_details_ajax' );
add_action( 'wp_ajax_edac_readability_ajax', 'edac_readability_ajax' );
add_action( 'wp_ajax_edac_insert_ignore_data', 'edac_insert_ignore_data' );
add_action( 'wp_ajax_edac_update_simplified_summary', 'edac_update_simplified_summary' );
add_filter( 'the_content', 'edac_output_simplified_summary' );
add_filter( 'wp_footer', 'edac_output_accessibility_statement' );
add_action( 'wp_trash_post', 'edac_delete_post' );
add_action( 'pre_get_posts', 'edac_show_draft_posts' );
add_action( 'admin_init', 'edac_process_actions' );
add_action( 'edac_download_sysinfo', 'edac_tools_sysinfo_download' );
if(edac_check_plugin_active('oxygen/functions.php')){
	add_action( 'added_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
	add_action( 'updated_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
}
add_action( 'admin_init', 'edac_anww_update_post_meta');
add_action( 'admin_notices', 'edac_review_notice');
add_action( 'wp_ajax_edac_review_notice_ajax', 'edac_review_notice_ajax' );

/**
 * Create/Update database
 *
 * @return void
 */
function edac_update_database(){
	
	if(get_option('edac_db_version') == EDAC_DB_VERSION) return;

	global $wpdb;
	
	$table_name = $wpdb->prefix . "accessibility_checker";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		postid bigint(20) NOT NULL,
		siteid text NOT NULL,
		type text NOT NULL,
		rule text NOT NULL,
		ruletype text NOT NULL,
		object mediumtext NOT NULL,
		recordcheck mediumint(9) NOT NULL,
		created timestamp NOT NULL default CURRENT_TIMESTAMP,
		user bigint(20) NOT NULL,
		ignre mediumint(9) NOT NULL,
		ignre_global mediumint(9) NOT NULL,
		ignre_user bigint(20) NULL,
		ignre_date timestamp NULL,
		ignre_comment mediumtext NULL,
		UNIQUE KEY id (id)
	  ) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	
	// Update database version option
	$option_name = 'edac_db_version' ;
	$new_value = EDAC_DB_VERSION;
	if ( get_option( $option_name ) !== false ) {
		update_option( $option_name, $new_value );
	} else {
		add_option( $option_name, $new_value);
	}

}

/**
 * Register Rules
 *
 * @return void
 */
function edac_register_rules(){
	$rules = [];
	
	// img_alt_missing
	array_push($rules, [
		'title' => 'Image Missing Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help1927',
		'slug'  => 'img_alt_missing',
		'rule_type' => 'error',
	]);

	// img_alt_empty
	array_push($rules, [
		'title' => 'Image Empty Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help4991',
		'slug'  => 'img_alt_empty',
		'rule_type' => 'warning',
	]);
	
	// img_alt_invalid
	array_push($rules, [
		'title' => 'Low-quality Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help1977',
		'slug'  => 'img_alt_invalid',
		'rule_type' => 'warning',
	]);
	
	// img_linked_alt_missing
	array_push($rules, [
		'title' => 'Linked Image Missing Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help1930',
		'slug'  => 'img_linked_alt_missing',
		'rule_type' => 'error',
	]);

	// img_linked_alt_empty
	array_push($rules, [
		'title' => 'Linked Image Empty Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help1930',
		'slug'  => 'img_linked_alt_empty',
		'rule_type' => 'error',
	]);

	// img_alt_redundant
	array_push($rules, [
		'title' => 'Duplicate Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help1976',
		'slug'  => 'img_alt_redundant',
		'rule_type' => 'warning',
	]);
	
	// incorrect_heading_order
	array_push($rules, [
		'title' => 'Incorrect Heading Order',
		'info_url'  => 'https://a11ychecker.com/help1940',
		'slug'  => 'incorrect_heading_order',
		'rule_type' => 'error',
	]);

	// iframe_missing_title
	array_push($rules, [
		'title' => 'iFrame Missing Title',
		'info_url'  => 'https://a11ychecker.com/help1953',
		'slug'  => 'iframe_missing_title',
		'rule_type' => 'error',
	]);

	// missing_headings
	array_push($rules, [
		'title' => 'Missing Subheadings',
		'info_url'  => 'https://a11ychecker.com/help1967',
		'slug'  => 'missing_headings',
		'rule_type' => 'warning',
	]);

	// text_justified
	array_push($rules, [
		'title' => 'Text Justified',
		'info_url'  => 'https://a11ychecker.com/help1980',
		'slug'  => 'text_justified',
		'rule_type' => 'warning',
	]);

	// link_blank
	array_push($rules, [
		'title' => 'Link Opens New Window or Tab',
		'info_url'  => 'https://a11ychecker.com/help1982',
		'slug'  => 'link_blank',
		'rule_type' => 'warning',
	]);

	// image_map_missing_alt_text
	array_push($rules, [
		'title' => 'Image Map Missing Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help1938',
		'slug'  => 'image_map_missing_alt_text',
		'rule_type' => 'error',
	]);

	//tab_order_modified
	array_push($rules, [
		'title' => 'Tab Order Modified',
		'info_url'  => 'https://a11ychecker.com/help1974',
		'slug'  => 'tab_order_modified',
		'rule_type' => 'warning',
	]);

	//empty_heading_tag
	array_push($rules, [
		'title' => 'Empty Heading Tag',
		'info_url'  => 'https://a11ychecker.com/help1957',
		'slug'  => 'empty_heading_tag',
		'rule_type' => 'error',
	]);

	//empty_link
	array_push($rules, [
		'title' => 'Empty Link',
		'info_url'  => 'https://a11ychecker.com/help4108',
		'slug'  => 'empty_link',
		'rule_type' => 'error',
	]);

	//empty_button
	array_push($rules, [
		'title' => 'Empty Button',
		'info_url'  => 'https://a11ychecker.com/help1960',
		'slug'  => 'empty_button',
		'rule_type' => 'error',
	]);

	//img_alt_long
	array_push($rules, [
		'title' => 'Image Long Alternative Text',
		'info_url'  => 'https://a11ychecker.com/help1966',
		'slug'  => 'img_alt_long',
		'rule_type' => 'warning',
	]);

	//aria_hidden
	array_push($rules, [
		'title' => 'ARIA hidden',
		'info_url'  => 'https://a11ychecker.com/help1979',
		'slug'  => 'aria_hidden',
		'rule_type' => 'warning',
	]);

	//empty table header
	array_push($rules, [
		'title' => 'Empty Table Header',
		'info_url'  => 'https://a11ychecker.com/help1963',
		'slug'  => 'empty_table_header',
		'rule_type' => 'error',
	]);

	//link_ms_office_file
	array_push($rules, [
		'title' => 'Link to MS Office File',
		'info_url'  => 'https://a11ychecker.com/help1970',
		'slug'  => 'link_ms_office_file',
		'rule_type' => 'warning',
	]);

	//link_pdf
	array_push($rules, [
		'title' => 'Link to PDF',
		'info_url'  => 'https://a11ychecker.com/help1972',
		'slug'  => 'link_pdf',
		'rule_type' => 'warning',
	]);

	//link_non_html_file
	array_push($rules, [
		'title' => 'Link to Non-HTML File',
		'info_url'  => 'https://a11ychecker.com/help1973',
		'slug'  => 'link_non_html_file',
		'rule_type' => 'warning',
	]);

	//long_description_invalid
	array_push($rules, [
		'title' => 'Long Description Invalid',
		'info_url'  => 'https://a11ychecker.com/help1948',
		'slug'  => 'long_description_invalid',
		'rule_type' => 'error',
	]);
	
	//Empty Form Label
	array_push($rules, [
		'title' => 'Empty Form Label',
		'info_url'  => 'https://a11ychecker.com/help4109',
		'slug'  => 'empty_form_label',
		'rule_type' => 'error',
	]);

	//Missing Form Label
	array_push($rules, [
		'title' => 'Missing Form Label',
		'info_url'  => 'https://a11ychecker.com/help1949',
		'slug'  => 'missing_form_label',
		'rule_type' => 'error',
	]);

	// link_ambiguous_text
	array_push($rules, [
		'title' => 'Ambiguous Anchor Text',
		'info_url'  => 'https://a11ychecker.com/help1944',
		'slug'  => 'link_ambiguous_text',
		'rule_type' => 'error',
	]);

	// underlined_text
	array_push($rules, [
		'title' => 'Underlined Text',
		'info_url'  => 'https://a11ychecker.com/help1978',
		'slug'  => 'underlined_text',
		'rule_type' => 'warning',
	]);

	// broken_skip_link
	array_push($rules, [
		'title' => 'Broken Skip or Anchor Link',
		'info_url'  => 'https://a11ychecker.com/help1962',
		'slug'  => 'broken_skip_anchor_link',
		'rule_type' => 'error',
	]);

	// missing_table_header
	array_push($rules, [
		'title' => 'Missing Table Header',
		'info_url'  => 'https://a11ychecker.com/help1963',
		'slug'  => 'missing_table_header',
		'rule_type' => 'error',
	]);

	// duplicate_form_label
	array_push($rules, [
		'title' => 'Duplicate Form Label',
		'info_url'  => 'https://a11ychecker.com/help1954',
		'slug'  => 'duplicate_form_label',
		'rule_type' => 'error',
	]);

	// text_small
	array_push($rules, [
		'title' => 'Text Too Small',
		'info_url'  => 'https://a11ychecker.com/help1975',
		'slug'  => 'text_small',
		'rule_type' => 'warning',
	]);

	// possible_heading
	array_push($rules, [
		'title' => 'Possible Heading',
		'info_url'  => 'https://a11ychecker.com/help1969',
		'slug'  => 'possible_heading',
		'rule_type' => 'warning',
	]);

	// text_blinking_scrolling
	array_push($rules, [
		'title' => 'Blinking or Scrolling Content',
		'info_url'  => 'https://a11ychecker.com/help1965',
		'slug'  => 'text_blinking_scrolling',
		'rule_type' => 'error',
	]);

	// color_contrast_failure
	array_push($rules, [
		'title' => 'Insufficient Color Contrast',
		'info_url'  => 'https://a11ychecker.com/help1983',
		'slug'  => 'color_contrast_failure',
		'rule_type' => 'error',
	]);

	// missing transcript
	array_push($rules, [
		'title' => 'Missing Transcript',
		'info_url'  => 'https://a11ychecker.com/help1947',
		'slug'  => 'missing_transcript',
		'rule_type' => 'error',
	]);

	// broken_aria_reference
	array_push($rules, [
		'title' => 'Broken ARIA Reference',
		'info_url'  => 'https://a11ychecker.com/help1956',
		'slug'  => 'broken_aria_reference',
		'rule_type' => 'error',
	]);

	// missing_lang_attr
	array_push($rules, [
		'title' => 'Missing Language Declaration',
		'info_url'  => 'https://a11ychecker.com/help4429',
		'slug'  => 'missing_lang_attr',
		'rule_type' => 'error',
	]);

	// img_animated_gif
	array_push($rules, [
		'title' => 'Image Animated GIF',
		'info_url'  => 'https://a11ychecker.com/help4428',
		'slug'  => 'img_animated_gif',
		'rule_type' => 'warning',
	]);

	// video_present
	array_push($rules, [
		'title' => 'A Video is Present',
		'info_url'  => 'https://a11ychecker.com/help4414',
		'slug'  => 'video_present',
		'rule_type' => 'warning',
	]);

	// slider_present
	array_push($rules, [
		'title' => 'A Slider is Present',
		'info_url'  => 'https://a11ychecker.com/help3264',
		'slug'  => 'slider_present',
		'rule_type' => 'warning',
	]);

	
	// missing_title
	array_push($rules, [
		'title' => 'Missing Title',
		'info_url'  => 'https://a11ychecker.com/help4431',
		'slug'  => 'missing_title',
		'rule_type' => 'error',
	]);

	// filter rules
	if(has_filter('edac_filter_register_rules')) {
		$rules = apply_filters('edac_filter_register_rules', $rules);
	}

	return $rules;
}

/**
 * Include Rules
 */
$rules = edac_register_rules();
if($rules){
	foreach ($rules as $rule) {
		if($rule['slug']){
			require_once(plugin_dir_path( __FILE__ ).'includes/rules/'.$rule['slug'].'.php');
		}
	}
}

/**
 * Summary Ajax
 *
 * @return void
 * 
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that there isn't any summary data to return
 */
function edac_summary_ajax(){

	// nonce security
	if ( !isset( $_REQUEST['nonce'] ) || !wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
		
		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {
	
		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	$post_id = intval($_REQUEST['post_id']);
	$summary = edac_summary($post_id);
	$html = '';
	if($summary['readability'] <= 9){
		$simplified_summary_text = 'Your content has a reading level at or below 9th grade and does not require a simplified summary.';
	}else{
		$simplified_summary_text = $summary['simplified_summary'] ? 'A Simplified summary has been included for this content.' : 'A Simplified summary has not been included for this content.';
	}

	$html .='<div class="edac-summary-total">';

		$html .='<div class="edac-summary-total-progress-circle '.(($summary['passed_tests'] > 50) ? ' over50' : '').'">
			<div class="edac-summary-total-progress-circle-label">
				<div class="edac-panel-number">'.$summary['passed_tests'].'%</div>
				<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
			</div>
			<div class="left-half-clipper">
				<div class="first50-bar"></div>
				<div class="value-bar" style="transform: rotate('.$summary['passed_tests'] * 3.6.'deg);"></div>
			</div>
		</div>';

		$html .= '<div class="edac-summary-total-mobile">
			<div class="edac-panel-number">'.$summary['passed_tests'].'%</div>
			<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
			<div class="edac-summary-total-mobile-bar"><span style="width:'.($summary['passed_tests']).'%;"></span></div>
		</div>';

	$html .='</div>';

	$html .='
	<div class="edac-summary-stats">
		<div class="edac-summary-stat edac-summary-errors'.(($summary['errors'] > 0) ? ' has-errors' : '').'">
			<div class="edac-panel-number">
				'.$summary['errors'].'
			</div>
			<div class="edac-panel-number-label">Error'.(($summary['errors'] != 1) ? 's' : '').'</div>
		</div>
		<div class="edac-summary-stat edac-summary-contrast'.(($summary['contrast_errors'] > 0) ? ' has-errors' : '').'">
			<div class="edac-panel-number">
				'.$summary['contrast_errors'].'
			</div>
			<div class="edac-panel-number-label">Contrast Error'.(($summary['contrast_errors'] != 1) ? 's' : '').'</div>
		</div>
		<div class="edac-summary-stat edac-summary-warnings'.(($summary['warnings'] > 0) ? ' has-warning' : '').'">
			<div class="edac-panel-number">
				'.$summary['warnings'].'
			</div>
			<div class="edac-panel-number-label">Warning'.(($summary['warnings'] != 1) ? 's' : '').'</div>
		</div>
		<div class="edac-summary-stat edac-summary-ignored">
			<div class="edac-panel-number">
				'.$summary['ignored'].'
			</div>
			<div class="edac-panel-number-label">Ignored Item'.(($summary['ignored'] != 1) ? 's' : '').'</div>
		</div>
	</div>
	<div class="edac-summary-readability">
		<div class="edac-summary-readability-level">
			<div><img src="'.plugin_dir_url( __FILE__ ).'assets/images/readability icon navy.png" alt="" width="54"></div>
			<div class="edac-panel-number'.(($summary['readability'] <= 9) ? ' passed-text-color' : ' failed-text-color').'">
				'.$summary['readability'].'
			</div>
			<div class="edac-panel-number-label'.(($summary['readability'] <= 9) ? ' passed-text-color' : ' failed-text-color').'">Reading <br />Level</div>
		</div>
		<div class="edac-summary-readability-summary">
			<div class="edac-summary-readability-summary-icon'.(($summary['simplified_summary'] || $summary['readability'] <= 9) ? ' active' : '').'"></div>
			<div class="edac-summary-readability-summary-text'.(($summary['simplified_summary'] || $summary['readability'] <= 9) ? ' active' : '').'">'.$simplified_summary_text.'</div>
		</div>
	</div>
	<div class="edac-summary-disclaimer"><small>* Accessibility Checker uses automated scanning to help you to identify if common accessibility errors are present on your website. Automated tools are great for catching some accessibility problems and are part of achieving and maintaining an accessible website, however not all accessibility problems can be identified by a scanning tool. Learn more about <a href="https://a11ychecker.com/help4280" target="_blank">manual accessibility testing</a> and <a href="https://a11ychecker.com/help4279" target="_blank">why 100% passed tests does not necessarily mean your website is accessible</a>.</small></div>
	';
	
	if( !$html ){

		$error = new WP_Error( '-3', 'No summary to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode($html) );

}

/**
 * Summary Data
 *
 * @param int $post_id
 * @return array
 */
function edac_summary($post_id){
	global $wpdb;
	$summary = [];

	// Passed Tests
	$rules = edac_register_rules();

	// if ANWW is active remove link_blank for details meta box
	if(EDAC_ANWW_ACTIVE){
		$rules = edac_remove_element_with_value($rules, 'slug', 'link_blank');
	}

	$rules_passed = [];

	if($rules){
		foreach ($rules as $rule){
			global $wpdb;
			$table_name = $wpdb->prefix . "accessibility_checker";
			$postid = $post_id;
			$siteid = get_current_blog_id();
			$query = "SELECT count(*) FROM ".$table_name." where rule = %s and siteid = %d and postid = %d and ignre = %d";
			$rule_count = $wpdb->get_var($wpdb->prepare($query, $rule['slug'], $siteid, $postid, 0));
			if(!$rule_count){
				$rules_passed[] = $rule['slug'];
			}
		}
	}

	$summary['passed_tests'] = round(count($rules_passed) / count($rules) * 100);

	// count errors
	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker where siteid = %d and postid = %d and ruletype = %s and ignre = %d";
	$summary['errors'] = intval($wpdb->get_var($wpdb->prepare($query, get_current_blog_id(), $post_id, 'error', 0)));

	// count warnings
	$warnings_parameters = array(get_current_blog_id(), $post_id, 'warning', 0);
	$warnings_where = 'WHERE siteid = siteid = %d and postid = %d and ruletype = %s and ignre = %d';
	if (EDAC_ANWW_ACTIVE) {
		array_push($warnings_parameters, 'link_blank');
		$warnings_where .= ' and rule != %s';
	}
	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker ".$warnings_where;
	$summary['warnings'] = intval($wpdb->get_var($wpdb->prepare($query, $warnings_parameters)));

	// count ignored issues	
	$ignored_parameters = array(get_current_blog_id(), $post_id, 1);
	$ignored_where = 'WHERE siteid = %d and postid = %d and ignre = %d';
	if (EDAC_ANWW_ACTIVE) {
		array_push($ignored_parameters, 'link_blank');
		$ignored_where .= ' and rule != %s';
	}
	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker ".$ignored_where;
	$summary['ignored'] = intval($wpdb->get_var($wpdb->prepare($query, $ignored_parameters)));

	// contrast errors
	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker where siteid = %d and postid = %d and rule = %s and ignre = %d";
	$summary['contrast_errors'] = intval($wpdb->get_var($wpdb->prepare($query, get_current_blog_id(), $post_id, 'color_contrast_failure', 0)));

	// remove color contrast from errors count
	$summary['errors'] = $summary['errors'] - $summary['contrast_errors'];

	// reading grade level
	$content_post = get_post($post_id);
	
	$content = $content_post->post_content;
	$content = wp_filter_nohtml_kses($content);
	//$content = apply_filters('the_content', $content);
	$content = str_replace(']]>', ']]&gt;', $content);
	$textStatistics = new TS\TextStatistics;
	$content_grade = floor($textStatistics->fleschKincaidGradeLevel($content));
	$summary['readability'] = ($content_grade == 0) ? 'N/A' : edac_ordinal($content_grade);

	// simplified summary
	$summary['simplified_summary'] = get_post_meta($post_id, '_edac_simplified_summary', $single = true ) ? true : false ;

	// save summary data as post meta
	if ( ! add_post_meta( $post_id, '_edac_summary', $summary, true ) ) {
		update_post_meta ( $post_id, '_edac_summary', $summary );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_passed_tests', $summary['passed_tests'], true ) ) {
		update_post_meta ( $post_id, '_edac_summary_passed_tests', $summary['passed_tests'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_errors', $summary['errors'], true ) ) {
		update_post_meta ( $post_id, '_edac_summary_errors', $summary['errors'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_warnings', $summary['warnings'], true ) ) {
		update_post_meta ( $post_id, '_edac_summary_warnings', $summary['warnings'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_ignored', $summary['ignored'], true ) ) {
		update_post_meta ( $post_id, '_edac_summary_ignored', $summary['ignored'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_contrast_errors', $summary['contrast_errors'], true ) ) {
		update_post_meta ( $post_id, '_edac_summary_contrast_errors', $summary['contrast_errors'] );
	}

	return $summary;
}

/**
 * Update post meta when Accessibility New Window Warning pluing is installed or uninstalled
 *
 * @return void
 */
function edac_anww_update_post_meta(){

	$option_name = 'edac_anww_update_post_meta';

	if ( get_option( $option_name ) === false && EDAC_ANWW_ACTIVE === true ) {
		
		add_option( $option_name, true);

		edac_update_post_meta('link_blank');
		

	} elseif(get_option( $option_name ) == true && EDAC_ANWW_ACTIVE == false ) {
		
		delete_option($option_name);

		edac_update_post_meta('link_blank');

	}
	
}

/**
 * Update post meta by rule
 *
 * @param string $rule
 * @return void
 */
function edac_update_post_meta($rule){
	global $wpdb;
	$site_id = get_current_blog_id();

	$posts = $wpdb->get_results( $wpdb->prepare( 'SELECT postid FROM '.$wpdb->prefix.'accessibility_checker WHERE rule = %s and siteid = %d', $rule, $site_id), ARRAY_A );

	if($posts){
		foreach ($posts as $post) {
			edac_summary($post['postid']);
		}
	}
}

/**
 * Details Ajax
 *
 * @return void
 * 
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that there isn't any details to return
 */
function edac_details_ajax(){

	// nonce security
	if ( !isset( $_REQUEST['nonce'] ) || !wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
		
		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {
	
		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}
	
	$html = '';
	global $wpdb;
	$table_name = $wpdb->prefix . "accessibility_checker";
	$postid = intval($_REQUEST['post_id']);
	$siteid = get_current_blog_id();

	$rules = edac_register_rules();
	if($rules){

		// if ANWW is active remove link_blank for details meta box
		if(EDAC_ANWW_ACTIVE){
			$rules = edac_remove_element_with_value($rules, 'slug', 'link_blank');
		}

		// separate rule types
		$passed_rules = [];
		$error_rules = edac_remove_element_with_value($rules, 'rule_type', 'warning');
		$warning_rules = edac_remove_element_with_value($rules, 'rule_type', 'error');

		// add count, unset passed error rules and add passed rules to array
		if($error_rules){
			foreach ($error_rules as $key => $error_rule) {	
				$count = count($wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM '.$table_name.' where postid = %d and rule = %s and siteid = %d and ignre = %d', $postid, $error_rule['slug'], $siteid, 0), ARRAY_A ));
				if($count){
					$error_rules[$key]['count'] = $count;
				}else{
					$error_rule['count'] = 0;	
					$passed_rules[] = $error_rule;
					unset($error_rules[$key]);
				}	
			}
		}

		// add count, unset passed warning rules and add passed rules to array
		if($warning_rules){
			foreach ($warning_rules as $key => $error_rule) {
				$count = count($wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM '.$table_name.' where postid = %d and rule = %s and siteid = %d and ignre = %d', $postid, $error_rule['slug'], $siteid, 0), ARRAY_A ));
				if($count){
					$warning_rules[$key]['count'] = $count;
				}else{
					$error_rule['count'] = 0;	
					$passed_rules[] = $error_rule;
					unset($warning_rules[$key]);
				}	
			}
		}
	}
	
	// sort error rules by count
	usort($error_rules, function($a, $b) {
		return $a['count'] < $b['count'];
	});

	// sort warning rules by count
	usort($warning_rules, function($a, $b) {
		return $a['count'] < $b['count'];
	});

	// sort passed rules array by title
	usort($passed_rules, function($a, $b) {
		
		// PHP 5.6
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
		
		// PHP 7 with spaceship operator
		//return $a['title'] <=> $b['title'];
		
	});

	// merge rule arrays together
	$rules = array_merge($error_rules, $warning_rules, $passed_rules);

	if($rules){
		global $wp_version;
		$days_active = edac_days_active();
		$ignore_permission = true;
		if(has_filter('edac_ignore_permission')){
			$ignore_permission = apply_filters('edac_ignore_permission', $ignore_permission);
		}
		foreach ($rules as $rule) {
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment, ignre_global FROM '.$table_name.' where postid = %d and rule = %s and siteid = %d', $postid, $rule['slug'], $siteid), ARRAY_A );			
			$count_classes = ($rule['rule_type'] == 'error') ? ' edac-details-rule-count-error' : ' edac-details-rule-count-warning';
			$count_classes .= ($rule['count'] != 0) ? ' active' : '';
			
			$count_ignored = 0;
			$ignores = array_column($results, 'ignre');
			if($ignores){
				foreach ($ignores as $ignore) {
					if($ignore == 1){
						$count_ignored++;
					}
				}
			}
			
			$expand_rule = count($wpdb->get_results( $wpdb->prepare( 'SELECT id FROM '.$table_name.' where postid = %d and rule = %s and siteid = %d', $postid, $rule['slug'], $siteid), ARRAY_A ));

			$tool_tip_link = $rule['info_url'].'?utm_source=accessibility-checker&utm_medium=software&utm_term='.esc_html($rule['slug']).'&utm_content=content-analysis&utm_campaign=wordpress-general&php_version='.PHP_VERSION.'&platform=wordpress&platform_version='.$wp_version.'&software=free&software_version='.EDAC_VERSION.'&days_active='.$days_active.'';

			$html .= '<div class="edac-details-rule">';

				$html .= '<div class="edac-details-rule-title">';

					$html .= '<span class="edac-details-rule-count'.$count_classes.'">'.$rule['count'].'</span>';
					if($count_ignored > 0){
						$html .= '<span class="edac-details-rule-count-ignore">'.$count_ignored.'</span>';
					}
					$html .= esc_html($rule['title']);
					$html .= '<a href="'.$tool_tip_link.'" class="edac-details-rule-information" target="_blank"><span class="dashicons dashicons-info"></span></a>';
					$html .= ($expand_rule) ? '<button class="edac-details-rule-title-arrow"><i class="dashicons dashicons-arrow-down-alt2"></i></button>' : '';

				$html .= '</div>';

				if($results){

					$html .= '<div class="edac-details-rule-records">';

						$html .=
						'<div class="edac-details-rule-records-labels">
							<div class="edac-details-rule-records-labels-label">
								Affected Code
							</div>
							<div class="edac-details-rule-records-labels-label">
								Actions
							</div>
						</div>';

						foreach ($results as $row){

							// vars
							$id = intval($row['id']);
							$ignore = intval($row['ignre']);
							$ignore_class = $ignore ? ' active' : '';
							$ignore_label = $ignore ? 'Ignored' : 'Ignore';
							$ignore_user = intval($row['ignre_user']);
							$ignore_user_info = get_userdata($ignore_user);
							$ignore_username = is_object($ignore_user_info) ? '<strong>Username:</strong> '.$ignore_user_info->user_login : '';
							$ignore_date = ($row['ignre_date'] && $row['ignre_date'] != '0000-00-00 00:00:00' ) ? '<strong>Date:</strong> '.date("F j, Y g:i a", strtotime(esc_html($row['ignre_date']))) : '';
							$ignore_comment = esc_html($row['ignre_comment']);
							$ignore_action = $ignore ? 'disable' : 'enable';
							$ignore_type = $rule['rule_type'];
							$ignore_submit_label = $ignore ? 'Stop Ignoring' : 'Ignore This '.$ignore_type;
							$ignore_comment_disabled = $ignore ? 'disabled' : '';
							$ignore_global = intval($row['ignre_global']);

							$html .= '<div id="edac-details-rule-records-record-'.$id.'" class="edac-details-rule-records-record">';

								$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-object">';

									$html .= '<code>'.esc_html($row['object']).'</code>';

								$html .= '</div>';

								$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-actions">';

									$html .= '<button class="edac-details-rule-records-record-actions-ignore'.$ignore_class.'">'.EDAC_SVG_IGNORE_ICON.'<span class="edac-details-rule-records-record-actions-ignore-label">'.$ignore_label.'</span></button>';

								$html .= '</div>';

								$html .= '<div class="edac-details-rule-records-record-ignore">';
									
									$html .= '<div class="edac-details-rule-records-record-ignore-info">';
										$html .= '<span class="edac-details-rule-records-record-ignore-info-user">'.$ignore_username.'</span>';

										$html .= ' <span class="edac-details-rule-records-record-ignore-info-date">'.$ignore_date.'</span>';
									$html .= '</div>';
									
									$html .= ($ignore_permission == true || !empty($ignore_comment)) ? '<label for="edac-details-rule-records-record-ignore-comment-'.$id.'">Comment</label><br>' : '';
									$html .= ($ignore_permission == true || !empty($ignore_comment)) ? '<textarea rows="4" class="edac-details-rule-records-record-ignore-comment" id="edac-details-rule-records-record-ignore-comment-'.$id.'" '.$ignore_comment_disabled.'>'.$ignore_comment.'</textarea>' : '';

									if($ignore_global){
										$html .= ($ignore_permission == true) ? '<a href="'.admin_url('admin.php?page=accessibility_checker_ignored&tab=global').'" class="edac-details-rule-records-record-ignore-global">Manage Globally Ignored</a>' : '';
									}else{
										$html .= ($ignore_permission == true) ? '<button class="edac-details-rule-records-record-ignore-submit" data-id='.$id.' data-action='.$ignore_action.' data-type='.$ignore_type.'>'.EDAC_SVG_IGNORE_ICON.' <span class="edac-details-rule-records-record-ignore-submit-label">'.$ignore_submit_label.'<span></button>' : '';
									}

									$html .= ($ignore_permission == false && $ignore == false) ? __('Your user account doesn\'t have permission to ignore this issue.','edac') : '';

								$html .= '</div>';

							$html .= '</div>';

						}

					$html .= '</div>';

				}

			$html .= '</div>';

		}
	}

	if( !$html ){

		$error = new WP_Error( '-3', 'No details to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode($html) );

}

/**
 * Readability Ajax
 *
 * @return void
 * 
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that there isn't any readability data to return
 */
function edac_readability_ajax(){

	// nonce security
	if ( !isset( $_REQUEST['nonce'] ) || !wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
			
		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	$post_id = intval($_REQUEST['post_id']);
	$html = '';
	$simplified_summary = get_post_meta($post_id, '_edac_simplified_summary', $single = true ) ?: '';
	$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );
	$content_post = get_post($post_id);
	$content = $content_post->post_content;
	$content = apply_filters('the_content', $content);
	$oxygen_builder_shortcodes_meta = get_post_meta($post_id, 'ct_builder_shortcodes', true);
	
	// add oxygen builder shortcode content to readability scan
	if($oxygen_builder_shortcodes_meta){
		$oxygen_builder_shortcodes = do_shortcode($oxygen_builder_shortcodes_meta);
		if($oxygen_builder_shortcodes){
			$content .= $oxygen_builder_shortcodes;
		}
	}

	if(has_filter('edac_filter_readability_content')) {
		$content = apply_filters('edac_filter_readability_content', $content, $post_id);
	}
	$content = wp_filter_nohtml_kses($content);
	$content = str_replace(']]>', ']]&gt;', $content);
	$textStatistics = new TS\TextStatistics;
	$post_grade = floor($textStatistics->fleschKincaidGradeLevel($content));
	$post_grade_failed = ($post_grade > 9) ? true : false;
	$simplified_summary_grade = edac_ordinal(floor($textStatistics->fleschKincaidGradeLevel($simplified_summary)));
	$simplified_summary_grade_failed = ($simplified_summary_grade > 9) ? true : false;
	$simplified_summary_prompt = get_option('edac_simplified_summary_prompt');

	$html .= '<ul class="edac-readability-list">';

		$html .=
		 '<li class="edac-readability-list-item edac-readability-grade-level">
			<span class="edac-readability-list-item-icon dashicons '.(($post_grade_failed || $post_grade == 0) ? 'dashicons-no-alt' : 'dashicons-saved').'"></span>
			<p class="edac-readability-list-item-title">Post Reading Grade Level: <strong class="'.(($post_grade_failed || $post_grade == 0) ? 'failed-text-color' : 'passed-text-color').'">'.(( $post_grade == 0) ? 'None' : edac_ordinal($post_grade)).'</strong><br /></p>';
			if($post_grade_failed){
				$html .='<p class="edac-readability-list-item-description">Your post has a reading level higher than 9th grade. Web Content Accessibility Guidelines (WCAG) at the AAA level require a simplified summary of your post that is 9th grade or below.</p>';
			}elseif($post_grade == 0){
				$html .='<p class="edac-readability-list-item-description">Your post does not contain enough content to calculate its reading level.</p>';
			}else{
				$html .= '<p class="edac-readability-list-item-description">A simplified summary is not necessary when content reading level is 9th grade or below. Choose when to prompt for a simplified summary on the settings page.</p>';
			}
		$html .= '</li>';

		if($post_grade_failed){

			if($simplified_summary){	
			$html .= 
			'<li class="edac-readability-list-item edac-readability-summary-grade-level">
				<span class="edac-readability-list-item-icon dashicons '.(($simplified_summary_grade_failed) ? 'dashicons-no-alt' : 'dashicons-saved').'"></span>
				<p class="edac-readability-list-item-title">Simplified Summary Reading Grade Level: <strong class="'.(($simplified_summary_grade_failed) ? 'failed-text-color' : 'passed-text-color').'">'.$simplified_summary_grade.'</strong></p>
				<p class="edac-readability-list-item-description">Your simplified summary has a reading level '.(($simplified_summary_grade_failed > 9) ? 'higher' : 'lower').' than 9th grade.</p>
			</li>';
			}

			if($simplified_summary_position != 'none'){

				$html .= 
				'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon dashicons dashicons-saved"></span>
					<p class="edac-readability-list-item-title">Simplified summary is being automatically inserted <strong>'.$simplified_summary_position.' the content</strong>.</p>
						<p class="edac-readability-list-item-description">Set where the Simplified Summary is inserted into the content on the <a href="'.get_bloginfo('url').'/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
				</li>';

			}else{

				$html .= 
				'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon"><img src="'.plugin_dir_url( __FILE__ ).'assets/images/warning icon yellow.png" alt="" width="22"></span>
					<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
						<p class="edac-readability-list-item-description">Your Simplified Summary location is set to "manually" which requires a function be added to your page template. If you would like the simplified summary to displayed automatically, you can change this on the <a href="'.get_bloginfo('url').'/wp-admin/options-general.php?page=edac_settings">settings page</a>.</p>
				</li>';

			}

		}
		
	$html .= '</ul>';
	
	if($post_grade_failed || $simplified_summary_prompt == 'always'){
		$html .= 
		'</form>
		<form action="/" class="edac-readability-simplified-summary">
			<label for="edac-readability-text">Simplified Summary</label>
			<textarea name="" id="edac-readability-text" cols="30" rows="10">'.$simplified_summary.'</textarea>
			<input type="submit">
		</form>';
	}

	global $wp_version;
	$html .= '<span class="dashicons dashicons-info"></span><a href="https://a11ychecker.com/help3265?utm_source=accessibility-checker&utm_medium=software&utm_term=readability&utm_content=content-analysis&utm_campaign=wordpress-general&php_version='.PHP_VERSION.'&platform=wordpress&platform_version='.$wp_version.'&software=free&software_version='.EDAC_VERSION.'&days_active='.edac_days_active().'" target="_blank">Learn more about improving readability and simplified summary requirements</a>';

	if( !$html ){

		$error = new WP_Error( '-3', 'No readability data to return' );
		wp_send_json_error( $error );
	
	}
	
	wp_send_json_success( json_encode($html) );

}

/**
 * Update simplified summary
 *
 * @return void
 * 
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that the summary was not specified
 *  - '-4' means that there isn't any summary to return
 */
function edac_update_simplified_summary(){

	// nonce security
	if ( !isset( $_REQUEST['nonce'] ) || !wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
			
		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );
	
	}

	if ( ! isset( $_REQUEST['summary'] ) ) {

		$error = new WP_Error( '-3', 'The summary was not set' );
		wp_send_json_error( $error );
	
	}

	$post_id = intval($_REQUEST['post_id']);
	$summary = sanitize_text_field($_REQUEST['summary']);

	if ( ! add_post_meta( $post_id, '_edac_simplified_summary', $summary, true ) ) { 
		update_post_meta ( $post_id, '_edac_simplified_summary', $summary );
	}

	$simplified_summary = get_post_meta( $post_id, '_edac_simplified_summary', $single = true );

	if( !$simplified_summary ){

		$error = new WP_Error( '-4', 'No simplified summary to return' );
		wp_send_json_error( $error );
	
	}
	
	wp_send_json_success( json_encode($simplified_summary) );
	
}

/**
 * Output simplified summary
 *
 * @param string $content
 * @return string
 */
function edac_output_simplified_summary($content){
	$simplified_summary = edac_simplified_summary_markup(get_the_ID());
	$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );
	if($simplified_summary && $simplified_summary_position == 'before'){
		return $simplified_summary.$content;
	}elseif($simplified_summary && $simplified_summary_position == 'after'){
		return $content.$simplified_summary;
	}else{
		return $content;
	}
}

/**
 * Get simplified summary
 *
 * @param integer $post
 * @return string
 */
function edac_get_simplified_summary( int $post = null){
	if($post == null){
		$post = get_the_ID();
	}
	echo edac_simplified_summary_markup($post);
}

/**
 * Simplified summary markup
 *
 * @param int $post
 * @return string
 */
function edac_simplified_summary_markup($post){
	$simplified_summary = get_post_meta($post, '_edac_simplified_summary', $single = true ) ?: '';
	$simplified_summary_heading = 'Simplified Summary';

	// filter title
	if(has_filter('edac_filter_simplified_summary_heading')) {
		$simplified_summary_heading = apply_filters('edac_filter_simplified_summary_heading', $simplified_summary_heading);
	}

	if($simplified_summary){
		return '<div class="edac-simplified-summary"><h2>'.$simplified_summary_heading.'</h2><p>'.sanitize_text_field($simplified_summary).'</p></div>';
	}else{
		return;
	}
}

/**
 * Get accessibility statement
 *
 * @param void
 * @return string
 */
function edac_get_accessibility_statement(){
	$statement = '';
	$add_footer_statement = get_option( 'edac_add_footer_accessibility_statement');
	$include_statement_link = get_option( 'edac_include_accessibility_statement_link');
	$policy_page = get_option( 'edac_accessibility_policy_page');
	$policy_page = is_numeric($policy_page) ? get_page_link($policy_page) : $policy_page;

	if($add_footer_statement){
		$statement .= get_bloginfo('name').' '.__('uses','edac').' <a href="https://equalizedigital.com/accessibility-checker" target="_blank">'.__('Accessibility Checker','edac').'</a> '.__('to monitor our website\'s accessibility. ','edac');
	}

	if($include_statement_link && $policy_page){
		$statement .= __('Read our ','edac').'<a href="'.$policy_page.'">'.__('Accessibility Policy','edac').'</a>.';
	}

	return $statement;
}

/**
 * Output simplified summary
 *
 * @param string $content
 * @return string
 */
function edac_output_accessibility_statement(){
	$statement = edac_get_accessibility_statement();
	if(!empty($statement)){
		echo '<p class="edac-accessibility-statement" style="text-align: center; max-width: 800px; margin: auto; padding: 15px;"><small>'.$statement.'</small></p>';
	}
}

/**
 * Review Admin Notice
 *
 * @return void
 */
function edac_review_notice(){

	$option = 'edac_review_notice';
	$edac_review_notice = get_option($option);

	// exit if option is set to stop
	if($edac_review_notice == 'stop') return;

	$transient = 'edac_review_notice_reminder';
	$edac_review_notice_reminder = get_transient($transient);

	// first time if notice has never been shown wait 14 days
	if($edac_review_notice_reminder == false && $edac_review_notice == false){
		// if option isn't set and plugin has been active for more than 14 days show notice. This is for current users.
		if(edac_days_active() > 14){
			update_option($option,'play');
		}else{
			// if plugin has been active less than 14 days set transient for 14 days
			set_transient($transient, true, 14 * DAY_IN_SECONDS);
			// set option to pause
			update_option($option,'pause');
		}
	}

	// if transient has expired and option is set to pause update option to play
	if($edac_review_notice_reminder == false && $edac_review_notice == 'pause'){
		update_option($option,'play');
	}

	// if option is not set to play exit
	if(get_option($option) != 'play') return;
	
	?>
	<div class="notice notice-info edac-review-notice">
		<p>
			<?php _e( "Hello! Thank you for using Accessibility Checker as part of your accessibility toolkit. Since you've been using it for a while, would you please write a 5-star review of Accessibility Checker in the WordPress plugin directory? This will help increase our visibility so more people can learn about the importance of web accessibility. Thanks so much!", 'edac' ); ?>
			
		</p>
		<p>
			<button class="edac-review-notice-review"><?php _e( 'Write A Review', 'edac' ); ?></button>
			<button class="edac-review-notice-remind"><?php _e( 'Remind Me In Two Weeks', 'edac' ); ?></button>
			<button class="edac-review-notice-dismiss"><?php _e( 'Never Ask Again', 'edac' ); ?></button>
		</p>
	</div>
	<?php
}
/**
 * Review Admin Notice Ajax
 *
 * @return void
 * 
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the review action value was not specified
 *  - '-3' means that update option wasn't successful
 */
function edac_review_notice_ajax(){

	// nonce security
	if ( !isset( $_REQUEST['nonce'] ) || !wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
		
		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['review_action'] ) ) {
	
		$error = new WP_Error( '-2', 'The review action value was not set' );
		wp_send_json_error( $error );

	}

	$results = update_option( 'edac_review_notice', $_REQUEST['review_action'] );

	if($_REQUEST['review_action'] == 'pause'){
		set_transient('edac_review_notice_reminder', true, 14 * DAY_IN_SECONDS);
	}
	
	if( !$results ){

		$error = new WP_Error( '-3', 'Update option wasn\'t successful' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( json_encode($results) );

}