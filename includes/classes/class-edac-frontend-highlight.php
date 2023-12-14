<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Class EDAC_Frontend_Highlight
 *
 * A class that handles AJAX requests for frontend highlighting of accessibility issues.
 */
class EDAC_Frontend_Highlight {

	/**
	 * Constructor function for the class.
	 * Adds AJAX action hooks for handling frontend highlighting requests.
	 */
	public function __construct() {
		add_action( 'wp_ajax_edac_frontend_highlight_ajax', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax', array( $this, 'ajax' ) );
	}

	/**
	 * Retrieves accessibility issues for a specific post.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array|null The array of issues or null if no issues found.
	 */
	public function get_issues( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$post_id    = intval( $post_id );
		$siteid     = get_current_blog_id();
		$results    = $wpdb->get_results( $wpdb->prepare( 'SELECT id, rule, ignre, object, ruletype FROM ' . $table_name . ' where postid = %d and siteid = %d', $post_id, $siteid ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name.
		if ( ! $results ) {
			return null;
		}
		return $results;
	}

	/**
	 * AJAX handler function for frontend highlighting requests.
	 */
	public function ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {
			$error = new WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			$error = new WP_Error( '-2', 'The id value was not set' );
			wp_send_json_error( $error );
		}

		$post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
		$results = $this->get_issues( $post_id );

		if ( ! $results ) {
			$error = new WP_Error( '-3', 'Issue query returned no results' );
			wp_send_json_error( $error );
		}

		$rules = edac_register_rules();

		$output = array();
		foreach ( $results as $result ) {
			$array     = array();
			$rule      = edac_filter_by_value( $rules, 'slug', $result['rule'] );
			$rule_type = ( true === boolval( $result['ignre'] ) ) ? 'ignored' : $rule[0]['rule_type'];

			$array['rule_type']  = $rule_type;
			$array['slug']       = $rule[0]['slug'];
			$array['rule_title'] = $rule[0]['title'];
			$array['summary']    = $rule[0]['summary'];
			$array['link']       = edac_documentation_link( $rule[0] );
			$array['object']     = html_entity_decode( esc_html( $result['object'] ) );
			$array['id']         = $result['id'];
			$array['ignored']    = $result['ignre'];

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
