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
use EqualizeDigital\AccessibilityChecker\Admin\IgnoreUI;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 *  - '-5' means that the user does not have permission to view this information for this post
	 */
	public function summary() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {
			wp_send_json_error( new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) ) );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) ) );
		}

		if ( ! current_user_can( 'edit_post', (int) $_REQUEST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( '-5', __( 'You do not have permission to view this information for this post.', 'accessibility-checker' ) ) );
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

			$html['content'] .= '<li class="edac-summary-total">';

				$html['content'] .= '<span class="screen-reader-text">' . sprintf(
				/* translators: %d: percentage of passed checks */
					esc_html__( '%d%% Passed Checks', 'accessibility-checker' ),
					$summary['passed_tests']
				) . '</span>';

				$html['content'] .= edac_icon( 'info', '', true );

				$html['content'] .= '<div aria-hidden="true" class="edac-summary-total-progress-circle ' . ( ( $summary['passed_tests'] > 50 ) ? ' over50' : '' ) . '">
					<div class="edac-summary-total-progress-circle-label">
						<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
						<div class="edac-panel-number-label">' . esc_html__( 'Passed Checks', 'accessibility-checker' ) . '<sup>*</sup></div>
					</div>
					<div class="left-half-clipper">
						<div class="first50-bar"></div>
						<div class="value-bar" style="transform: rotate(' . $summary['passed_tests'] * 3.6 . 'deg);"></div>
					</div>
				</div>';

				$html['content'] .= '<div aria-hidden="true" class="edac-summary-total-mobile">
					<div class="edac-panel-number">' . $summary['passed_tests'] . '%</div>
					<div class="edac-panel-number-label">' . esc_html__( 'Passed Tests', 'accessibility-checker' ) . '<sup>*</sup></div>
					<div class="edac-summary-total-mobile-bar"><span style="width:' . ( $summary['passed_tests'] ) . '%;"></span></div>
				</div>';

			$html['content'] .= '</li>';

			// if this is a virtual page, we don't show the readability section.
			$is_virtual_page = edac_is_virtual_page( $post_id );

			$html['content'] .= '
				' . edac_generate_summary_stat(
				'edac-summary-errors',
				$summary['errors'],
				_n( 'Problem', 'Problems', $summary['errors'], 'accessibility-checker' ),
				$summary['errors'] > 0 ? 'error' : 'check'
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-contrast',
				$summary['contrast_errors'],
				_n( 'Contrast Problem', 'Contrast Problems', $summary['contrast_errors'], 'accessibility-checker' ),
				$summary['contrast_errors'] > 0 ? 'error' : 'check'
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-warnings',
				$summary['warnings'],
				_n( 'Needs Review', 'Needs Review', $summary['warnings'], 'accessibility-checker' ),
				$summary['warnings'] > 0 ? 'warning' : 'check'
			) . '
				' . edac_generate_summary_stat(
				'edac-summary-ignored',
				$summary['ignored'],
				_n( 'Dismissed Issue', 'Dismissed Issues', $summary['ignored'], 'accessibility-checker' ),
				'dismissed'
			) . '

		</ul>
		<div class="edac-summary-readability" ' . ( $is_virtual_page ? 'style="display: none;"' : '' ) . '>
			<div class="edac-summary-readability-level">
				<div>' . edac_icon(
			edac_get_readability_panel_icon(
				(int) $summary['content_grade'] > 0,
				(int) $summary['content_grade'],
				(int) $summary['content_grade'] > 9,
				$simplified_summary,
				$simplified_summary_grade,
				$simplified_summary_grade_failed
			)
		) . '</div>
				<div class="edac-panel-number">
					' . $summary['readability'] . '
				</div>
				<div class="edac-panel-number-label">' . esc_html__( 'Reading Level', 'accessibility-checker' ) . '</div>
			</div>
			<div class="edac-summary-readability-summary">
				<div class="edac-summary-readability-summary-text' . ( ( ( 'none' === $simplified_summary_prompt || $summary['simplified_summary'] || (int) $summary['content_grade'] <= 9 ) && ! $simplified_summary_grade_failed ) ? ' active' : '' ) . '">' . $simplified_summary_text . '</div>
			</div>
		</div>
		';

		$html['content'] .= '<div class="edac-summary-disclaimer" id="edac-summary-disclaimer"><small>' . PHP_EOL;
		$html['content'] .= sprintf(
			/* translators: 1: opening anchor tag, 2: closing anchor tag with arrow icon and screen reader text */
			__( '* True accessibility requires manual testing in addition to automated scans. %1$sLearn how to manually test for accessibility%2$s', 'accessibility-checker' ),
			'<a target="_blank" href="' . esc_url(
				edac_generate_link_type(
					[
						'utm_campaign' => 'dashboard-widget',
						'utm_content'  => 'how-to-manually-check',
					],
					'help',
					[ 'help_id' => 4280 ]
				)
			) . '">',
			'<span aria-hidden="true"> ↗</span><span class="screen-reader-text">' . __( ', opens a new window', 'accessibility-checker' ) . '</span></a>'
		) . PHP_EOL;
		$html['content'] .= '</small></div>' . PHP_EOL;

		if ( ! $html ) {
			wp_send_json_error( new \WP_Error( '-3', __( 'No summary to return', 'accessibility-checker' ) ) );
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
	 *  - '-5' means that the user does not have permission to view this information for this post
	 */
	public function details() {

		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {
			wp_send_json_error( new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) ) );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) ) );
		}

		if ( ! current_user_can( 'edit_post', (int) $_REQUEST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( '-5', __( 'You do not have permission to view this information for this post.', 'accessibility-checker' ) ) );
		}

		$html = '';
		global $wpdb;
		$table_name = edac_get_valid_table_name( $wpdb->prefix . 'accessibility_checker' );
		$postid     = (int) $_REQUEST['post_id'];
		$siteid     = get_current_blog_id();

		// Send error if table name is not valid.
		if ( ! $table_name ) {
			wp_send_json_error( new \WP_Error( '-3', __( 'Invalid table name', 'accessibility-checker' ) ) );
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

		// shared comparator: sort by severity (ascending, 1=critical first), then count (descending).
		$sort_by_severity_then_count = function ( $a, $b ) {
			$severity_a = isset( $a['severity'] ) ? (int) $a['severity'] : PHP_INT_MAX;
			$severity_b = isset( $b['severity'] ) ? (int) $b['severity'] : PHP_INT_MAX;
			if ( $severity_a !== $severity_b ) {
				return $severity_a - $severity_b;
			}
			return (int) $b['count'] - (int) $a['count'];
		};

		usort( $error_rules, $sort_by_severity_then_count );
		usort( $warning_rules, $sort_by_severity_then_count );

		// sort passed rules array by severity (ascending, 1=critical first), then title.
		usort(
			$passed_rules,
			function ( $a, $b ) {
				$severity_a = isset( $a['severity'] ) ? (int) $a['severity'] : PHP_INT_MAX;
				$severity_b = isset( $b['severity'] ) ? (int) $b['severity'] : PHP_INT_MAX;
				if ( $severity_a !== $severity_b ) {
					return $severity_a - $severity_b;
				}
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

			$severity_map = [
				1 => [
					'label' => __( 'Critical', 'accessibility-checker' ),
					'class' => 'severity-critical',
				],
				2 => [
					'label' => __( 'High', 'accessibility-checker' ),
					'class' => 'severity-high',
				],
				3 => [
					'label' => __( 'Medium', 'accessibility-checker' ),
					'class' => 'severity-medium',
				],
				4 => [
					'label' => __( 'Low', 'accessibility-checker' ),
					'class' => 'severity-low',
				],
			];

			foreach ( $rules as $rule ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for interacting with custom database, safe variable used for table name, caching not required for one time operation.
				$results        = $wpdb->get_results( $wpdb->prepare( 'SELECT id, postid, object, ruletype, ignre, ignre_user, ignre_date, ignre_comment, ignre_reason, ignre_global, landmark, landmark_selector FROM %i where postid = %d and rule = %s and siteid = %d', $table_name, $postid, $rule['slug'], $siteid ), ARRAY_A );
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

				$severity_badge = '';
				if ( ! empty( $rule['severity'] ) && isset( $severity_map[ (int) $rule['severity'] ] ) ) {
					$sev            = $severity_map[ (int) $rule['severity'] ];
					$severity_badge = '<span class="edac-badge edac-badge--' . esc_attr( $sev['class'] ) . '"><span class="edac-badge__label">' . esc_html( $sev['label'] ) . '</span></span>';
				}

				$html .= '<div class="edac-details-rule">';

				$html .= '<div class="edac-details-rule-title">';

				$icon_name = ( 0 === $rule['count'] ) ? 'check' : ( ( 'error' === $rule['rule_type'] ) ? 'error' : 'warning' );

				$html .= '<h3>';
				$html .= edac_icon( $icon_name );
				$html .= ' ' . esc_html( $rule['title'] );
				$html .= ' <span class="edac-details-rule-count' . $count_classes . '"><span aria-hidden="true">(</span>' . $rule['count'] . '<span aria-hidden="true">)</span><span class="screen-reader-text">' . esc_html__( ' total', 'accessibility-checker' ) . '</span></span></span>';
				if ( $count_ignored > 0 ) {
					$html .= '<span class="edac-details-rule-count-ignore">' . $count_ignored . ' ' . esc_html( _n( 'Dismissed Issue', 'Dismissed Issues', $count_ignored, 'accessibility-checker' ) ) . '</span>';
				}
				$html .= $severity_badge;
				$html .= '</h3>';
				$html .= '<a href="' . $tool_tip_link . '" class="edac-details-rule-information" target="_blank" aria-label="' . esc_attr(
					sprintf(
						/* translators: 1: rule title, 2: "Opens in a new window." */
						__( 'Read documentation for %1$s. %2$s', 'accessibility-checker' ),
						$rule['title'],
						__( 'Opens in a new window.', 'accessibility-checker' )
					)
				) . '"><span class="dashicons dashicons-info"></span></a>';
				$html .= ( $expand_rule ) ? '<button class="edac-details-rule-title-arrow" aria-expanded="false" aria-controls="edac-details-rule-records-' . $rule['slug'] . '" aria-label="' . esc_attr(
					sprintf(
						/* translators: %s: rule title */
						__( 'Expand issues for %s', 'accessibility-checker' ),
						$rule['title']
					)
				) . '"><i class="dashicons dashicons-arrow-down-alt2"></i></button>' : '';
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
								' . esc_html__( 'Image', 'accessibility-checker' ) . '
							</div>
							<div class="edac-details-rule-records-labels-label" aria-hidden="true">
								' . esc_html__( 'Landmark', 'accessibility-checker' ) . '
							</div>
							<div class="edac-details-rule-records-labels-label" aria-hidden="true">
								' . esc_html__( 'Actions', 'accessibility-checker' ) . '
							</div>
						</div>';

					foreach ( $results as $row ) {

						$id            = (int) $row['id'];
						$ignore        = (int) $row['ignre'];
						$ignore_class  = $ignore ? ' active' : '';
						$ignore_label  = $ignore ? __( 'Dismissed', 'accessibility-checker' ) : __( 'Dismiss', 'accessibility-checker' );
						$ignore_global = (int) $row['ignre_global'];
						$ignore_reason = isset( $row['ignre_reason'] ) ? sanitize_text_field( $row['ignre_reason'] ) : '';

						// check for images and svgs in object code.
						$media      = edac_parse_html_for_media( $row['object'] );
						$object_img = $media['img'];
						$object_svg = $media['svg'];

						$html .= '<h4 class="screen-reader-text">' . sprintf(
							/* translators: %d: issue ID number */
							esc_html__( 'Issue ID %d', 'accessibility-checker' ),
							$id
						) . '</h4>';

						$html .= '<div id="edac-details-rule-records-record-' . $id . '" class="edac-details-rule-records-record">';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-object">';

						$html .= '<code>' . esc_html( $row['object'] ) . '</code>';

						$html .= '</div>';

						$html .= '<div class="edac-details-rule-records-record-cell edac-details-rule-records-record-image">';

						if ( $object_img ) {
							$html .= '<img src="' . esc_url( $object_img ) . '" alt="' . esc_attr(
								sprintf(
									/* translators: %d: issue ID number */
									__( 'image for issue %d', 'accessibility-checker' ),
									$id
								)
							) . '" />';
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

						$html .= IgnoreUI::render_ignore_panel(
							[
								'issue_id'          => $id,
								'is_ignored'        => (bool) $ignore,
								'ignore_user'       => (int) $row['ignre_user'],
								'ignore_date'       => $row['ignre_date'] ?? '',
								'ignore_comment'    => $row['ignre_comment'] ?? '',
								'ignore_reason'     => $ignore_reason,
								'ignore_global'     => $ignore_global,
								'ignore_type'       => $rule['rule_type'],
								'ignore_permission' => $ignore_permission,
							]
						);

						$html .= '</div>';

					}

					$html .= '</div>';

				}

				$html .= '</div>';
			}
		}

		if ( ! $html ) {
			wp_send_json_error( new \WP_Error( '-4', __( 'No details to return', 'accessibility-checker' ) ) );
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
	 *  - '-5' means that the user does not have permission to view this information for this post
	 */
	public function readability() {

		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {
			wp_send_json_error( new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) ) );
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( '-2', __( 'The post ID was not set', 'accessibility-checker' ) ) );
		}

		if ( ! current_user_can( 'edit_post', (int) $_REQUEST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( '-5', __( 'You do not have permission to view this information for this post.', 'accessibility-checker' ) ) );
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
		' . edac_icon( ( $post_grade_failed && $simplified_summary && $simplified_summary_grade > 0 && ! $simplified_summary_grade_failed ) ? 'info' : ( ( $post_grade_failed || 0 === $post_grade ) ? 'warning' : 'check' ), '', true, '', 'edac-readability-list-item-icon' ) . '
		<h3 class="edac-readability-list-item-title">' .
		sprintf(
			/* translators: %s: reading grade level value e.g. "8th Grade (Flesch-Kincaid)", displayed in a <strong> element. Do not translate the %s placeholder. */
			esc_html__( 'Post Reading Grade Level: %s', 'accessibility-checker' ),
			'<strong class="' . ( ( $post_grade_failed || 0 === $post_grade ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . ( ( 0 === $post_grade ) ? esc_html__( 'None', 'accessibility-checker' ) : esc_html( $post_grade_readability ) ) . '</strong>'
		) . '<br /></h3>';
		if ( $post_grade_failed ) {
			$html .= '<p class="edac-readability-list-item-description">' . esc_html__( 'Your post has a reading level higher than 9th grade. Web Content Accessibility Guidelines (WCAG) at the AAA level require a simplified summary of your post that is 9th grade or below.', 'accessibility-checker' ) . '</p>';
		} elseif ( 0 === $post_grade ) {
			$html .= '<p class="edac-readability-list-item-description">' . esc_html__( 'Your post does not contain enough content to calculate its reading level.', 'accessibility-checker' ) . '</p>';
		} else {
			$html .= '<p class="edac-readability-list-item-description">' . esc_html__( 'A simplified summary is not necessary when content reading level is 9th grade or below. Choose when to prompt for a simplified summary on the settings page.', 'accessibility-checker' ) . '</p>';
		}
		$html .= '</li>';

		if ( $post_grade_failed ) {

			if ( $simplified_summary && 'none' !== $simplified_summary_prompt ) {
				if ( 0 === $simplified_summary_grade ) {
					$html .= '<li class="edac-readability-list-item edac-readability-summary-grade-level">
					' . edac_icon( 'warning', '', true, '', 'edac-readability-list-item-icon' ) . '
						<h3 class="edac-readability-list-item-title">' .
						sprintf(
							/* translators: %s: the simplified summary grade level value (wrapped in a <strong> tag) */
							esc_html__( 'Simplified Summary Reading Grade Level: %s', 'accessibility-checker' ),
							'<strong class="failed-text-color">' . esc_html__( 'None', 'accessibility-checker' ) . '</strong>'
						)
						. '</h3>
						<p class="edac-readability-list-item-description">' . esc_html__( 'Not enough content to determine an accurate reading level.', 'accessibility-checker' ) . '</p>
					</li>';
				} else {
					$html .= '<li class="edac-readability-list-item edac-readability-summary-grade-level">
				' . edac_icon( $simplified_summary_grade_failed ? 'warning' : 'check', '', true, '', 'edac-readability-list-item-icon' ) . '
					<h3 class="edac-readability-list-item-title">' .
						sprintf(
							/* translators: %s: the simplified summary grade level value (wrapped in a <strong> tag) */
							esc_html__( 'Simplified Summary Reading Grade Level: %s', 'accessibility-checker' ),
							'<strong class="' . ( ( $simplified_summary_grade_failed ) ? 'failed-text-color' : 'passed-text-color' ) . '">' . esc_html( edac_ordinal( $simplified_summary_grade ) ) . '</strong>'
						)
					. '</h3>
					<p class="edac-readability-list-item-description">' . ( ( $simplified_summary_grade_failed )
						? esc_html__( 'Your simplified summary has a reading level above 9th grade.', 'accessibility-checker' )
						: esc_html__( 'Your simplified summary has a reading level at or below 9th grade.', 'accessibility-checker' )
					) . '</p>
				</li>';
				}
			}

			if ( 'none' === $simplified_summary_prompt ) {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					' . edac_icon( 'warning', '', true, '', 'edac-readability-list-item-icon' ) . '
					<h3 class="edac-readability-list-item-title">' . esc_html__( 'Simplified summary is not being automatically inserted into the content.', 'accessibility-checker' ) . '</h3>
						<p class="edac-readability-list-item-description">' . sprintf(
							/* translators: %s: link to the settings page */
						__( 'Your Prompt for Simplified Summary is set to &ldquo;never.&rdquo; If you would like the simplified summary to be displayed automatically, you can change this on the %s.', 'accessibility-checker' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ) . '">' . esc_html__( 'settings page', 'accessibility-checker' ) . '</a>'
					) . '</p>
				</li>';

			} elseif ( 'none' !== $simplified_summary_position ) {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
				' . edac_icon( 'check', '', true, '', 'edac-readability-list-item-icon' ) . '
					<h3 class="edac-readability-list-item-title">' . sprintf(
						/* translators: %s: position phrase wrapped in a <strong> element (e.g. "<strong>before the content</strong>"). Do not translate the %s placeholder. */
					__( 'Simplified summary is being automatically inserted %s.', 'accessibility-checker' ),
					'<strong>' . esc_html( $simplified_summary_position ) . ' ' . esc_html__( 'the content', 'accessibility-checker' ) . '</strong>'
				) . '</h3>
						<p class="edac-readability-list-item-description">' . sprintf(
							/* translators: %s: link to the settings page */
					__( 'Set where the Simplified Summary is inserted into the content on the %s.', 'accessibility-checker' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ) . '">' . esc_html__( 'settings page', 'accessibility-checker' ) . '</a>'
				) . '</p>
				</li>';

			} else {

				$html .=
					'<li class="edac-readability-list-item edac-readability-summary-position">
					' . edac_icon( 'warning', '', true, '', 'edac-readability-list-item-icon' ) . '
					<h3 class="edac-readability-list-item-title">' . esc_html__( 'Simplified summary is not being automatically inserted into the content.', 'accessibility-checker' ) . '</h3>
						<p class="edac-readability-list-item-description">' . sprintf(
							/* translators: %s: link to the settings page */
						__( 'Your Simplified Summary location is set to &ldquo;manually&rdquo; which requires a function be added to your page template. If you would like the simplified summary to be displayed automatically, you can change this on the %s.', 'accessibility-checker' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ) . '">' . esc_html__( 'settings page', 'accessibility-checker' ) . '</a>'
					) . '</p>
				</li>';

			}
		}

		$html .= '</ul>';

		if ( ( $post_grade_failed || 'always' === $simplified_summary_prompt ) && ( 'none' !== $simplified_summary_prompt ) ) {
			$html .=
				'</form>
			<form action="/" class="edac-readability-simplified-summary">
				<label for="edac-readability-text">' . esc_html__( 'Simplified Summary', 'accessibility-checker' ) . '</label>
				<textarea name="" id="edac-readability-text" cols="30" rows="10">' . $simplified_summary . '</textarea>
				<p><input type="submit" class="button button-primary" value="' . esc_attr__( 'Save Summary', 'accessibility-checker' ) . '"></p>
			</form>';
		}

		$html .= '<span class="dashicons dashicons-info" aria-hidden="true"></span> <a href="' . esc_url( edac_link_wrapper( 'https://a11ychecker.com/help3265', 'wordpress-general', 'content-analysis', false ) ) . '" target="_blank">' . esc_html__( 'Learn more about improving readability and simplified summary requirements', 'accessibility-checker' ) . '<span aria-hidden="true"> ↗</span><span class="screen-reader-text">' . __( ', opens a new window', 'accessibility-checker' ) . '</span></a>';

		if ( ! $html ) {
			wp_send_json_error( new \WP_Error( '-3', __( 'No readability data to return', 'accessibility-checker' ) ) );
		}

		wp_send_json_success( wp_json_encode( $html ) );
	}

	/**
	 * Insert ignore data into database
	 *
	 * Note: There is a new dismiss-issue rest endpoint that covers this functionality
	 * now and should be used going forward. This ajax was updated to support dismiss
	 * reasons to align with the new endpoint and allow other things to continue to
	 * work here gracefully.
	 *
	 * This should be removed in a future release.
	 *
	 * @return void
	 *
	 *  - '-1' means that nonce could not be varified
	 *  - '-2' means that there isn't any ignore data to return
	 */
	public function add_ignore() {

		// nonce security.
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'ajax-nonce' ) ) {
			wp_send_json_error( new \WP_Error( '-1', __( 'Permission Denied', 'accessibility-checker' ) ) );
		}

		global $wpdb;
		$table_name           = $wpdb->prefix . 'accessibility_checker';
		$raw_ids              = isset( $_REQUEST['ids'] ) ? (array) wp_unslash( $_REQUEST['ids'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization handled below.
		$ids                  = array_map(
			function ( $value ) {
				return (int) $value;
			},
			$raw_ids
		); // Sanitizing array elements to integers.
		$action               = isset( $_REQUEST['ignore_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ignore_action'] ) ) : '';
		$type                 = isset( $_REQUEST['ignore_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ignore_type'] ) ) : '';
		$siteid               = get_current_blog_id();
		$ignre                = ( 'enable' === $action ) ? 1 : 0;
		$ignre_user           = ( 'enable' === $action ) ? get_current_user_id() : null;
		$ignre_user_info      = ( 'enable' === $action ) ? get_userdata( $ignre_user ) : '';
		$ignre_username       = ( 'enable' === $action ) ? $ignre_user_info->user_login : '';
		$ignre_date           = ( 'enable' === $action ) ? edac_get_current_utc_datetime() : null;
		$ignre_date_formatted = ( 'enable' === $action ) ? edac_format_datetime_from_utc( $ignre_date ) : '';
		$ignre_comment        = ( 'enable' === $action && isset( $_REQUEST['comment'] ) ) ? sanitize_textarea_field( wp_unslash( $_REQUEST['comment'] ) ) : null;
		$ignre_reason         = ( 'enable' === $action && isset( $_REQUEST['reason'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['reason'] ) ) : null;
		$ignore_global        = ( 'enable' === $action && isset( $_REQUEST['ignore_global'] ) ) ? sanitize_textarea_field( wp_unslash( $_REQUEST['ignore_global'] ) ) : 0;

		// If largeBatch is set and 'true', we need to perform an update using the 'object'
		// instead of IDs. It is a much less efficient query than by IDs - but many IDs run
		// into request size limits which caused this to not function at all.
		if ( isset( $_REQUEST['largeBatch'] ) && 'true' === $_REQUEST['largeBatch'] ) {
			// Get the 'object' from the first id.
			$first_id = $ids[0];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- We need to get the latest value, not a cached value.
			$object = $wpdb->get_var( $wpdb->prepare( 'SELECT object FROM %i WHERE id = %d', $table_name, $first_id ) );

			if ( ! $object ) {
				wp_send_json_error( new \WP_Error( '-2', __( 'No ignore data to return', 'accessibility-checker' ) ) );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
			$wpdb->query( $wpdb->prepare( 'UPDATE %i SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_reason = %s, ignre_global = %d WHERE siteid = %d and object = %s', $table_name, $ignre, $ignre_user, $ignre_date, $ignre_comment, $ignre_reason, $ignore_global, $siteid, $object ) );
		} else {
			// For small batches of IDs, we can just loop through.
			foreach ( $ids as $id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe variable used for table name, caching not required for one time operation.
				$wpdb->query( $wpdb->prepare( 'UPDATE %i SET ignre = %d, ignre_user = %d, ignre_date = %s, ignre_comment = %s, ignre_reason = %s, ignre_global = %d WHERE siteid = %d and id = %d', $table_name, $ignre, $ignre_user, $ignre_date, $ignre_comment, $ignre_reason, $ignore_global, $siteid, $id ) );
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
			wp_send_json_error( new \WP_Error( '-2', __( 'No ignore data to return', 'accessibility-checker' ) ) );
		}
		wp_send_json_success( wp_json_encode( $data ) );
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
