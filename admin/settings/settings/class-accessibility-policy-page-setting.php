<?php
/**
 * Class file for managing the registration and rendering of the "Accessibility Policy Page" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Interface;

/**
 * Manages the registration and rendering of the "Accessibility Policy Page" setting.
 */
class Accessibility_Policy_Page_Setting implements Setting_Interface {

	/**
	 * Registers the settings field and the associated setting in WordPress.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_accessibility_policy_page',
			__( 'Accessibility Policy page', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_footer_accessibility_statement',
			array( 'label_for' => 'edac_accessibility_policy_page' )
		);

		register_setting( 'edac_settings', 'edac_accessibility_policy_page', array( $this, 'sanitize' ) );
	}

	/**
	 * Render the select field for accessibility policy page option
	 */
	public function callback() {

		$policy_page = get_option( 'edac_accessibility_policy_page' );
		$policy_page = is_numeric( $policy_page ) ? get_page_link( $policy_page ) : $policy_page;
		?>

		<input style="width: 100%;" type="text" name="edac_accessibility_policy_page"
			id="edac_accessibility_policy_page" value="<?php echo esc_attr( $policy_page ); ?>">

		<?php
	}

	/**
	 * Sanitize accessibility policy page values before being saved to database
	 *
	 * @param string $page Page to sanitize.
	 *
	 * @return string
	 */
	public function sanitize( $page ) {
		if ( $page ) {
			return esc_url( $page );
		}
	}
}
