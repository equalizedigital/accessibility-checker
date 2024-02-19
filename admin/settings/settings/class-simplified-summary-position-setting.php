<?php
/**
 * Class file for managing the registration and rendering of the "Simplified Summary Position" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

use EDAC\Admin\Settings\Setting_Interface;

/**
 * Manages the registration and rendering of the "Simplified Summary Position" setting.
 */
class Simplified_Summary_Position_Setting implements Setting_Interface {

	/**
	 * Registers the settings field and the associated setting in WordPress.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_simplified_summary_position',
			__( 'Simplified Summary Position', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_simplified_summary',
			array( 'label_for' => 'edac_simplified_summary_position' )
		);

		register_setting(
			'edac_settings',
			'edac_simplified_summary_position',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => 'after',
			)
		);
	}

	/**
	 * Render the radio input field for position option
	 */
	public function callback() {
		$position = get_option( 'edac_simplified_summary_position' );
		?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>"
					id="<?php echo 'edac_simplified_summary_position'; ?>"
					value="before" <?php checked( $position, 'before' ); ?>>
				<?php esc_html_e( 'Before the content', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>"
					value="after" <?php checked( $position, 'after' ); ?>>
				<?php esc_html_e( 'After the content', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>"
					value="none" <?php checked( $position, 'none' ); ?>>
				<?php esc_html_e( 'Insert manually', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<div id="ac-simplified-summary-option-code">
			<p><?php esc_html_e( 'Use this function to manually add the simplified summary to your theme within the loop.', 'accessibility-checker' ); ?></p>
			<kbd>edac_get_simplified_summary();</kbd>
			<p><?php esc_html_e( 'The function optionally accepts the post ID as a parameter.', 'accessibility-checker' ); ?>
			<p>
				<kbd>edac_get_simplified_summary($post);</kbd>
		</div>
		<p class="edac-description"><?php echo esc_html__( 'Set where you would like simplified summaries to appear in relation to your content if filled in.', 'accessibility-checker' ); ?></p>
		<?php
	}

	/**
	 * Sanitize the text position value before being saved to database
	 *
	 * @param array $position Position value.
	 *
	 * @return array
	 */
	public function sanitize( $position ) {
		if ( in_array( $position, array( 'before', 'after', 'none' ), true ) ) {
			return $position;
		}
	}
}
