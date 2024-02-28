<?php
/**
 * Class file for managing the registration and rendering of the "Prompt for Simplified Summary" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Interface;

/**
 * Manages the registration and rendering of the "Prompt for Simplified Summary" setting.
 */
class Simplified_Summary_Prompt_Setting implements Setting_Interface {

	/**
	 * Registers the settings field and the associated setting in WordPress.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_simplified_summary_prompt',
			__( 'Prompt for Simplified Summary', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_simplified_summary',
			array( 'label_for' => 'edac_simplified_summary_prompt' )
		);

		register_setting(
			'edac_settings',
			'edac_simplified_summary_prompt',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => 'when required',
			)
		);
	}

	/**
	 * Render the radio input field for position option
	 */
	public function callback() {
		$prompt = get_option( 'edac_simplified_summary_prompt' );
		?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" id="<?php echo 'edac_simplified_summary_prompt'; ?>" value="when required" <?php checked( $prompt, 'when required' ); ?>>
				<?php esc_html_e( 'When Required', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="always" <?php checked( $prompt, 'always' ); ?>>
				<?php esc_html_e( 'Always', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="none" <?php checked( $prompt, 'none' ); ?>>
				<?php esc_html_e( 'Never', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<p class="edac-description"><?php echo esc_html__( 'Should Accessibility Checker only ask for a simplified summary when the reading level of your post or page is above 9th grade, always ask for it regardless of reading level, or never ask for it regardless of reading level?', 'accessibility-checker' ); ?></p>
		<?php
	}

	/**
	 * Sanitize the text position value before being saved to database
	 *
	 * @param array $prompt The text.
	 *
	 * @return array
	 */
	public function sanitize( $prompt ) {
		if ( in_array( $prompt, array( 'when required', 'always', 'none' ), true ) ) {
			return $prompt;
		}
	}
}
