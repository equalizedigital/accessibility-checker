<?php
/**
 * Class file for managing the registration and rendering of the "Add Footer Accessibility Statement" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Interface;

/**
 * Manages the registration and rendering of the "Add Footer Accessibility Statement" setting.
 */
class Add_Footer_Accessibility_Statement_Setting implements Setting_Interface {

	/**
	 * Registers the settings field and the associated setting in WordPress.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_add_footer_accessibility_statement',
			__( 'Add Footer Accessibility Statement', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_footer_accessibility_statement',
			array( 'label_for' => 'edac_add_footer_accessibility_statement' )
		);

		register_setting( 'edac_settings', 'edac_add_footer_accessibility_statement', array( $this, 'sanitize' ) );
	}

	/**
	 * Render the checkbox input field for add footer accessibility statement option
	 */
	public function callback() {
		$option = get_option( 'edac_add_footer_accessibility_statement' ) ? get_option( 'edac_add_footer_accessibility_statement' ) : false;

		?>
		<fieldset>
			<label>
				<input type="checkbox" name="edac_add_footer_accessibility_statement"
					value="1" <?php checked( $option, 1 ); ?>>
				<?php esc_html_e( 'Add Footer Accessibility Statement', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Sanitize add footer accessibility statement values before being saved to database
	 *
	 * @param int $option Option value to sanitize.
	 *
	 * @return int
	 */
	public function sanitize( $option ) {
		if ( 1 === (int) $option ) {
			return $option;
		}
	}
}
