<?php
/**
 * Class file for managing the registration and rendering of the "Include Link to Accessibility Policy" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Interface;

/**
 * Manages the registration and rendering of the "Include Link to Accessibility Policy" setting.
 */
class Include_Accessibility_Statement_Link_Setting implements Setting_Interface {

	/**
	 * Registers the settings field and the associated setting in WordPress.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_include_accessibility_statement_link',
			__( 'Include Link to Accessibility Policy', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_footer_accessibility_statement',
			array( 'label_for' => 'edac_include_accessibility_statement_link' )
		);

		register_setting( 'edac_settings', 'edac_include_accessibility_statement_link', array( $this, 'sanitize' ) );
	}

	/**
	 * Render the checkbox input field for add footer accessibility statement option
	 */
	public function callback() {
		$option   = get_option( 'edac_include_accessibility_statement_link' ) ? get_option( 'edac_include_accessibility_statement_link' ) : false;
		$disabled = get_option( 'edac_add_footer_accessibility_statement' ) ? get_option( 'edac_add_footer_accessibility_statement' ) : false;

		?>
		<fieldset>
			<label>
				<input type="checkbox" name="<?php echo 'edac_include_accessibility_statement_link'; ?>"
					value="<?php echo '1'; ?>"
					<?php
					checked( $option, 1 );
					disabled( $disabled, false );
					?>
				>
				<?php esc_html_e( 'Include Link to Accessibility Policy', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Sanitize add footer accessibility statement values before being saved to database
	 *
	 * @param int $option Option to sanitize.
	 *
	 * @return int
	 */
	public function sanitize( $option ) {
		if ( 1 === (int) $option ) {
			return $option;
		}
	}
}
