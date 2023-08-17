<?php
/**
 * Class file for widgets
 * 
 * @package Accessibility_Checker
 */

namespace EDAC;

use EDAC\Scan_Report_Data;

/**
 * Class that handles widgets
 */
class Widgets {
	

	/**
	 * Renders as widget
	 *
	 * @return void
	 */
	public static function render_dashboard_scan_summary() {

		$html = '';
		$scan_data = new Scan_Report_Data(5);
		$summary = $scan_data->scan_summary();
	
		$html .= '
	
		
		<div class="edac-widget">
		<div class="edac-summary">	

			<h3 class="edac-summary-header">
				Full Site Accessibility Status
			</h3>';
			
		if( 
			! edac_check_plugin_installed( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ||
			( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && ! EDAC_KEY_VALID) 
		){
			$html .='
			<div class="edac-widget-modal">
				<div class="edac-widget-modal-content">
					<span class="edac-widget-modal-content-close">&times;</span>
					<h3>Get Detailed Accessibility Reports</h3>
					<p>Start scanning your entire website for accessibility issues, get full-site reports,
					and become compliant with accessibility guidelines faster.</p>
					<p class="edac-align-center">
					<a class="button" href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">
					Upgrade Accessibility Checker
					</a>
					</p>
				</div>
			</div>';
		}
	
		$html .='
			<div class="edac-summary-passed">
				<div class="edac-progress-bar" role="progressbar" aria-valuenow="' . $summary['passed_percentage'] . '" 
					aria-valuemin="0" aria-valuemax="100"
					style="text-align: center; 
					background: radial-gradient(closest-side, white 79%, transparent 80% 100%), 
					conic-gradient(#006600 ' . $summary['passed_percentage'] . '%, #e2e4e7 0);">
					<div class="edac-progress-percentage">' . $summary['passed_percentage'] . '%</div>
					<div class="edac-progress-label">Passed Tests</div>
				</div>
			</div>
		
			<div class="edac-summary-info">
				<div class="edac-summary-info-date">
					<div class="edac-summary-info-date-label">Last Full-Site Scan: </div>
				';
			
		if($summary['fullscan_completed_at'] > 0){
			$html .= '
					<div class="edac-summary-info-date-date edac-timestamp-to-local">' . $summary['fullscan_completed_at'] . '</div>';
		} else {
			$html .= '
					<div class="edac-summary-info-date-date">Never</div>';
		}

		$html .= '
				</div>

				<div class="edac-summary-info-count">
					<div class="edac-summary-scan-count-label">
						URLs Scanned:
					</div>
					<div class="edac-summary-info-count-number">' . $summary['posts_scanned'] . '</div>
				</div>';

				

		$html .= '
			<div class="edac-summary-info-stats">
				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-error ' . ( ( $summary['distinct_errors_without_contrast'] > 0 ) ? ' has-errors' : '' ) . '">
					<div class="edac-summary-info-stats-box-label">Errors: </div>
					<div class="edac-summary-info-stats-box-number">
						' . $summary['distinct_errors_without_contrast'] . '
					</div>
				</div>
				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-contrast ' . ( ( $summary['distinct_contrast_errors'] > 0 ) ? ' has-errors' : '' ) . '">
					<div class="edac-summary-info-stats-box-label">Color Contrast Errors: </div>
					<div class="edac-summary-info-stats-box-number">
						' . $summary['distinct_contrast_errors'] . '
					</div>
				</div>
				<div class="edac-summary-info-stats-box edac-summary-info-stats-box-warning ' . ( ( $summary['distinct_warnings'] > 0 ) ? ' has-warning' : '' ) . '">
					<div class="edac-summary-info-stats-box-label">Warnings: </div>
					<div class="edac-summary-info-stats-box-number">
						' . $summary['distinct_warnings'] . '
					</div>
				</div>
			</div>

			</div>
			
		';
			
		if( (int)($summary['distinct_errors'] + $summary['distinct_warnings']) > 0){
			$html .= '
			
		<div class="edac-summary-notice">
			Your site has accessibility issues that should be addressed as soon as possible 
			to ensure compliance with accessibility guidelines.
		</div>';

		} else {
			$html .= '
		<div class="edac-summary-notice">
			Way to go! Accessibility Checker cannot find any accessibility problems in the content it tested. 
			Some problems cannot be found by automated tools so don\'t forget to <a href="https://equalizedigital.com/accessibility-checker/how-to-manually-check-your-website-for-accessibility/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">manually test your site</a>.
		</div>';

		}

	
		$html .= '
		<hr />

		</div>
		
		<div class="edac-issues-summary">

			<h3 class="edac-issues-summary-header">
				Issues By Post Type
			</h3> 

			<table class="widefat striped">
				<thead>
				<tr>
					<th scope="col">
						Post Type
					</th>
					<th scope="col" >
						Errors
					</th>
					<th scope="col" >
						Contrast
					</th>
					<th scope="col" >
						Warnings
					</th>
				</tr>
				</thead>

				<tbody>';
		

			
				$edacp_inactive = false;
				$scannable_post_types = Settings::get_scannable_post_types();
				if(
					edac_check_plugin_installed( 'accessibility-checker-pro/accessibility-checker-pro.php' ) &&
					(
						! edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ||
						! EDAC_KEY_VALID
					)
				){
					$edacp_inactive = true;
					$first_non_scannable_post_type = true;
				}

			
				$post_types = get_post_types( [
					'public' => true,
				] );
				unset($post_types['attachment']);
					
			
				foreach($post_types as $post_type){

					if( in_array( $post_type, $scannable_post_types )) {
		
						$by_issue = $scan_data->issue_summary_by_post_type( $post_type );
	
						$html .= '
						<tr>
							<td>' . esc_html( ucwords($post_type) ) . '</td>
							<td>' . $by_issue['distinct_errors_without_contrast'] . '</td>
							<td>' . $by_issue['distinct_contrast_errors'] . '</td>
							<td>' . $by_issue['distinct_warnings'] . '</td>
						</tr>';
					
					} else {
						if(	$edacp_inactive ){

							if($first_non_scannable_post_type){
								$first_non_scannable_post_type = false;
								$html .= '
									<tr >
										<td>' . esc_html( ucwords($post_type) ) . '</td>
										<td colspan="3">
											<div class="edac-issues-summary-notice-enable-edacp">
												Accessibility Checker Pro not enabled.
											</div>
										</td>
									</tr>';
							
							} else {
	
								$html .= '
								<tr >
									<td>' . esc_html( ucwords($post_type) ) . '</td>
									<td>-</td>
									<td>-</td>
									<td>-</td>
								</tr>';
					
							}
	
						} else if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {
						
							$html .= '
							<tr >
								<td>' . esc_html( ucwords($post_type) ) . '</td>
								<td>-</td>
								<td>-</td>
								<td>-</td>
							</tr>';
				
						} else {

							$html .= '
							<tr >
								<td>' . esc_html( ucwords($post_type) ) . '</td>
								<td colspan="3">
									<div class="edac-issues-summary-notice-upgrade-to-edacp">
										<a href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">
											Upgrade to Scan
										</a>
									</div>
								</td>
							</tr>';
					
						}
						
					}

				}	

		$html .='
				</tbody>
			</table>
		</div>
		
		<hr />
		';


		if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ) {
			$html .= '
			<a class="button edac-mr-1" href="/wp-admin/admin.php?page=accessibility_checker">See More</a>';
		}
			

		$html .= '
		<a href="/wp-admin/admin.php?page=accessibility_checker_settings">Edit Accessibility Checker Settings</a>
		<hr />
		<h3 class="edac-summary-header">
			Learn Accessibility
		</h3>';

		$html .= edac_get_upcoming_meetups_html('wordpress-accessibility-meetup-group', 2);


		$html .= '
		<hr />
		<div class="edac-widget-footer-link-list">';

		$html .='<a class="edac-widget-footer-link-list-item edac-mr-1" href="https://equalizedigital.com/resources/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">Blog</a>';
		$html .='<span class="edac-widget-footer-link-list-spacer" /><a class="edac-widget-footer-link-list-item edac-ml-1" href="https://equalizedigital.com/accessibility-checker/documentation/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=dashboard-widget">Documentation</a>
		</div></div>';


	

		echo $html;

	}

}







