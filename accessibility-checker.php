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
 * Version:           1.16.4
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
	define( 'EDAC_VERSION', '1.16.4' );
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

if ( class_exists( 'EDAC\Inc\Plugin' ) ) {
	new Plugin();
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
require_once plugin_dir_path( __FILE__ ) . 'includes/options-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/validate.php';
/**
 * Filters and Actions
 */
add_action( 'admin_menu', 'edac_add_options_page' );
add_action( 'admin_init', 'edac_register_setting' );
add_action( 'admin_head', 'edac_post_on_load' );
add_filter( 'save_post', 'edac_save_post', 10, 3 );
add_action( 'pre_get_posts', 'edac_show_draft_posts' );
if ( is_plugin_active( 'oxygen/functions.php' ) ) {
	add_action( 'added_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
	add_action( 'updated_post_meta', 'edac_oxygen_builder_save_post', 10, 4 );
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
add_action( 'init', 'edac_include_rules_files' );

/**
 * Include Rule Functions
 *
 * @return void
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'accessibility-checker/v1',
			'/generate-summary',
			[
				'methods'             => 'POST',
				'callback'            => 'edac_generate_summary',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}
);

/**
 * Generate a summary of the post content using the Eden AI API.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response|WP_Error The REST response object or error.
 */
function edac_generate_summary( WP_REST_Request $request ) {
	// Log the start of the function.
	error_log( 'edac_generate_summary function initiated.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	// Retrieve and log the nonce.
	$nonce = $request->get_header( 'X-WP-Nonce' );
	error_log( 'Received nonce: ' . print_r( $nonce, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r

	// Verify nonce.
	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		error_log( 'Nonce verification failed.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return new WP_Error( 'rest_invalid_nonce', 'Invalid nonce', [ 'status' => 403 ] );
	}
	error_log( 'Nonce verification succeeded.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	// Retrieve and log the post_id parameter.
	$post_id = $request->get_param( 'post_id' );
	error_log( 'Received post_id: ' . print_r( $post_id, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r

	if ( ! $post_id ) {
		error_log( 'No post_id provided.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return new WP_Error( 'no_post_id', 'Post ID is required', [ 'status' => 400 ] );
	}

	// Retrieve and log the post object.
	$post = get_post( $post_id );
	if ( ! $post ) {
		error_log( 'Invalid post_id: ' . print_r( $post_id, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		return new WP_Error( 'invalid_post_id', 'Invalid Post ID', [ 'status' => 404 ] );
	}
	error_log( 'Post retrieved successfully.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	// Process the post content.
	$content = apply_filters( 'the_content', $post->post_content );
	$content = wp_strip_all_tags( $content );
	error_log( 'Processed content: ' . substr( $content, 0, 100 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	// Prepare the Eden AI API request.
	$api_key  = 'API_KEY'; // Replace with your actual Eden AI API key.
	$api_url  = 'https://api.edenai.run/v2/text/summarize';
	$api_body = [
		'providers' => [ 'openai' ], // Specify the provider; adjust as needed.
		'text'      => 'Summarize the following content in a single paragraph suitable for an 8th-grade or below student. Use simple vocabulary and concise sentences to ensure readability. Paragraph must pass the Flesch Kincaid Grade Level formula: ' . $content,
		'language'  => 'en', // Set the language code; adjust as needed.
	];
	error_log( 'Eden AI API request body: ' . print_r( $api_body, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r

	// Send the request to the Eden AI API.
	$response = wp_remote_post(
		$api_url,
		[
			'headers' => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $api_body ),
		]
	);

	// Check for errors in the API response.
	if ( is_wp_error( $response ) ) {
		error_log( 'Eden AI API request failed: ' . print_r( $response->get_error_message(), true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
		return new WP_Error( 'ai_api_error', 'Error communicating with AI API', [ 'status' => 500 ] );
	}
	error_log( 'Eden AI API request succeeded.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	// Retrieve and decode the response body.
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	error_log( 'Eden AI API response data: ' . print_r( $data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r

	// Check for the summary in the response.
	if ( ! empty( $data ) && is_array( $data ) ) {
		// Get the first provider's data.
		$provider_data = reset( $data );
		if ( ! empty( $provider_data['result'] ) ) {
			$summary = trim( $provider_data['result'] );
			error_log( 'Generated summary: ' . $summary ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			return rest_ensure_response(
				[
					'success' => true,
					'summary' => $summary,
				]
			);
		} else {
			error_log( 'Eden AI did not return a summary.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return new WP_Error( 'ai_no_summary', 'AI failed to generate a summary', [ 'status' => 500 ] );
		}
	} else {
		error_log( 'Eden AI response data is empty or not an array.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return new WP_Error( 'ai_no_summary', 'AI failed to generate a summary', [ 'status' => 500 ] );
	}

	// Return the generated summary.
	$summary = trim( $data['openai']['result'] );
	error_log( 'Generated summary: ' . $summary ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	return rest_ensure_response(
		[
			'success' => true,
			'summary' => $summary,
		]
	);
}
