<?php
/**
 * Admin page that handles setup of the external dashboard.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

/**
 * Registers the external dashboard page and it's settings.
 *
 * @since 1.22.0
 */
class DashboardPage implements PageInterface {

	/**
	 * The capability required to access the settings page.
	 *
	 * @var string
	 */
	private $settings_capability;

	const PAGE_TAB_SLUG = 'dashboard';

	/**
	 * The settings page slug which all sections, settings and fields are registered to.
	 *
	 * @var string
	 */
	const SETTINGS_SLUG = 'edac_settings_dashboard';

	/**
	 * Constructor.
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	public function __construct( $settings_capability ) {
		$this->settings_capability = $settings_capability;
	}

	/**
	 * Add the settings sections and fields and setup it's tabs and filter in for the content.
	 */
	public function add_page() {

		$this->register_settings_sections();
		$this->register_fields_and_settings();

		add_filter( 'edac_filter_admin_scripts_slugs', [ $this, 'add_slug_to_admin_scripts' ] );
		add_filter( 'edac_filter_remove_admin_notices_screens', [ $this, 'add_slug_to_admin_notices' ] );
		add_filter( 'edac_filter_settings_tab_items', [ $this, 'add_dashboard_tab' ] );
		add_action( 'edac_settings_tab_content', [ $this, 'add_dashboard_tab_content' ], 11, 1 );

		add_filter(
			'allowed_options',
			function ( $allowed_options ) {
				$allowed_options['edac_settings_dashboard'] = [
					'edac_dashboard_url',
					'edac_api_token',
				];
				return $allowed_options;
			}
		);
	}

	/**
	 * Add fixes tab to settings page.
	 *
	 * @param  array $settings_tab_items arrray of tab items.
	 * @return array
	 */
	public function add_dashboard_tab( $settings_tab_items ) {

		$scan_tab = [
			'slug'  => 'dashboard',
			'label' => __( 'Dashboard Connection', 'accessibility-checker' ),
			'order' => 5,
		];
		array_push( $settings_tab_items, $scan_tab );

		return $settings_tab_items;
	}

	/**
	 * Dashboard tab content to settings page.
	 *
	 * @param  string $tab name of tab.
	 * @return void
	 */
	public function add_dashboard_tab_content( $tab ) {
		if ( 'dashboard' === $tab ) {
			include EDAC_PLUGIN_DIR . '/partials/admin-page/dashboard-page.php';
		}
	}

	/**
	 * Add the fixes slug to the admin scripts.
	 *
	 * @param array $slugs The slugs that are already added.
	 * @return array
	 */
	public function add_slug_to_admin_scripts( $slugs ) {
		$slugs[] = 'accessibility_checker_' . self::PAGE_TAB_SLUG;
		return $slugs;
	}

	/**
	 * Add the dashboard page slug to the admin notices.
	 *
	 * @param array $slugs The slugs that are already added.
	 * @return array
	 */
	public function add_slug_to_admin_notices( $slugs ) {
		$slugs[] = 'accessibility-checker_page_accessibility_checker_' . self::PAGE_TAB_SLUG;
		return $slugs;
	}

	/**
	 * Render the page.
	 */
	public function render_page() {
		include_once EDAC_PLUGIN_DIR . 'partials/admin-page/dashboard-page.php';
	}

	/**
	 * Register the settings sections for this options page.
	 *
	 * Sections are passed through a filter so that other plugins can add their own.
	 */
	public function register_settings_sections() {
		/**
		 * Filter the sections that are registered for the fixes settings page.
		 *
		 * @since 1.16.0
		 *
		 * @param array $sections The sections that are to be registered for the fixes settings page. Must have the id as the key and an array with a title and callback.
		 */
		$sections = apply_filters(
			'edac_filter_dashboard_settings_sections',
			[
				'edac_dashboard_general' => [
					'title'    => __( 'Dashboard Connection', 'accessibility-checker' ),
					'callback' => [ $this, 'section_general_cb' ],
				],
			]
		);

		foreach ( $sections as $section_id => $section ) {
			if ( ! is_string( $section_id ) || ! isset( $section['title'], $section['callback'] ) ) {
				continue;
			}

			// The callback must be callable otherwise it will throw an error.
			if ( ! is_callable( $section['callback'] ) ) {
				continue;
			}

			add_settings_section(
				$section_id,
				$section['title'],
				$section['callback'],
				self::SETTINGS_SLUG,
			);
		}
	}

	/**
	 * Register the settings fields and settings for this options page.
	 *
	 * It passed through a filter so that other plugins can add their own settings.
	 */
	public function register_fields_and_settings() {
		// Add a text input to the general section.
		add_settings_field(
			'edac_dashboard_url',
			__( 'Dashboard URL', 'accessibility-checker' ),
			[ $this, 'field_dashboard_token_connect' ],
			self::SETTINGS_SLUG,
			'edac_dashboard_general'
		);
	}

	/**
	 * Callback for the general settings section that renders a description for it.
	 */
	public function section_general_cb() {
		?>
		<p><?php esc_html_e( 'Connect this site to the accessibility dashboard.', 'accessibility-checker' ); ?></p>
		<?php
	}

	/**
	 * Callback for the dashboard token field.
	 */
	public function field_dashboard_token_connect() {
		$dashboard_token = get_option( 'edac_api_token', '' );
		?>
		<input type="text" name="edac_api_token" value="<?php echo esc_attr( $dashboard_token ); ?>" />
		<?php
	}
}
