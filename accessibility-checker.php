<?php
/**
 * Accessibility Checker Plugin.
 *
 * @package Accessibility_Checker
 * @link    https://a11ychecker.com
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Accessibility Checker
 * Plugin URI:        https://a11ychecker.com
 * Description:       Audit and check your website for accessibility before you hit publish. In-post accessibility scanner and guidance.
 * Version:           1.6.10
 * Author:            Equalize Digital
 * Author URI:        https://equalizedigital.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       accessibility-checker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check for WordPress Playground.
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-playground-check.php';
$plugin_check = new EDAC\Playground_Check();
if ( ! $plugin_check->should_load ) {
	return;
}

// Include plugin dependency.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Load composer packages.
if ( is_admin() && file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	include_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

/**
 * Setup constants.
 */

// Current plugin version.
if ( ! defined( 'EDAC_VERSION' ) ) {
	define( 'EDAC_VERSION', '1.6.10' );
}

// Current database version.
if ( ! defined( 'EDAC_DB_VERSION' ) ) {
	define( 'EDAC_DB_VERSION', '1.0.3' );
}

// Plugin Folder Path.
if ( ! defined( 'EDAC_PLUGIN_DIR' ) ) {
	define( 'EDAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL.
if ( ! defined( 'EDAC_PLUGIN_URL' ) ) {
	define( 'EDAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin Root File.
if ( ! defined( 'EDAC_PLUGIN_FILE' ) ) {
	define( 'EDAC_PLUGIN_FILE', __FILE__ );
}

// Accessibility New Window Warning Plugin Active.
if ( ! defined( 'EDAC_ANWW_ACTIVE' ) ) {
	define(
		'EDAC_ANWW_ACTIVE',
		is_plugin_active( 'accessibility-new-window-warnings/accessibility-new-window-warnings.php' )
	);
}

/**
 * Key Valid.
 */
define(
	'EDAC_KEY_VALID',
	'valid' === get_option( 'edacp_license_status' )
);

// Enable EDAC_DEBUG mode.
if ( ! defined( 'EDAC_DEBUG' ) ) {
	define( 'EDAC_DEBUG', false );
}

// SVG Icons.
define(
	'EDAC_SVG_IGNORE_ICON',
	'<?xml version="1.0" standalone="no"?>
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
</svg>'
);

/**
 * Plugin Activation & Deactivation
 */
register_activation_hook( __FILE__, 'edac_activation' );
register_deactivation_hook( __FILE__, 'edac_deactivation' );

/**
 * Add simple dom support (need to over ride max file size, if clashes with another install of simple dom there the max file size will be dependednt upon that installation)
 */
if ( ! defined( 'MAX_FILE_SIZE' ) ) {
	define( 'MAX_FILE_SIZE', 6000000 );
}
if ( ! class_exists( 'simple_html_dom' ) ) {
	include_once plugin_dir_path( __FILE__ ) . 'includes/simplehtmldom/simple_html_dom.php';
	include_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-edac-dom.php';
}

require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-edac-frontend-highlight.php';

/**
 * Import Resources
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/activation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/deactivation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/enqueue-scripts.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/meta-boxes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/options-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/validate.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/insert.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/purge.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/system-info.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-admin-notices.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-rest-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-issues-query.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-scans-stats.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-widgets.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-welcome-page.php';

/**
 * Filters and Actions
 */
add_action( 'init', 'edac_init' );
add_action( 'admin_enqueue_scripts', 'edac_admin_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'edac_admin_enqueue_styles' );
add_action( 'wp_enqueue_scripts', 'edac_enqueue_scripts' );
add_action( 'admin_init', 'edac_update_database', 10 );
add_action( 'add_meta_boxes', 'edac_register_meta_boxes' );
add_action( 'admin_menu', 'edac_add_options_page' );
add_action( 'admin_init', 'edac_register_setting' );
add_action( 'admin_head', 'edac_post_on_load' );
add_filter( 'save_post', 'edac_save_post', 10, 3 );
add_action( 'wp_ajax_edac_summary_ajax', 'edac_summary_ajax' );
add_action( 'wp_ajax_edac_details_ajax', 'edac_details_ajax' );
add_action( 'wp_ajax_edac_readability_ajax', 'edac_readability_ajax' );
add_action( 'wp_ajax_edac_insert_ignore_data', 'edac_insert_ignore_data' );
add_action( 'wp_ajax_edac_update_simplified_summary', 'edac_update_simplified_summary' );
add_filter( 'the_content', 'edac_output_simplified_summary' );
add_action( 'wp_footer', 'edac_output_accessibility_statement' );
add_action( 'wp_trash_post', 'edac_delete_post' );
add_action( 'pre_get_posts', 'edac_show_draft_posts' );
add_action( 'template_redirect', 'edac_before_page_render' );
add_action( 'admin_init', 'edac_process_actions' );
add_action( 'edac_download_sysinfo', 'edac_tools_sysinfo_download' );
if ( edac_check_plugin_active( 'oxygen/functions.php' ) ) {
	add_action( 'added_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
	add_action( 'updated_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
}
add_action( 'admin_init', 'edac_anww_update_post_meta' );
add_action( 'wp_ajax_edac_frontend_highlight_single_ajax', 'edac_frontend_highlight_ajax' );
add_action( 'wp_ajax_nopriv_edac_frontend_highlight_single_ajax', 'edac_frontend_highlight_ajax' );
add_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax', 'edac_dismiss_welcome_cta' );
add_action( 'wp_ajax_nopriv_edac_dismiss_welcome_cta_ajax', 'edac_dismiss_welcome_cta' );
add_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax', 'edac_dismiss_dashboard_cta' );
add_action( 'wp_ajax_nopriv_edac_dismiss_dashboard_cta_ajax', 'edac_dismiss_dashboard_cta' );
add_action( 'wp_ajax_edac_email_opt_in_ajax', 'edac_email_opt_in' );
add_action( 'wp_dashboard_setup', 'edac_wp_dashboard_setup' );

/**
 * Init the plugin
 */
function edac_init() {
	// instantiate the classes that need to load hooks early.
	new \EDAC\Rest_Api();
}

/**
 * Create/Update database
 *
 * @return void
 */
function edac_update_database() {

	global $wpdb;
	$table_name = $wpdb->prefix . 'accessibility_checker';

	$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepare above, Safe variable used for table name, caching not required for one time operation.
	if ( get_option( 'edac_db_version' ) !== EDAC_DB_VERSION || $wpdb->get_var( $query ) !== $table_name ) {

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
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
			UNIQUE KEY id (id),
			KEY postid_index (postid)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

	}

	// Update database version option.
	$option_name = 'edac_db_version';
	$new_value   = EDAC_DB_VERSION;
	update_option( $option_name, $new_value );
}

/**
 * Gets an array of default filters,
 * and applies the rules `edac_filter_register_rules` filter to it.
 *
 * @return array
 */
function edac_register_rules() {

	// Use a static variable to avoid multiple calls to the filesystem.
	static $default_rules = null;
	if ( ! is_null( $default_rules ) ) {
		return $default_rules;
	}

	// If we got this far, this is the 1st time we called this function.
	// We need to load the rules from the filesystem, and apply any filters.
	$default_rules = include __DIR__ . '/includes/rules.php';
	$default_rules = apply_filters( 'edac_filter_register_rules', $default_rules );

	return $default_rules;
}

/**
 * Include Rules
 *
 * @var object $rules
 */
$rules = edac_register_rules();
if ( $rules ) {
	foreach ( $rules as $rule ) {
		if ( ( array_key_exists( 'ruleset', $rule ) && 'php' === $rule['ruleset'] ) ||
		( ! array_key_exists( 'ruleset', $rule ) && $rule['slug'] ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/rules/' . $rule['slug'] . '.php';
		}
	}
}


/**
 * Code that needs to run before the page is rendered
 *
 * @return void
 */
function edac_before_page_render() {

	global $pagenow;

	if ( 'index.php' === $pagenow && false === is_customize_preview() && current_user_can( 'edit_posts' ) ) {

		// Check the page if it hasn't already been checked.
		global $post;
		$checked = get_post_meta( $post->ID, '_edac_post_checked', true );
		if ( false === boolval( $checked ) ) {
			edac_validate( $post->ID, $post, $action = 'load' );
		}
	}
}

/**
 * Add dashboard widget
 *
 * @return void
 */
function edac_wp_dashboard_setup() {
	wp_add_dashboard_widget(
		'edac_dashboard_scan_summary',
		'Accessibility Checker',
		array(
			'\EDAC\Widgets',
			'render_dashboard_scan_summary',
		)
	);
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
function edac_summary_ajax() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	$html            = array();
	$html['content'] = '';

	// password check.
	if ( boolval( get_option( 'edac_password_protected' ) ) === true ) {
		$admin_notices              = new \EDAC\Admin_Notices();
		$notice_text                = $admin_notices->edac_password_protected_notice_text();
		$html['password_protected'] = $notice_text;
		$html['content']           .= '<div class="edac-summary-notice">' . $notice_text . '</div>';
	}

	$post_id                   = intval( $_REQUEST['post_id'] );
	$summary                   = edac_summary( $post_id );
	$simplified_summary_text   = '';
	$simplified_summary_prompt = get_option( 'edac_simplified_summary_prompt' );

	$simplified_summary_text = esc_html__( 'A Simplified summary has not been included for this content.', 'accessibility-checker' );
	if ( 'none' !== $simplified_summary_prompt ) {
		if ( $summary['content_grade'] <= 9 ) {
			$simplified_summary_text = esc_html__( 'Your content has a reading level at or below 9th grade and does not require a simplified summary.', 'accessibility-checker' );
		} elseif ( $summary['simplified_summary'] ) {
			$simplified_summary_text = esc_html__( 'A Simplified summary has been included for this content.', 'accessibility-checker' );
		}
	}

	$html['content'] .= '<div class="edac-summary-total">';

	$html['content'] .= '<div class="edac-summary-total-progress-circle ' . ( ( $summary['passed_tests'] > 50 ) ? ' over50' : '' ) . '">
		<div class="edac-summary-total-progress-circle-label">
			<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
			<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
		</div>
		<div class="left-half-clipper">
			<div class="first50-bar"></div>
			<div class="value-bar" style="transform: rotate(' . $summary['passed_tests'] * 3.6 . 'deg);"></div>
		</div>
	</div>';

	$html['content'] .= '<div class="edac-summary-total-mobile">
		<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
		<div class="edac-panel-number-label">Passed Tests<sup>*</sup></div>
		<div class="edac-summary-total-mobile-bar"><span style="width:' . ( $summary['passed_tests'] ) . '%;"></span></div>
	</div>';

	$html['content'] .= '</div>';

	$html['content'] .= '
	<div class="edac-summary-stats">
		<div class="edac-summary-stat edac-summary-errors' . ( ( $summary['errors'] > 0 ) ? ' has-errors' : '' ) . '">
			<div class="edac-panel-number">
				' . $summary['errors'] . '
			</div>
			<div class="edac-panel-number-label">Error' . ( ( 1 !== $summary['errors'] ) ? 's' : '' ) . '</div>
		</div>
		<div class="edac-summary-stat edac-summary-contrast' . ( ( $summary['contrast_errors'] > 0 ) ? ' has-errors' : '' ) . '">
			<div class="edac-panel-number">
				' . $summary['contrast_errors'] . '
			</div>
			<div class="edac-panel-number-label">Contrast Error' . ( ( 1 !== $summary['contrast_errors'] ) ? 's' : '' ) . '</div>
		</div>
		<div class="edac-summary-stat edac-summary-warnings' . ( ( $summary['warnings'] > 0 ) ? ' has-warning' : '' ) . '">
			<div class="edac-panel-number">
				' . $summary['warnings'] . '
			</div>
			<div class="edac-panel-number-label">Warning' . ( ( 1 !== $summary['warnings'] ) ? 's' : '' ) . '</div>
		</div>
		<div class="edac-summary-stat edac-summary-ignored">
			<div class="edac-panel-number">
				' . $summary['ignored'] . '
			</div>
			<div class="edac-panel-number-label">Ignored Item' . ( ( 1 !== $summary['ignored'] ) ? 's' : '' ) . '</div>
		</div>
	</div>
	<div class="edac-summary-readability">
		<div class="edac-summary-readability-level">
			<div><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/readability icon navy.png" alt="" width="54"></div>
			<div class="edac-panel-number' . ( ( (int) $summary['readability'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">
				' . $summary['readability'] . '
			</div>
			<div class="edac-panel-number-label' . ( ( (int) $summary['readability'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">Reading <br />Level</div>
		</div>
		<div class="edac-summary-readability-summary">
			<div class="edac-summary-readability-summary-icon' . ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['readability'] <= 9 ) ? ' active' : '' ) . '"></div>
			<div class="edac-summary-readability-summary-text' . ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['readability'] <= 9 ) ? ' active' : '' ) . '">' . $simplified_summary_text . '</div>
		</div>
	</div>
	<div class="edac-summary-disclaimer"><small>* True accessibility requires manual testing in addition to automated scans. <a href="https://a11ychecker.com/help4280">Learn how to manually test for accessibility</a>.</small></div>
	';

	if ( ! $html ) {

		$error = new WP_Error( '-3', 'No summary to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( wp_json_encode( $html ) );
}

/**
 * Summary Data
 *
 * @param int $post_id ID of the post.
 * @return array
 */
function edac_summary( $post_id ) {
	global $wpdb;
	$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
	$summary    = array();

	// Check if table exists.
	if ( ! $table_name ) {
		return $summary;
	}

	// Passed Tests.
	$rules = edac_register_rules();

	// if ANWW is active remove link_blank for details meta box.
	if ( EDAC_ANWW_ACTIVE ) {
		$rules = edac_remove_element_with_value( $rules, 'slug', 'link_blank' );
	}

	$rules_passed = array();

	if ( $rules ) {
		foreach ( $rules as $rule ) {
			$postid = $post_id;
			$siteid = get_current_blog_id();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching  -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
			$rule_count = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i where rule = %s and siteid = %d and postid = %d and ignre = %d', $table_name, $rule['slug'], $siteid, $postid, 0 ) );

			if ( ! $rule_count ) {
				$rules_passed[] = $rule['slug'];
			}
		}
	}

	$summary['passed_tests'] = round( count( $rules_passed ) / count( $rules ) * 100 );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$summary['errors'] = intval( $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i where siteid = %d and postid = %d and ruletype = %s and ignre = %d', $table_name, get_current_blog_id(), $post_id, 'error', 0 ) ) );

	// count warnings.
	$warnings_parameters = array( get_current_blog_id(), $post_id, 'warning', 0 );
	$warnings_where      = 'WHERE siteid = siteid = %d and postid = %d and ruletype = %s and ignre = %d';
	if ( EDAC_ANWW_ACTIVE ) {
		array_push( $warnings_parameters, 'link_blank' );
		$warnings_where .= ' and rule != %s';
	}
	$query = 'SELECT count(*) FROM ' . $table_name . ' ' . $warnings_where;
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$summary['warnings'] = intval( $wpdb->get_var( $wpdb->prepare( $query, $warnings_parameters ) ) );

	// count ignored issues.
	$ignored_parameters = array( get_current_blog_id(), $post_id, 1 );
	$ignored_where      = 'WHERE siteid = %d and postid = %d and ignre = %d';
	if ( EDAC_ANWW_ACTIVE ) {
		array_push( $ignored_parameters, 'link_blank' );
		$ignored_where .= ' and rule != %s';
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared , WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$summary['ignored'] = intval( $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $table_name $ignored_where", $ignored_parameters ) ) );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$summary['contrast_errors'] = intval( $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM %i where siteid = %d and postid = %d and rule = %s and ignre = %d', $table_name, get_current_blog_id(), $post_id, 'color_contrast_failure', 0 ) ) );

	// remove color contrast from errors count.
	$summary['errors'] = $summary['errors'] - $summary['contrast_errors'];

	// issue density.
	$issue_count = $summary['warnings'] + $summary['errors'] + $summary['contrast_errors'];

	$issue_density_array = get_post_meta( $post_id, '_edac_density_data' );

	if ( is_array( $issue_density_array ) &&
		count( $issue_density_array ) > 0 &&
		count( $issue_density_array[0] ) > 0
	) {

		$element_count  = $issue_density_array[0][0];
		$content_length = $issue_density_array[0][1];

		$issue_density = edac_get_issue_density( $issue_count, $element_count, $content_length );

		if ( ! add_post_meta( $post_id, '_edac_issue_density', $issue_density, true ) ) {
			update_post_meta( $post_id, '_edac_issue_density', $issue_density );
		}
	} else {
		delete_post_meta( $post_id, '_edac_issue_density' );
	}

	// reading grade level.
	$content_post = get_post( $post_id );

	$content = $content_post->post_content;
	$content = wp_filter_nohtml_kses( $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	$summary['content_grade'] = 0;
	if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
		$text_statistics          = new DaveChild\TextStatistics\TextStatistics();
		$summary['content_grade'] = floor( $text_statistics->fleschKincaidGradeLevel( $content ) );
	}

	$summary['readability'] = ( 0 === $summary['content_grade'] ) ? 'N/A' : edac_ordinal( $summary['content_grade'] );

	// simplified summary.
	$summary['simplified_summary'] = get_post_meta( $post_id, '_edac_simplified_summary', true ) ? true : false;

	// save summary data as post meta.
	if ( ! add_post_meta( $post_id, '_edac_summary', $summary, true ) ) {
		update_post_meta( $post_id, '_edac_summary', $summary );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_passed_tests', $summary['passed_tests'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_passed_tests', $summary['passed_tests'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_errors', $summary['errors'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_errors', $summary['errors'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_warnings', $summary['warnings'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_warnings', $summary['warnings'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_ignored', $summary['ignored'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_ignored', $summary['ignored'] );
	}

	if ( ! add_post_meta( $post_id, '_edac_summary_contrast_errors', $summary['contrast_errors'], true ) ) {
		update_post_meta( $post_id, '_edac_summary_contrast_errors', $summary['contrast_errors'] );
	}

	return $summary;
}

/**
 * Update post meta when Accessibility New Window Warning pluing is installed or uninstalled
 *
 * @return void
 */
function edac_anww_update_post_meta() {

	$option_name = 'edac_anww_update_post_meta';

	if ( get_option( $option_name ) === false && EDAC_ANWW_ACTIVE ) {
		update_option( $option_name, true );
	} elseif ( get_option( $option_name ) === true && ! EDAC_ANWW_ACTIVE ) {
		delete_option( $option_name );
	}
	edac_update_post_meta( 'link_blank' );
}

/**
 * Update post meta by rule
 *
 * @param string $rule rule name.
 * @return void
 */
function edac_update_post_meta( $rule ) {
	global $wpdb;
	$site_id = get_current_blog_id();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$posts = $wpdb->get_results( $wpdb->prepare( 'SELECT postid FROM %i WHERE rule = %s and siteid = %d', $wpdb->prefix . 'accessibility_checker', $rule, $site_id ), ARRAY_A );

	if ( $posts ) {
		foreach ( $posts as $post ) {
			edac_summary( $post['postid'] );
		}
	}
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

	return $rule['info_url'] . '?utm_source=accessibility-checker&utm_medium=software&utm_term=' . esc_attr( $rule['slug'] ) . '&utm_content=content-analysis&utm_campaign=wordpress-general&php_version=' . PHP_VERSION . '&platform=wordpress&platform_version=' . $wp_version . '&software=free&software_version=' . EDAC_VERSION . '&days_active=' . $days_active . '';
}

/**
 * Details Ajax
 *
 * @return void
 *
 *  - '-1' means that nonce could not be varified
 *  - '-2' means that the post ID was not specified
 *  - '-3' means that the table name is not valid
 *  - '-4' means that there isn't any details to return
 */
function edac_details_ajax() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	$html = '';
	global $wpdb;
	$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
	$postid     = intval( $_REQUEST['post_id'] );
	$siteid     = get_current_blog_id();

	// Send error if table name is not valid.
	if ( ! $table_name ) {

		$error = new WP_Error( '-3', 'Invalid table name' );
		wp_send_json_error( $error );

	}

	$rules = edac_register_rules();
	if ( $rules ) {

		// if ANWW is active remove link_blank for details meta box.
		if ( EDAC_ANWW_ACTIVE ) {
			$rules = edac_remove_element_with_value( $rules, 'slug', 'link_blank' );
		}

		// separate rule types.
		$passed_rules  = array();
		$error_rules   = edac_remove_element_with_value( $rules, 'rule_type', 'warning' );
		$warning_rules = edac_remove_element_with_value( $rules, 'rule_type', 'error' );

		// add count, unset passed error rules and add passed rules to array.
		if ( $error_rules ) {
			foreach ( $error_rules as $key => $error_rule ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
				$count = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM %i where postid = %d and rule = %s and siteid = %d and ignre = %d', $table_name, $postid, $error_rule['slug'], $siteid, 0 ), ARRAY_A ) );
				if ( $count ) {
					$error_rules[ $key ]['count'] = $count;
				} else {
					$error_rule['count'] = 0;
					$passed_rules[]      = $error_rule;
					unset( $error_rules[ $key ] );
				}
			}
		}

		// add count, unset passed warning rules and add passed rules to array.
		if ( $warning_rules ) {
			foreach ( $warning_rules as $key => $error_rule ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
				$count = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM %i where postid = %d and rule = %s and siteid = %d and ignre = %d', $table_name, $postid, $error_rule['slug'], $siteid, 0 ), ARRAY_A ) );
				if ( $count ) {
					$warning_rules[ $key ]['count'] = $count;
				} else {
					$error_rule['count'] = 0;
					$passed_rules[]      = $error_rule;
					unset( $warning_rules[ $key ] );
				}
			}
		}
	}

	// sort error rules by count.
	usort(
		$error_rules,
		function ( $a, $b ) {

			return strcmp( $b['count'], $a['count'] );
		}
	);

	// sort warning rules by count.
	usort(
		$warning_rules,
		function ( $a, $b ) {

			return strcmp( $b['count'], $a['count'] );
		}
	);

	// sort passed rules array by title.
	usort(
		$passed_rules,
		function ( $a, $b ) {

			return strcmp( $b['title'], $a['title'] );
		}
	);

	// merge rule arrays together.
	$rules = array_merge( $error_rules, $warning_rules, $passed_rules );

	if ( $rules ) {
		$ignore_permission = apply_filters( 'edac_ignore_permission', true );
		foreach ( $rules as $rule ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
			$results        = $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment, ignre_global FROM %i where postid = %d and rule = %s and siteid = %d', $table_name, $postid, $rule['slug'], $siteid ), ARRAY_A );
			$count_classes  = ( 'error' === $rule['rule_type'] ) ? ' edac-details-rule-count-error' : ' edac-details-rule-count-warning';
			$count_classes .= ( 0 !== $rule['count'] ) ? ' active' : '';

			$count_ignored = 0;
			$ignores       = array_column( $results, 'ignre' );
			if ( $ignores ) {
				foreach ( $ignores as $ignore ) {
					if ( true === (bool) $ignore ) {
						++$count_ignored;
					}
				}
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
			$expand_rule = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM %i where postid = %d and rule = %s and siteid = %d', $table_name, $postid, $rule['slug'], $siteid ), ARRAY_A ) );

			$tool_tip_link = edac_documentation_link( $rule );

			$html .= '<div class="edac-details-rule">';

				$html .= '<div class="edac-details-rule-title">';

					$html     .= '<h3>';
						$html .= '<span class="edac-details-rule-count' . $count_classes . '">' . $rule['count'] . '</span> ';
						$html .= esc_html( $rule['title'] );
			if ( $count_ignored > 0 ) {
				$html .= '<span class="edac-details-rule-count-ignore">' . $count_ignored . ' Ignored Items</span>';
			}
					$html .= '</h3>';
					$html .= '<a href="' . $tool_tip_link . '" class="edac-details-rule-information" target="_blank" aria-label="Read documentation for ' . esc_html( $rule['title'] ) . '"><span class="dashicons dashicons-info"></span></a>';
					$html .= ( $expand_rule ) ? '<button class="edac-details-rule-title-arrow" aria-expanded="false" aria-controls="edac-details-rule-records-' . $rule['slug'] . '" aria-label="Expand issues for ' . esc_html( $rule['title'] ) . '"><i class="dashicons dashicons-arrow-down-alt2"></i></button>' : '';

				$html .= '</div>';

			if ( $results ) {

				$html .= '<div id="edac-details-rule-records-' . $rule['slug'] . '" class="edac-details-rule-records">';

					$html .=
					'<div class="edac-details-rule-records-labels">
						<div class="edac-details-rule-records-labels-label" aria-hidden="true">
							Affected Code
						</div>
						<div class="edac-details-rule-records-labels-label" aria-hidden="true">
							Image
						</div>
						<div class="edac-details-rule-records-labels-label" aria-hidden="true">
							Actions
						</div>
					</div>';

				foreach ( $results as $row ) {

					$id                      = intval( $row['id'] );
					$ignore                  = intval( $row['ignre'] );
					$ignore_class            = $ignore ? ' active' : '';
					$ignore_label            = $ignore ? 'Ignored' : 'Ignore';
					$ignore_user             = intval( $row['ignre_user'] );
					$ignore_user_info        = get_userdata( $ignore_user );
					$ignore_username         = is_object( $ignore_user_info ) ? '<strong>Username:</strong> ' . $ignore_user_info->user_login : '';
					$ignore_date             = ( $row['ignre_date'] && '0000-00-00 00:00:00' !== $row['ignre_date'] ) ? '<strong>Date:</strong> ' . gmdate( 'F j, Y g:i a', strtotime( esc_html( $row['ignre_date'] ) ) ) : '';
					$ignore_comment          = esc_html( $row['ignre_comment'] );
					$ignore_action           = $ignore ? 'disable' : 'enable';
					$ignore_type             = $rule['rule_type'];
					$ignore_submit_label     = $ignore ? 'Stop Ignoring' : 'Ignore This ' . $ignore_type;
					$ignore_comment_disabled = $ignore ? 'disabled' : '';
					$ignore_global           = intval( $row['ignre_global'] );

					// check for images and svgs in object code.
					$object_img      = null;
					$object_svg      = null;
					$object_img_html = str_get_html( htmlspecialchars_decode( $row['object'], ENT_QUOTES ) );
					if ( $object_img_html ) {
						$object_img_elements = $object_img_html->find( 'img' );
						$object_svg_elements = $object_img_html->find( 'svg' );
						if ( $object_img_elements ) {
							foreach ( $object_img_elements as $element ) {
								$object_img = $element->getAttribute( 'src' );
								if ( $object_img ) {
									break;
								}
							}
						} elseif ( $object_svg_elements ) {
							foreach ( $object_svg_elements as $element ) {
								$object_svg = $element;
								break;
							}
						}
					}

					$html .= '<h4 class="screen-reader-text">Issue ID ' . $id . '</h4>';

					$html .= '<div id="edac-details-rule-records-record-' . $id . '" class="edac-details-rule-records-record">';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-object">';

							$html .= '<code>' . esc_html( $row['object'] ) . '</code>';

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-image">';

					if ( $object_img ) {
						$html .= '<img src="' . $object_img . '" alt="image for issue ' . $id . '" />';
					} elseif ( $object_svg ) {
						$html .= $object_svg;
					}

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-actions">';

							$html .= '<button class="edac-details-rule-records-record-actions-ignore' . $ignore_class . '" aria-expanded="false" aria-controls="edac-details-rule-records-record-ignore-' . $row['id'] . '">' . EDAC_SVG_IGNORE_ICON . '<span class="edac-details-rule-records-record-actions-ignore-label">' . $ignore_label . '</span></button>';

					if ( 'missing_headings' !== $rule['slug'] ) {

						$url = add_query_arg(
							array(
								'edac'       => $id,
								'edac_nonce' => wp_create_nonce( 'edac_highlight' ),
							),
							get_the_permalink( $postid )
						);

						$html .= '<a href="' . $url . '" class="edac-details-rule-records-record-actions-highlight-front" target="_blank" aria-label="' . __( 'View, opens a new window', 'accessibility-checker' ) . '" ><span class="dashicons dashicons-welcome-view-site"></span>View on page</a>';
					}

						$html .= '</div>';

						$html .= '<div id="edac-details-rule-records-record-ignore-' . $row['id'] . '" class="edac-details-rule-records-record-ignore">';

							$html     .= '<div class="edac-details-rule-records-record-ignore-info">';
								$html .= '<span class="edac-details-rule-records-record-ignore-info-user">' . $ignore_username . '</span>';

								$html .= ' <span class="edac-details-rule-records-record-ignore-info-date">' . $ignore_date . '</span>';
							$html     .= '</div>';

							$html .= ( true === $ignore_permission || ! empty( $ignore_comment ) ) ? '<label for="edac-details-rule-records-record-ignore-comment-' . $id . '">Comment</label><br>' : '';
							$html .= ( true === $ignore_permission || ! empty( $ignore_comment ) ) ? '<textarea rows="4" class="edac-details-rule-records-record-ignore-comment" id="edac-details-rule-records-record-ignore-comment-' . $id . '" ' . $ignore_comment_disabled . '>' . $ignore_comment . '</textarea>' : '';

					if ( $ignore_global ) {
						$html .= ( true === $ignore_permission ) ? '<a href="' . admin_url( 'admin.php?page=accessibility_checker_ignored&tab=global' ) . '" class="edac-details-rule-records-record-ignore-global">Manage Globally Ignored</a>' : '';
					} else {
						$html .= ( true === $ignore_permission ) ? '<button class="edac-details-rule-records-record-ignore-submit" data-id=' . $id . ' data-action=' . $ignore_action . ' data-type=' . $ignore_type . '>' . EDAC_SVG_IGNORE_ICON . ' <span class="edac-details-rule-records-record-ignore-submit-label">' . $ignore_submit_label . '<span></button>' : '';
					}

							$html .= ( false === $ignore_permission && false === $ignore ) ? __( 'Your user account doesn\'t have permission to ignore this issue.', 'accessibility-checker' ) : '';

						$html .= '</div>';

					$html .= '</div>';

				}

				$html .= '</div>';

			}

			$html .= '</div>';
		}
	}

	if ( ! $html ) {

		$error = new WP_Error( '-4', 'No details to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( wp_json_encode( $html ) );
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
function edac_readability_ajax() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

		$error = new WP_Error( '-1', 'Permission Denied' );
		wp_send_json_error( $error );

	}

	if ( ! isset( $_REQUEST['post_id'] ) ) {

		$error = new WP_Error( '-2', 'The post ID was not set' );
		wp_send_json_error( $error );

	}

	$post_id                        = intval( $_REQUEST['post_id'] );
	$html                           = '';
	$simplified_summary             = get_post_meta( $post_id, '_edac_simplified_summary', true ) ? get_post_meta( $post_id, '_edac_simplified_summary', true ) : '';
	$simplified_summary_position    = get_option( 'edac_simplified_summary_position', $default = false );
	$content_post                   = get_post( $post_id );
	$content                        = $content_post->post_content;
	$content                        = apply_filters( 'the_content', $content );
	$oxygen_builder_shortcodes_meta = get_post_meta( $post_id, 'ct_builder_shortcodes', true );

	// add oxygen builder shortcode content to readability scan.
	if ( $oxygen_builder_shortcodes_meta ) {
		$oxygen_builder_shortcodes = do_shortcode( $oxygen_builder_shortcodes_meta );
		if ( $oxygen_builder_shortcodes ) {
			$content .= $oxygen_builder_shortcodes;
		}
	}

	$content = apply_filters( 'edac_filter_readability_content', $content, $post_id );
	$content = wp_filter_nohtml_kses( $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	// get readability metadata and determine if a simplified summary is required.
	$edac_summary           = get_post_meta( $post_id, '_edac_summary', true );
	$post_grade_readability = ( isset( $edac_summary['readability'] ) ) ? $edac_summary['readability'] : 0;
	$post_grade             = (int) filter_var( $post_grade_readability, FILTER_SANITIZE_NUMBER_INT );
	$post_grade_failed      = ( $post_grade < 9 ) ? false : true;

	$simplified_summary_grade = 0;
	if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
		$text_statistics          = new DaveChild\TextStatistics\TextStatistics();
		$simplified_summary_grade = edac_ordinal( floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) ) );
	}

	$simplified_summary_grade_failed = ( $simplified_summary_grade > 9 ) ? true : false;
	$simplified_summary_prompt       = get_option( 'edac_simplified_summary_prompt' );

	$html .= '<ul class="edac-readability-list">';

	$html .= '<li class="edac-readability-list-item edac-readability-grade-level">
	<span class="edac-readability-list-item-icon dashicons ' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
	<p class="edac-readability-list-item-title">Post Reading Grade Level: <strong class="' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . ( ( 0 === $post_grade ) ? 'None' : $post_grade_readability ) . '</strong><br /></p>';
	if ( $post_grade_failed ) {
		$html .= '<p class="edac-readability-list-item-description">Your post has a reading level higher than 9th grade. Web Content Accessibility Guidelines (WCAG) at the AAA level require a simplified summary of your post that is 9th grade or below.</p>';
	} elseif ( 0 === $post_grade ) {
		$html .= '<p class="edac-readability-list-item-description">Your post does not contain enough content to calculate its reading level.</p>';
	} else {
		$html .= '<p class="edac-readability-list-item-description">A simplified summary is not necessary when content reading level is 9th grade or below. Choose when to prompt for a simplified summary on the settings page.</p>';
	}
		$html .= '</li>';

	if ( $post_grade_failed ) {

		if ( $simplified_summary && 'none' !== $simplified_summary_prompt ) {
			$html .= '<li class="edac-readability-list-item edac-readability-summary-grade-level">
				<span class="edac-readability-list-item-icon dashicons ' . ( ( $simplified_summary_grade_failed ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
				<p class="edac-readability-list-item-title">Simplified Summary Reading Grade Level: <strong class="' . ( ( $simplified_summary_grade_failed ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . $simplified_summary_grade . '</strong></p>
				<p class="edac-readability-list-item-description">Your simplified summary has a reading level ' . ( ( $simplified_summary_grade_failed > 9 ) ? 'higher' : 'lower' ) . ' than 9th grade.</p>
			</li>';
		}

		if ( 'none' === $simplified_summary_prompt ) {

			$html .=
			'<li class="edac-readability-list-item edac-readability-summary-position">
				<span class="edac-readability-list-item-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/warning icon yellow.png" alt="" width="22"></span>
				<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
					<p class="edac-readability-list-item-description">Your Prompt for Simplified Summary is set to "never." If you would like the simplified summary to be displayed automatically, you can change this on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
			</li>';

		} elseif ( 'none' !== $simplified_summary_position ) {

			$html .=
			'<li class="edac-readability-list-item edac-readability-summary-position">
				<span class="edac-readability-list-item-icon dashicons dashicons-saved"></span>
				<p class="edac-readability-list-item-title">Simplified summary is being automatically inserted <strong>' . $simplified_summary_position . ' the content</strong>.</p>
					<p class="edac-readability-list-item-description">Set where the Simplified Summary is inserted into the content on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
			</li>';

		} else {

			$html .=
			'<li class="edac-readability-list-item edac-readability-summary-position">
				<span class="edac-readability-list-item-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/warning icon yellow.png" alt="" width="22"></span>
				<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
					<p class="edac-readability-list-item-description">Your Simplified Summary location is set to "manually" which requires a function be added to your page template. If you would like the simplified summary to be displayed automatically, you can change this on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
			</li>';

		}
	}

	$html .= '</ul>';

	if ( ( $post_grade_failed || 'always' === $simplified_summary_prompt ) && ( 'none' !== $simplified_summary_prompt ) ) {
		$html .=
		'</form>
		<form action="/" class="edac-readability-simplified-summary">
			<label for="edac-readability-text">Simplified Summary</label>
			<textarea name="" id="edac-readability-text" cols="30" rows="10">' . $simplified_summary . '</textarea>
			<input type="submit">
		</form>';
	}

	global $wp_version;
	$html .= '<span class="dashicons dashicons-info"></span><a href="https://a11ychecker.com/help3265?utm_source=accessibility-checker&utm_medium=software&utm_term=readability&utm_content=content-analysis&utm_campaign=wordpress-general&php_version=' . PHP_VERSION . '&platform=wordpress&platform_version=' . $wp_version . '&software=free&software_version=' . EDAC_VERSION . '&days_active=' . edac_days_active() . '" target="_blank">Learn more about improving readability and simplified summary requirements</a>';

	if ( ! $html ) {

		$error = new WP_Error( '-3', 'No readability data to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( wp_json_encode( $html ) );
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
function edac_update_simplified_summary() {

	// nonce security.
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

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

	$post_id = intval( $_REQUEST['post_id'] );
	$summary = sanitize_text_field( $_REQUEST['summary'] );

	if ( ! add_post_meta( $post_id, '_edac_simplified_summary', $summary, true ) ) {
		update_post_meta( $post_id, '_edac_simplified_summary', $summary );
	}

	$simplified_summary = get_post_meta( $post_id, '_edac_simplified_summary', $single = true );

	if ( ! $simplified_summary ) {

		$error = new WP_Error( '-4', 'No simplified summary to return' );
		wp_send_json_error( $error );

	}

	wp_send_json_success( wp_json_encode( $simplified_summary ) );
}

/**
 * Output simplified summary
 *
 * @param string $content The content.
 * @return string
 */
function edac_output_simplified_summary( $content ) {
	$simplified_summary_prompt = get_option( 'edac_simplified_summary_prompt' );
	if ( 'none' === $simplified_summary_prompt ) {
		return $content;
	}
	$simplified_summary          = edac_simplified_summary_markup( get_the_ID() );
	$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );
	if ( $simplified_summary && 'before' === $simplified_summary_position ) {
		return $simplified_summary . $content;
	}
	if ( $simplified_summary && 'after' === $simplified_summary_position ) {
		return $content . $simplified_summary;
	}
	return $content;
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
	echo wp_kses_post( edac_simplified_summary_markup( $post ) );
}

/**
 * Simplified summary markup
 *
 * @param int $post Post ID.
 * @return string
 */
function edac_simplified_summary_markup( $post ) {
	$simplified_summary         = get_post_meta( $post, '_edac_simplified_summary', true ) ? get_post_meta( $post, '_edac_simplified_summary', true ) : '';
	$simplified_summary_heading = apply_filters(
		'edac_filter_simplified_summary_heading',
		esc_html__( 'Simplified Summary', 'accessibility-checker' )
	);

	if ( $simplified_summary ) {
		return '<div class="edac-simplified-summary"><h2>' . wp_kses_post( $simplified_summary_heading ) . '</h2><p>' . wp_kses_post( $simplified_summary ) . '</p></div>';
	}
	return '';
}

/**
 * Get accessibility statement
 *
 * @return string
 */
function edac_get_accessibility_statement() {
	$statement              = '';
	$add_footer_statement   = get_option( 'edac_add_footer_accessibility_statement' );
	$include_statement_link = get_option( 'edac_include_accessibility_statement_link' );
	$policy_page            = get_option( 'edac_accessibility_policy_page' );
	$policy_page            = is_numeric( $policy_page ) ? get_page_link( $policy_page ) : $policy_page;

	if ( $add_footer_statement ) {
		$statement .= get_bloginfo( 'name' ) . ' ' . esc_html__( 'uses', 'accessibility-checker' ) . ' <a href="https://equalizedigital.com/accessibility-checker" target="_blank" aria-label="' . esc_attr__( 'Accessibility Checker', 'accessibility-checker' ) . ', opens a new window">' . esc_html__( 'Accessibility Checker', 'accessibility-checker' ) . '</a> ' . esc_html__( 'to monitor our website\'s accessibility. ', 'accessibility-checker' );
	}

	if ( $include_statement_link && $policy_page ) {
		$statement .= esc_html__( 'Read our ', 'accessibility-checker' ) . '<a href="' . $policy_page . '">' . esc_html__( 'Accessibility Policy', 'accessibility-checker' ) . '</a>.';
	}

	return $statement;
}

/**
 * Output simplified summary
 *
 * @return void
 */
function edac_output_accessibility_statement() {
	$statement = edac_get_accessibility_statement();
	if ( ! empty( $statement ) ) {
		echo '<p class="edac-accessibility-statement" style="text-align: center; max-width: 800px; margin: auto; padding: 15px;"><small>' . wp_kses_post( $statement ) . '</small></p>';
	}
}

/**
 * Handle AJAX request to dismiss Welcome CTA
 *
 * @return void
 */
function edac_dismiss_welcome_cta() {
	// Update user meta to indicate the button has been clicked.
	update_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true );

	// Return success response.
	wp_send_json( 'success' );
}

/**
 * Handle AJAX request to dismiss dashboard CTA
 *
 * @return void
 */
function edac_dismiss_dashboard_cta() {
	// Update user meta to indicate the button has been clicked.
	update_user_meta( get_current_user_id(), 'edac_dashboard_cta_dismissed', true );

	// Return success response.
	wp_send_json( 'success' );
}

/**
 * Handle AJAX request to opt in to email
 *
 * @return void
 */
function edac_email_opt_in() {
	// Update user meta to indicate the button has been clicked.
	update_user_meta( get_current_user_id(), 'edac_email_optin', true );

	// Return success response.
	wp_send_json( 'success' );
}

// Add a filter for lazyloading images using the perfmatters_lazyload hook.
add_filter(
	'perfmatters_lazyload',
	function ( $lazyload ) {
		if ( ! isset( $_GET['edac_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['edac_nonce'] ), 'edac_highlight' ) ) {
			return $lazyload;
		}
		if ( isset( $_GET['edac'] ) ) {
			return false;
		}
		return $lazyload;
	}
);
