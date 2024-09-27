<?php
/**
 * A class with methods to handle settings checkboxes for on the fixes page.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesSettingType;

/**
 * A trait to handle settings checkboxes for on the fixes page.
 */
trait Checkbox {

	/**
	 * Render a checkbox input.
	 *
	 * @param array $args The arguments for the checkbox. This is expected to have a name and a description.
	 */
	public static function checkbox( $args ) {

		// We need a name and a description or the checkbox is useless.
		if ( ! isset( $args['name'], $args['description'] ) ) {
			return;
		}
		$upsell       = isset( $args['upsell'] ) && $args['upsell'] ? true : false;
		$option_value = get_option( $args['name'] );
		?>
		<label <?php echo ( $upsell ) ? 'class="edac-fix--disabled edac-fix--upsell"' : ''; ?>>
			<input
				type="checkbox"
				value="1"
				id="<?php echo esc_attr( $args['name'] ); ?>"
				name="<?php echo esc_attr( $args['name'] ); ?>"
				<?php checked( 1, $option_value ); ?>
				<?php echo isset( $args['condition'] ) ? 'data-condition="' . esc_attr( $args['condition'] ) . '"' : ''; ?>
				<?php echo isset( $args['required_when'] ) ? 'data-required_when="' . esc_attr( $args['required_when'] ) . '"' : ''; ?>
				<?php echo $upsell ? 'disabled' : ''; ?>
				<?php echo isset( $args['fix_slug'] ) ? 'data-fix-slug="' . esc_attr( $args['fix_slug'] ) . '"' : ''; ?>
			/>
			<?php echo wp_kses( $args['description'], [ 'code' => [] ] ); ?>
		</label>
		<?php
	}

	/**
	 * Sanitize a checkbox input.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return int
	 */
	public static function sanitize_checkbox( $input ) {
		// if $input is not a bool or int then check if it is a string of '1' or 'true'.
		if ( ! is_bool( $input ) && ! is_int( $input ) ) {
			$input = ( '1' === $input || 'true' === strtolower( $input ) ) ? 1 : 0;
		}
		return isset( $input ) && $input ? 1 : 0;
	}
}
