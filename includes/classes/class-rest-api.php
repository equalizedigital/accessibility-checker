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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
							$post_id = (int) $request['id'];
							return current_user_can( 'edit_post', $post_id ); // able to edit the post.
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
							$post_id = (int) $request['id'];
							return current_user_can( 'edit_post', $post_id ); // able to edit the post.
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

		// Sidebar data endpoint - returns all metabox data in one call.
		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/sidebar-data/(?P<id>\d+)',
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'get_sidebar_data' ],
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
							$post_id = (int) $request['id'];
							return current_user_can( 'edit_post', $post_id );
						},
					]
				);
			}
		);

		// Simplified summary endpoint - saves the simplified summary text.
		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/simplified-summary/(?P<id>\d+)',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'save_simplified_summary' ],
						'args'                => [
							'id'      => [
								'required'          => true,
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
								'sanitize_callback' => 'absint',
							],
							'summary' => [
								'required'          => true,
								'sanitize_callback' => 'sanitize_textarea_field',
								'validate_callback' => function ( $param ) {
									return is_string( $param );
								},
							],
						],
						'permission_callback' => function ( $request ) {
							$post_id = (int) $request['id'];
							return current_user_can( 'edit_post', $post_id );
						},
					]
				);
			}
		);

		// Dismiss/restore issue endpoint.
		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/dismiss-issue/(?P<issue_id>\d+)',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'dismiss_issue' ],
						'args'                => [
							'issue_id'      => [
								'required'          => true,
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
								'sanitize_callback' => 'absint',
							],
							'action'        => [
								'required'          => true,
								'validate_callback' => function ( $param ) {
									return in_array( $param, [ 'enable', 'disable', 'dismiss', 'undismiss', 'ignore', 'unignore' ], true );
								},
								'sanitize_callback' => 'sanitize_text_field',
							],
							'reason'        => [
								'required'          => false,
								'sanitize_callback' => 'sanitize_text_field',
							],
							'comment'       => [
								'required'          => false,
								'sanitize_callback' => function ( $param ) {
									// Allow basic tags, then store as HTML entities.
									$allowed_html = [
										'strong' => [],
										'b'      => [],
										'em'     => [],
										'i'      => [],
										'a'      => [
											'href'   => true,
											'target' => true,
											'rel'    => true,
										],
									];
									return esc_html( wp_kses( $param, $allowed_html ) );
								},
							],
							'ignore_global' => [
								'required'          => false,
								'default'           => 0,
								'sanitize_callback' => 'absint',
							],
							'largeBatch'    => [
								'required'          => false,
								'default'           => false,
								'sanitize_callback' => function ( $param ) {
									return filter_var( $param, FILTER_VALIDATE_BOOLEAN );
								},
							],
						],
						'permission_callback' => function ( $request ) {
							global $wpdb;
							$issue_id = isset( $request['issue_id'] ) ? (int) $request['issue_id'] : 0;
							if ( $issue_id <= 0 ) {
								return false;
							}

							$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
							if ( ! $table_name ) {
								return false;
							}

							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Permission check requires direct lookup.
							$post_id = (int) $wpdb->get_var(
								$wpdb->prepare( 'SELECT postid FROM %i WHERE id = %d', $table_name, $issue_id )
							);

							return (bool) ( $post_id > 0 && current_user_can( 'edit_post', $post_id ) );
						},
					]
				);
			}
		);
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
	public function filter_js_validation_html( string $html, string $rule_id, array $violation ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- the variable was used previously and will be used in future most likely.
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

	/**
	 * REST handler that gets all sidebar data for a post (summary, details, readability).
	 *
	 * @since 1.xx.x
	 *
	 * @param \WP_REST_Request $request The request passed from the REST call.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_sidebar_data( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		try {
			$data = [
				'post_id'     => $post_id,
				'summary'     => $this->get_summary_data( $post_id ),
				'details'     => $this->get_details_data( $post_id ),
				'readability' => $this->get_readability_data( $post_id ),
			];

			return new \WP_REST_Response(
				[
					'success' => true,
					'data'    => $data,
				],
				200
			);
		} catch ( \Exception $ex ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => $ex->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Get summary data for a post.
	 *
	 * Returns cached summary data from post meta. If no cache exists, returns defaults.
	 *
	 * @since 1.xx.x
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array
	 */
	private function get_summary_data( $post_id ) {
		// Get summary from post meta.
		$summary = get_post_meta( $post_id, '_edac_summary', true );

		// If summary doesn't exist or is invalid, return defaults.
		if ( ! $summary || ! is_array( $summary ) ) {
			$summary = [
				'passed_tests'    => 0,
				'errors'          => 0,
				'contrast_errors' => 0,
				'warnings'        => 0,
				'ignored'         => 0,
				'readability'     => 0,
			];
		}

		return $summary;
	}

	/**
	 * Get details data for a post (errors, warnings, passed rules).
	 *
	 * @since 1.xx.x
	 *
	 * @param int $post_id The post ID.
	 *
	 * @throws \Exception If the database table name is invalid.
	 * @return array
	 */
	private function get_details_data( $post_id ) {
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$siteid     = get_current_blog_id();

		if ( ! $table_name ) {
			throw new \Exception( esc_html__( 'Invalid table name', 'accessibility-checker' ) );
		}

		$rules = edac_register_rules();
		if ( ! $rules ) {
			return [
				'errors'   => [],
				'warnings' => [],
				'passed'   => [],
			];
		}

		if ( ! current_user_can( apply_filters( 'edac_filter_settings_capability', 'manage_options' ) ) ) {
			foreach ( $rules as $rule_key => $rule ) {
				if ( isset( $rule['fixes'] ) ) {
					unset( $rules[ $rule_key ]['fixes'] );
				}
			}
		}

		// If ANWW is active remove link_blank for details.
		if ( defined( 'ANWW_VERSION' ) ) {
			$rules = edac_remove_element_with_value( $rules, 'slug', 'link_blank' );
		}

		$passed_rules  = [];
		$error_rules   = edac_filter_by_value( $rules, 'rule_type', 'error' );
		$warning_rules = edac_filter_by_value( $rules, 'rule_type', 'warning' );

		// Process both error and warning rules.
		$error_rules   = $this->process_rules_for_details( $error_rules, $post_id, $table_name, $siteid, $passed_rules );
		$warning_rules = $this->process_rules_for_details( $warning_rules, $post_id, $table_name, $siteid, $passed_rules );

		return [
			'errors'   => array_values( $error_rules ),
			'warnings' => array_values( $warning_rules ),
			'passed'   => $passed_rules,
		];
	}

	/**
	 * Process rules and fetch issue details from the database.
	 *
	 * @since 1.xx.x
	 *
	 * @param array  $rules         The rules to process.
	 * @param int    $post_id       The post ID.
	 * @param string $table_name    The database table name.
	 * @param int    $siteid        The site/blog ID.
	 * @param array  &$passed_rules Reference to passed rules array (populated by this method).
	 *
	 * @return array The processed rules with counts and details.
	 */
	private function process_rules_for_details( $rules, $post_id, $table_name, $siteid, &$passed_rules ) {
		global $wpdb;
		static $user_cache = [];

		// Early return if no rules to process.
		if ( empty( $rules ) ) {
			return $rules;
		}

		// Extract rule slugs for IN clause.
		$rule_slugs = array_column( $rules, 'slug' );

		// Build a simple, escaped IN clause.
		$safe_table    = esc_sql( $table_name );
		$escaped_slugs = array_map( 'esc_sql', $rule_slugs );
		$in_clause     = "'" . implode( "','", $escaped_slugs ) . "'";

		// Direct SQL query (table and values already escaped).
		$sql = "SELECT *\n"
			. "FROM `{$safe_table}`\n"
			. "WHERE postid = {$post_id}\n"
			. "AND rule IN ( {$in_clause} )\n"
			. "AND siteid = {$siteid}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$all_results = $wpdb->get_results( $sql, ARRAY_A );

		// Group results by rule slug.
		$results_by_rule = [];
		foreach ( $all_results as $result ) {
			$rule_slug = $result['rule'];
			if ( ! isset( $results_by_rule[ $rule_slug ] ) ) {
				$results_by_rule[ $rule_slug ] = [];
			}
			// If we have non-zero ignre_user then get the username.
			if ( isset( $result['ignre_user'] ) && (int) $result['ignre_user'] > 0 ) {
				$user_id = (int) $result['ignre_user'];
				if ( ! array_key_exists( $user_id, $user_cache ) ) {
					$user_info              = get_userdata( $user_id );
					$user_cache[ $user_id ] = $user_info ? $user_info->user_login : __( 'Unknown', 'accessibility-checker' );
				}
				$result['ignre_user_name'] = $user_cache[ $user_id ];
			}
			$results_by_rule[ $rule_slug ][] = $result;
		}

		// Process each rule with its results.
		foreach ( $rules as $key => $rule ) {
			$rule_slug = $rule['slug'];
			$results   = $results_by_rule[ $rule_slug ] ?? [];
			$count     = count( $results );

			if ( $count ) {
				$rules[ $key ]['count']   = $count;
				$rules[ $key ]['details'] = $results;
				// Add WCAG URL based on wcag number.
				if ( isset( $rule['wcag'] ) ) {
					$rules[ $key ] += $this->get_wcag_url_and_title_from_number( $rule['wcag'] );
				}
			} else {
				$rule['count']  = 0;
				$passed_rules[] = $rule;
				unset( $rules[ $key ] );
			}
		}

		return $rules;
	}

	/**
	 * Get readability data for a post.
	 *
	 * @since 1.xx.x
	 *
	 * @param int $post_id The post ID.
	 *
	 * @throws \Exception If the post is not found.
	 * @return array
	 */
	private function get_readability_data( $post_id ) {
		$simplified_summary          = (string) get_post_meta( $post_id, '_edac_simplified_summary', true );
		$simplified_summary_position = get_option( 'edac_simplified_summary_position', false );

		$content_post = get_post( $post_id );
		if ( ! $content_post ) {
			throw new \Exception( esc_html__( 'Post not found', 'accessibility-checker' ) );
		}

		$content = $content_post->post_content;
		$content = apply_filters( 'the_content', $content );

		/**
		 * Filter the content used for reading grade readability analysis.
		 *
		 * @since 1.4.0
		 *
		 * @param string $content The content to be filtered.
		 * @param int    $post_id The post ID.
		 */
		$content = apply_filters( 'edac_filter_readability_content', $content, $post_id );
		$content = wp_filter_nohtml_kses( $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		// Get readability metadata.
		$edac_summary           = get_post_meta( $post_id, '_edac_summary', true );
		$post_grade_readability = isset( $edac_summary['readability'] ) ? $edac_summary['readability'] : 0;
		$post_grade             = (int) filter_var( $post_grade_readability, FILTER_SANITIZE_NUMBER_INT );
		$post_grade_failed      = $post_grade > 9; // Treat Flesch-Kincaid grade 9+ (above roughly 8th-grade reading level recommended for plain language) as a readability failure.

		$simplified_summary_grade = 0;
		if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
			$text_statistics          = new \DaveChild\TextStatistics\TextStatistics();
			$simplified_summary_grade = (int) floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) );
		}

		$simplified_summary_grade_failed      = $simplified_summary_grade >= 9;
		$simplified_summary_grade_readability = edac_ordinal( $simplified_summary_grade );
		$simplified_summary_prompt            = get_option( 'edac_simplified_summary_prompt' );

		return [
			'post_grade'                           => $post_grade,
			'post_grade_readability'               => $post_grade_readability,
			'post_grade_failed'                    => $post_grade_failed,
			'simplified_summary'                   => $simplified_summary,
			'simplified_summary_grade'             => $simplified_summary_grade,
			'simplified_summary_grade_readability' => $simplified_summary_grade_readability,
			'simplified_summary_grade_failed'      => $simplified_summary_grade_failed,
			'simplified_summary_prompt'            => $simplified_summary_prompt,
			'simplified_summary_position'          => $simplified_summary_position,
			'content_length'                       => strlen( $content ),
		];
	}

	/**
	 * Save simplified summary for a post.
	 *
	 * @since 1.xx.x
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_simplified_summary( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$summary = sanitize_textarea_field( wp_unslash( $request['summary'] ) );

		// Update the post meta with the simplified summary (matching AJAX behavior).
		update_post_meta(
			$post_id,
			'_edac_simplified_summary',
			$summary
		);

		// Get the complete readability data structure (same as the main readability endpoint).
		try {
			$readability_data = $this->get_readability_data( $post_id );

			// Return data structure that matches the readability endpoint format.
			return new \WP_REST_Response(
				[
					'success'                              => true,
					'post_grade'                           => $readability_data['post_grade'],
					'post_grade_readability'               => $readability_data['post_grade_readability'],
					'post_grade_failed'                    => $readability_data['post_grade_failed'],
					'simplified_summary'                   => $readability_data['simplified_summary'],
					'simplified_summary_grade'             => $readability_data['simplified_summary_grade'],
					'simplified_summary_grade_readability' => $readability_data['simplified_summary_grade_readability'],
					'simplified_summary_grade_failed'      => $readability_data['simplified_summary_grade_failed'],
					'simplified_summary_prompt'            => $readability_data['simplified_summary_prompt'],
					'simplified_summary_position'          => $readability_data['simplified_summary_position'],
					'content_length'                       => $readability_data['content_length'],
				],
				200
			);
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'readability_data_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get WCAG URL from wcag number
	 *
	 * @param string $wcag_number The WCAG number (e.g., '1.1.1').
	 * @return array An array containing 'wcag_title' and 'wcag_url' keys. Both values will be empty strings if the WCAG number is not found.
	 */
	private function get_wcag_url_and_title_from_number( $wcag_number ) {
		$wcag_data_to_return = [
			'wcag_title' => '',
			'wcag_url'   => '',
		];

		if ( ! $wcag_number ) {
			return $wcag_data_to_return;
		}

		static $wcag_lookup = null;

		if ( null === $wcag_lookup ) {
			// Load the WCAG data file.
			$wcag_file = EDAC_PLUGIN_DIR . 'includes/wcag.php';
			if ( ! file_exists( $wcag_file ) ) {
				$wcag_lookup = [];
				return $wcag_data_to_return;
			}

			$wcag_data = include $wcag_file;
			if ( ! is_array( $wcag_data ) ) {
				$wcag_lookup = [];
				return $wcag_data_to_return;
			}

			// Re-key the array by WCAG number for O(1) lookups.
			$wcag_lookup = array_column( $wcag_data, null, 'number' );
		}

		// O(1) lookup by WCAG number.
		if ( isset( $wcag_lookup[ $wcag_number ] ) ) {
			$entry               = $wcag_lookup[ $wcag_number ];
			$wcag_data_to_return = [
				'wcag_title' => $entry['title'] ?? '',
				'wcag_url'   => $entry['wcag_url'] ?? '',
			];
		}

		return $wcag_data_to_return;
	}

	/**
	 * REST handler for dismissing or restoring an issue.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error Response object on success, WP_Error on failure.
	 */
	public function dismiss_issue( $request ) {
		global $wpdb;

		$issue_id      = (int) $request['issue_id'];
		$action        = $request->get_param( 'action' );
		$reason        = $request->get_param( 'reason' ) ?? '';
		$comment       = $request->get_param( 'comment' ) ?? '';
		$ignore_global = $request->get_param( 'ignore_global' ) ?? 0;
		$large_batch   = $request->get_param( 'largeBatch' ) ?? false;

		$table_name = $wpdb->prefix . 'accessibility_checker';
		$site_id    = get_current_blog_id();

		$allowed_ignore_actions = [ 'enable', 'ignore', 'dismiss' ];
		// Set values based on action (matching AJAX endpoint behavior).
		$is_ignoring          = in_array( $action, $allowed_ignore_actions, true ); // old systems send 'enable' when ignoring. This handles both for back compat but 'enable' is very unclear and should be swapped.
		$ignre                = $is_ignoring ? 1 : 0;
		$ignre_user           = $is_ignoring ? get_current_user_id() : null;
		$ignre_user_info      = $is_ignoring ? get_userdata( $ignre_user ) : null;
		$ignre_username       = $is_ignoring && $ignre_user_info ? $ignre_user_info->user_login : '';
		$ignre_date           = $is_ignoring ? edac_get_current_utc_datetime() : null;
		$ignre_date_formatted = $is_ignoring ? edac_format_datetime_from_utc( $ignre_date ) : '';
		$ignre_reason         = $is_ignoring ? $reason : null;
		$ignre_comment        = $is_ignoring ? $comment : null;
		$ignre_global         = $is_ignoring ? (int) $ignore_global : 0;

		// If largeBatch is set, update using the 'object' instead of ID.
		// This handles cases where the same issue appears multiple times.
		if ( $large_batch ) {
			// Get the 'object' from the issue id.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data.
			$object = $wpdb->get_var( $wpdb->prepare( 'SELECT object FROM %i WHERE id = %d', $table_name, $issue_id ) );

			if ( ! $object ) {
				return new \WP_Error(
					'issue_not_found',
					__( 'Issue not found.', 'accessibility-checker' ),
					[ 'status' => 404 ]
				);
			}

			// Update all issues with the same object.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update required, no caching needed.
			$result = $wpdb->query(
				$wpdb->prepare(
					'UPDATE %i SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_reason = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d AND object = %s',
					$table_name,
					$ignre,
					$ignre_user,
					$ignre_date,
					$ignre_reason,
					$ignre_comment,
					$ignre_global,
					$site_id,
					$object
				)
			);
		} else {
			// Update single issue by ID.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct update required, no caching needed.
			$result = $wpdb->query(
				$wpdb->prepare(
					'UPDATE %i SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_reason = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d AND id = %d',
					$table_name,
					$ignre,
					$ignre_user,
					$ignre_date,
					$ignre_reason,
					$ignre_comment,
					$ignre_global,
					$site_id,
					$issue_id
				)
			);
		}

		if ( false === $result ) {
			return new \WP_Error(
				'database_error',
				__( 'Failed to update the issue.', 'accessibility-checker' ),
				[ 'status' => 500 ]
			);
		}

		return new \WP_REST_Response(
			[
				'success'         => true,
				'issue_id'        => $issue_id,
				'action'          => $action,
				'ignre'           => $is_ignoring,
				'ignre_global'    => $ignre_global,
				'ignre_user'      => $ignre_user,
				'ignre_user_name' => $ignre_username,
				'ignre_date'      => $ignre_date_formatted,
				'ignre_reason'    => $ignre_reason,
				'ignre_comment'   => $ignre_comment,
				'large_batch'     => $large_batch,
			],
			200
		);
	}
}
