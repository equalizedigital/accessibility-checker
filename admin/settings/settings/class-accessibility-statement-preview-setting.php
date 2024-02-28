<?php
/**
 * Class file for managing the rendering of the "Accessibility Statement Preview" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Interface;

/**
 * Manages the rendering of the "Accessibility Statement Preview" setting.
 */
class Accessibility_Statement_Preview_Setting implements Setting_Interface {

	/**
	 * Registers the settings field in WordPress.
	 * Note: This setting is for display purposes and does not require registration with register_setting.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_accessibility_statement_preview',
			__( 'Accessibility Statement Preview', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_footer_accessibility_statement',
			array( 'label_for' => 'edac_accessibility_statement_preview' )
		);
	}

	/**
	 * Render the accessibility statement preview
	 */
	public function callback() {
		echo wp_kses_post(
			( new \EDAC\Inc\Accessibility_Statement() )->get_accessibility_statement()
		);
	}

	// phpcs:disable
	/**
	 * Since this setting is for display only, a sanitize callback is not applicable.
	 * Including an empty method to satisfy the interface contract.
	 *
	 * @param mixed $input The input value to sanitize.
	 *
	 * @return mixed Unmodified input value.
	 */
	public function sanitize( $input ) {
		// TODO: Implement sanitize() method.
	}
	// phpcs:enable
}
