<?php
/**
 * Class file for managing the "Simplified Summary Settings" section within the plugin's settings.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Section_Interface;

/**
 * Manages the "Simplified Summary Settings" section within the plugin's settings.
 */
class Simplified_Summary_Section implements Setting_Section_Interface {

	/**
	 * Initialize class hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		// phpcs:disable
		add_action( 'admin_init', function () {
			$this->register_section();
			$this->register_fields();
		} );
		// phpcs:enable
	}

	/**
	 * Registers the "Simplified Summary Settings" section with WordPress.
	 */
	public function register_section() {
		add_settings_section(
			'edac_simplified_summary',
			__( 'Simplified Summary Settings', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings'
		);
	}

	/**
	 * Registers all settings fields associated with the "Simplified Summary Settings" section.
	 */
	public function register_fields() {
		( new Simplified_Summary_Prompt_Setting() )->add_settings_field();
		( new Simplified_Summary_Position_Setting() )->add_settings_field();
	}

	/**
	 * Render the text for the simplified summary section
	 */
	public function callback() {
		printf(
			'<p>%1$s %2$s</p>',
			esc_html__( 'Web Content Accessibility Guidelines (WCAG) at the AAA level require any content with a reading level above 9th grade to have an alternative that is easier to read. Simplified summary text is added on the readability tab in the Accessibility Checker meta box on each post\'s or page\'s edit screen.', 'accessibility-checker' ),
			'<a href="https://a11ychecker.com/help3265" target="_blank" aria-label="' . esc_attr__( 'Learn more about simplified summaries and readability requirements (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'Learn more about simplified summaries and readability requirements.', 'accessibility-checker' ) . '</a>'
		);
	}
}
