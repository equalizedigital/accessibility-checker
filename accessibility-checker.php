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
 * Version:           1.8.0
 * Author:            Equalize Digital
 * Author URI:        https://equalizedigital.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       accessibility-checker
 * Domain Path:       /languages
 */

use EDAC\Admin\Options;
use EDAC\Admin\Post_Options;
use EDAC\Inc\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Check for WordPress Playground.
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-playground-check.php';
if ( ! ( new EDAC\Inc\Playground_Check() )->should_load ) {
	return;
}

// Include plugin dependency.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Setup constants.
 */

// Current plugin version.
if ( ! defined( 'EDAC_VERSION' ) ) {
	define( 'EDAC_VERSION', '1.8.0' );
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
define( 'EDAC_SVG_IGNORE_ICON', file_get_contents( __DIR__ . '/assets/images/ignore-icon.svg' ) );


/**
 * Plugin Activation & Deactivation
 */
register_activation_hook( __FILE__, 'edac_activation' );
register_deactivation_hook( __FILE__, 'edac_deactivation' );

/* ***************************** CLASS AUTOLOADING *************************** */
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
	include_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}


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
require_once plugin_dir_path( __FILE__ ) . 'includes/deprecated/deprecated.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/activation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/deactivation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/meta-boxes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/options-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/validate.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/insert.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/purge.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/system-info.php';

if ( class_exists( 'EDAC\Inc\Plugin' ) ) {
	new Plugin();
}



/**
 * Filters and Actions
 */
add_action( 'admin_init', 'edac_update_database', 10 );
add_action( 'add_meta_boxes', 'edac_register_meta_boxes' );
add_action( 'admin_menu', 'edac_add_options_page' );
add_action( 'admin_init', 'edac_register_setting' );
add_action( 'admin_head', 'edac_post_on_load' );
add_filter( 'save_post', 'edac_save_post', 10, 3 );
add_action( 'wp_trash_post', 'edac_delete_post' );
add_action( 'pre_get_posts', 'edac_show_draft_posts' );
add_action( 'template_redirect', 'edac_before_page_render' );
add_action( 'admin_init', 'edac_process_actions' );
add_action( 'edac_download_sysinfo', 'edac_tools_sysinfo_download' );
if ( is_plugin_active( 'oxygen/functions.php' ) ) {
	add_action( 'added_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
	add_action( 'updated_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
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
	if ( Options::get( 'db_version' ) !== EDAC_DB_VERSION || $wpdb->get_var( $query ) !== $table_name ) {

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

		// Update database version option.
		$option_name = 'db_version';
		$new_value   = EDAC_DB_VERSION;
		Options::set( $option_name, $new_value );

	}
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
 * @return void
 */
function edac_include_rules_files() {
	$rules = edac_register_rules();
	if ( ! $rules ) {
		return;
	}
	foreach ( $rules as $rule ) {
		if ( ( array_key_exists( 'ruleset', $rule ) && 'php' === $rule['ruleset'] )
			|| ( ! array_key_exists( 'ruleset', $rule ) && $rule['slug'] )
		) {
			require_once plugin_dir_path( __FILE__ ) . 'includes/rules/' . $rule['slug'] . '.php';
		}
	}
}
edac_include_rules_files();

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
		$post_id = is_object( $post ) ? $post->ID : null;       
		if ( null === $post_id ) {
			return;
		}

		$post_options = new Post_Options( $post_id );
		$checked      = $post_options->get( 'post_checked' );
		if ( ! $checked ) {
			edac_validate( $post->ID, $post, $action = 'load' );
		}
	}
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

	$post_options = new Post_Options( $post_id );
	
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

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
			$rule_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT count(*) FROM %i where rule = %s and siteid = %d and postid = %d and ignre = %d',
					$table_name,
					$rule['slug'],
					$siteid,
					$postid,
					0
				)
			);

			if ( ! $rule_count ) {
				$rules_passed[] = $rule['slug'];
			}
		}
	}

	$passed_tests = round( count( $rules_passed ) / count( $rules ) * 100 );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$errors = (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT count(*) FROM %i where siteid = %d and postid = %d and ruletype = %s and ignre = %d',
			$table_name,
			get_current_blog_id(),
			$post_id,
			'error',
			0
		)
	);

	// count warnings.
	$warnings_parameters = array( get_current_blog_id(), $post_id, 'warning', 0 );
	$warnings_where      = 'WHERE siteid = siteid = %d and postid = %d and ruletype = %s and ignre = %d';
	if ( EDAC_ANWW_ACTIVE ) {
		array_push( $warnings_parameters, 'link_blank' );
		$warnings_where .= ' and rule != %s';
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$warnings = (int) $wpdb->get_var(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			'SELECT count(*) FROM ' . $table_name . ' ' . $warnings_where,
			$warnings_parameters
		)
	);

	// count ignored issues.
	$ignored_parameters = array( get_current_blog_id(), $post_id, 1 );
	$ignored_where      = 'WHERE siteid = %d and postid = %d and ignre = %d';
	if ( EDAC_ANWW_ACTIVE ) {
		array_push( $ignored_parameters, 'link_blank' );
		$ignored_where .= ' and rule != %s';
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$ignored = (int) $wpdb->get_var(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared , WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			"SELECT count(*) FROM $table_name $ignored_where",
			$ignored_parameters
		)
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
	$contrast_errors = (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT count(*) FROM %i where siteid = %d and postid = %d and rule = %s and ignre = %d',
			$table_name,
			get_current_blog_id(),
			$post_id,
			'color_contrast_failure',
			0
		)
	);

	// remove color contrast from errors count.
	$errors = $errors - $contrast_errors;

	// issue density.
	$issue_count    = $warnings + $errors + $contrast_errors;
	$element_count  = $post_options->get( 'issue_density_elements' );
	$content_length = $post_options->get( 'issue_density_strlen' );
	if ( ( $element_count + $content_length ) > 0 ) {
		$issue_density = edac_get_issue_density( $issue_count, $element_count, $content_length );
		$post_options->set( 'issue_density', $issue_density );
	} else {
		$post_options->set( 'issue_density', 0 );
	}

	// reading grade level.
	$content_post = get_post( $post_id );

	$content = $content_post->post_content;
	$content = wp_filter_nohtml_kses( $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	$content_grade = 0;
	if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
		$content_grade = floor(
			( new DaveChild\TextStatistics\TextStatistics() )->fleschKincaidGradeLevel( $content )
		);
	}

	$readability = 0 === $content_grade ? 'N/A' : edac_ordinal( $content_grade );

	$post_options->set( 'readability', $readability );
	$post_options->set( 'passed_tests', $passed_tests );
	$post_options->set( 'errors', $errors );
	$post_options->set( 'warnings', $warnings );
	$post_options->set( 'ignored', $ignored );
	$post_options->set( 'contrast_errors', $contrast_errors );

	$retval = array_merge(
		$post_options->as_array(),
		array(
			'passed_tests'    => $post_options->get( 'passed_tests' ),
			'errors'          => $post_options->get( 'errors' ),
			'warnings'        => $post_options->get( 'warnings' ),
			'contrast_errors' => $post_options->get( 'contrast_errors' ),
			'ignored'         => $post_options->get( 'ignored' ),
		)
	);

	return $retval;
}

/**
 * Update post meta when Accessibility New Window Warning pluing is installed or uninstalled
 *
 * @return void
 */
function edac_anww_update_post_meta() {

	$option_name = 'edac_anww_update_post_meta';

	if ( ! Options::get( $option_name ) && EDAC_ANWW_ACTIVE ) {
		Options::set( $option_name, true );
	} elseif ( Options::get( $option_name ) && ! EDAC_ANWW_ACTIVE ) {
		Options::delete( $option_name );
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
	$posts = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT postid FROM %i WHERE rule = %s and siteid = %d',
			$wpdb->prefix . 'accessibility_checker',
			$rule,
			$site_id
		),
		ARRAY_A
	);

	if ( $posts ) {
		foreach ( $posts as $post ) {
			edac_summary( $post['postid'] );
		}
	}
}
