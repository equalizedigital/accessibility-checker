<?php
/**
 * Class file for Welcome Page
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Scan_Report_Data;

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
		$scan_data = new Scan_Report_Data( 5 );
		$summary = $scan_data->scan_summary();

		
		$html .= '
			<div id="edac_welcome_page_summary">';


		if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {
	
			$html .= '
			<section>
				<div class="edac-cols">
				<h2 class="edac-cols-left">
					Most Recent Test Summary
				</h2>

				<p class="edac-cols-right edac-right-text"> 
					<a class="button edac-mr-1" href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=scan' ) ) . '">Start New Scan</a>
					<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_issues' ) ) . '">View All Open Issues</a>
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
						<div class="edac-progress-label edac-large-text">Passed Tests</div>
						
					</div>
				</div>';


			$html .= '
					<div class="edac-welcome-grid-c2 edac-welcome-grid-item' . ( ( $summary['distinct_errors_without_contrast'] > 0 ) ? ' has-errors' : ' has-no-errors' ) . '">
						<div class="edac-inner-row">
							<div class="edac-stat-number">' . $summary['distinct_errors_without_contrast'] . '</div>
						</div>
						<div class="edac-inner-row">
							<div class="edac-stat-label">Unique Error' . ( ( 1 == $summary['distinct_errors_without_contrast'] ) ? '' : 's' ) . '</div>
						</div>
					</div>
	
					<div class="edac-welcome-grid-c3 edac-welcome-grid-item' . ( ( $summary['distinct_contrast_errors'] > 0 ) ? ' has-contrast-errors' : ' has-no-contrast-errors' ) . '">
						<div class="edac-inner-row">
							<div class="edac-stat-number">' . $summary['distinct_contrast_errors'] . '</div>
						</div>
						<div class="edac-inner-row">
							<div class="edac-stat-label">Unique Color Contrast Error' . ( ( 1 == $summary['distinct_errors_without_contrast'] ) ? '' : 's' ) . '</div>
						</div>
					</div>

			
					<div class="edac-welcome-grid-c4 edac-welcome-grid-item' . ( ( $summary['distinct_warnings'] > 0 ) ? ' has-warning' : ' has-no-warning' ) . '">
						<div class="edac-inner-row">
							<div class="edac-stat-number">' . $summary['distinct_warnings'] . '</div>
						</div>
						<div class="edac-inner-row">
							<div class="edac-stat-label">Unique Warning' . ( ( 1 == $summary['distinct_warnings'] ) ? '' : 's' ) . '</div>
						</div>
					</div>
		

					
			
					<div class="edac-welcome-grid-c5 edac-welcome-grid-item' . ( ( $summary['distinct_ignored'] > 0 ) ? ' has-ignored' : ' has-no-ignored' ) . '">
						<div class="edac-inner-row">
							<div class="edac-stat-number">' . $summary['distinct_ignored'] . '</div>
						</div>
						<div class="edac-inner-row">
							<div class="edac-stat-label">Ignored Item' . ( ( 1 == $summary['distinct_ignored'] ) ? '' : 's' ) . '</div>
						</div>
					</div>';

			$html .= '
					
				<div class="edac-welcome-grid-c6 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">Average Issues Per Page</div>
					</div>

					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['avg_issues_per_post'] . '</div>
					</div>
				</div>

		
				<div class="edac-welcome-grid-c7 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">Average Issue Density</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['avg_issue_density_percentage'] . '%</div>
					</div>
				</div>

		
				<div class="edac-welcome-grid-c8 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">Last Full-Site Scan: </div>
					</div>
					<div class="edac-inner-row">
				
					';
				
				if($summary['fullscan_completed_at'] > 0){
					$html .= '
						<div class="edac-stat-number edac-timestamp-to-local">' . $summary['fullscan_completed_at'] . '</div>';
				} else {
					$html .= '
						<div class="edac-stat-number">Never</div>';
				}

				$html .= '
					</div>
				</div>



				<div class="edac-welcome-grid-c9 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['posts_scanned'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">URLs Scanned</div>
					</div>
				</div>

	
				<div class="edac-welcome-grid-c10 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['scannable_post_types_count'] . ' of ' . $summary['public_post_types_count'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">Post Types Checked</div>
					</div>
				</div>

				<div class="edac-welcome-grid-c11 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['posts_without_issues'] . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">URLs with 100% score</div>
					</div>
				</div>

			</div>
			</section>';

			
		} else {

		
			if ( true !== boolval( get_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true )) ) {
	
				$html .='
					<section>
					<div class="edac-cols">
						<h3 class="edac-cols-left">
							Site-Wide Accessibility Reports
						</h3>

						<p class="edac-cols-right edac-right-text"> 
							<button id="dismiss_welcome_cta" class="button">Hide banner</button>
						</p>
					</div>
 
					<div class="edac-modal-container"> 
						
					
						<div class="edac-modal">

							<div class="edac-modal-content">

								<h3 class="edac-align-center">Unlock Detailed Accessibility Reports</h3>
								<p class="edac-align-center">Start scanning your entire website for accessibility issues, get full-site reports,
								and become compliant with accessibility guidelines faster.</p>
								<p class="edac-align-center">
									<a class="button" href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">
									Upgrade Accessibility Checker
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
