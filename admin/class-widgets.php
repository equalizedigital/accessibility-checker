<?php
/**
 * Class file for widgets
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Class that handles widgets
 */
class Widgets {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'wp_dashboard_setup', [ $this, 'dashboard_setup' ] );
	}

	/**
	 * Add dashboard widget
	 *
	 * @return void
	 */
	public function dashboard_setup() {

		if ( ! Helpers::current_user_can_see_widgets_and_notices() ) {
			return;
		}
		wp_add_dashboard_widget(
			'edac_dashboard_scan_summary',
			'Accessibility Checker',
			[ $this, 'render_dashboard_scan_summary' ]
		);
	}

	/**
	 * Renders as widget
	 *
	 * @return void
	 */
	public function render_dashboard_scan_summary() {

		$html  = '';
		$html .= '

		<div class="edac-widget">';

		if ( Settings::get_scannable_post_types() && Settings::get_scannable_post_statuses() ) {

			$pro_modal_html = '';
			if ( ( ! is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ||
			false === EDAC_KEY_VALID ) &&
			true !== (bool) get_user_meta( get_current_user_id(), 'edac_dashboard_cta_dismissed', true )
			) {
				$pro_modal_html = '
			<div class="edac-modal">
				<div class="edac-modal-content">
					<button class="edac-modal-content-close edac-widget-modal-content-close" aria-label="' . esc_attr__( 'close ad', 'accessibility-checker' ) . '">&times;</button>
					<h3>' . __( 'Get Detailed Accessibility Reports', 'accessibility-checker' ) . '</h3>
					<p class="edac-align-center">' . __( 'Start scanning your entire website for accessibility issues, get full-site reports, and become compliant with accessibility guidelines faster.', 'accessibility-checker' ) . '</p>
					<p class="edac-align-center">
					<a class="button button-primary" href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">' . __( 'Upgrade Accessibility Checker', 'accessibility-checker' ) . '</a>
					</p>
				</div>
			</div>';
			}

			if ( ( is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) || '' !== $pro_modal_html ) {

				$html .= '
			<div class="edac-summary edac-modal-container edac-hidden">';

				$html .= $pro_modal_html;

				$html .= '
			<h3 class="edac-summary-header">' .
				__( 'Full Site Accessibility Status', 'accessibility-checker' ) .
				'</h3>
			<div class="edac-summary-passed">
				<div id="edac-summary-passed" class="edac-circle-progress" role="progressbar" aria-valuenow="0"
					aria-valuemin="0" aria-valuemax="100"
					style="text-align: center;
					background: radial-gradient(closest-side, white 85%, transparent 80% 100%),
					conic-gradient(#006600 0%, #e2e4e7 0);">
					<div class="edac-progress-percentage">-</div>
					<div class="edac-progress-label">' .
					__( 'Passed Tests', 'accessibility-checker' ) .
					'</div>
				</div>
			</div>

			<div class="edac-summary-info">
				<div class="edac-summary-info-date">
					<div class="edac-summary-info-date-label">' . __( 'Report Last Updated:', 'accessibility-checker' ) . '</div>
					<div id="edac-summary-info-date" class="edac-summary-info-date-date edac-timestamp-to-local">-</div>';

				$html .= '
				</div>

				<div class="edac-summary-info-count">
					<div class="edac-summary-scan-count-label">
						' . __( 'URLs Scanned:', 'accessibility-checker' ) . '
					</div>
					<div id="edac-summary-info-count" class="edac-summary-info-count-number">-</div>
				</div>';

				$html .= '
				<div class="edac-summary-info-stats">
					<div  class="edac-summary-info-stats-box edac-summary-info-stats-box-error">
						<div class="edac-summary-info-stats-box-label">' . __( 'Errors:', 'accessibility-checker' ) . ' </div>
						<div id="edac-summary-info-errors" class="edac-summary-info-stats-box-number">-</div>
					</div>
					<div class="edac-summary-info-stats-box edac-summary-info-stats-box-contrast">
						<div class="edac-summary-info-stats-box-label">' . __( 'Color Contrast Errors:', 'accessibility-checker' ) . ' </div>
						<div id="edac-summary-info-contrast-errors" class="edac-summary-info-stats-box-number">-</div>
					</div>
					<div class="edac-summary-info-stats-box edac-summary-info-stats-box-warning">
						<div class="edac-summary-info-stats-box-label">' . __( 'Warnings:', 'accessibility-checker' ) . ' </div>
						<div id="edac-summary-info-warnings" class="edac-summary-info-stats-box-number">-</div>
					</div>
				</div>
			</div>
			';

				$html .= '
			<div class="edac-summary-notice edac-summary-notice-has-issues edac-hidden">
				' . __( 'Your site has accessibility issues that should be addressed as soon as possible to ensure compliance with accessibility guidelines.', 'accessibility-checker' ) . '
			</div>
			 <div class="edac-summary-notice edac-summary-notice-no-issues edac-hidden">
				' . __( 'Way to go! Accessibility Checker cannot find any accessibility problems in the content it tested. Some problems cannot be found by automated tools so don\'t forget to', 'accessibility-checker' ) . ' <a href="https://equalizedigital.com/accessibility-checker/how-to-manually-check-your-website-for-accessibility/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">' . __( 'manually test your site', 'accessibility-checker' ) . '</a>.
			</div>';

				$html .= '
		</div>
		<hr class="edac-hr" />';

			}
		}

		$html .= '

		<div class="edac-issues-summary edac-hidden">

			<h3 class="edac-issues-summary-header">
				' . __( 'Issues By Post Type', 'accessibility-checker' ) . '
			</h3>

			<table class="widefat striped">
				<thead>
				<tr>
					<th scope="col">
						' . __( 'Post Type', 'accessibility-checker' ) . '
					</th>
					<th scope="col" >
						' . __( 'Errors', 'accessibility-checker' ) . '
					</th>
					<th scope="col" >
						' . __( 'Contrast', 'accessibility-checker' ) . '
					</th>
					<th scope="col" >
						' . __( 'Warnings', 'accessibility-checker' ) . '
					</th>
				</tr>
				</thead>

				<tbody>';

				$scannable_post_types = Settings::get_scannable_post_types();

				$post_types = get_post_types(
					[
						'public' => true,
					]
				);
				unset( $post_types['attachment'] );

		foreach ( $post_types as $post_type ) {

			$post_types_to_check = array_merge( [ 'post', 'page' ], $scannable_post_types );

			if ( in_array( $post_type, $post_types_to_check, true ) ) {

				if ( in_array( $post_type, $scannable_post_types, true ) ) {

					$html .= '
							<tr>
								<th scope="col">' . esc_html( ucwords( $post_type ) ) . '</th>
								<td id="' . esc_attr( $post_type ) . '-errors">-</td>
								<td id="' . esc_attr( $post_type ) . '-contrast-errors">-</td>
								<td id="' . esc_attr( $post_type ) . '-warnings">-</td>
							</tr>';
				} else {
					$html .= '
							<tr>
								<th scope="col">' . esc_html( ucwords( $post_type ) ) . '</th>
								<td>-</td>
								<td>-</td>
								<td>-</td>
							</tr>';

				}
			} elseif ( is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {

				$html .= '
						<tr >
							<th scope="col">' . esc_html( ucwords( $post_type ) ) . '</th>
							<td>-</td>
							<td>-</td>
							<td>-</td>
						</tr>';

			} else {

				$html .= '
						<tr >
							<th scope="col">' . esc_html( ucwords( $post_type ) ) . '</th>
							<td colspan="3">
								<div class="edac-issues-summary-notice-upgrade-to-edacp">
									<a href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">
										' . __( 'Upgrade to Scan', 'accessibility-checker' ) . '
									</a>
								</div>
							</td>
						</tr>';

			}
		}

		$html .= '
				</tbody>
			</table>
		</div>';

		$html .= '<div class="edac-summary-notice edac-summary-notice-is-truncated edac-hidden">Your site has a large number of issues. For performance reasons, not all issues have been included in this report.</div>';

		if ( ! Settings::get_scannable_post_types() || ! Settings::get_scannable_post_statuses() ) {

			$html .= '<div class="edac-summary-notice edac-summary-notice-no-posts">There are no pages set to be checked. Update the post types to be checked under the
			<a href="/wp-admin/admin.php?page=accessibility_checker_settings">general settings</a> tab.</div>';

		}

		$html .= '
		<div class="edac-buttons-container edac-mt-3 edac-mb-3">
		';

		if ( is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {
			$html .= '
			<a class="button edac-mr-1" href="/wp-admin/admin.php?page=accessibility_checker">' . __( 'See More Reports', 'accessibility-checker' ) . '</a>';
		}

		$html .= '
		<a href="/wp-admin/admin.php?page=accessibility_checker_settings">Edit Accessibility Checker Settings</a>
		</div>';

		$html .= '
		<hr class="edac-hr" />
		<h3 class="edac-summary-header">
			' . __( 'Learn Accessibility', 'accessibility-checker' ) . '
		</h3>';

		$html .= edac_get_upcoming_meetups_html( 'wordpress-accessibility-meetup-group', 2, 4 );

		$html .= '
		<hr class="edac-hr" />
		<div class="edac-widget-footer-link-list">';

		$html .= '<h3 class="screen-reader-text">' . __( 'Additional Resources', 'accessibility-checker' ) . '</h3>';
		$html .= '<a target="_blank" aria-label="' . __( 'Blog (opens in a new window)', 'accessibility-checker' ) . '" class="edac-widget-footer-link-list-item edac-mr-1" href="https://equalizedigital.com/resources/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">' . __( 'Blog', 'accessibility-checker' ) . '</a>';
		$html .= '<span class="edac-widget-footer-link-list-spacer"></span><a target="_blank" aria-label="' . __( 'Documentation (opens in a new window)', 'accessibility-checker' ) . '" class="edac-widget-footer-link-list-item edac-ml-1" href="https://equalizedigital.com/accessibility-checker/documentation/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">' . __( 'Documentation', 'accessibility-checker' ) . '</a></div></div>';

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}
}
