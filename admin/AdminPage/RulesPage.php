<?php
/**
 * Admin page that holds the active rules setting.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Rules page and its settings.
 *
 * @since 1.37.0
 */
class RulesPage implements PageInterface {

	/**
	 * The capability required to access the settings page.
	 *
	 * @var string
	 */
	private $settings_capability;

	const PAGE_TAB_SLUG = 'rules';

	/**
	 * The settings page slug which all sections, settings and fields are registered to.
	 *
	 * @var string
	 */
	const SETTINGS_SLUG = 'edac_settings_rules';

	/**
	 * Constructor.
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	public function __construct( $settings_capability ) {
		$this->settings_capability = $settings_capability;
	}

	/**
	 * Add the settings sections and fields and setup its tabs and filter in for the content.
	 */
	public function add_page() {

		$this->register_settings_sections();
		$this->register_fields_and_settings();

		add_filter( 'edac_filter_admin_scripts_slugs', [ $this, 'add_slug_to_admin_scripts' ] );
		add_filter( 'edac_filter_remove_admin_notices_screens', [ $this, 'add_slug_to_admin_notices' ] );
		add_filter( 'edac_filter_settings_tab_items', [ $this, 'add_rules_tab' ], 8 );
		add_action( 'edac_settings_tab_content', [ $this, 'add_rules_tab_content' ], 12, 1 );

		// Run early so other filters can refilter after us.
		add_filter( 'edac_filter_register_rules', [ $this, 'apply_disabled_rules_setting' ], 5 );
	}

	/**
	 * Add rules tab to settings page.
	 *
	 * @param  array $settings_tab_items Array of tab items.
	 * @return array
	 */
	public function add_rules_tab( $settings_tab_items ) {

		$rules_tab = [
			'slug'  => 'rules',
			'label' => __( 'Rules', 'accessibility-checker' ),
			'order' => 3,
		];
		array_push( $settings_tab_items, $rules_tab );

		return $settings_tab_items;
	}

	/**
	 * Render the rules tab content on the settings page.
	 *
	 * @param  string $tab Name of the active tab.
	 * @return void
	 */
	public function add_rules_tab_content( $tab ) {
		if ( 'rules' === $tab ) {
			include EDAC_PLUGIN_DIR . '/partials/admin-page/rules-page.php';
		}
	}

	/**
	 * Add the rules slug to the admin scripts.
	 *
	 * @param array $slugs The slugs that are already added.
	 * @return array
	 */
	public function add_slug_to_admin_scripts( $slugs ) {
		$slugs[] = 'accessibility_checker_' . self::PAGE_TAB_SLUG;
		return $slugs;
	}

	/**
	 * Add the rules slug to the admin notices.
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
		include_once EDAC_PLUGIN_DIR . 'partials/admin-page/rules-page.php';
	}

	/**
	 * Register the settings sections for this options page.
	 */
	public function register_settings_sections() {

		add_settings_section(
			'edac_rules_general',
			__( 'Active Rules', 'accessibility-checker' ),
			[ $this, 'rules_section_general_cb' ],
			self::SETTINGS_SLUG,
		);
	}

	/**
	 * Register the settings fields and settings for this options page.
	 */
	public function register_fields_and_settings() {

		add_settings_field(
			'edac_reset_rules',
			'',
			'edac_reset_rules_cb',
			self::SETTINGS_SLUG,
			'edac_rules_general',
		);

		add_settings_field(
			'edac_disabled_rules',
			__( 'Active Rules', 'accessibility-checker' ),
			'edac_disabled_rules_cb',
			self::SETTINGS_SLUG,
			'edac_rules_general',
			[ 'label_for' => 'edac_disabled_rules' ]
		);

		register_setting( self::SETTINGS_SLUG, 'edac_disabled_rules', 'edac_sanitize_disabled_rules' );
	}

	/**
	 * Callback for the general rules section that renders a description for it.
	 */
	public function rules_section_general_cb() {
		echo '<p>' . esc_html__( 'Choose which rules are active during a scan. Unchecked rules will not be processed.', 'accessibility-checker' ) . '</p>';
	}

	/**
	 * Filter callback that removes disabled rules from the registered rules list.
	 *
	 * Hooked onto edac_filter_register_rules at priority 5 so that other filters
	 * can further modify the list after us.
	 *
	 * @param array $rules The full list of registered rules.
	 * @return array Rules with disabled entries removed.
	 */
	public function apply_disabled_rules_setting( array $rules ): array {

		if ( ! edac_is_pro() ) {
			return $rules;
		}

		$disabled = get_option( 'edac_disabled_rules', [] );

		if ( empty( $disabled ) || ! is_array( $disabled ) ) {
			return $rules;
		}

		return array_values(
			array_filter(
				$rules,
				fn( $rule ) => ! in_array( $rule['slug'] ?? '', $disabled, true )
			)
		);
	}
}
