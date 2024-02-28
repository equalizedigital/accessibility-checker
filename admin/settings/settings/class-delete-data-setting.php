<?php
/**
 * Class file for managing the registration and rendering of the "Delete Data" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Interface;

/**
 * Manages the registration and rendering of the "Delete Data" setting.
 */
class Delete_Data_Setting implements Setting_Interface {

	/**
	 * Registers the settings field and the associated setting in WordPress.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_delete_data',
			__( 'Delete Data', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_general',
			array( 'label_for' => 'edac_delete_data' )
		);

		register_setting( 'edac_settings', 'edac_delete_data', array( $this, 'sanitize' ) );
	}

	/**
	 * Render the checkbox input field for delete data option
	 */
	public function callback() {

		$option = get_option( 'edac_delete_data' ) ? get_option( 'edac_delete_data' ) : false;

		?>
		<fieldset>
			<label>
				<input type="checkbox" name="edac_delete_data" value="1" <?php checked( $option, 1 ); ?>>
				<?php esc_html_e( 'Delete all Accessibility Checker data when the plugin is uninstalled.', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Sanitize delete data values before being saved to database
	 *
	 * @param int $option Option to sanitize.
	 *
	 * @return int
	 */
	public function sanitize( $option ) {
		if ( 1 === $option ) {
			return $option;
		}
	}
}
