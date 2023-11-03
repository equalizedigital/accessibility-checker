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

		$html        = '';
		$scans_stats = new Scans_Stats();
		$summary     = $scans_stats->summary();
		
		$html .= '
			<div id="edac_welcome_page_summary">';


		if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {
	
			$html .= '
			<section>
				<div class="edac-cols edac-cols-header">
					<div class="edac-cols-left">
						<h2>
							' . __( 'Most Recent Test Summary', 'accessibility-checker' ) . '
						</h2>
					</div>
					<p class="edac-cols-right"> 
						<button class="button" id="edac_clear_cached_stats">' . __( 'Update Counts', 'accessibility-checker' ) . '</button>	
	
						<a class="edac-ml-1 button" href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=scan' ) ) . '">' . __( 'Start New Scan', 'accessibility-checker' ) . '</a>
					
						<a class="edac-ml-1 button button-primary" href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_issues' ) ) . '">' . __( 'View All Open Issues', 'accessibility-checker' ) . '</a>';

			if ( edac_check_plugin_active( 'accessibility-checker-audit-history/accessibility-checker-audit-history.php' ) ) {
				$html .= '
				<a class="edac-ml-1 button button-primary" href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_audit_history' ) ) . '">' . __( 'See History', 'accessibility-checker' ) . '</a>';
			}
						
			$html .= '		
					</p>
				</div>
				<div class="edac-welcome-grid-container">';

			$html .= '
				<div class="edac-welcome-grid-c1 edac-welcome-grid-item edac-background-light" style="grid-area: 1 / 1 / span 2;">
					<div class="edac-circle-progress" role="progressbar" aria-valuenow="' . esc_attr( $summary['passed_percentage'] ) . '" 
						aria-valuemin="0" aria-valuemax="100"
						style="text-align: center; 
						background: radial-gradient(closest-side, white 90%, transparent 80% 100%), 
						conic-gradient(#006600 ' . esc_attr( $summary['passed_percentage'] ) . '%, #e2e4e7 0);">
						<div class="edac-progress-percentage edac-xxx-large-text">' . esc_html( $summary['passed_percentage_formatted'] ) . '</div>
						<div class="edac-progress-label edac-large-text">' . __( 'Passed Tests', 'accessibility-checker' ) . '</div>
					</div>
				</div>';

			$html .= '
				<div class="edac-welcome-grid-c2 edac-welcome-grid-item' . ( ( $summary['distinct_errors_without_contrast'] > 0 ) ? ' has-errors' : ' has-no-errors' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['distinct_errors_without_contrast_formatted'] ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Unique Error', 'Unique Errors', $summary['distinct_errors_without_contrast_formatted'], 'accessibility-checker' ), $summary['distinct_errors_without_contrast_formatted'] ) . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c3 edac-welcome-grid-item' . ( ( $summary['distinct_contrast_errors'] > 0 ) ? ' has-contrast-errors' : ' has-no-contrast-errors' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['distinct_contrast_errors_formatted'] ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Unique Color Contrast Error', 'Unique Color Contrast Errors', $summary['distinct_contrast_errors_formatted'], 'accessibility-checker' ), $summary['distinct_contrast_errors_formatted'] ) . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c4 edac-welcome-grid-item' . ( ( $summary['distinct_warnings'] > 0 ) ? ' has-warning' : ' has-no-warning' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['distinct_warnings_formatted'] ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Unique Warning', 'Unique Warnings', $summary['distinct_warnings_formatted'], 'accessibility-checker' ), $summary['distinct_warnings_formatted'] ) . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c5 edac-welcome-grid-item' . ( ( $summary['distinct_ignored'] > 0 ) ? ' has-ignored' : ' has-no-ignored' ) . '">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['distinct_ignored_formatted'] ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . sprintf( _n( 'Ignored Item', 'Ignored Items', $summary['distinct_ignored_formatted'], 'accessibility-checker' ), $summary['distinct_ignored_formatted'] ) . '</div>
					</div>
				</div>';
			
			$html .= '
				<div class="edac-welcome-grid-c6 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Average Issues Per Page', 'accessibility-checker' ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . $summary['avg_issues_per_post_formatted'] . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c7 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Average Issue Density', 'accessibility-checker' ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['avg_issue_density_percentage_formatted'] ) . '</div>
					</div>
				</div>
			
				<div class="edac-welcome-grid-c8 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Report Last Updated:', 'accessibility-checker' ) . '</div>
					</div>
					<div class="edac-inner-row">
						';
				
			if ( $summary['fullscan_completed_at'] > 0 ) {
				$html .= '
							<div class="edac-stat-number edac-timestamp-to-local">' . esc_html( $summary['fullscan_completed_at_formatted'] ) . '</div>';
			} else {
				$html .= '
							<div class="edac-stat-number">' . esc_html__( 'Never', 'accessibility-checker' ) . '</div>';
			}

			$html .= '
					</div>
				</div>

				<div class="edac-welcome-grid-c9 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['posts_scanned_formatted'] ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'URLs Scanned', 'accessibility-checker' ) . '</div>
					</div>
				</div>

				<div class="edac-welcome-grid-c10 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['scannable_post_types_count_formatted'] ) . ' ' . esc_html__( 'of', 'accessibility-checker' ) . ' ' . esc_html( $summary['public_post_types_count_formatted'] ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'Post Types Checked', 'accessibility-checker' ) . '</div>
					</div>
				</div>

				<div class="edac-welcome-grid-c11 edac-welcome-grid-item edac-background-light">
					<div class="edac-inner-row">
						<div class="edac-stat-number">' . esc_html( $summary['posts_without_issues_formatted'] ) . '</div>
					</div>
					<div class="edac-inner-row">
						<div class="edac-stat-label">' . esc_html__( 'URLs with 100% score', 'accessibility-checker' ) . '</div>
					</div>
				</div>

			</div>';

			$html .= '<div><p>' . __( 'This summary is automatically updated every 24 hours, or any time a full site scan is completed. You can also manually update these results by clicking the Update Counts button.', 'accessibility-checker' ) . '</p></div>';

			if ( $summary['is_truncated'] ) {
				$html .= '<div class="edac-center-text edac-mt-3">Your site has a large number of issues. For performance reasons, not all issues have been included in this summary.</div>';
			}
	
			$html .= '
			</section>';
			
		} elseif ( true !== boolval( get_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true ) ) ) {
	
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
		
		$html .= '
		</div>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}


	/**
	 * Render the zoho email opt in panel
	 *
	 * @param  string $zx         zoho form field.
	 * @param  string $zcld       zoho form field.
	 * @param  string $zctd       zoho form field.
	 * @param  string $zc_form_ix zoho form field.
	 * @return void
	 */
	public static function render_email_opt_in( $zx, $zcld, $zctd, $zc_form_ix ) {
		
		$current_user = wp_get_current_user();
		$email        = $current_user->user_email;

		if ( empty( $email ) ) {
			$email = '';
		}

		$form_id = 'sf' . $zc_form_ix;

		$html = '
			<script type="text/javascript" src="https://zmp-glf.maillist-manage.com/js/optin.min.js" onload="setupSF(\'' . esc_attr( $form_id ) . '\',\'ZCFORMVIEW\',false,\'light\',false,\'0\')"></script>
			<script type="text/javascript">
				var _edac_email_opt_in_email = "";
				function runOnFormSubmit_' . esc_html( $form_id ) . '(th){
					_edac_email_opt_in_email = document.querySelector("#EMBED_FORM_EMAIL_LABEL").value;
				};
			</script>

			<div class="edac-panel edac-mt-1 edac-pb-3">
			<div id="' . esc_attr( $form_id ) . '" data-type="signupform" style="opacity: 1;">

			<div id="customForm">
					<div class="quick_form_8_css" name="SIGNUP_BODY">
						<div>
							<h2 id="SIGNUP_HEADING">' . __( 'Get Notified of Upcoming Events', 'accessibility-checker' ) . '</h2>
							<div>' . __( 'Join our email list and get event reminders and recordings in your inbox.', 'accessibility-checker' ) . '</div>
							<div>
								<div id="Zc_SignupSuccess" style="display: none;"></div>
							</div>
							
							<form method="POST" id="zcampaignOptinForm" style="margin: 0px; width: 100%" action="https://zmp-glf.maillist-manage.com/weboptin.zc" target="_zcSignup">
							
							<div class="SIGNUP_FLD edac-mt-3" id="edac-opt-in-email">
								<label style="font-weight: 600;" for="EMBED_FORM_EMAIL_LABEL">Email Address (Required)</label>
								<input style="max-width: 100%;" type="email" changeitem="SIGNUP_FORM_FIELD" name="CONTACT_EMAIL" id="EMBED_FORM_EMAIL_LABEL" aria-describedby="email-info-region" value="' . esc_attr( $email ) . '">
							</div>
							<div class="edac-mt-0" style="display: none;" id="errorMsgDiv">' . __( 'Please enter a valid email address.', 'accessibility-checker' ) . '</div>
						
							<div class="SIGNUP_FLD edac-mt-3">
								<input type="button" class="button" name="SIGNUP_SUBMIT_BUTTON" id="zcWebOptin" value="' . __( 'Subscribe', 'accessibility-checker' ) . '">
							</div>
						
							
							<input type="hidden" id="fieldBorder" value="">
							<input type="hidden" id="submitType" name="submitType" value="optinCustomView">
							<input type="hidden" id="emailReportId" name="emailReportId" value="">
							<input type="hidden" id="formType" name="formType" value="QuickForm">
							<input type="hidden" name="zcvers" value="3.0">
							<input type="hidden" name="oldListIds" id="allCheckedListIds" value="">
							<input type="hidden" id="mode" name="mode" value="OptinCreateView">
							<input type="hidden" id="document_domain" value="">
							<input type="hidden" id="zc_Url" value="zmp-glf.maillist-manage.com">
							<input type="hidden" id="new_optin_response_in" value="2">
							<input type="hidden" id="duplicate_optin_response_in" value="2">
							<input type="hidden" name="zc_trackCode" id="zc_trackCode" value="ZCFORMVIEW">
							<input type="hidden" id="viewFrom" value="URL_ACTION">
							<input type="hidden" name="zx" id="cmpZuid" value="' . esc_attr( $zx ) . '">
							<input type="hidden" id="zcld" name="zcld" value="' . esc_attr( $zcld ) . '">
							<input type="hidden" id="zctd" name="zctd" value="' . esc_attr( $zctd ) . '">
							<input type="hidden" id="zc_formIx" name="zc_formIx" value="' . esc_attr( $zc_form_ix ) . '">
							<span style="display: none" id="dt_CONTACT_EMAIL">1,true,6,Contact Email,2</span>
							</form>

							<!-- Confirmation Message -->
							<div id="confirmationMessage" tabindex="-1" style="outline: none;">
							<!-- This will be populated with the confirmation message on form submit -->
							</div>
						</div>
					</div>
				</div>
				<img src="https://zmp-glf.maillist-manage.com/images/spacer.gif" id="refImage" onload="referenceSetter(this)" style="display:none;" alt="">
			</div>


			<input type="hidden" id="signupFormType" value="QuickForm_Horizontal">
			<div id="zcOptinOverLay" oncontextmenu="return false" style="display:none;text-align: center; background-color: rgb(0, 0, 0); opacity: 0.5; z-index: 100; position: fixed; width: 100%; top: 0px; left: 0px; height: 988px;"></div>
			<div id="zcOptinSuccessPopup" style="display:none;z-index: 9999;width: 800px; height: 40%;top: 84px;position: fixed; left: 26%;background-color: #FFFFFF;border-color: #E6E6E6; border-style: solid; border-width: 1px;  box-shadow: 0 1px 10px #424242;padding: 35px;">
				<span style="position: absolute;top: -16px;right:-14px;z-index:99999;cursor: pointer;" id="closeSuccess">
					<img src="https://zmp-glf.maillist-manage.com/images/videoclose.png" alt="close">
				</span>
				<div>' . __( 'There was a problem saving your email. Please try again.', 'accessibility-checker' ) . '
					<div id="zcOptinSuccessPanel"></div>
				</div>
			</div>
			
			<script>

				function initOnComplete(){
					const optIn = document.querySelector("#edac-opt-in-email");

					// Function to be executed when the HTML content of the element changes
					function handleHtmlChange(mutationsList, observer) {
						for (const mutation of mutationsList) {
							if (mutation.type === "childList") {
		

								const data = { action: "edac_email_opt_in_ajax", nonce: edac_script_vars.nonce };
								const queryString = Object.keys(data)
									.map(key => encodeURIComponent(key) + "=" + encodeURIComponent(data[key]))
									.join("&");


								fetch(ajaxurl + "?" + queryString )
								.then(response => {
									if (!response.ok) {
										throw new Error("There was a network problem. Please try again.");
									} else {
									
										// HTML content of the element has changed so assume zoho has saved the email.

										document.querySelector("#zcampaignOptinForm").remove();
				
										// Populate the confirmation message
										const confirmMessage = document.getElementById("confirmationMessage");
										confirmMessage.textContent = "' . esc_js( __( 'Thank-you for joining!', 'accessibility-checker' ) ) . '";
										confirmMessage.classList.add("edac-mt-3");
										confirmMessage.focus();
				
										
										document.querySelectorAll(".opt-in-show").forEach((item) => {
											item.style.display = "block";
										});
						
									}
								})
								.catch(error => {
									// Handle errors here
								});

					
								
							}
						}
					}
				
				

					// Create a MutationObserver to watch for changes in the HTML content
					const observer = new MutationObserver(handleHtmlChange);

					// Configure the observer to look for childList changes (changes to the element content)
					const config = { childList: true };

					// Start observing the element
					observer.observe(optIn, config);

				}

				initOnComplete();
			</script>
			</div>';
	
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped	
		echo $html;
	}
}
