<?php
/**
 * Admin page that holds the list of fix options.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

/**
 * Registers the Fixes page and it's settings.
 *
 * Sections and settings are passed through filters so that other plugins can add their own.
 *
 * @since 1.16.0
 */
class FixesPage implements PageInterface {

	use FixesSettingType\Checkbox;
	use FixesSettingType\Text;

	/**
	 * The capability required to access the settings page.
	 *
	 * @var string
	 */
	private $settings_capability;

	const PAGE_TAB_SLUG = 'fixes';

	/**
	 * The settings page slug which all sections, settings and fields are registered to.
	 *
	 * @var string
	 */
	const SETTINGS_SLUG = 'edac_settings_fixes';

	/**
	 * Constructor.
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	public function __construct( $settings_capability ) {
		$this->settings_capability = $settings_capability;
	}

	/**
	 * Add the page to the admin menu, setup it's tabs and filter in for the content.
	 */
	public function add_page() {

		add_submenu_page(
			'accessibility_checker',
			__( 'Accessibility Checker Settings', 'accessibility-checker' ),
			__( 'Accessibility Fixes', 'accessibility-checker' ),
			$this->settings_capability,
			'accessibility_checker_' . self::PAGE_TAB_SLUG,
			[ $this, 'render_page' ],
			1
		);

		$this->register_settings_sections();
		$this->register_fields_and_settings();

		add_filter( 'edac_filter_admin_scripts_slugs', [ $this, 'add_slug_to_admin_scripts' ] );
		add_filter( 'edac_filter_remove_admin_notices_screens', [ $this, 'add_slug_to_admin_notices' ] );
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
	 * Add the fixes slug to the admin notices.
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
		include_once EDAC_PLUGIN_DIR . 'partials/admin-page/fixes-page.php';
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
			'edac_filter_fixes_settings_sections',
			[
				'edac_fixes_general' => [
					'title'    => __( 'General Settings', 'accessibility-checker' ),
					'callback' => [ $this, 'fixes_section_general_cb' ],
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
		/**
		 * Filter the fields that are registered for the fixes settings page.
		 *
		 * @since 1.16.0
		 *
		 * @param array $fields The fields that are to be registered for the fixes settings page.
		 */
		$fields = apply_filters( 'edac_filter_fixes_settings_fields', [] );

		foreach ( $fields as $field_id => $field ) {

			$field_type = $field['type'] ?? 'checkbox';
			$sanitizer  = $field['sanitize_callback'] ?? [ $this, 'sanitize_checkbox' ];

			add_settings_field(
				$field_id,
				$field['label'],
				$field['callback'] ?? [ $this, $field_type ],
				self::SETTINGS_SLUG,
				$field['section'] ?? 'edac_fixes_general',
				[
					'name'        => $field_id,
					'labelledby'  => $field_id,
					'description' => $field['description'] ?? '',
				]
			);

			register_setting( self::SETTINGS_SLUG, $field_id, $sanitizer );
		}
	}

	/**
	 * Callback for the general settings section that renders a description for it.
	 */
	public function fixes_section_general_cb() {
		echo '<p>' . esc_html__( 'General settings for the fixes.', 'accessibility-checker' ) . '</p>';
	}
}
