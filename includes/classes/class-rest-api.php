<?php
/**
 * Class file for REST api
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Helpers;
use EDAC\Admin\Insert_Rule_Data;
use EDAC\Admin\Scans_Stats;
use EDAC\Admin\Settings;
use EDAC\Admin\Purge_Post_Data;
use EDAC\Inc\Screenshot\Screenshot_Manager;

/**
 * Class that initializes and handles the REST api
 */
class REST_Api {

	/**
	 * Screenshot manager instance.
	 *
	 * @var Screenshot_Manager
	 */
	private $screenshot_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->screenshot_manager = new Screenshot_Manager();
	}

	/**
	 * Add the class the hooks.
	 */
	public function init_hooks() {
		add_action( 'init', [ $this, 'init_rest_routes' ] );
		add_filter( 'edac_filter_js_violation_html', [ $this, 'filter_js_validation_html' ], 10, 3 );
	}

	/**
	 * Add the rest routes.
	 *
	 * @return void
	 */
	public function init_rest_routes() {

		$ns      = 'accessibility-checker/';
		$version = 'v1';

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/test',
					[
						'methods'             => [ 'GET', 'POST' ],
						'callback'            => function () {
							$messages          = [];
							$messages['time']  = time();
							$messages['perms'] = current_user_can( 'edit_posts' );

							return new \WP_REST_Response( [ 'messages' => $messages ], 200 );
						},
						'permission_callback' => function () {
							return true;
						},
					]
				);
			}
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/post-scan-results/(?P<id>\d+)',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'set_post_scan_results' ],
						'args'                => [
							'id' => [
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
							],
						],
						'permission_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					]
				);
			}
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/scans-stats',
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'get_scans_stats' ],
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					]
				);
			}
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/clear-cached-scans-stats',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'clear_cached_scans_stats' ],
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					]
				);
			}
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/scans-stats-by-post-type/(?P<slug>[a-zA-Z0-9_-]+)',
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'get_scans_stats_by_post_type' ],
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					]
				);
			}
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/scans-stats-by-post-types',
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'get_scans_stats_by_post_types' ],
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					]
				);
			}
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/clear-issues/(?P<id>\d+)',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'clear_issues_for_post' ],
						'args'                => [
							'id' => [
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
							],
						],
						'permission_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					]
				);
			}
		);

		// Exposes the scan summary data.
		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/site-summary',
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'get_site_summary' ],
						'permission_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					]
				);
			}
		);
	}

	/**
	 * REST handler to clear issues results for a given post ID.
	 *
	 * @param WP_REST_Request $request  The request passed from the REST call.
	 *
	 * @return \WP_REST_Response
	 */
	public function clear_issues_for_post( $request ) {

		if ( ! isset( $request['id'] ) ) {
			return new \WP_REST_Response( [ 'message' => 'The ID is required to be passed.' ], 400 );
		}

		$json    = $request->get_json_params();
		$post_id = (int) $request['id'];
		if ( ! isset( $json['skip_post_exists_check'] ) ) {
			$post = get_post( $post_id );
			if ( ! is_object( $post ) ) {
				return new \WP_REST_Response( [ 'message' => 'The post is not valid.' ], 400 );
			}

			$post_type  = get_post_type( $post );
			$post_types = Helpers::get_option_as_array( 'edac_post_types' );
			if ( empty( $post_types ) || ! in_array( $post_type, $post_types, true ) ) {
				return new \WP_REST_Response( [ 'message' => 'The post type is not set to be scanned.' ], 400 );
			}
		}

		// if flush is set then clear the issues for that ID.
		if ( isset( $json['flush'] ) ) {
			// purge the issues for this post.
			Purge_Post_Data::delete_post( $post_id );
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'flushed' => isset( $json['flush'] ),
				'id'      => $post_id,
			]
		);
	}

	/**
	 * Filter the html of the js validation violation.
	 *
	 * This can be used to store additional data in the html of the violation.
	 *
	 * @since 1.13.0
	 * @param string $html      The html of the violation.
	 * @param string $rule_id   The id of the rule.
	 * @param array  $violation The violation data.
	 *
	 * @return string
	 */
	public function filter_js_validation_html( string $html, string $rule_id, array $violation ): string {
		// Add the selector to the violation message as empty paragraphs are almost always
		// duplicate html fragments. Adding the selector makes it unique, so it can be saved.
		if ( 'empty_paragraph_tag' === $rule_id ) {
			$html .= $violation['selector'][0]
				? '// {{ ' . $violation['selector'][0] . ' }}'
				: '';
		}

		// Use just the opening <html> and closing </html> tag, prevents storing entire page as the affected code.
		if ( 'html-has-lang' === $rule_id || 'document-title' === $rule_id ) {
			$html = preg_replace( '/^.*(<html.*?>).*(<\/html>).*$/s', '$1...$2', $html );

		}
		return $html;
	}

	/**
	 * REST handler that saves to the DB a list of js rule violations for a post.
	 *
	 * @param WP_REST_Request $request  The request passed from the REST call.
	 *
	 * @return \WP_REST_Response
	 */
	public function set_post_scan_results( $request ) {

		if ( ! isset( $request['violations'] ) ) {
			return new \WP_REST_Response( [ 'message' => 'A required parameter is missing.' ], 400 );
		}

		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return new \WP_REST_Response( [ 'message' => 'The post is not valid.' ], 400 );
		}

		$post_type  = get_post_type( $post );
		$post_types = Helpers::get_option_as_array( 'edac_post_types' );
		if ( empty( $post_types ) || ! in_array( $post_type, $post_types, true ) ) {
			return new \WP_REST_Response( [ 'message' => 'The post type is not set to be scanned.' ], 400 );
		}

		$rules             = edac_register_rules();
		$js_rule_ids       = [];
		$combined_rule_ids = [];
		foreach ( $rules as $rule ) {
			if ( array_key_exists( 'ruleset', $rule ) && 'js' === $rule['ruleset'] ) {
				$js_rule_ids[] = $rule['slug'];

				if ( array_key_exists( 'combines', $rule ) && ! empty( $rule['combines'] ) ) {
					foreach ( $rule['combines'] as $combine_rule_id ) {
						$combined_rule_ids[ $combine_rule_id ] = $rule['slug'];
					}
				}
			}
		}

		try {
			do_action( 'edac_before_validate', $post_id, 'js' );

			$violations = $request['violations'];

			edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 1, 'js' );

			if ( is_array( $violations ) && count( $violations ) > 0 ) {
				foreach ( $violations as $violation ) {
					$rule_id        = $violation['ruleId'];
					$actual_rule_id = array_key_exists( $rule_id, $combined_rule_ids ) ? $combined_rule_ids[ $rule_id ] : $rule_id;

					if ( in_array( $actual_rule_id, $js_rule_ids, true ) ) {
						$html   = apply_filters( 'edac_filter_js_violation_html', $violation['html'], $rule_id, $violation );
						$impact = $violation['impact'];

						foreach ( $rules as $rule ) {
							if ( $rule['slug'] === $actual_rule_id ) {
								$impact = $rule['rule_type'];
							}
						}

						do_action( 'edac_before_rule', $post_id, $actual_rule_id, 'js' );

						// Save screenshot if one was provided.
						$screenshot_url = null; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
						if ( isset( $violation['screenshot'] ) && ! empty( $violation['screenshot'] ) ) {
							$screenshot = $this->screenshot_manager->save_screenshot(
								$post_id,
								$actual_rule_id,
								$violation['screenshot']
							);
							if ( ! is_wp_error( $screenshot ) ) {
								$screenshot_url = $screenshot; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
							}
						}

						$insert_rule = new Insert_Rule_Data();
						$insert_rule->insert(
							(object) [
								'ID'        => $post_id,
								'post_type' => $post_type,
							],
							$actual_rule_id,
							$impact,
							$html
						);

						do_action( 'edac_after_rule', $post_id, $actual_rule_id, 'js' );
					}
				}
			}

			do_action( 'edac_after_validate', $post_id, 'js' );

			edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 2, 'js' );

			$metrics = $request['densityMetrics'] ?? [ 0, 0 ];
			if ( is_array( $metrics ) && count( $metrics ) > 0 ) {
				update_post_meta(
					$post_id,
					'_edac_density_data',
					[
						$metrics['elementCount'] ?? 0,
						$metrics['contentLength'] ?? 0,
					]
				);
			}

			( new Summary_Generator( $post_id ) )->generate_summary();

			update_post_meta( $post_id, '_edac_post_checked_js', time() );

			do_action( 'edac_validate_before_sending_rest_response', $post_id, 'js', $request );

			return new \WP_REST_Response(
				[
					'success'   => true,
					'id'        => $post_id,
					'timestamp' => time(),
				]
			);

		} catch ( \Exception $ex ) {
			return new \WP_REST_Response(
				[
					'message' => $ex->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * REST handler that clears the cached stats about the scans
	 *
	 * @return \WP_REST_Response
	 */
	public function clear_cached_scans_stats() {

		try {

			// Clear the cache.
			$scans_stats = new Scans_Stats();
			$scans_stats->clear_cache();

			// Prime the cache.
			$scans_stats = new Scans_Stats();

			return new \WP_REST_Response(
				[
					'success' => true,
				]
			);

		} catch ( \Exception $ex ) {

			return new \WP_REST_Response(
				[
					'message' => $ex->getMessage(),
				],
				500
			);

		}
	}

	/**
	 * REST handler that gets stats about the scans
	 *
	 * @return \WP_REST_Response
	 */
	public function get_scans_stats() {

		try {

			$scans_stats = new Scans_Stats( 60 * 5 );
			$stats       = $scans_stats->summary();

			return new \WP_REST_Response(
				[
					'success' => true,
					'stats'   => $stats,
				]
			);

		} catch ( \Exception $ex ) {

			return new \WP_REST_Response(
				[
					'message' => $ex->getMessage(),
				],
				500
			);

		}
	}

	/**
	 * REST handler that gets stats about the scans by post type
	 *
	 * @param WP_REST_Request $request The request passed from the REST call.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_scans_stats_by_post_type( $request ) {

		if ( ! isset( $request['slug'] ) ) {
			return new \WP_REST_Response( [ 'message' => 'A required parameter is missing.' ], 400 );
		}

		try {

			$post_type            = strval( $request['slug'] );
			$scannable_post_types = Settings::get_scannable_post_types();

			if ( in_array( $post_type, $scannable_post_types, true ) ) {

				$scans_stats = new Scans_Stats( 60 * 5 );
				$by_type     = $scans_stats->issues_summary_by_post_type( $post_type );

				return new \WP_REST_Response(
					[
						'success' => true,
						'stats'   => $by_type,
					]
				);
			}
			return new \WP_REST_Response( [ 'message' => 'The post type is not set to be scanned.' ], 400 );
		} catch ( \Exception $ex ) {
			return new \WP_REST_Response(
				[
					'message' => $ex->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * REST handler that gets stats about the scans by post types
	 *
	 * @param WP_REST_Request $request The request passed from the REST call.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_scans_stats_by_post_types( $request ) { //phpcs:ignore

		try {

			$scans_stats = new Scans_Stats( 60 * 5 );

			$scannable_post_types = Settings::get_scannable_post_types();

			$post_types = get_post_types(
				[
					'public' => true,
				]
			);
			unset( $post_types['attachment'] );

			$post_types_to_check = array_merge( [ 'post', 'page' ], $scannable_post_types );

			$by_types = [];

			foreach ( $post_types as $post_type ) {

				$by_types[ $post_type ] = false;
				if ( in_array( $post_type, $scannable_post_types, true ) && in_array( $post_type, $post_types_to_check, true ) ) {
					$by_types[ $post_type ] = $scans_stats->issues_summary_by_post_type( $post_type );
				}
			}

			return new \WP_REST_Response(
				[
					'success' => true,
					'stats'   => $by_types,
				]
			);

		} catch ( \Exception $ex ) {

			return new \WP_REST_Response(
				[
					'message' => $ex->getMessage(),
				],
				500
			);

		}
	}

	/**
	 * REST handler that gets stats about the scans
	 *
	 * @param \WP_REST_Request $request The request passed from the REST call.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_site_summary( \WP_REST_Request $request ) {

		try {
			$scan_stats = new Scans_Stats();
			if ( (bool) $request->get_param( 'clearCache' ) ) {
				$scan_stats->clear_cache();
			}

			return new \WP_REST_Response(
				[
					'success' => true,
					'stats'   => $scan_stats->summary(),
				]
			);
		} catch ( \Exception $ex ) {
			return new \WP_REST_Response(
				[
					'message' => $ex->getMessage(),
				],
				500
			);
		}
	}
}
