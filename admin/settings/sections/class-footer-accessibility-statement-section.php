<?php
/**
 * Class file for managing the "Footer Accessibility Statement" section within the plugin's settings.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Section_Interface;

/**
 * Manages the "Footer Accessibility Statement" section within the plugin's settings.
 */
class Footer_Accessibility_Statement_Section implements Setting_Section_Interface {

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
	 * Registers the "Footer Accessibility Statement" section with WordPress.
	 */
	public function register_section() {
		add_settings_section(
			'edac_footer_accessibility_statement',
			__( 'Footer Accessibility Statement', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings'
		);
	}

	/**
	 * Registers all settings fields associated with the "Footer Accessibility Statement" section.
	 */
	public function register_fields() {
		( new Add_Footer_Accessibility_Statement_Setting() )->add_settings_field();
		( new Include_Accessibility_Statement_Link_Setting() )->add_settings_field();
		( new Accessibility_Policy_Page_Setting() )->add_settings_field();
		( new Accessibility_Statement_Preview_Setting() )->add_settings_field();
	}

	/**
	 * Render the text for the footer accessiblity statement section
	 */
	public function callback() {
		echo '<p>';
		echo esc_html__( 'Are you thinking "Wow, this plugin is amazing" and is it helping you make your website more accessible? Share your efforts to make your website more accessible with your customers and let them know you\'re using Accessibility Checker to ensure all people can use your website. Add a small text-only link and statement in the footer of your website.', 'accessibility-checker' );
		echo '</p>';
	}
}
