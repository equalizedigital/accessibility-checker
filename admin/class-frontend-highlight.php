<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Class Frontend_Highlight
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

		if ( ! check_ajax_referer( 'ajax-nonce', 'nonce', false ) ) {
			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			$error = new \WP_Error( '-2', __( 'The id value was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );
		}

		$post_id = isset( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : 0;
		$results = $this->get_issues( $post_id );

		if ( ! $results ) {
			$error = new \WP_Error( '-3', __( 'Issue query returned no results', 'accessibility-checker' ) );
			wp_send_json_error( $error );
		}

		$rules = edac_register_rules();

		$issues = [];
		$fixes  = [];
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
			$array['how_to_fix'] = wp_kses_post( $rule[0]['how_to_fix'] ?? '' );
			$array['link']       = edac_link_wrapper( $rule[0]['info_url'], 'frontend-highlighter', $rule[0]['slug'], false );
			$array['object']     = html_entity_decode( $result['object'], ENT_QUOTES | ENT_HTML5 );
			$array['id']         = $result['id'];
			$array['ignored']    = $result['ignre'];

			$issues[] = $array;

			if ( ! isset( $fixes[ $rule[0]['slug'] ] ) ) {
				$fixes_for_rule = $rule[0]['fixes'] ?? [];

				foreach ( $fixes_for_rule as $fix_for_rule ) {
					$fix = FixesManager::get_instance()->get_fix( $fix_for_rule );
					if ( $fix && method_exists( $fix, 'get_fields_array' ) ) {
						$fixes[ $rule[0]['slug'] ] = isset( $fixes[ $rule[0]['slug'] ] ) ? array_merge( $fixes[ $rule[0]['slug'] ], $fix->get_fields_array() ) : $fix->get_fields_array();
					}
				}
			}
		}

		if ( ! $issues ) {

			$error = new \WP_Error( '-5', __( 'Object query returned no results', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		// if we have fixes then create fields for each of the groups.
		if ( ! empty( $fixes ) ) {
			foreach ( $fixes as $key => $fix ) {
				// count the number of fields in the fix.
				$fields_count      = count( $fix );
				$itteration        = 0;
				$fix_fields_markup = '';
				foreach ( $fix as $index => $field ) {
					++$itteration;
					$field_type = $field['type'] ?? 'checkbox';
					ob_start();
					if ( isset( $field['group_name'] ) ) {
						// if this is anything other than the first field in the group then close the fieldset.
						if ( 1 !== $itteration ) {
							?>
							</fieldset>
							<?php
						}
						?>
						<fieldset>
						<legend><h3 class="title"><?php echo esc_html( $field['group_name'] ); ?></h3></legend>
						<?php
					}
					FixesPage::{$field_type}(
						array_merge(
							[
								'name'     => $index,
								'location' => 'frontend-highlighter',
							],
							$field
						)
					);
					if ( $fields_count === $itteration ) {
						?>
						</fieldset>
						<?php
					}
					$fix_fields_markup .= ob_get_clean();
				}
				$fixes[ $key ]['fields'] = $fix_fields_markup . PHP_EOL . '</fieldset>';
			}
		}

		wp_send_json_success(
			wp_json_encode(
				[
					'issues' => $issues,
					'fixes'  => $fixes,
				]
			)
		);
	}
}
