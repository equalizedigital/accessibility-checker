<?php
/**
 * Class file for details ajax requests
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Class that handles details-related ajax requests.
 */
class Details_Ajax {

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
		add_action( 'wp_ajax_edac_details_ajax', [ $this, 'details' ] );
		add_action( 'wp_ajax_edac_insert_ignore_data', [ $this, 'add_ignore' ] );
	}

	/**
	 * Details Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the post ID was not specified
	 *  - '-3' means that the table name is not valid
	 *  - '-4' means that there isn't any details to return
	 */
	public function details() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$html = '';
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$postid     = (int) $_REQUEST['post_id'];
		$siteid     = get_current_blog_id();

		// Send error if table name is not valid.
		if ( ! $table_name ) {

			$error = new \WP_Error( '-3', __( 'Invalid table name', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$rules = edac_register_rules();
		if ( $rules ) {

			// if ANWW is active remove link_blank for details meta box.
			if ( defined( 'ANWW_VERSION' ) ) {
				$rules = edac_remove_element_with_value( $rules, 'slug', 'link_blank' );
			}

			// separate rule types.
			$passed_rules  = [];
			$error_rules   = edac_remove_element_with_value( $rules, 'rule_type', 'warning' );
			$warning_rules = edac_remove_element_with_value( $rules, 'rule_type', 'error' );

			// add count, unset passed error rules and add passed rules to array.
			if ( $error_rules ) {
				foreach ( $error_rules as $key => $error_rule ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
					$count = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM %i where postid = %d and rule = %s and siteid = %d and ignre = %d', $table_name, $postid, $error_rule['slug'], $siteid, 0 ), ARRAY_A ) );
					if ( $count ) {
						$error_rules[ $key ]['count'] = $count;
					} else {
						$error_rule['count'] = 0;
						$passed_rules[]      = $error_rule;
						unset( $error_rules[ $key ] );
					}
				}
			}

			// add count, unset passed warning rules and add passed rules to array.
			if ( $warning_rules ) {
				foreach ( $warning_rules as $key => $error_rule ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
					$count = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment FROM %i where postid = %d and rule = %s and siteid = %d and ignre = %d', $table_name, $postid, $error_rule['slug'], $siteid, 0 ), ARRAY_A ) );
					if ( $count ) {
						$warning_rules[ $key ]['count'] = $count;
					} else {
						$error_rule['count'] = 0;
						$passed_rules[]      = $error_rule;
						unset( $warning_rules[ $key ] );
					}
				}
			}
		}

		// sort error rules by count.
		usort(
			$error_rules,
			function ( $a, $b ) {

				return strcmp( $b['count'], $a['count'] );
			}
		);

		// sort warning rules by count.
		usort(
			$warning_rules,
			function ( $a, $b ) {

				return strcmp( $b['count'], $a['count'] );
			}
		);

		// sort passed rules array by title.
		usort(
			$passed_rules,
			function ( $a, $b ) {

				return strcmp( $b['title'], $a['title'] );
			}
		);

		// merge rule arrays together.
		$rules = array_merge( $error_rules, $warning_rules, $passed_rules );

		if ( $rules ) {
			/**
			 * Filters if a user can ignore issues.
			 *
			 * @since 1.4.0
			 *
			 * @allowed bool True if allowed, false if not
			 */
			$ignore_permission = apply_filters( 'edac_ignore_permission', true );

			foreach ( $rules as $rule ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
				$results        = $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment, ignre_global, landmark, landmark_selector FROM %i where postid = %d and rule = %s and siteid = %d', $table_name, $postid, $rule['slug'], $siteid ), ARRAY_A );
				$count_classes  = ( 'error' === $rule['rule_type'] ) ? ' edac-details-rule-count-error' : ' edac-details-rule-count-warning';
				$count_classes .= ( 0 !== $rule['count'] ) ? ' active' : '';

				$count_ignored = 0;
				$ignores       = array_column( $results, 'ignre' );
				if ( $ignores ) {
					foreach ( $ignores as $ignore ) {
						if ( true === (bool) $ignore ) {
							++$count_ignored;
						}
					}
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
				$expand_rule = count( $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM %i where postid = %d and rule = %s and siteid = %d', $table_name, $postid, $rule['slug'], $siteid ), ARRAY_A ) );

				$tool_tip_link = edac_link_wrapper( $rule['info_url'], 'frontend-highlighter', $rule['slug'], false );

				$html .= '<div class="edac-details-rule">';

				$html .= '<div class="edac-details-rule-title">';

				$html .= '<h3>';
				$html .= '<span class="edac-details-rule-count' . $count_classes . '">' . $rule['count'] . '</span> ';
				$html .= esc_html( $rule['title'] );
				if ( $count_ignored > 0 ) {
					$html .= '<span class="edac-details-rule-count-ignore">' . $count_ignored . ' Ignored Items</span>';
				}
				$html .= '</h3>';
				$html .= '<a href="' . $tool_tip_link . '" class="edac-details-rule-information" target="_blank" aria-label="Read documentation for ' . esc_html( $rule['title'] ) . '. ' . esc_attr__( 'Opens in a new window.', 'accessibility-checker' ) . '"><span class="dashicons dashicons-info"></span></a>';
				$html .= ( $expand_rule ) ? '<button class="edac-details-rule-title-arrow" aria-expanded="false" aria-controls="edac-details-rule-records-' . $rule['slug'] . '" aria-label="Expand issues for ' . esc_html( $rule['title'] ) . '"><i class="dashicons dashicons-arrow-down-alt2"></i></button>' : '';

				$html .= '</div>';

				if ( $results ) {

					$html .= '<div id="edac-details-rule-records-' . $rule['slug'] . '" class="edac-details-rule-records">';

					$fixes_for_item = [];
					if ( isset( $rule['fixes'] ) && current_user_can( apply_filters( 'edac_filter_settings_capability', 'manage_options' ) ) ) {
						foreach ( $rule['fixes'] as $fix_slug ) {
							$fixes_for_item[] = FixesManager::get_instance()->get_fix( $fix_slug );
						}

						$controls_id = 'edac-fix-modal-' . $rule['slug'] . '__' . implode( '__', $rule['fixes'] );
						ob_start();
						// NOTE: This is markup to be cloned into a thickbox modal. It gets cloned from the inner div.
						?>
						<div style="display:none">
							<div id="<?php echo esc_attr( $controls_id ); ?>" class="edac-details-fix-settings fix-settings--container">
								<div class="setting-row fix-settings--container" data-fix="<?php echo esc_attr( $controls_id ); ?>">
									<?php
									printf(
										'<p class="modal-opening-message">%s <span class="hide-in-editor">%s</span></p>',
										esc_html__( 'These settings enable global fixes across your entire site.', 'accessibility-checker' ),
										esc_html__( 'Pages may need to be resaved or a full site scan run to see fixes reflected in reports.', 'accessibility-checker' )
									)
									?>
									<div class="edac-fix-settings">
										<?php
										foreach ( $fixes_for_item as $index => $fix ) :
											?>
											<div class="edac-fix-settings--fields">
												<fieldset>
													<div class="title">
														<legend>
															<h2 class="edac-fix-settings--title"><?php echo esc_html( $fix->get_nicename() ); ?></h2>
														</legend>
													</div>
													<?php
													foreach ( $fix->get_fields_array() as $name => $field ) {
														$field['name']     = $name;
														$field['location'] = 'details-panel';
														FixesPage::{$field['type']}( $field );
													}
													?>
												</fieldset>
												<?php
												// Output the save button only in the last group.
												if ( count( $fixes_for_item ) === $index + 1 ) :
													?>
													<div class="edac-fix-settings--action-row">
														<button role="button" class="button button-primary edac-fix-settings--button--save">
															<?php esc_html_e( 'Save', 'accessibility-checker' ); ?>
														</button>
														<span class="edac-fix-settings--notice-slot" aria-live="polite" role="alert"></span>
													</div>
													<?php
												endif;
												?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>
						<?php
						$html .= ob_get_clean();
					}



					$html .=
						'<div class="edac-details-rule-records-labels">
							<div class="edac-details-rule-records-labels-label" aria-hidden="true">
								' . esc_html__( 'Affected Code', 'accessibility-checker' ) . '
							</div>
							<div class="edac-details-rule-records-labels-label" aria-hidden="true">
								Image
							</div>
							<div class="edac-details-rule-records-labels-label" aria-hidden="true">
								Landmark
							</div>
							<div class="edac-details-rule-records-labels-label" aria-hidden="true">
								Actions
							</div>
						</div>';

					foreach ( $results as $row ) {

						$id                      = (int) $row['id'];
						$ignore                  = (int) $row['ignre'];
						$ignore_class            = $ignore ? ' active' : '';
						$ignore_label            = $ignore ? 'Ignored' : 'Ignore';
						$ignore_user             = (int) $row['ignre_user'];
						$ignore_user_info        = get_userdata( $ignore_user );
						$ignore_username         = is_object( $ignore_user_info ) ? '<strong>Username:</strong> ' . $ignore_user_info->user_login : '';
						$ignore_date             = ( $row['ignre_date'] && '0000-00-00 00:00:00' !== $row['ignre_date'] ) ? '<strong>Date:</strong> ' . gmdate( 'F j, Y g:i a', strtotime( esc_html( $row['ignre_date'] ) ) ) : '';
						$ignore_comment          = esc_html( $row['ignre_comment'] );
						$ignore_action           = $ignore ? 'disable' : 'enable';
						$ignore_type             = $rule['rule_type'];
						$ignore_submit_label     = $ignore ? 'Stop Ignoring' : 'Ignore This ' . $ignore_type;
						$ignore_comment_disabled = $ignore ? 'disabled' : '';
						$ignore_global           = (int) $row['ignre_global'];

						// check for images and svgs in object code.
						$media      = edac_parse_html_for_media( $row['object'] );
						$object_img = $media['img'];
						$object_svg = $media['svg'];

						$html .= '<h4 class="screen-reader-text">Issue ID ' . $id . '</h4>';

						$html .= '<div id="edac-details-rule-records-record-' . $id . '" class="edac-details-rule-records-record">';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-object">';

						$html .= '<code>' . esc_html( $row['object'] ) . '</code>';

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-image">';

						if ( $object_img ) {
							$html .= '<img src="' . $object_img . '" alt="image for issue ' . $id . '" />';
						} elseif ( $object_svg ) {
							$html .= $object_svg;
						}

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-landmark">';

						$landmark          = isset( $row['landmark'] ) ? $row['landmark'] : '';
						$landmark_selector = isset( $row['landmark_selector'] ) ? $row['landmark_selector'] : '';
						
						$html .= edac_generate_landmark_link( $landmark, $landmark_selector, $postid );

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-actions">';

						if ( ! isset( $rule['viewable'] ) || $rule['viewable'] ) {

							$url = add_query_arg(
								[
									'edac'       => $id,
									'edac_nonce' => wp_create_nonce( 'edac_highlight' ),
								],
								get_the_permalink( $postid )
							);

							// Translators: %d is the issue ID.
							$aria_label = sprintf( __( 'View Issue ID %d on website, opens a new window', 'accessibility-checker' ), $id );
							$html      .= '<a href="' . $url . '" class="edac-details-rule-records-record-actions-highlight-front" target="_blank" aria-label="' . esc_attr( $aria_label ) . '" ><span class="dashicons dashicons-welcome-view-site"></span>' . __( 'View on page', 'accessibility-checker' ) . '</a>';
						}

						if ( true === $ignore_permission ) {
							$html .= '<button class="edac-details-rule-records-record-actions-ignore' . $ignore_class . '" aria-expanded="false" aria-controls="edac-details-rule-records-record-ignore-' . $row['id'] . '">' . EDAC_SVG_IGNORE_ICON . '<span class="edac-details-rule-records-record-actions-ignore-label">' . $ignore_label . '</span></button>';
						}

						if ( ! empty( $fixes_for_item ) ) {
							$html .= sprintf(
								'<button class="edac-details-rule-records-record-actions-fix"
									aria-haspopup="true"
									aria-controls="%1$s"
									aria-label="%2$s"
									type="button"
								>
									<span class="dashicons dashicons-admin-tools"></span>
									%3$s
								</button>',
								esc_attr( $controls_id ),
								esc_attr( __( 'Fix: ', 'accessibility-checker' ) . $fixes_for_item[0]->get_nicename() ),
								esc_html__( 'Fix', 'accessibility-checker' )
							);
						}

						$html .= '</div>';

						$html .= '<div id="edac-details-rule-records-record-ignore-' . $row['id'] . '" class="edac-details-rule-records-record-ignore">';

						$html .= '<div class="edac-details-rule-records-record-ignore-info">';
						$html .= '<span class="edac-details-rule-records-record-ignore-info-user">' . $ignore_username . '</span>';

						$html .= ' <span class="edac-details-rule-records-record-ignore-info-date">' . $ignore_date . '</span>';
						$html .= '</div>';

						$html .= ( true === $ignore_permission || ! empty( $ignore_comment ) ) ? '<label for="edac-details-rule-records-record-ignore-comment-' . $id . '">Comment</label><br>' : '';
						$html .= ( true === $ignore_permission || ! empty( $ignore_comment ) ) ? '<textarea rows="4" class="edac-details-rule-records-record-ignore-comment" id="edac-details-rule-records-record-ignore-comment-' . $id . '" ' . $ignore_comment_disabled . '>' . $ignore_comment . '</textarea>' : '';

						if ( $ignore_global ) {
							$html .= ( true === $ignore_permission ) ? '<a href="' . admin_url( 'admin.php?page=accessibility_checker_ignored&tab=global' ) . '" class="edac-details-rule-records-record-ignore-global">Manage Globally Ignored</a>' : '';
						} else {
							$html .= ( true === $ignore_permission ) ? '<button class="edac-details-rule-records-record-ignore-submit" data-id=' . $id . ' data-action=' . $ignore_action . ' data-type=' . $ignore_type . '>' . EDAC_SVG_IGNORE_ICON . ' <span class="edac-details-rule-records-record-ignore-submit-label">' . $ignore_submit_label . '<span></button>' : '';
						}

						$html .= ( false === $ignore_permission && false === $ignore ) ? __( 'Your user account doesn\'t have permission to ignore this issue.', 'accessibility-checker' ) : '';

						$html .= '</div>';

						$html .= '</div>';

					}

					$html .= '</div>';

				}

				$html .= '</div>';
			}
		}

		if ( ! $html ) {

			$error = new \WP_Error( '-4', __( 'No details to return', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $html ) );
	}

	/**
	 * Insert ignore data into database
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that there isn't any ignore data to return
	 */
	public function add_ignore() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		global $wpdb;
		$table_name           = $wpdb->prefix . 'accessibility_checker';
		$raw_ids              = isset( $_REQUEST['ids'] ) ? $_REQUEST['ids'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled below.
		$ids                  = array_map(
			function ( $value ) {
				return (int) $value;
			},
			$raw_ids
		); // Sanitizing array elements to integers.
		$action               = isset( $_REQUEST['ignore_action'] ) ? sanitize_text_field( $_REQUEST['ignore_action'] ) : '';
		$type                 = isset( $_REQUEST['ignore_type'] ) ? sanitize_text_field( $_REQUEST['ignore_type'] ) : '';
		$siteid               = get_current_blog_id();
		$ignre                = ( 'enable' === $action ) ? 1 : 0;
		$ignre_user           = ( 'enable' === $action ) ? get_current_user_id() : null;
		$ignre_user_info      = ( 'enable' === $action ) ? get_userdata( $ignre_user ) : '';
		$ignre_username       = ( 'enable' === $action ) ? $ignre_user_info->user_login : '';
		$ignre_date           = ( 'enable' === $action ) ? gmdate( 'Y-m-d H:i:s' ) : null;
		$ignre_date_formatted = ( 'enable' === $action ) ? gmdate( 'F j, Y g:i a', strtotime( $ignre_date ) ) : '';
		$ignre_comment        = ( 'enable' === $action && isset( $_REQUEST['comment'] ) ) ? sanitize_textarea_field( $_REQUEST['comment'] ) : null;
		$ignore_global        = ( 'enable' === $action && isset( $_REQUEST['ignore_global'] ) ) ? sanitize_textarea_field( $_REQUEST['ignore_global'] ) : 0;

		// If largeBatch is set and 'true', we need to perform an update using the 'object'
		// instead of IDs. It is a much less efficient query than by IDs - but many IDs run
		// into request size limits which caused this to not function at all.
		if ( isset( $_REQUEST['largeBatch'] ) && 'true' === $_REQUEST['largeBatch'] ) {
			// Get the 'object' from the first id.
			$first_id = $ids[0];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- We need to get the latest value, not a cached value.
			$object = $wpdb->get_var( $wpdb->prepare( 'SELECT object FROM %i WHERE id = %d', $table_name, $first_id ) );

			if ( ! $object ) {
				$error = new \WP_Error( '-2', __( 'No ignore data to return', 'accessibility-checker' ) );
				wp_send_json_error( $error );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
			$wpdb->query( $wpdb->prepare( 'UPDATE %i SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d and object = %s', $table_name, $ignre, $ignre_user, $ignre_date, $ignre_comment, $ignore_global, $siteid, $object ) );
		} else {
			// For small batches of IDs, we can just loop through.
			foreach ( $ids as $id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
				$wpdb->query( $wpdb->prepare( 'UPDATE %i SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_global = %d WHERE siteid = %d and id = %d', $table_name, $ignre, $ignre_user, $ignre_date, $ignre_comment, $ignore_global, $siteid, $id ) );
			}
		}

		$data = [
			'ids'    => $ids,
			'action' => $action,
			'type'   => $type,
			'user'   => $ignre_username,
			'date'   => $ignre_date_formatted,
		];

		if ( ! $data ) {

			$error = new \WP_Error( '-2', __( 'No ignore data to return', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}
		wp_send_json_success( wp_json_encode( $data ) );
	}
}