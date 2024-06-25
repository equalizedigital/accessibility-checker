<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class EDAC_Frontend_Highlight
 *
 * A class that handles AJAX requests for frontend highlighting of accessibility issues.
 */
class Frontend_Highlight {

	/**
	 * Constructor function for the class.
	 */
	public function __construct() {
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_edac_frontend_highlight_ajax', [ $this, 'ajax' ] );

		/**
		 * Filter the visibility of the frontend highlighter.
		 *
		 * 'edac_filter_frontend_highlighter_visibility' is a filter that can be used
		 * to allow users without edit permissions on the post to see the frontend
		 * highlighter. You can use the filter to perform additional permission checks
		 * on who can see it.
		 *
		 * @since 1.14.0
		 *
		 * @param bool $visibility The visibility of the frontend highlighter. Default is false, return true to show the frontend highlighter.
		 */
		if ( apply_filters( 'edac_filter_frontend_highlighter_visibility', false ) ) {
			// A nopriv endpoint allows logged-out users to access the endpoint.
			add_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax', [ $this, 'ajax' ] );
		}
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
		$post_id    = (int) $post_id;
		$siteid     = get_current_blog_id();
		$results    = $wpdb->get_results( $wpdb->prepare( 'SELECT id, rule, ignre, object, ruletype FROM %i where postid = %d and siteid = %d', $table_name, $post_id, $siteid ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name.
		if ( ! $results ) {
			return null;
		}

		return Helpers::filter_results_to_only_active_rules( $results );
	}

	/**
	 * AJAX handler function for frontend highlighting requests.
	 */
	public function ajax() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {
			$error = new \WP_Error( '-1', 'Permission Denied' );
			wp_send_json_error( $error );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			$error = new \WP_Error( '-2', 'The id value was not set' );
			wp_send_json_error( $error );
		}

		$post_id = isset( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : 0;
		$results = $this->get_issues( $post_id );

		if ( ! $results ) {
			$error = new \WP_Error( '-3', 'Issue query returned no results' );
			wp_send_json_error( $error );
		}

		$rules = edac_register_rules();

		$output = [];
		foreach ( $results as $result ) {
			$array = [];
			$rule  = edac_filter_by_value( $rules, 'slug', $result['rule'] );

			// When rules are filtered out, they are not in the rules array and this can be empty. Skip when the rule
			// is empty to avoid php warnings and passing null values to the frontend highlighter.
			if ( ! $rule ) {
				continue;
			}

			$rule_type = ( true === (bool) $result['ignre'] ) ? 'ignored' : $rule[0]['rule_type'];

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

			$error = new \WP_Error( '-5', 'Object query returned no results' );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $output ) );
	}
}
