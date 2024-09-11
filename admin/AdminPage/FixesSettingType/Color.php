<?php
/**
 * A class with methods to handle settings color inputs for on the fixes page.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesSettingType;

/**
 * A trait to handle settings color inputs for on the fixes page.
 */
trait Color {

	/**
	 * Render a color input.
	 *
	 * @param array $args The arguments for the color input. This is expected to have a name and a description.
	 */
	public static function color( $args ) {
		// We need a name and a description or the color field is useless.
		if ( ! isset( $args['name'], $args['description'] ) ) {
			return;
		}

		$option_value = get_option( $args['name'] );
		$option_value = ! empty( $option_value ) ? $option_value : ( $args['default'] ?? '' );
		?>
		<label
			for="<?php echo esc_attr( $args['name'] ); ?>"
			style="display: block; margin-bottom: 6px;"
		>
			<?php echo wp_kses( $args['description'], [ 'code' => [] ] ); ?>
		</label>
		<input
			type="color"
			id="<?php echo esc_attr( $args['name'] ); ?>"
			name="<?php echo esc_attr( $args['name'] ); ?>"
			value="<?php echo esc_attr( $option_value ); ?>"
			<?php echo isset( $args['condition'] ) ? 'data-condition="' . esc_attr( $args['condition'] ) . '"' : ''; ?>
		/>
		<?php
	}

	/**
	 * Sanitize a color input.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return string
	 */
	public function sanitize_color( $input ) {
		return sanitize_hex_color( $input );
	}
}
