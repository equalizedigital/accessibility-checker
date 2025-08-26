<?php
/**
 * Class file for REST api
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Insert_Rule_Data;
use EDAC\Admin\Scans_Stats;
use EDAC\Admin\Settings;
use EDAC\Admin\Purge_Post_Data;

/**
 * Class that initializes and handles the REST api
 */
class REST_Api {


	/**
	 * Constructor
	 */
	public function __construct() {
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
					'/post-scan-results/(?P<id>\d+)',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'set_post_scan_results' ],
						'args'                => [
							'id' => [
								'required'          => true,
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
								'sanitize_callback' => 'absint',
							],
						],
						'permission_callback' => function ( $request ) {
							return $this->user_can_edit_passed_post_id( $request );
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
					'/clear-cached-scans-stats',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'clear_cached_scans_stats' ],
						'permission_callback' => function () {
							return current_user_can( 'publish_posts' );
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
					'/scans-stats-by-post-types',
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'get_scans_stats_by_post_types' ],
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
					'/clear-issues/(?P<id>\d+)',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'clear_issues_for_post' ],
						'args'                => [
							'id' => [
								'required'          => true,
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
								'sanitize_callback' => 'absint',
							],
						],
						'permission_callback' => function ( $request ) {
							return $this->user_can_edit_passed_post_id( $request );
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
	 * Check if the user can edit a post.
	 *
	 * This is a permission callback to replace several places where we check if the user can edit a post.
	 *
	 * @since 1.30.1
	 *
	 * @param \WP_REST_Request $request The request object passed from the REST call. This should contain the 'id' of the post to check permissions for.
	 *
	 * @return bool|\WP_Error
	 */
	public function user_can_edit_passed_post_id( $request ) {
		if ( ! isset( $request['id'] ) ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'A required parameter is missing.', 'accessibility-checker' ), [ 'status' => 400 ] );
		}
		$post_id = (int) $request['id'];
		return current_user_can( 'edit_post', $post_id ); // able to edit the post.
	}

	/**
	 * REST handler to clear issues results for a given post ID.
	 *
	 * @param \WP_REST_Request $request  The request passed from the REST call.
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
			$post_types = Settings::get_scannable_post_types();
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
		$post_types = Settings::get_scannable_post_types();
		if ( empty( $post_types ) || ! in_array( $post_type, $post_types, true ) ) {

			return new \WP_REST_Response( [ 'message' => 'The post type is not set to be scanned.' ], 400 );

		}

		//phpcs:ignore Generic.Commenting.Todo.TaskFound
		// TODO: setup a rules class for loading/filtering rules.
		$rules             = edac_register_rules();
		$js_rule_ids       = [];
		$combined_rule_ids = [];
		foreach ( $rules as $rule ) {
			if ( array_key_exists( 'ruleset', $rule ) && 'js' === $rule['ruleset'] ) {
				$js_rule_ids[] = $rule['slug'];

				// Some rules can be a grouping of other checks with different ids. This tracks those combined check IDs for later mapping.
				if ( array_key_exists( 'combines', $rule ) && ! empty( $rule['combines'] ) ) {
					foreach ( $rule['combines'] as $combine_rule_id ) {
						$combined_rule_ids[ $combine_rule_id ] = $rule['slug'];
					}
				}
			}
		}

		try {

			/**
			 * Fires before the validation process starts.
			 *
			 * This is only running in the JS check context.
			 *
			 * @since 1.5.0
			 *
			 * @param int    $post_id The post ID.
			 * @param string $type    The type of validation which is always 'js' in this path.
			 */
			do_action( 'edac_before_validate', $post_id, 'js' );

			$violations = $request['violations'];

			// set record check flag on previous error records.
			edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 1, 'js' );

			if ( is_array( $violations ) && count( $violations ) > 0 ) {

				foreach ( $violations as $violation ) {
					$rule_id = $violation['ruleId'];

					// If this rule is a combined rule then map it to the actual reporting rule ID.
					$actual_rule_id = array_key_exists( $rule_id, $combined_rule_ids ) ? $combined_rule_ids[ $rule_id ] : $rule_id;

					if ( in_array( $actual_rule_id, $js_rule_ids, true ) ) {

						// This rule is one that we've included in our js ruleset.

						$html   = apply_filters( 'edac_filter_js_violation_html', $violation['html'], $rule_id, $violation );
						$impact = $violation['impact']; // by default, use the impact setting from the js rule.

						//phpcs:ignore Generic.Commenting.Todo.TaskFound
						// TODO: setup a rules class for loading/filtering rules.
						foreach ( $rules as $rule ) {
							if ( $rule['slug'] === $actual_rule_id ) {
								$impact = $rule['rule_type']; // if we are defining the rule_type in php rules config, use that instead of the js rule's impact setting.
							}
						}

						//phpcs:ignore Generic.Commenting.Todo.TaskFound, Squiz.PHP.CommentedOutCode.Found
						// TODO: add support storing $violation['selector'], $violation['tags'].

						/**
						 * Fires before a rule is run against the content.
						 *
						 * This is only running in the JS check context.
						 *
						 * @since 1.5.0
						 *
						 * @param int    $post_id The post ID.
						 * @param string $rule_id The rule ID.
						 * @param string $type    The type of validation which is always 'js' in this path.
						 */
						do_action( 'edac_before_rule', $post_id, $actual_rule_id, 'js' );

						$landmark          = $violation['landmark'] ?? null;
						$landmark_selector = $violation['landmarkSelector'] ?? null;

						$selectors = [
							'selector' => $violation['selector'] ?? [],
							'ancestry' => $violation['ancestry'] ?? [],
							'xpath'    => $violation['xpath'] ?? [],
						];
						( new Insert_Rule_Data() )->insert( $post, $actual_rule_id, $impact, $html, $landmark, $landmark_selector, $selectors );

						/**
						 * Fires after a rule is run against the content.
						 *
						 * This is only running in the JS check context.
						 *
						 * @since 1.5.0
						 *
						 * @param int    $post_id The post ID.
						 * @param string $rule_id The rule ID.
						 * @param string $type    The type of validation which is always 'js' in this path.
						 */
						do_action( 'edac_after_rule', $post_id, $actual_rule_id, 'js' );

					}
				}
			}

			/**
			 * Fires after the validation process is complete.
			 *
			 * This is only running in the JS check context.
			 *
			 * @since 1.5.0
			 *
			 * @param int    $post_id The post ID.
			 * @param string $type    The type of validation which is always 'js' in this path.
			 */
			do_action( 'edac_after_validate', $post_id, 'js' );

			// remove corrected records.
			edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 2, 'js' );

			// Save the density metrics before the summary is generated.
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

			// Update the summary info that is stored in meta this post.
			( new Summary_Generator( $post_id ) )->generate_summary();

			// store a record of this scan in the post's meta.
			update_post_meta( $post_id, '_edac_post_checked_js', time() );

			/**
			 * Fires before sending the REST response ending the validation process.
			 *
			 * @since 1.14.0
			 *
			 * @param int             $post_id The post ID.
			 * @param string          $type    The type of validation which is always 'js' in this path.
			 * @param WP_REST_Request $request The request passed from the REST call.
			 */
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
