<?php
class EDAC_Frontend_Highlight {

	public function __construct() {
		add_action( 'wp_ajax_edac_frontend_highlight_ajax', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax', array( $this, 'ajax' ) );
	}

	public function get_issues( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$post_id    = intval( $post_id );
		$siteid     = get_current_blog_id();
		$results    = $wpdb->get_results( $wpdb->prepare( 'SELECT id, rule, object, ruletype FROM ' . $table_name . ' where postid = %d and siteid = %d', $post_id, $siteid ), ARRAY_A );
		if ( ! $results ) {
			return null;
		}
		return $results;
	}

	public function ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ajax-nonce' ) ) {
			$error = new WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			$error = new WP_Error( '-2', 'The id value was not set' );
			wp_send_json_error( $error );
		}

		$results = $this->get_issues( $_REQUEST['post_id'] );

		if ( ! $results ) {
			$error = new WP_Error( '-3', 'Issue query returned no results' );
			wp_send_json_error( $error );
		}

		$rules = edac_register_rules();
		//$rule  = edac_filter_by_value( $rules, 'slug', $results['rule'] );

		// if ( ! $rule ) {
		// 	$error = new WP_Error( '-4', 'Rule value not set' );
		// 	wp_send_json_error( $error );
		// }

		// $results['rule_title'] = $rule[0]['title'];
		// $results['summary']    = $rule[0]['summary'];
		// $results['link']       = edac_documentation_link( $rule[0] );
		// $results['object']     = html_entity_decode( esc_html( $results['object'] ) );

		$output = [];
		foreach ( $results as $result ) {
			$array = [];
			$rule  = edac_filter_by_value( $rules, 'slug', $result['rule'] );

			$array['rule_type']  = $rule[0]['rule_type'];
			$array['slug']       = $rule[0]['slug'];
			$array['rule_title'] = $rule[0]['title'];
			$array['summary']    = $rule[0]['summary'];
			$array['link']       = edac_documentation_link( $rule[0] );
			$array['object']     = html_entity_decode( esc_html( $result['object'] ) );

			$output[] = $array;
		}

		if ( ! $output ) {

			$error = new WP_Error( '-5', 'Object query returned no results' );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $output ) );
	}
}

new EDAC_Frontend_Highlight();
//var_dump( $frontend_highlight->get_issues( 3842 ) );
