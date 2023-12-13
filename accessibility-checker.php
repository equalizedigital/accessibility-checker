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
if ( ! ( new EDAC\Playground_Check() )->should_load ) {
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

/**
 * Import Resources
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/deprecated.php';
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
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-frontend-highlight.php';

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
add_filter( 'the_content', 'edac_output_simplified_summary' );
add_action( 'wp_footer', 'edac_output_accessibility_statement' );
add_action( 'wp_trash_post', 'edac_delete_post' );
add_action( 'pre_get_posts', 'edac_show_draft_posts' );
add_action( 'template_redirect', 'edac_before_page_render' );
add_action( 'admin_init', 'edac_process_actions' );
add_action( 'edac_download_sysinfo', 'edac_tools_sysinfo_download' );
if ( is_plugin_active( 'oxygen/functions.php' ) ) {
	add_action( 'added_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
	add_action( 'updated_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
}
add_action( 'admin_init', 'edac_anww_update_post_meta' );
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
