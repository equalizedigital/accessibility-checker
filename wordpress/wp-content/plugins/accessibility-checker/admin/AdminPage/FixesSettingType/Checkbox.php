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
		$upsell       = isset( $args['upsell'] ) && $args['upsell'];
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
				<?php echo isset( $args['group_name'] ) ? 'data-group-name="' . esc_attr( $args['group_name'] ) . '"' : ''; ?>
				<?php echo isset( $args['fancy_name'] ) ? 'data-fancy-name="' . esc_attr( $args['fancy_name'] ) . '"' : ''; ?>
			/>
			<?php if ( isset( $args['location'] ) && $upsell ) : ?>
				<a class="edac-fix--upsell-link"
					href="<?php echo esc_url( \edac_generate_link_type( [ 'fix' => $args['fix_slug'] ] ) ); ?>"
					aria-label="<?php esc_attr_e( 'Get Pro to unlock this feature, opens in a new window.', 'accessibility-checker' ); ?>"
				><?php esc_html_e( 'Get Pro', 'accessibility-checker' ); ?></a>
			<?php endif; ?>
			<?php echo wp_kses( $args['description'], [ 'code' => [] ] ); ?>
			<?php
			if ( isset( $args['help_id'] ) && ! empty( $args['help_id'] ) && $args['label'] ) :
				$link = \edac_generate_link_type(
					[
						'utm-content' => 'fix-description',
						'utm-term'    => $args['name'],
					],
					'help',
					[
						'help_id' => $args['help_id'],
					]
				)
				?>
				<a
					href="<?php echo esc_url( $link ); ?>"
					class="edac-fix-description-help-link"
					target="_blank"
					aria-label="Read documentation for <?php echo esc_attr( $args['label'] ); ?>. <?php esc_attr_e( 'Opens in a new window.', 'accessibility-checker' ); ?>"
				>
					<span class="dashicons dashicons-info edac-dashicon-muted"></span>
				</a>
			<?php endif; ?>
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

		if ( null === $input ) {
			return 0;
		}
		// if $input is not a bool or int then check if it is a string of '1' or 'true'.
		if ( ! is_bool( $input ) && ! is_int( $input ) ) {
			$input = ( '1' === $input || 'true' === strtolower( $input ) ) ? 1 : 0;
		}
		return isset( $input ) && $input ? 1 : 0;
	}
}
