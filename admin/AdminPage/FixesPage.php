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
	 * Add the settings sections and fields and setup it's tabs and filter in for the content.
	 */
	public function add_page() {

		$this->register_settings_sections();
		$this->register_fields_and_settings();

		add_filter( 'edac_filter_admin_scripts_slugs', [ $this, 'add_slug_to_admin_scripts' ] );
		add_filter( 'edac_filter_remove_admin_notices_screens', [ $this, 'add_slug_to_admin_notices' ] );
		add_filter( 'edac_filter_settings_tab_items', [ $this, 'add_fixes_tab' ] );
		add_action( 'edac_settings_tab_content', [ $this, 'add_fixes_tab_content' ], 11, 1 );
	}

	/**
	 * Add fixes tab to settings page.
	 *
	 * @param  array $settings_tab_items arrray of tab items.
	 * @return array
	 */
	public function add_fixes_tab( $settings_tab_items ) {

		$scan_tab = [
			'slug'  => 'fixes',
			'label' => 'Fixes',
			'order' => 2,
		];
		array_push( $settings_tab_items, $scan_tab );

		return $settings_tab_items;
	}

	/**
	 * Licence fixes tab content to settings page.
	 *
	 * @param  string $tab name of tab.
	 * @return void
	 */
	public function add_fixes_tab_content( $tab ) {
		if ( 'fixes' === $tab ) {
			include EDAC_PLUGIN_DIR . '/partials/admin-page/fixes-page.php';
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
					'title'    => __( 'General Fixes', 'accessibility-checker' ),
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

			$is_upsell = $field['upsell'] ?? false;

			add_settings_field(
				$field_id,
				$field['label'],
				$field['callback'] ?? [ $this, $field_type ],
				self::SETTINGS_SLUG,
				$field['section'] ?? 'edac_fixes_general',
				[
					'name'          => $field_id,
					'labelledby'    => $field_id,
					'description'   => $field['description'] ?? '',
					'condition'     => $field['condition'] ?? '',
					'required_when' => $field['required_when'] ?? '',
					'default'       => $field['default'] ?? '',
					'upsell'        => $is_upsell,
					'help_id'       => $field['help_id'] ?? '',
					'label'         => $field['label'] ?? '',
				]
			);

			register_setting( self::SETTINGS_SLUG, $field_id, $sanitizer );
		}
	}

	/**
	 * Callback for the general settings section that renders a description for it.
	 */
	public function fixes_section_general_cb() {
		echo '<p>' . esc_html__( 'These fixes help improve accessibility by modifying HTML elements and behaviors on your site.', 'accessibility-checker' ) . '</p>';
	}
}
