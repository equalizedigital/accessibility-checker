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
 * Version:           1.30.1
 * Requires PHP:      7.4
 * Author:            Equalize Digital
 * Author URI:        https://equalizedigital.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       accessibility-checker
 * Domain Path:       /languages
 */

use EDAC\Inc\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Include plugin dependency.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Setup constants.
 */

// Current plugin version.
if ( ! defined( 'EDAC_VERSION' ) ) {
	define( 'EDAC_VERSION', '1.30.1' );
}

// Current database version.
if ( ! defined( 'EDAC_DB_VERSION' ) ) {
	define( 'EDAC_DB_VERSION', '1.0.4' );
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

if ( class_exists( 'EDAC\Inc\Plugin' ) ) {
	new Plugin();
}

/**
 * Import Resources
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/deprecated/deprecated.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/activation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/deactivation.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helper-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/options-page.php';

/**
 * Filters and Actions
 */
add_action( 'admin_menu', 'edac_add_options_page' );
add_action( 'admin_init', 'edac_register_setting' );

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

	// Use the new class-based rules system.
	$default_rules = \EqualizeDigital\AccessibilityChecker\Rules\RuleRegistry::load_rules();

	/**
	 * Filter the default rules.
	 *
	 * Allows removing or adding rules. If you are adding a rule make
	 * sure you have added a function matching the pattern:
	 * `edac_rule_{$rule_id}`.
	 *
	 * @since 1.4.0
	 *
	 * @param array $default_rules The default rules.
	 */
	$default_rules = apply_filters( 'edac_filter_register_rules', $default_rules );

	return $default_rules;
}
