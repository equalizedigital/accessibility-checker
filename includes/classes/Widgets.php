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
		$scan_data = new Scan_Report_Data();
		$summary = $scan_data->scan_summary();
	
		$html .= '
		<div class="edac-summary">	

			<div class="edac-summary-header">
				<div class="edac-summary-label">
					Full Site Accessibility Status
				</div>
			</div> 
		
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
					<div class="edac-summary-info-date-label">Last Full-Site Scan:</div>
				';
			
		if($summary['fullscan_completed_at'] > 0){
			$html .= '
					<div class="edac-summary-info-date-date">';
			$html .= '<script>';
			$html .= 'document.write( new Date(' . $summary['fullscan_completed_at'] . ' * 1000).toLocaleDateString("en-US") );';
			$html .= 'document.write( " " + new Date(' . $summary['fullscan_completed_at'] . ' * 1000).toLocaleTimeString("en-US") );';
			$html .= '</script>';
			$html .= '
					</div>';
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
					<div class="edac-summary-info-count-number">TODO</div>
				</div>';

				

		$html .= '
			<div class="edac-summary-info-stats">
				<div class="edac-summary-stats-error ' . ( ( $summary['errors'] > 0 ) ? ' has-errors' : '' ) . '">
					<div class="edac-summary-stats-label">Error' . ( ( 1 !== $summary['distinct_errors'] ) ? 's:' : ':' ) . '</div>
					<div class="edac-summary-stats-number">
						' . $summary['distinct_errors'] . '
					</div>
				</div>
				<div class="edac-summary-stats-contrast ' . ( ( $summary['distinct_contrast_errors'] > 0 ) ? ' has-errors' : '' ) . '">
					<div class="edac-summary-stats-label">Color Contrast Error' . ( ( 1 !== $summary['distinct_contrast_errors'] ) ? 's:' : ':' ) . '</div>
					<div class="edac-summary-stats-number">
						' . $summary['distinct_contrast_errors'] . '
					</div>
				</div>
				<div class="edac-summary-stats-warning ' . ( ( $summary['distinct_warnings'] > 0 ) ? ' has-warning' : '' ) . '">
					<div class="edac-summary-stats-label">Warning' . ( ( 1 !== $summary['distinct_warnings'] ) ? 's:' : ':' ) . '</div>
					<div class="edac-summary-stats-number">
						' . $summary['distinct_warnings'] . '
					</div>
				</div>
			</div>

			</div>
			
		</div>';
			
		$html .= '
		<div class="edac-issues-summary">
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
		

				$post_types = get_post_types( [
					'public' => true,
				] );
				unset($post_types['attachment']);

			
				foreach($post_types as $post_type){
					$by_issue = $scan_data->issue_summary_by_post_type( $post_type );
	
		$html .= '
					<tr>
						<td>' . esc_html( ucwords($post_type) ) . '</td>
						<td>' . $by_issue['distinct_errors'] . '</td>
						<td>' . $by_issue['distinct_contrast_errors'] . '</td>
						<td>' . $by_issue['distinct_warnings'] . '</td>
					</tr>';

				}	

		$html .='
				</tbody>
			</table>
		</div>';


		echo $html;

	}

}







