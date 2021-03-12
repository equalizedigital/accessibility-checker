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
 * Version:           1.1.2
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
	define( 'EDAC_VERSION', '1.1.2' );
}

// Current database version
if ( ! defined( 'EDAC_DB_VERSION' ) ) {
	define( 'EDAC_DB_VERSION', '1.0.0' );
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

// Enable EDAC_DEBUG mode
if ( ! defined( 'EDAC_DEBUG' ) ) {
	define( 'EDAC_DEBUG', false );
}

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
add_filter( 'pre_delete_post', 'edac_delete_post', 10, 3 );
add_action( 'pre_get_posts', 'edac_show_draft_posts' );
add_action( 'admin_init', 'edac_process_actions' );
add_action( 'edac_download_sysinfo', 'edac_tools_sysinfo_download' );

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
		'info_url'  => 'https://a11ychecker.com/help1927',
		'slug'  => 'img_alt_empty',
		'rule_type' => 'error',
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
 */
function edac_summary_ajax(){

	if(!isset($_REQUEST['post_id'])) die();

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

	print json_encode($html);
	die();
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
	$rules_passed = [];

	//edac_log($rules_count);
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
	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker where siteid = %d and postid = %d and ruletype = %s and ignre = %d";
	$summary['warnings'] = intval($wpdb->get_var($wpdb->prepare($query, get_current_blog_id(), $post_id, 'warning', 0)));

	// count ignored issues	
	$query = "SELECT count(*) FROM ".$wpdb->prefix."accessibility_checker where siteid = %d and postid = %d and ignre = %d";
	$summary['ignored'] = intval($wpdb->get_var($wpdb->prepare($query, get_current_blog_id(), $post_id, 1)));

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

	return $summary;
}

/**
 * Details Ajax
 *
 * @return void
 */
function edac_details_ajax(){

	if(!isset($_REQUEST['post_id'])) die();
	
	$html = '';
	global $wpdb;
	$table_name = $wpdb->prefix . "accessibility_checker";
	$postid = intval($_REQUEST['post_id']);
	$siteid = get_current_blog_id();

	$rules = edac_register_rules();
	if($rules){

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
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM '.$table_name.' where postid = %d and rule = %s and siteid = %d', $postid, $rule['slug'], $siteid), ARRAY_A );	
			$count_classes = ($rule['rule_type'] == 'error') ? ' edac-details-rule-count-error' : ' edac-details-rule-count-warning';
			$count_classes .= ($rule['count'] != 0) ? ' active' : '';
			$expand_rule = count($wpdb->get_results( $wpdb->prepare( 'SELECT id FROM '.$table_name.' where postid = %d and rule = %s and siteid = %d', $postid, $rule['slug'], $siteid), ARRAY_A ));

			$tool_tip_link = $rule['info_url'].'?utm_source=accessibility-checker&utm_medium=software&utm_term='.esc_html($rule['slug']).'&utm_content=content-analysis&utm_campaign=wordpress-general&php_version='.PHP_VERSION.'&platform=wordpress&platform_version='.$wp_version.'&software=free&software_version='.EDAC_VERSION.'&days_active='.$days_active.'';

			$html .= '<div class="edac-details-rule">';

				$html .= '<div class="edac-details-rule-title">';

					$html .= '<span class="edac-details-rule-count'.$count_classes.'">'.$rule['count'].'</span>';
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
							$ignore_submit_icon = $ignore ? 'dashicons-visibility' : 'dashicons-hidden';
							$ignore_comment_disabled = $ignore ? 'disabled' : '';

							$html .= '<div id="edac-details-rule-records-record-'.$id.'" class="edac-details-rule-records-record">';

								$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-object">';

									$html .= '<code>'.esc_html($row['object']).'</code>';

								$html .= '</div>';

								$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-actions">';

									$html .= '<button class="edac-details-rule-records-record-actions-ignore'.$ignore_class.'"><span class="dashicons dashicons-admin-generic"></span><span class="edac-details-rule-records-record-actions-ignore-label">'.$ignore_label.'</span></button>';

								$html .= '</div>';

								$html .= '<div class="edac-details-rule-records-record-ignore">';
									
									$html .= '<div class="edac-details-rule-records-record-ignore-info">';
										$html .= '<span class="edac-details-rule-records-record-ignore-info-user">'.$ignore_username.'</span>';

										$html .= ' <span class="edac-details-rule-records-record-ignore-info-date">'.$ignore_date.'</span>';
									$html .= '</div>';
									
									$html .= ($ignore_permission == true || !empty($ignore_comment)) ? '<label for="edac-details-rule-records-record-ignore-comment-'.$id.'">Comment</label><br>' : '';
									$html .= ($ignore_permission == true || !empty($ignore_comment)) ? '<textarea rows="4" class="edac-details-rule-records-record-ignore-comment" id="edac-details-rule-records-record-ignore-comment-'.$id.'" '.$ignore_comment_disabled.'>'.$ignore_comment.'</textarea>' : '';

									$html .= ($ignore_permission == true) ? '<button class="edac-details-rule-records-record-ignore-submit" data-id='.$id.' data-action='.$ignore_action.' data-type='.$ignore_type.'><span class="dashicons '.$ignore_submit_icon.'"></span> <span class="edac-details-rule-records-record-ignore-submit-label">'.$ignore_submit_label.'<span></button>' : '';

									$html .= ($ignore_permission == false && $ignore == false) ? __('Your user account doesn\'t have permission to ignore this issue.','edac') : '';

								$html .= '</div>';

							$html .= '</div>';

						}

					$html .= '</div>';

				}

			$html .= '</div>';

		}
	}

	print json_encode($html);
	die();
}

/**
 * Readability Ajax
 *
 * @return void
 */
function edac_readability_ajax(){

	if(!isset($_REQUEST['post_id'])) die();

	$post_id = intval($_REQUEST['post_id']);
	$html = '';
	$simplified_summary = get_post_meta($post_id, '_edac_simplified_summary', $single = true ) ?: '';
	$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );
	$content_post = get_post($post_id);
	$content = $content_post->post_content;
	$content = apply_filters('the_content', $content);
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

	print json_encode($html);
	die();
}

/**
 * Update simplified summary
 *
 * @return void
 */
function edac_update_simplified_summary(){

	if(!isset($_REQUEST['post_id']) || !isset($_REQUEST['summary'])) die();

	$post_id = intval($_REQUEST['post_id']);
	$summary = sanitize_text_field($_REQUEST['summary']);

	if ( ! add_post_meta( $post_id, '_edac_simplified_summary', $summary, true ) ) { 
		update_post_meta ( $post_id, '_edac_simplified_summary', $summary );
	}

	$simplified_summary = get_post_meta( $post_id, '_edac_simplified_summary', $single = true );
	

	print json_encode($simplified_summary);
	die();
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
		$statement .= get_bloginfo('name').' '.__('uses','edac').' <a href="https://a11ychecker.com/" target="_blank">'.__('Accessibility Checker','edac').'</a> '.__('to monitor our website\'s accessibility. ','edac');
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