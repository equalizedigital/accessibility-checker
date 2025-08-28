<?php
/**
 * Class file for admin notices
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\OptIn\Email_Opt_In;
use EDAC\Inc\Summary_Generator;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Class that handles ajax requests.
 */
class Ajax {

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
		add_action( 'wp_ajax_edac_summary_ajax', [ $this, 'summary' ] );
		add_action( 'wp_ajax_edac_details_ajax', [ $this, 'details' ] );
		add_action( 'wp_ajax_edac_readability_ajax', [ $this, 'readability' ] );
		add_action( 'wp_ajax_edac_insert_ignore_data', [ $this, 'add_ignore' ] );
		add_action( 'wp_ajax_edac_update_simplified_summary', [ $this, 'simplified_summary' ] );
		add_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax', [ $this, 'dismiss_welcome_cta' ] );
		add_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax', [ $this, 'dismiss_dashboard_cta' ] );
		( new Email_Opt_In() )->register_ajax_handlers();
	}

	/**
	 * Summary Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the post ID was not specified
	 *  - '-3' means that there isn't any summary data to return
	 */
	public function summary() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$html            = [];
		$html['content'] = '';


		$post_id                   = (int) $_REQUEST['post_id'];
		$summary                   = ( new Summary_Generator( $post_id ) )->generate_summary();
		$simplified_summary_text   = '';
		$simplified_summary_prompt = get_option( 'edac_simplified_summary_prompt' );
		$simplified_summary        = get_post_meta( $post_id, '_edac_simplified_summary', true ) ? get_post_meta( $post_id, '_edac_simplified_summary', true ) : '';

		$simplified_summary_grade = 0;
		if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
			$text_statistics          = new \DaveChild\TextStatistics\TextStatistics();
			$simplified_summary_grade = (int) floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) );
		}
		$simplified_summary_grade_failed = ( $simplified_summary_grade > 9 ) ? true : false;

		$simplified_summary_text = esc_html__( 'A Simplified summary has not been included for this content.', 'accessibility-checker' );
		if ( 'none' !== $simplified_summary_prompt ) {
			if ( $summary['content_grade'] <= 9 ) {
				$simplified_summary_text = esc_html__( 'Your content has a reading level at or below 9th grade and does not require a simplified summary.', 'accessibility-checker' );
			} elseif ( $summary['simplified_summary'] ) {
				if ( $simplified_summary_grade_failed ) {
					$simplified_summary_text = esc_html__( 'The reading level of the simplified summary is too high.', 'accessibility-checker' );
				} else {
					$simplified_summary_text = esc_html__( 'A simplified summary has been included for this content.', 'accessibility-checker' );
				}
			}
		}

		$html['content'] .= '<ul class="edac-summary-grid">';

			$html['content'] .= '<li class="edac-summary-total" aria-label="' . $summary['passed_tests'] . '% Passed Tests">';

				$html['content'] .= '<div class="edac-summary-total-progress-circle ' . ( ( $summary['passed_tests'] > 50 ) ? ' over50' : '' ) . '">
					<div class="edac-summary-total-progress-circle-label">
						<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
						<div class="edac-panel-number-label">Passed Tests<sup><a href="#edac-summary-disclaimer" aria-label="About passed tests.">*</a></sup></div>
					</div>
					<div class="left-half-clipper">
						<div class="first50-bar"></div>
						<div class="value-bar" style="transform: rotate(' . $summary['passed_tests'] * 3.6 . 'deg);"></div>
					</div>
				</div>';

				$html['content'] .= '<div class="edac-summary-total-mobile">
					<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
					<div class="edac-panel-number-label">Passed Tests<sup><a href="#edac-summary-disclaimer" aria-label="About passed tests.">*</a></sup></div>
					<div class="edac-summary-total-mobile-bar"><span style="width:' . ( $summary['passed_tests'] ) . '%;"></span></div>
				</div>';

			$html['content'] .= '</li>';

			// if this is a virtual page, we don't show the readability section.
			$is_virtual_page = edac_is_virtual_page( $post_id );

			$html['content'] .= '
				' . edac_generate_summary_stat(
				'edac-summary-errors',
				$summary['errors'],
				/* translators: %s: Number of errors */
					sprintf( _n( '%s Error', '%s Errors', $summary['errors'], 'accessibility-checker' ), $summary['errors'] )
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-contrast',
				$summary['contrast_errors'],
				/* translators: %s: Number of contrast errors */
					sprintf( _n( '%s Contrast Error', '%s Contrast Errors', $summary['contrast_errors'], 'accessibility-checker' ), $summary['contrast_errors'] )
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-warnings',
				$summary['warnings'],
				/* translators: %s: Number of warnings */
					sprintf( _n( '%s Warning', '%s Warnings', $summary['warnings'], 'accessibility-checker' ), $summary['warnings'] )
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-ignored',
				$summary['ignored'],
				/* translators: %s: Number of ignored items */
					sprintf( _n( '%s Ignored Item', '%s Ignored Items', $summary['ignored'], 'accessibility-checker' ), $summary['ignored'] )
			) . '

		</ul>
		<div class="edac-summary-readability" ' . ( $is_virtual_page ? 'style="display: none;"' : '' ) . '>
			<div class="edac-summary-readability-level">
				<div><img src="' . EDAC_PLUGIN_URL . 'assets/images/readability-icon-navy.png" alt="" width="54"></div>
				<div class="edac-panel-number' . ( ( (int) $summary['content_grade'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">
					' . $summary['readability'] . '
				</div>
				<div class="edac-panel-number-label' . ( ( (int) $summary['readability'] <= 9 || 'none' === $simplified_summary_prompt ) ? ' passed-text-color' : ' failed-text-color' ) . '">Reading <br />Level</div>
			</div>
			<div class="edac-summary-readability-summary">
				<div class="edac-summary-readability-summary-icon' . ( ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['content_grade'] <= 9 ) && ! $simplified_summary_grade_failed ) ? ' active' : '' ) . '"></div>
				<div class="edac-summary-readability-summary-text' . ( ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['content_grade'] <= 9 ) && ! $simplified_summary_grade_failed ) ? ' active' : '' ) . '">' . $simplified_summary_text . '</div>
			</div>
		</div>
		';

		$html['content'] .= '<div class="edac-summary-disclaimer" id="edac-summary-disclaimer"><small>' . PHP_EOL;
		$html['content'] .= sprintf(
			'* True accessibility requires manual testing in addition to automated scans. %1$sLearn how to manually test for accessibility%2$s.',
			'<a href="' . esc_url(
				edac_generate_link_type(
					[
						'utm_campaign' => 'dashboard-widget',
						'utm_content'  => 'how-to-manually-check',
					],
					'help',
					[ 'help_id' => 4280 ]
				)
			) . '">',
			'</a>'
		) . PHP_EOL;
		$html['content'] .= '</small></div>' . PHP_EOL;

		if ( ! $html ) {

			$error = new \WP_Error( '-3', __( 'No summary to return', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		wp_send_json_success( wp_json_encode( $html ) );
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
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {

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

							$post_view_link = apply_filters(
								'edac_get_origin_url_for_virtual_page',
								get_the_permalink( $postid ),
								$postid
							);

							$url = add_query_arg(
								[
									'edac'       => $id,
									'edac_nonce' => wp_create_nonce( 'edac_highlight' ),
								],
								$post_view_link
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
	 * Readability Ajax
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the post ID was not specified
	 *  - '-3' means that there isn't any readability data to return
	 */
	public function readability() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		$post_id                     = (int) $_REQUEST['post_id'];
		$html                        = '';
		$simplified_summary          = get_post_meta( $post_id, '_edac_simplified_summary', true ) ? get_post_meta( $post_id, '_edac_simplified_summary', true ) : '';
		$simplified_summary_position = get_option( 'edac_simplified_summary_position', $default = false );
		$content_post                = get_post( $post_id );
		$content                     = $content_post->post_content;
		$content                     = apply_filters( 'the_content', $content );

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

		// get readability metadata and determine if a simplified summary is required.
		$edac_summary           = get_post_meta( $post_id, '_edac_summary', true );
		$post_grade_readability = ( isset( $edac_summary['readability'] ) ) ? $edac_summary['readability'] : 0;
		$post_grade             = (int) filter_var( $post_grade_readability, FILTER_SANITIZE_NUMBER_INT );
		$post_grade_failed      = ( $post_grade < 9 ) ? false : true;

		$simplified_summary_grade = 0;
		if ( class_exists( 'DaveChild\TextStatistics\TextStatistics' ) ) {
			$text_statistics          = new \DaveChild\TextStatistics\TextStatistics();
			$simplified_summary_grade = (int) floor( $text_statistics->fleschKincaidGradeLevel( $simplified_summary ) );
		}

		$simplified_summary_grade_failed = ( $simplified_summary_grade > 9 ) ? true : false;
		$simplified_summary_prompt       = get_option( 'edac_simplified_summary_prompt' );

		$html .= '<ul class="edac-readability-list">';

		$html .= '<li class="edac-readability-list-item edac-readability-grade-level">
		<span class="edac-readability-list-item-icon dashicons ' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
		<p class="edac-readability-list-item-title">Post Reading Grade Level: <strong class="' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . ( ( 0 === $post_grade ) ? 'None' : $post_grade_readability ) . '</strong><br /></p>';
		if ( $post_grade_failed ) {
			$html .= '<p class="edac-readability-list-item-description">Your post has a reading level higher than 9th grade. Web Content Accessibility Guidelines (WCAG) at the AAA level require a simplified summary of your post that is 9th grade or below.</p>';
		} elseif ( 0 === $post_grade ) {
			$html .= '<p class="edac-readability-list-item-description">Your post does not contain enough content to calculate its reading level.</p>';
		} else {
			$html .= '<p class="edac-readability-list-item-description">A simplified summary is not necessary when content reading level is 9th grade or below. Choose when to prompt for a simplified summary on the settings page.</p>';
		}
		$html .= '</li>';

		if ( $post_grade_failed ) {

			if ( $simplified_summary && 'none' !== $simplified_summary_prompt ) {
				$html .= '<li class="edac-readability-list-item edac-readability-summary-grade-level">
					<span class="edac-readability-list-item-icon dashicons ' . ( ( $simplified_summary_grade_failed ) ? 'dashicons-no-alt' : 'dashicons-saved' ) . '"></span>
					<p class="edac-readability-list-item-title">Simplified Summary Reading Grade Level: <strong class="' . ( ( $simplified_summary_grade_failed ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . edac_ordinal( $simplified_summary_grade ) . '</strong></p>
					<p class="edac-readability-list-item-description">Your simplified summary has a reading level ' . ( ( $simplified_summary_grade_failed ) ? 'higher' : 'lower' ) . ' than 9th grade.</p>
				</li>';
			}

			if ( 'none' === $simplified_summary_prompt ) {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/warning-icon-yellow.png" alt="" width="22"></span>
					<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
						<p class="edac-readability-list-item-description">Your Prompt for Simplified Summary is set to "never." If you would like the simplified summary to be displayed automatically, you can change this on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
				</li>';

			} elseif ( 'none' !== $simplified_summary_position ) {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon dashicons dashicons-saved"></span>
					<p class="edac-readability-list-item-title">Simplified summary is being automatically inserted <strong>' . $simplified_summary_position . ' the content</strong>.</p>
						<p class="edac-readability-list-item-description">Set where the Simplified Summary is inserted into the content on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
				</li>';

			} else {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					<span class="edac-readability-list-item-icon"><img src="' . plugin_dir_url( __FILE__ ) . 'assets/images/warning-icon-yellow.png" alt="" width="22"></span>
					<p class="edac-readability-list-item-title">Simplified summary is not being automatically inserted into the content.</p>
						<p class="edac-readability-list-item-description">Your Simplified Summary location is set to "manually" which requires a function be added to your page template. If you would like the simplified summary to be displayed automatically, you can change this on the <a href="' . get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=accessibility_checker_settings">settings page</a>.</p>
				</li>';

			}
		}

		$html .= '</ul>';

		if ( ( $post_grade_failed || 'always' === $simplified_summary_prompt ) && ( 'none' !== $simplified_summary_prompt ) ) {
			$html .=
				'</form>
			<form action="/" class="edac-readability-simplified-summary">
				<label for="edac-readability-text">Simplified Summary</label>
				<textarea name="" id="edac-readability-text" cols="30" rows="10">' . $simplified_summary . '</textarea>
				<input type="submit" value="Submit">
			</form>';
		}

		$html .= '<span class="dashicons dashicons-info"></span><a href="' . esc_url( edac_link_wrapper( 'https://a11ychecker.com/help3265', 'wordpress-general', 'content-analysis', false ) ) . '" target="_blank">Learn more about improving readability and simplified summary requirements</a>';

		if ( ! $html ) {

			$error = new \WP_Error( '-3', __( 'No readability data to return', 'accessibility-checker' ) );
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
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		global $wpdb;
		$table_name            = $wpdb->prefix . 'accessibility_checker';
				$raw_ids       = isset( $_REQUEST['ids'] ) ? (array) wp_unslash( $_REQUEST['ids'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled below.
		$ids                   = array_map(
			function ( $value ) {
				return (int) $value;
			},
			$raw_ids
		); // Sanitizing array elements to integers.
				$action        = isset( $_REQUEST['ignore_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ignore_action'] ) ) : '';
				$type          = isset( $_REQUEST['ignore_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ignore_type'] ) ) : '';
		$siteid                = get_current_blog_id();
		$ignre                 = ( 'enable' === $action ) ? 1 : 0;
		$ignre_user            = ( 'enable' === $action ) ? get_current_user_id() : null;
		$ignre_user_info       = ( 'enable' === $action ) ? get_userdata( $ignre_user ) : '';
		$ignre_username        = ( 'enable' === $action ) ? $ignre_user_info->user_login : '';
		$ignre_date            = ( 'enable' === $action ) ? gmdate( 'Y-m-d H:i:s' ) : null;
		$ignre_date_formatted  = ( 'enable' === $action ) ? gmdate( 'F j, Y g:i a', strtotime( $ignre_date ) ) : '';
				$ignre_comment = ( 'enable' === $action && isset( $_REQUEST['comment'] ) ) ? sanitize_textarea_field( wp_unslash( $_REQUEST['comment'] ) ) : null;
				$ignore_global = ( 'enable' === $action && isset( $_REQUEST['ignore_global'] ) ) ? sanitize_textarea_field( wp_unslash( $_REQUEST['ignore_global'] ) ) : 0;

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

	/**
	 * Update simplified summary
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that the post ID was not specified
	 *  - '-3' means that the summary was not specified
	 */
	public function simplified_summary() {

			// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {

			$error = new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {

			$error = new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

		if ( ! isset( $_REQUEST['summary'] ) ) {

			$error = new \WP_Error( '-3', __( 'The summary was not set', 'accessibility-checker' ) );
			wp_send_json_error( $error );

		}

			$post_id = (int) $_REQUEST['post_id'];
			update_post_meta(
				$post_id,
				'_edac_simplified_summary',
				sanitize_text_field( wp_unslash( $_REQUEST['summary'] ) )
			);

		$edac_simplified_summary = get_post_meta( $post_id, '_edac_simplified_summary', $single = true );
		$simplified_summary      = $edac_simplified_summary ? $edac_simplified_summary : '';

		wp_send_json_success( wp_json_encode( $simplified_summary ) );
	}

	/**
	 * Handle AJAX request to dismiss Welcome CTA
	 *
	 * @return void
	 */
	public function dismiss_welcome_cta() {

		update_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true );

		wp_send_json( 'success' );
	}

	/**
	 * Handle AJAX request to dismiss dashboard CTA
	 *
	 * @return void
	 */
	public function dismiss_dashboard_cta() {

		update_user_meta( get_current_user_id(), 'edac_dashboard_cta_dismissed', true );

		wp_send_json( 'success' );
	}
}
