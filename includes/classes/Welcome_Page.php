<?php
/**
 * Class file for Welcome Page
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Scans_Stats;

/**
 * Class that handles welcome page
 */
class Welcome_Page {
	

	/**
	 * Renders page summary
	 *
	 * @return void
	 */
	public static function render_summary() {

		$html = '';
		$scans_stats = new Scans_Stats();
		$summary = $scans_stats->summary();
		
		
	
		$html .= '
			<div id="edac_welcome_page_summary">';


		if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {
	
			$html .= '
			<section>
				<div class="edac-cols edac-cols-header">
					<h2 class="edac-cols-left">
						' . __( 'Most Recent Test Summary', 'accessibility-checker' ) . '
					</h2>

					<p class="edac-cols-right"> 
						<a class="button" href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=scan' ) ) . '">' . __( 'Start New Scan', 'accessibility-checker' ) . '</a>
						<a class="edac-ml-1 button button-primary" href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_issues' ) ) . '">' . __( 'View All Open Issues', 'accessibility-checker' ) . '</a>
					</p>
				</div>
				<div class="edac-welcome-grid-container">';

			$html .= '
				<div class="edac-welcome-grid-c1 edac-welcome-grid-item edac-background-light" style="grid-area: 1 / 1 / span 2;">
					<div class="edac-circle-progress" role="progressbar" aria-valuenow="' . $summary['passed_percentage'] . '" 
						aria-valuemin="0" aria-valuemax="100"
						style="text-align: center; 
						background: radial-gradient(closest-side, white 90%, transparent 80% 100%), 
						conic-gradient(#006600 ' . $summary['passed_percentage'] . '%, #e2e4e7 0);">
						<div class="edac-progress-percentage edac-xxx-large-text">' . $summary['passed_percentage'] . '%</div>
						<div class="edac-progress-label edac-large-text">' . __( 'Passed Tests', 'accessibility-checker' ) . '</div>
					</div>
				</div>';

			$html .= '
				<div class="edac-welcome-grid-c2 edac-welcome-grid-item' . ( ( $summary['distinct_errors_without_contrast'] > 0 ) ? ' has-errors' : ' has-no-errors' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['distinct_errors_without_contrast'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Unique Error', 'Unique Errors', $summary['distinct_errors_without_contrast'], 'accessibility-checker' ), $summary['distinct_errors_without_contrast'] ) . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c3 edac-welcome-grid-item' . ( ( $summary['distinct_contrast_errors'] > 0 ) ? ' has-contrast-errors' : ' has-no-contrast-errors' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['distinct_contrast_errors'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Unique Color Contrast Error', 'Unique Color Contrast Errors', $summary['distinct_contrast_errors'], 'accessibility-checker' ), $summary['distinct_contrast_errors'] ) . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c4 edac-welcome-grid-item' . ( ( $summary['distinct_warnings'] > 0 ) ? ' has-warning' : ' has-no-warning' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['distinct_warnings'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Unique Warning', 'Unique Warnings', $summary['distinct_warnings'], 'accessibility-checker' ), $summary['distinct_warnings'] ) . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c5 edac-welcome-grid-item' . ( ( $summary['distinct_ignored'] > 0 ) ? ' has-ignored' : ' has-no-ignored' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['distinct_ignored'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Ignored Item', 'Ignored Items', $summary['distinct_ignored'], 'accessibility-checker' ), $summary['distinct_ignored'] ) . '</div>
					</div>
				</div>';
			

			$html .= '
				<div class="edac-welcome-grid-c6 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Average Issues Per Page', 'accessibility-checker' ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['avg_issues_per_post'] . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c7 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Average Issue Density', 'accessibility-checker' ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['avg_issue_density_percentage'] . '%</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c8 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Last Full-Site Scan:', 'accessibility-checker' ) . '</div>
					</div>
					<div class="edac-inner-row">
						';
				
					if ($summary['fullscan_completed_at'] > 0) {
						$html .= '
							<div class="edac-stat-number edac-timestamp-to-local">' . $summary['fullscan_completed_at'] . '</div>';
					} else {
						$html .= '
							<div class="edac-stat-number">' . esc_html__( 'Never', 'accessibility-checker' ) . '</div>';
					}

				$html .= '
					</div>
				</div>

				<div class="edac-welcome-grid-c9 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['posts_scanned'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'URLs Scanned', 'accessibility-checker' ) . '</div>
					</div>
				</div>

				<div class="edac-welcome-grid-c10 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['scannable_post_types_count'] . ' ' . esc_html__( 'of', 'accessibility-checker' ) . ' ' . $summary['public_post_types_count'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Post Types Checked', 'accessibility-checker' ) . '</div>
					</div>
				</div>

				<div class="edac-welcome-grid-c11 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['posts_without_issues'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'URLs with 100% score', 'accessibility-checker' ) . '</div>
					</div>
				</div>

			</div>';


			if ( $summary['is_truncated'] ) {
				$html .= '<div class="edac-center-text edac-mt-3">Your site has a large number of issues. For performance reasons, not all issues have been included in this summary.</div>';
			}
	
			$html .= '
			</section>';
			
		} else {

		
			if ( true !== boolval( get_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true ) ) ) {
	
				$html .= '
				<section>
					<div class="edac-cols edac-cols-header">
						<h3 class="edac-cols-left">' . esc_html__( 'Site-Wide Accessibility Reports', 'accessibility-checker' ) . '</h3>

						<p class="edac-cols-right"> 
							<button id="dismiss_welcome_cta" class="button">' . esc_html__( 'Hide banner', 'accessibility-checker' ) . '</button>
						</p>
					</div>

					<div class="edac-modal-container"> 
						<div class="edac-modal">
							<div class="edac-modal-content">
								<h3 class="edac-align-center">' . esc_html__( 'Unlock Detailed Accessibility Reports', 'accessibility-checker' ) . '</h3>
								<p class="edac-align-center">' . esc_html__( 'Start scanning your entire website for accessibility issues, get full-site reports, and become compliant with accessibility guidelines faster.', 'accessibility-checker' ) . '</p>
								<p class="edac-align-center">
									<a class="button button-primary" href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">
										' . esc_html__( 'Upgrade Accessibility Checker', 'accessibility-checker' ) . '
									</a>
								</p>
							</div>	
						</div>					
					</div>
				</section>';

			}

		}
		
		$html .= '
		</div>';

		echo $html;

	}
}
