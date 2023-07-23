<?php
/**
 * Class file for REST api
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

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

		$ns = 'accessibility-checker/';
		$version = 'v1';

		add_action(
			'rest_api_init',
			function () use ( $ns, $version ) {
				register_rest_route(
					$ns . $version,
					'/post-scan-results/(?P<id>\d+)',
					array(
						'methods' => 'GET',
						'callback' => array( $this, 'set_post_scan_results' ),
						'args' => array(
							'id' => array(
								'validate_callback' => function( $param, $request, $key ) {
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
	}


/**
 * REST handler that saves to the DB a list of js rule violations for a post.
 *
 * @param WP_REST_Request $request  The request passed from the REST call.
 * 
 * @return void
 */
public function edac_set_post_scan_results( WP_REST_Request $request ) {
	
	if ( ! isset( $request['violations'] ) 
	) {
		$error = new WP_Error( 400, 'A required parameter is missing.' );
		wp_send_json_error( $error );

	}

	$post_id = intval( $request['id'] );
	$post = get_post( $post_id );
	if ( ! is_object( $post ) ) {    

		$error = new WP_Error( 400, 'The post id is not valid.' );
		wp_send_json_error( $error );
	}

	$post_type = get_post_type( $post );
	$post_types = get_option( 'edac_post_types' );
	if ( ! is_array( $post_types ) || ! in_array( $post_type, $post_types, true ) ) {

		$error = new WP_Error( 400, 'The post type is not set to be scanned.' );
		wp_send_json_error( $error );
	
	}

	
		
	// TODO: setup a rules class for loading/filtering rules.
	$rules = edac_register_rules();
	$js_rule_ids = array();
	foreach ( $rules as $rule ) {
		if ( array_key_exists( 'ruleset', $rule ) && 'js' === $rule['ruleset'] ) {
			$js_rule_ids[] = $rule['slug'];
		}
	}

		
	try {

		$violations = $request['violations'];
			
					
		// set record check flag on previous error records.
		edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 1, 'js' );

		if ( is_array( $violations ) && count( $violations ) > 0 ) {

			foreach ( $violations as $violation ) {
				$rule_id = $violation['ruleId'];
				
				if ( in_array( $rule_id, $js_rule_ids ) ) {
				
					$html = $violation['html'];
					$impact = $violation['impact']; // by default, use the impact setting from the js rule.
					
					// TODO: setup a rules class for loading/filtering rules.
					foreach ( $rules as $rule ) {
						if ( $rule['slug'] === $rule_id ) {
							$impact = $rule['rule_type']; // if we are defining the rule_type in php rules config, use that instead of the js rule's impact setting.
						}
					}

					// TODO:
					// $selector = $violation['selector'];
					// $tags = $violation['tags'];
						
					// This rule is one that we've included in our js ruleset.
					edac_insert_rule_data( $post, $rule_id, $impact, $html );
				}               
			}           
		}

		// remove corrected records.
		edac_remove_corrected_posts( $post_id, $post->post_type, $pre = 2, 'js' );

		// store a record of this scan in the post's meta.
		update_post_meta( $post_id, '_edac_post_checked_js', time() );
			
		wp_send_json_success(
			array(
				'id' => $post_id,
				'timestamp' => time()
			) 
		);

	} catch ( \Exception $ex ) {
			
		wp_send_json_error( $ex->getMessage() );

	}   

}

}