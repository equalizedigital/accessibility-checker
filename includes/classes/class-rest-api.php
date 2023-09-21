<?php
/**
 * Class file for REST api
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Scans_Stats;
use EDAC\Settings;
use EDAC\Helpers;


/**
 * Class that initializes and handles the REST api
 */
class REST_Api {

	/**
	 * If class has already been initialized.
	 *
	 * @var boolean
	 */
	private static $initialized = false;

	/**
	 * Constructor
	 */
	public function __construct() {
			
		if ( ! self::$initialized ) {
			$this->initialize();
		}
	}


	/**
	 * Adds the actions.
	 */
	private function initialize() {

		$ns      = 'accessibility-checker/';
		$version = 'v1';

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/test',
					array(
						'methods'             => array( 'GET', 'POST' ),
						'callback'            => function () {
							global $wp;
							$messages = array();
							$messages['time'] = time();
							$messages['perms'] = current_user_can( 'edit_posts' );
						
						
							return new \WP_REST_Response( array( 'messages' => $messages ), 200 );
						},
						'permission_callback' => function () {
							return true;
						},
					) 
				);
			} 
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/post-scan-results/(?P<id>\d+)',
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'set_post_scan_results' ),
						'args'                => array(
							'id' => array(
								'validate_callback' => function ( $param ) {
									return is_numeric( $param );
								},
							),
						),
						'permission_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					) 
				);
			} 
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/scans-stats',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_scans_stats' ),
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					) 
				);
			} 
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/scans-stats-by-post-type/(?P<slug>[a-zA-Z0-9_-]+)',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_scans_stats' ),
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					) 
				);
			} 
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/scans-stats-by-post-type/(?P<slug>[a-zA-Z0-9_-]+)',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_scans_stats_by_post_type' ),
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					) 
				);
			} 
		);

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/scans-stats-by-post-types',
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_scans_stats_by_post_types' ),
						'permission_callback' => function () {
							return current_user_can( 'read' ); // able to access the admin dashboard.
						},
					) 
				);
			} 
		);
	}

	

	/**
	 * REST handler that saves to the DB a list of js rule violations for a post.
	 *
	 * @param WP_REST_Request $request  The request passed from the REST call.
	 * 
	 * @return \WP_REST_Response 
	 */
	public function set_post_scan_results( $request ) {
	
		if ( ! isset( $request['violations'] ) 
		) {
			return new \WP_REST_Response( array( 'message' => 'A required parameter is missing.' ), 400 );
		}

		$post_id = intval( $request['id'] );
		$post    = get_post( $post_id );
		if ( ! is_object( $post ) ) {    

			return new \WP_REST_Response( array( 'message' => 'The post is not valid.' ), 400 );
		}

		$post_type  = get_post_type( $post );
		$post_types = Helpers::get_option_as_array( 'edac_post_types' );

		if ( ! empty( $post_types ) || ! in_array( $post_type, $post_types, true ) ) {

			return new \WP_REST_Response( array( 'message' => 'The post type is not set to be scanned.' ), 400 );

		}

		//phpcs:ignore Generic.Commenting.Todo.TaskFound			
		// TODO: setup a rules class for loading/filtering rules.
		$rules       = edac_register_rules();
		$js_rule_ids = array();
		foreach ( $rules as $rule ) {
			if ( array_key_exists( 'ruleset', $rule ) && 'js' === $rule['ruleset'] ) {
				$js_rule_ids[] = $rule['slug'];
			}
		}

	
		
		try {

			do_action( 'edac_before_validate', $post_id, 'js' );
	
			$violations = $request['violations'];
			
					
			// set record check flag on previous error records.
			edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 1, 'js' );

	
			if ( is_array( $violations ) && count( $violations ) > 0 ) {

				foreach ( $violations as $violation ) {
					$rule_id = $violation['ruleId'];
				
					if ( in_array( $rule_id, $js_rule_ids ) ) {

						// This rule is one that we've included in our js ruleset.
				
						$html   = $violation['html'];
						$impact = $violation['impact']; // by default, use the impact setting from the js rule.
					
						//phpcs:ignore Generic.Commenting.Todo.TaskFound
						// TODO: setup a rules class for loading/filtering rules.
						foreach ( $rules as $rule ) {
							if ( $rule['slug'] === $rule_id ) {
								$impact = $rule['rule_type']; // if we are defining the rule_type in php rules config, use that instead of the js rule's impact setting.
							}
						}

						// TODO: add support storing $violation['selector'], $violation['tags'].
						
						
						do_action( 'edac_before_rule', $post_id, $rule_id, 'js' );
			
						edac_insert_rule_data( $post, $rule_id, $impact, $html );

						do_action( 'edac_after_rule', $post_id, $rule_id, 'js' );
			
					}               
				}           
			}
	
			do_action( 'edac_after_validate', $post_id, 'js' );
	
			// Update the summary info that is stored in meta this post.
			$summary = edac_summary( $post_id );
	
			// remove corrected records.
			edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 2, 'js' );

			// store a record of this scan in the post's meta.
			update_post_meta( $post_id, '_edac_post_checked_js', time() );
			
			return new \WP_REST_Response(
				array(
					'success'   => true,
					'id'        => $post_id,
					'timestamp' => time(),
				) 
			);

		} catch ( \Exception $ex ) {
			
			return new \WP_REST_Response(
				array(
					'message' => $ex->getMessage(),
				), 
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
				array(
					'success' => true,
					'stats'   => $stats,
				) 
			);

		} catch ( \Exception $ex ) {
			
			return new \WP_REST_Response(
				array(
					'message' => $ex->getMessage(),
				), 
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

		if ( ! isset( $request['slug'] ) 
		) {
			return new \WP_REST_Response( array( 'message' => 'A required parameter is missing.' ), 400 );
		}

		try {

			$post_type = strval( $request['slug'] );
		
			$scannable_post_types = Settings::get_scannable_post_types();
		
			if ( in_array( $post_type, $scannable_post_types ) ) {
			
					$scans_stats = new Scans_Stats( 60 * 5 );   
	
					$by_type = $scans_stats->issues_summary_by_post_type( $post_type );

					return new \WP_REST_Response(
						array(
							'success' => true,
							'stats'   => $by_type,
						) 
					);
			

			} else {

				return new \WP_REST_Response( array( 'message' => 'The post type is not set to be scanned.' ), 400 );

			}       
		} catch ( \Exception $ex ) {
			
			return new \WP_REST_Response(
				array(
					'message' => $ex->getMessage(),
				), 
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
	public function get_scans_stats_by_post_types( $request ) {

		try {

			$scans_stats = new Scans_Stats( 60 * 5 );   

			$scannable_post_types = Settings::get_scannable_post_types();
				
			$post_types = get_post_types(
				array(
					'public' => true,
				) 
			);
			unset( $post_types['attachment'] );
	
			$post_types_to_check = array_merge( array( 'post', 'page' ), $scannable_post_types );
			
			$by_types = array();

			foreach ( $post_types as $post_type ) {

				if ( in_array( $post_type, $scannable_post_types ) && in_array( $post_type, $post_types_to_check ) ) {
		
					$by_types[ $post_type ] = $scans_stats->issues_summary_by_post_type( $post_type );
				
				} else {
					$by_types[ $post_type ] = false;
				}
			}
			
			return new \WP_REST_Response(
				array(
					'success' => true,
					'stats'   => $by_types,
				) 
			);
	

		
		} catch ( \Exception $ex ) {
			
			return new \WP_REST_Response(
				array(
					'message' => $ex->getMessage(),
				), 
				500
			);

		}   
	}
}
