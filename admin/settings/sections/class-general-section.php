<?php
/**
 * Class file for managing the "General Settings" section within the plugin's settings.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Section_Interface;

/**
 * Manages the "General Settings" section within the plugin's settings.
 */
class General_Section implements Setting_Section_Interface {

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
	 * Registers the "General Settings" section with WordPress.
	 */
	public function register_section() {
		add_settings_section(
			'edac_general',
			__( 'General Settings', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings'
		);
	}

	/**
	 * Registers all settings fields associated with the "General Settings" section.
	 */
	public function register_fields() {
		( new Post_Types_Setting() )->add_settings_field();
		( new Delete_Data_Setting() )->add_settings_field();
	}

	/**
	 * Render the text for the general section
	 */
	public function callback() {
		echo '<p>';

		printf(
		/* translators: %1$s: link to the plugin documentation website. */
			esc_html__( 'Use the settings below to configure Accessibility Checker. Additional information about each setting can be found in the %1$s.', 'accessibility-checker' ),
			'<a href="https://a11ychecker.com/" target="_blank" aria-label="' . esc_attr__( 'plugin documentation (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'plugin documentation', 'accessibility-checker' ) . '</a>'
		);

		if ( EDAC_KEY_VALID === false ) {
			printf(
			/* translators: %1$s: link to the "Accessibility Checker Pro" website. */
				' ' . esc_html__( 'More features and email support is available with %1$s.', 'accessibility-checker' ),
				'<a href="https://equalizedigital.com/accessibility-checker/pricing/" target="_blank" aria-label="' . esc_attr__( 'Accessibility Checker Pro (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'Accessibility Checker Pro', 'accessibility-checker' ) . '</a>'
			);
		}

		echo '</p>';
	}
}
