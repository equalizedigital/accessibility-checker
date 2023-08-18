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
	
		if ( edac_check_plugin_installed( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {
	
			$html .= '
			<div id="edac_welcome_page_summary" class="edac-summary">	

				<h3 class="edac-summary-header">
					Most Recent Test Summary
				</h3>

				<a class="button" href="/wp-admin/admin.php?page=accessibility_checker_settings&tab=scan">Start New Scan</a>
				<a class="button button-primary" href="/wp-admin/admin.php?page=accessibility_checker_issues">View All Open Issues</a>';
				
			
			$html .= '
			<div class="edac-summary-group">
				<div class="edac-summary-passed edac-dark-border">
					<div class="edac-progress-bar" role="progressbar" aria-valuenow="' . $summary['passed_percentage'] . '" 
						aria-valuemin="0" aria-valuemax="100"
						style="text-align: center; 
						background: radial-gradient(closest-side, white 79%, transparent 80% 100%), 
						conic-gradient(#006600 ' . $summary['passed_percentage'] . '%, #e2e4e7 0);">
						<div class="edac-progress-percentage">' . $summary['passed_percentage'] . '%</div>
						<div class="edac-progress-label">Passed Tests</div>
					</div>
				</div>';

			$html .= '
				<div class="edac-summary-info-stats">

				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-error ' . ( ( $summary['distinct_errors_without_contrast'] > 0 ) ? ' has-errors' : '' ) . '">
						<div class="edac-summary-info-stats-box-number">
							' . $summary['distinct_errors_without_contrast'] . '
						</div>
						<div class="edac-summary-info-stats-box-label">Unique Error' . ( ( 1 == $summary['distinct_errors_without_contrast'] ) ? '' : 's' ) . '</div>
					</div>

					<div class="edac-summary-info-stats-box edac-summary-info-stats-box-contrast ' . ( ( $summary['distinct_contrast_errors'] > 0 ) ? ' has-errors' : '' ) . '">
						<div class="edac-summary-info-stats-box-number">
							' . $summary['distinct_contrast_errors'] . '
						</div>
						<div class="edac-summary-info-stats-box-label">Unique Color Contrast Error' . ( ( 1 == $summary['distinct_contrast_errors'] ) ? '' : 's' ) . '</div>
					</div>

					<div class="edac-summary-info-stats-box edac-summary-info-stats-box-warning ' . ( ( $summary['distinct_warnings'] > 0 ) ? ' has-warning' : '' ) . '">
						<div class="edac-summary-info-stats-box-number">
							' . $summary['distinct_warnings'] . '
						</div>
						<div class="edac-summary-info-stats-box-label">Unique Warning' . ( ( 1 == $summary['distinct_warnings'] ) ? '' : 's' ) . '</div>
					</div>

					<div class="edac-summary-info-stats-box edac-summary-info-stats-box-ignore ' . ( ( $summary['distinct_ignored'] > 0 ) ? ' has-ignored' : '' ) . '">
						<div class="edac-summary-info-stats-box-number">
							' . $summary['distinct_ignored'] . '
						</div>
						<div class="edac-summary-info-stats-box-label">Dismissed Issue' . ( ( 1 == $summary['distinct_ignored'] ) ? '' : 's' ) . '</div>
					</div>

				</div>
			</div>';
	

			$html .= '
			<div class="edac-summary-group edac-summary-group-no-background-color">
					
				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-avg-issues edac-dark-border">
					<div class="edac-summary-info-stats-box-label">Average Issues Per Page</div>
					<div class="edac-summary-info-stats-box-number">
					' . $summary['avg_issues_per_post'] . '
					</div>
				</div>

		
				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-avg-density edac-dark-border">
					<div class="edac-summary-info-stats-box-label">Average Issue Density</div>
					<div class="edac-summary-info-stats-box-number">
					' . $summary['avg_issue_density_percentage'] . '%
					</div>
				</div>

				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-last-scan edac-dark-border">
					<div class="edac-summary-info-date">
						<div class="edac-summary-info-stats-box-label">Last Full-Site Scan: </div>
					';
				
				if($summary['fullscan_completed_at'] > 0){
					$html .= '
						<div class="edac-summary-info-stats-box-label edac-summary-info-date-date edac-timestamp-to-local">' . $summary['fullscan_completed_at'] . '</div>';
				} else {
					$html .= '
						<div class="edac-summary-info-stats-box-number edac-summary-info-date-date">Never</div>';
				}

				$html .= '
					</div>
				</div>



				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-urls-scanned edac-dark-border">
					<div class="edac-summary-info-stats-box-number">
						' . $summary['posts_scanned'] . '
					</div>
					<div class="edac-summary-info-stats-box-label">URLs Scanned</div>
				</div>


				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-types-checked edac-dark-border">
					<div class="edac-summary-info-stats-box-number">
						' . $summary['scannable_post_types_count'] . ' of ' . $summary['public_post_types_count'] . '
					</div>
					<div class="edac-summary-info-stats-box-label">Post Types Checked</div>
				</div>

			<div class="edac-summary-info-stats-box edac-summary-info-stats-box-no-issues edac-dark-border">
					<div class="edac-summary-info-stats-box-number">
						' . $summary['posts_without_issues'] . ' 
					</div>
					<div class="edac-summary-info-stats-box-label">URLs with 100% score</div>
				</div>

			</div>';

				
			$html .='
			</div>';

		} else {

			//TODO:
			if( 'dismissed' !== 'dismissed' ){

				$html .='
				<div id="edac_welcome_page_banner">	

					<h3>
						Site-Wide Accessibility Reports
					</h3>

					<a href="TODO">Hide banner</a>
					
					<div>
						TODO
					</div>

				</div>';
		
			}

		}
		
		echo $html;
		

	}



}
