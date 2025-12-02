<?php
/**
 * Class file for Welcome Page
 *
 * @package Accessibility_Checker
 *
 * phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
 */

namespace EDAC\Admin;

use EDAC\Admin\OptIn\Email_Opt_In;

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

		$scans_stats = new Scans_Stats();
		$summary     = $scans_stats->summary();
		?>

		<div id="edac_welcome_page_summary">

			<?php if ( defined( 'EDACP_VERSION' ) && EDAC_KEY_VALID ) : ?>
				<section>
					<div class="edac-cols edac-cols-header">
						<div class="edac-cols-left">
							<h2>
								<?php esc_html_e( 'Most Recent Test Summary', 'accessibility-checker' ); ?>
							</h2>
						</div>

						<p class="edac-cols-right">
							<?php if ( current_user_can( 'publish_posts' ) ) : ?>
								<button class="button" id="edac_clear_cached_stats">
									<?php esc_html_e( 'Update Counts', 'accessibility-checker' ); ?>
								</button>
							<?php endif; ?>

							<a class="edac-ml-1 button" href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_full_site_scan' ) ); ?>">
								<?php esc_html_e( 'Start New Scan', 'accessibility-checker' ); ?>
							</a>

							<a class="edac-ml-1 button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_issues' ) ); ?>">
								<?php esc_html_e( 'View All Open Issues', 'accessibility-checker' ); ?>
							</a>

							<?php if ( get_option( 'edacah_enable_show_history_button', false ) ) : ?>
								<a class="edac-ml-1 button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_audit_history' ) ); ?>">
									<?php esc_html_e( 'See History', 'accessibility-checker' ); ?>
								</a>
							<?php endif; ?>
						</p>
					</div>

					<div class="edac-welcome-grid-container">
						<div class="edac-welcome-grid-c1 edac-welcome-grid-item edac-background-light" style="grid-area: 1 / 1 / span 2;">
							<div class="edac-circle-progress" role="progressbar" aria-valuenow="<?php echo esc_attr( $summary['passed_percentage'] ); ?>" aria-valuemin="0" aria-valuemax="100" style="text-align: center; background: radial-gradient(closest-side, white 90%, transparent 80% 100%), conic-gradient(#006600 <?php echo esc_attr( $summary['passed_percentage'] ); ?>%, #e2e4e7 0);">
								<div class="edac-progress-percentage edac-xxx-large-text">
									<?php echo esc_html( $summary['passed_percentage_formatted'] ); ?>
								</div>
								<div class="edac-progress-label edac-large-text">
									<?php esc_html_e( 'Passed Tests', 'accessibility-checker' ); ?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c2 edac-welcome-grid-item <?php echo ( $summary['distinct_errors_without_contrast'] > 0 ) ? 'has-errors' : ' has-no-errors'; ?>">
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['distinct_errors_without_contrast_formatted'] ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php
										echo esc_html(
											sprintf(
												_n(
													'Unique Error',
													'Unique Errors',
													$summary['distinct_errors_without_contrast'],
													'accessibility-checker'
												),
												$summary['distinct_errors_without_contrast_formatted']
											)
										);
									?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c3 edac-welcome-grid-item <?php echo ( $summary['distinct_contrast_errors'] > 0 ) ? 'has-contrast-errors' : 'has-no-contrast-errors'; ?>">
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['distinct_contrast_errors_formatted'] ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php
										echo esc_html(
											sprintf(
												_n(
													'Unique Color Contrast Error',
													'Unique Color Contrast Errors',
													$summary['distinct_contrast_errors'],
													'accessibility-checker'
												),
												$summary['distinct_contrast_errors_formatted']
											)
										);
									?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c4 edac-welcome-grid-item <?php echo ( $summary['distinct_warnings'] > 0 ) ? 'has-warning' : 'has-no-warning'; ?>">
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['distinct_warnings_formatted'] ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php
										echo esc_html(
											sprintf(
												_n(
													'Unique Warning',
													'Unique Warnings',
													$summary['distinct_warnings'],
													'accessibility-checker'
												),
												$summary['distinct_warnings_formatted']
											)
										);
									?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c5 edac-welcome-grid-item <?php echo ( $summary['distinct_ignored'] > 0 ) ? 'has-ignored' : 'has-no-ignored'; ?>">
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['distinct_ignored_formatted'] ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php
										echo esc_html(
											sprintf(
												_n(
													'Ignored Item',
													'Ignored Items',
													$summary['distinct_ignored'],
													'accessibility-checker'
												),
												$summary['distinct_ignored_formatted']
											)
										);
									?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c6 edac-welcome-grid-item edac-background-light">
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php esc_html_e( 'Average Issues Per Page', 'accessibility-checker' ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['avg_issues_per_post_formatted'] ); ?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c7 edac-welcome-grid-item edac-background-light">
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php esc_html_e( 'Average Issue Density', 'accessibility-checker' ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['avg_issue_density_percentage_formatted'] ); ?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c8 edac-welcome-grid-item edac-background-light">
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php esc_html_e( 'Report Last Updated:', 'accessibility-checker' ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<?php if ( $summary['fullscan_completed_at'] > 0 ) : ?>
									<div class="edac-stat-number edac-timestamp-to-local">
										<?php echo isset( $summary['cached_at_formatted'] ) ? esc_html( $summary['cached_at_formatted'] ) : esc_html( $summary['fullscan_completed_at_formatted'] ); ?>
									</div>
								<?php else : ?>
									<div class="edac-stat-number">
										<?php esc_html_e( 'Never', 'accessibility-checker' ); ?>
									</div>
								<?php endif; ?>
							</div>
						</div>

						<div class="edac-welcome-grid-c9 edac-welcome-grid-item edac-background-light">
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['posts_scanned_formatted'] ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php esc_html_e( 'URLs Scanned', 'accessibility-checker' ); ?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c10 edac-welcome-grid-item edac-background-light">
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php
										printf(
											// translators: %1$s is the number of post types with issues, %2$s is the number of public post types.
											esc_html__( '%1$s of %2$s', 'accessibility-checker' ),
											esc_html( $summary['scannable_post_types_count_formatted'] ),
											esc_html( $summary['public_post_types_count_formatted'] )
										);
									?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php esc_html_e( 'Post Types Checked', 'accessibility-checker' ); ?>
								</div>
							</div>
						</div>

						<div class="edac-welcome-grid-c11 edac-welcome-grid-item edac-background-light">
							<div class="edac-inner-row">
								<div class="edac-stat-number">
									<?php echo esc_html( $summary['posts_without_issues'] ?? 0 ); ?>
								</div>
							</div>
							<div class="edac-inner-row">
								<div class="edac-stat-label">
									<?php echo esc_html__( 'URLs with 100% score', 'accessibility-checker' ); ?>
								</div>
							</div>
						</div>
					</div>

					<div>
						<p>
							<?php esc_html_e( 'This summary is automatically updated every 24 hours, or any time a full site scan is completed. You can also manually update these results by clicking the Update Counts button.', 'accessibility-checker' ); ?>
						</p>
					</div>

					<?php if ( $summary['is_truncated'] ) : ?>
						<div class="edac-center-text edac-mt-3">
							<?php esc_html_e( 'Your site has a large number of issues. For performance reasons, not all issues have been included in this summary.', 'accessibility-checker' ); ?>
						</div>
					<?php endif; ?>
				</section>

			<?php elseif ( true !== (bool) get_user_meta( get_current_user_id(), 'edac_welcome_cta_dismissed', true ) ) : ?>

				<section>
					<div class="edac-cols edac-cols-header">
						<h2 class="edac-cols-left">
							<?php esc_html_e( 'Site-Wide Accessibility Reports', 'accessibility-checker' ); ?>
						</h2>

						<p class="edac-cols-right">
							<button id="dismiss_welcome_cta" class="button">
								<?php esc_html_e( 'Hide banner', 'accessibility-checker' ); ?>
							</button>
						</p>
					</div>

					<div class="edac-modal-container">
						<div class="edac-modal">
							<div class="edac-modal-content">
								<h3 class="edac-align-center">
									<?php esc_html_e( 'Unlock Detailed Accessibility Reports', 'accessibility-checker' ); ?>
								</h3>
								<p class="edac-align-center">
									<?php esc_html_e( 'Start scanning your entire website for accessibility issues, get full-site reports, and become compliant with accessibility guidelines faster.', 'accessibility-checker' ); ?>
								</p>
								<p class="edac-align-center">
									<a class="button button-primary" href="<?php edac_link_wrapper( 'https://equalizedigital.com/accessibility-checker/pricing/', 'welcome-page', 'upgrade' ); ?>" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'Upgrade Accessibility Checker', 'accessibility-checker' ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>
				</section>

			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Render the ActiveCampaign email opt form in panel
	 *
	 * @return void
	 */
	public static function maybe_render_email_opt_in() {

		if ( Email_Opt_In::user_already_subscribed() ) {
			return;
		}

		if ( Email_Opt_In::should_show_modal() ) {
			return;
		}

		Email_Opt_In::render_form();
	}
}
