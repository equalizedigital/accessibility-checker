<?php
/**
 * Accessibility Reports settings page.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

use EDAC\Admin\Scans_Stats;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Accessibility Reports settings tab.
 */
class AccessibilityReportsPage implements PageInterface {

	/**
	 * Required capability for the tab.
	 *
	 * @var string
	 */
	private string $settings_capability;

	/**
	 * Constructor.
	 *
	 * @param string $settings_capability Capability required to view the page.
	 */
	public function __construct( $settings_capability ) {
		$this->settings_capability = $settings_capability;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function add_page() {
		add_filter( 'edac_filter_settings_tab_items', [ $this, 'add_reports_tab' ] );
		add_action( 'edac_settings_tab_content', [ $this, 'maybe_render_tab_content' ] );
	}

	/**
	 * Add the Accessibility Reports tab.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_reports_tab( $tabs ) {
		$tabs[] = [
			'slug'       => 'accessibility-reports',
			'label'      => esc_html__( 'Accessibility Reports', 'accessibility-checker' ),
			'badge'      => esc_html__( 'New', 'accessibility-checker' ),
			'order'      => defined( 'EDACP_VERSION' ) ? 4 : 3,
			'capability' => $this->settings_capability,
		];

		return $tabs;
	}

	/**
	 * Render content for the active tab.
	 *
	 * @param string|null $settings_tab Active tab slug.
	 * @return void
	 */
	public function maybe_render_tab_content( $settings_tab ) {
		if ( 'accessibility-reports' === $settings_tab ) {
			$this->render_page();
		}
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public function render_page() {
		$license_context = $this->get_license_context();
		$is_pro          = $license_context['is_pro'];
		$license_key     = (string) get_option( 'edacp_license_key', '' );
		$is_connected    = $license_context['is_connected'];
		$next_send_date  = $this->get_next_send_estimate_date();
		$scans_stats     = new Scans_Stats();
		$summary         = $scans_stats->summary();
		$preview_data    = is_array( $summary ) ? $this->get_preview_data( $summary, $is_pro ) : [];

		$upgrade_url = edac_generate_link_type(
			[
				'utm_campaign' => 'settings-page',
				'utm_content'  => 'accessibility-reports-upgrade',
			]
		);

		$dashboard_url       = edac_link_wrapper( 'https://my.equalizedigital.com/', 'accessibility-reports', 'account', false );
		$signup_url          = edac_link_wrapper( 'https://my.equalizedigital.com/sign-up/', 'accessibility-reports', 'signup', false );
		$privacy_url         = edac_link_wrapper( 'https://equalizedigital.com/privacy-policy/', 'accessibility-reports', 'privacy', false );
		$data_processing_url = edac_link_wrapper( 'https://equalizedigital.com/data-terms/', 'accessibility-reports', 'dpa', false );

		$allowed_icon_html = [
			'span' => [
				'class'       => true,
				'aria-hidden' => true,
				'aria-label'  => true,
			],
			'svg'  => [
				'width'   => true,
				'height'  => true,
				'viewbox' => true,
				'fill'    => true,
				'xmlns'   => true,
			],
			'path' => [
				'd'               => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			],
			'rect' => [
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'rx'           => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
		];
		?>
		<div class="edac-reports-page">
			<div class="edac-reports-page__main">
				<section class="edac-reports-page__panel">
					<h2><?php esc_html_e( 'Accessibility Reports', 'accessibility-checker' ); ?></h2>

				<?php if ( ! $is_connected ) : ?>
						<p class="edac-reports-page__intro">
							<?php
							if ( $is_pro ) {
								esc_html_e( 'Get recurring email reports with a snapshot of your site’s accessibility, including total issues, trends over time, most problematic pages, and the most severe issues to fix first.', 'accessibility-checker' );
							} else {
								esc_html_e( 'Get recurring email reports with a snapshot of your site’s accessibility, including total issues, trends over time, most problematic pages, and the most severe issues to fix first. Some details, like full-site coverage and issue breakdowns, are available in Pro.', 'accessibility-checker' );
							}
							?>
						</p>

						<?php if ( $is_pro ) : ?>
							<div class="edac-reports-page__single-action">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="edac_jwt_register" />
									<?php wp_nonce_field( 'edac_jwt_register', 'edac_jwt_register_nonce' ); ?>
									<button type="submit" class="button button-primary edac-reports-page__button">
										<?php esc_html_e( 'Enable Email Reports', 'accessibility-checker' ); ?>
									</button>
								</form>
							</div>
						<?php else : ?>
							<div class="edac-reports-grid edac-reports-grid--two">
								<div class="edac-reports-card">
									<h3><?php esc_html_e( 'Free License Key', 'accessibility-checker' ); ?></h3>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="edac_license" />
										<?php wp_nonce_field( 'edac_license_nonce', 'edac_license_nonce' ); ?>
										<label class="screen-reader-text" for="edac-reports-license-key"><?php esc_html_e( 'Free License Key', 'accessibility-checker' ); ?></label>
										<input id="edac-reports-license-key" name="edacp_license_key" type="text" class="regular-text edac-reports-page__license-input" value="<?php echo esc_attr( $license_key ); ?>" />
										<button type="submit" class="button button-primary edac-reports-page__button" name="edac_license_activate">
											<?php esc_html_e( 'Enable Email Reports', 'accessibility-checker' ); ?>
										</button>
									</form>
								</div>

								<div class="edac-reports-card">
									<h3><?php esc_html_e( 'Get a Free License Key', 'accessibility-checker' ); ?></h3>
									<p>
										<?php
										printf(
											/* translators: %s: dashboard URL. */
											wp_kses_post( __( 'Got the plugin from Equalize Digital? Your key is in your <a href="%s" target="_blank" rel="noopener noreferrer">dashboard</a>.', 'accessibility-checker' ) ),
											esc_url( $dashboard_url )
										);
										?>
									</p>
									<p>
										<?php
										printf(
											/* translators: %s: signup URL. */
											wp_kses_post( __( 'Installed from WordPress.org? <a href="%s" target="_blank" rel="noopener noreferrer">Create a free account</a> to get one.', 'accessibility-checker' ) ),
											esc_url( $signup_url )
										);
										?>
									</p>
									<p>
										<?php
										printf(
											/* translators: %s: signup URL. */
											wp_kses_post( __( 'Not sure? Just <a href="%s" target="_blank" rel="noopener noreferrer">create a free account</a>. It only takes a minute.', 'accessibility-checker' ) ),
											esc_url( $signup_url )
										);
										?>
									</p>
								</div>
							</div>
						<?php endif; ?>
				<?php else : ?>
						<div class="edac-reports-grid edac-reports-grid--two">
							<div class="edac-reports-card">
								<div class="edac-reports-card__header">
									<h3><?php esc_html_e( 'Reports Enabled', 'accessibility-checker' ); ?></h3>
									<span class="edac-reports-card__status edac-reports-card__status--success" aria-hidden="true"><?php echo wp_kses( $this->get_status_icon( 'check' ), $allowed_icon_html ); ?></span>
								</div>
								<p><?php esc_html_e( 'You’ll receive weekly accessibility reports for this site.', 'accessibility-checker' ); ?></p>
								<p class="edac-reports-card__meta">
									<?php if ( $next_send_date ) : ?>
										<?php /* translators: %s: next report date. */ ?>
										<span><?php printf( esc_html__( 'Next report: %s', 'accessibility-checker' ), esc_html( mysql2date( get_option( 'date_format' ), $next_send_date ) ) ); ?></span>
									<?php endif; ?>
									</p>
							</div>

							<div class="edac-reports-card">
								<div class="edac-reports-card__header">
									<h3><?php echo esc_html( $is_pro ? __( 'Full Coverage', 'accessibility-checker' ) : __( 'Limited Coverage', 'accessibility-checker' ) ); ?></h3>
									<span class="edac-reports-card__status <?php echo esc_attr( $is_pro ? 'edac-reports-card__status--success' : 'edac-reports-card__status--warning' ); ?>" aria-hidden="true"><?php echo wp_kses( $this->get_status_icon( $is_pro ? 'check' : 'warning' ), $allowed_icon_html ); ?></span>
								</div>
								<?php if ( $is_pro ) : ?>
									<p><?php esc_html_e( 'Your reports include all post types and taxonomies.', 'accessibility-checker' ); ?></p>
									<ul>
										<li><?php esc_html_e( 'Full-site scanning enabled', 'accessibility-checker' ); ?></li>
										<li><?php esc_html_e( 'Complete issue breakdowns', 'accessibility-checker' ); ?></li>
										<li><?php esc_html_e( 'Detailed reporting', 'accessibility-checker' ); ?></li>
									</ul>
								<?php else : ?>
									<p><?php esc_html_e( 'Your reports only include part of your site.', 'accessibility-checker' ); ?></p>
									<p><?php esc_html_e( 'Upgrade to Pro to:', 'accessibility-checker' ); ?></p>
									<ul>
										<li><?php esc_html_e( 'Scan all content types', 'accessibility-checker' ); ?></li>
										<li><?php esc_html_e( 'Get full issue breakdowns', 'accessibility-checker' ); ?></li>
										<li><?php esc_html_e( 'Receive more detailed reports', 'accessibility-checker' ); ?></li>
									</ul>
									<p>
										<a class="button button-primary edac-reports-page__button" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Upgrade to Pro', 'accessibility-checker' ); ?></a>
									</p>
								<?php endif; ?>
							</div>

							<div class="edac-reports-card">
								<h3><?php esc_html_e( 'Accessibility Summary', 'accessibility-checker' ); ?></h3>
								<p><?php esc_html_e( 'This summary reflects the latest scan data available for your site.', 'accessibility-checker' ); ?></p>
								<div class="edac-reports-stat">
									<div class="edac-reports-stat__label"><?php esc_html_e( 'Total Issues Found', 'accessibility-checker' ); ?></div>
									<div class="edac-reports-stat__value"><?php echo esc_html( number_format_i18n( $preview_data['total_issues'] ) ); ?></div>
									</div>
							</div>

							<div class="edac-reports-card">
								<h3><?php echo esc_html( $is_pro ? __( 'Account Connection', 'accessibility-checker' ) : __( 'Free License Key', 'accessibility-checker' ) ); ?></h3>
								<?php if ( ! $is_pro ) : ?>
									<div class="edac-reports-page__license-mask"><?php echo esc_html( $this->mask_license_key( $license_key ) ); ?></div>
								<?php endif; ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="edac_jwt_unregister" />
									<?php wp_nonce_field( 'edac_jwt_unregister', 'edac_jwt_unregister_nonce' ); ?>
									<button type="submit" class="button edac-reports-page__button edac-reports-page__button--secondary">
										<?php esc_html_e( 'Disable Email Reports', 'accessibility-checker' ); ?>
									</button>
								</form>
							</div>
						</div>
				<?php endif; ?>

					<p class="edac-reports-page__legal">
						<?php
						printf(
							/* translators: %1$s: privacy link, %2$s: data processing agreement link, %3$s: privacy policy aria label, %4$s: data processing agreement aria label. */
							wp_kses_post( __( 'By connecting this site, you agree to Equalize Digital\'s <a href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">Privacy Policy <span aria-hidden="true">&#8599;</span></a> and <a href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%4$s">Data Processing Agreement <span aria-hidden="true">&#8599;</span></a>.', 'accessibility-checker' ) ),
							esc_url( $privacy_url ),
							esc_url( $data_processing_url ),
							esc_attr__( 'Privacy Policy (opens in a new window)', 'accessibility-checker' ),
							esc_attr__( 'Data Processing Agreement (opens in a new window)', 'accessibility-checker' )
						);
						?>
					</p>
				</section>
			</div>

			<aside class="edac-reports-preview">
				<img
					class="edac-reports-preview__image"
					src="<?php echo esc_url( EDAC_PLUGIN_URL . 'assets/images/accessibility-reports-email-preview.jpg' ); ?>"
					alt="<?php esc_attr_e( 'Weekly email includes total issues found and changes since the last report, coverage information, counts of problems, needs review, passed test percentage, and information about the most problematic pages and the most severe issues.', 'accessibility-checker' ); ?>"
				/>
			</aside>
		</div>
		<?php
	}

	/**
	 * Resolve the effective license context for the reports UI.
	 *
	 * Pro should only be treated as authoritative when it is both installed and valid.
	 * Otherwise the reports page should fall back to the free plugin state.
	 *
	 * @return array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool}
	 */
	private function get_license_context(): array {
		$has_pro_plugin  = defined( 'EDACP_VERSION' );
		$pro_status      = (string) get_option( 'edacp_license_status', '' );
		$free_status     = (string) get_option( 'edac_license_status', '' );
		$site_id         = (string) get_option( 'edac_site_id', '' );
		$fallback_active = (bool) get_option( 'edac_fallback_active', false );

		return self::resolve_license_context( $has_pro_plugin, $pro_status, $free_status, $site_id, $fallback_active );
	}

	/**
	 * Resolve effective license context from current status values.
	 *
	 * @param bool   $has_pro_plugin  Whether the Pro plugin is installed.
	 * @param string $pro_status      Current Pro license status.
	 * @param string $free_status     Current free license status.
	 * @param string $site_id         Current connected site ID.
	 * @param bool   $fallback_active Whether a fallback from Pro to Free is currently active.
	 * @return array{has_pro_plugin:bool,is_pro:bool,status:string,is_connected:bool}
	 */
	private static function resolve_license_context( bool $has_pro_plugin, string $pro_status, string $free_status, string $site_id, bool $fallback_active = false ): array {
		$is_pro       = $has_pro_plugin && 'valid' === $pro_status && ! $fallback_active;
		$status       = $is_pro ? $pro_status : $free_status;
		$is_connected = 'valid' === $status && '' !== $site_id;


		return [
			'has_pro_plugin'  => $has_pro_plugin,
			'is_pro'          => $is_pro,
			'status'          => $status,
			'is_connected'    => $is_connected,
			'fallback_active' => $fallback_active,
		];
	}

	/**
	 * Gets the next estimated send date, assuming each send will be on
	 * Mondays.
	 *
	 * @return string
	 */
	private function get_next_send_estimate_date(): string {
		try {
			// If today is Monday, use today. Otherwise, use next Monday.
			$today = new \DateTime( 'now', wp_timezone() );

			if ( '1' === $today->format( 'N' ) ) {
				return $today->format( 'Y-m-d' );
			}

			$next_monday = new \DateTime( 'next monday', wp_timezone() );
			return $next_monday->format( 'Y-m-d' );
		} catch ( \Exception $exception ) {
			return '';
		}
	}

	/**
	 * Build preview data from current site stats.
	 *
	 * @param array $summary Current site summary.
	 * @param bool  $is_pro  Whether the effective active license is Pro.
	 * @return array
	 */
	private function get_preview_data( array $summary, bool $is_pro ): array {
		$problems           = (int) ( $summary['errors'] ?? 0 );
		$needs_review       = (int) ( $summary['warnings'] ?? 0 );
		$total_issues       = $problems + $needs_review;
		$urls_scanned       = (int) ( $summary['posts_scanned'] ?? 0 );
		$post_types_checked = (int) ( $summary['scannable_post_types_count'] ?? 0 );
		$public_post_types  = (int) ( $summary['public_post_types_count'] ?? 0 );
		$taxonomies_checked = $this->get_taxonomy_coverage_counts( $is_pro );
		$rules              = $this->get_rule_index();

		return [
			'total_issues'       => $total_issues,
			'problems'           => $problems,
			'needs_review'       => $needs_review,
			'passed_checks'      => (string) ( $summary['passed_percentage_formatted'] ?? __( 'N/A', 'accessibility-checker' ) ),
			'urls_scanned'       => $urls_scanned,
			'post_types_checked' => sprintf( '%1$d/%2$d', $post_types_checked, max( $public_post_types, $post_types_checked ) ),
			'taxonomies_checked' => sprintf( '%1$d/%2$d', $taxonomies_checked['checked'], max( $taxonomies_checked['total'], $taxonomies_checked['checked'] ) ),
			'top_pages'          => array_slice( $summary['top_pages_with_issues'] ?? [], 0, 5 ),
			'top_issues'         => $this->format_top_issues( $summary['top_issues_found_on_site'] ?? [], $rules ),
		];
	}

	/**
	 * Map rules by slug for display.
	 *
	 * @return array
	 */
	private function get_rule_index(): array {
		$rules = edac_register_rules();
		$index = [];

		foreach ( $rules as $rule ) {
			if ( empty( $rule['slug'] ) ) {
				continue;
			}

			$index[ $rule['slug'] ] = $rule;
		}

		return $index;
	}

	/**
	 * Get a resized status icon using the shared helper output.
	 *
	 * @param string $icon_name Icon name for edac_icon().
	 * @return string
	 */
	private function get_status_icon( string $icon_name ): string {
		return edac_icon( $icon_name, '', true, '', 'edac-reports-card__status-icon' );
	}

	/**
	 * Format top issue rows for the preview table.
	 *
	 * @param array $issues Raw issue counts.
	 * @param array $rules  Indexed rules.
	 * @return array
	 */
	private function format_top_issues( array $issues, array $rules ): array {
		$formatted = [];

		foreach ( array_slice( $issues, 0, 10 ) as $issue ) {
			$rule     = $rules[ $issue['rule_slug'] ] ?? [];
			$severity = isset( $rule['severity'] ) ? (int) $rule['severity'] : 99;

			$formatted[] = [
				'title'    => $rule['title'] ?? $issue['rule_slug'],
				'severity' => $this->format_severity( $severity ),
				'rank'     => $severity,
				'count'    => (int) $issue['issue_count'],
			];
		}

		usort(
			$formatted,
			function ( $a, $b ) {
				if ( $a['rank'] === $b['rank'] ) {
					return $b['count'] <=> $a['count'];
				}

				return $a['rank'] <=> $b['rank'];
			}
		);

		return array_map(
			function ( $issue ) {
				unset( $issue['rank'] );
				return $issue;
			},
			array_slice( $formatted, 0, 5 )
		);
	}

	/**
	 * Convert a numeric severity to a human-readable label.
	 *
	 * @param int $severity Severity number.
	 * @return string
	 */
	private function format_severity( int $severity ): string {
		switch ( $severity ) {
			case 1:
				return __( 'Critical', 'accessibility-checker' );
			case 2:
				return __( 'High', 'accessibility-checker' );
			case 3:
				return __( 'Medium', 'accessibility-checker' );
			case 4:
				return __( 'Low', 'accessibility-checker' );
			default:
				return __( 'Unknown', 'accessibility-checker' );
		}
	}

	/**
	 * Get taxonomy coverage counts for the preview panel.
	 *
	 * @param bool $is_pro Whether the effective active license is Pro.
	 * @return array
	 */
	private function get_taxonomy_coverage_counts( bool $is_pro ): array {
		$taxonomies = get_taxonomies(
			[
				'public' => true,
			],
			'names'
		);

		unset( $taxonomies['post_format'] );

		$total   = count( $taxonomies );
		$checked = 0;

		if ( $is_pro && get_option( 'edacp_enable_archive_scanning' ) ) {
			$checked = $total;
		}

		return [
			'checked' => $checked,
			'total'   => $total,
		];
	}

	/**
	 * Mask a license key for display.
	 *
	 * @param string $license_key License key.
	 * @return string
	 */
	private function mask_license_key( string $license_key ): string {
		if ( strlen( $license_key ) <= 8 ) {
			return $license_key;
		}

		return substr( $license_key, 0, 4 ) . str_repeat( '*', max( strlen( $license_key ) - 8, 4 ) ) . substr( $license_key, -4 );
	}
}
