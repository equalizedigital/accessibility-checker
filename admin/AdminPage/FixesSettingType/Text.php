<?php
/**
 * A class with methods to handle settings text inputs for on the fixes page.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesSettingType;

/**
 * A trait to handle settings text inputs for on the fixes page.
 */
trait Text {

	/**
	 * Render a text input.
	 *
	 * @param array $args The arguments for the text input. This is expected to have a name and a description.
	 */
	public static function text( $args ) {
		// We need a name and a description or the text field is useless.
		if ( ! isset( $args['name'], $args['description'] ) ) {
			return;
		}

		$option_value = get_option( $args['name'] );
		?>
		<label
			for="<?php echo esc_attr( $args['name'] ); ?>"
			style="display: block; margin-bottom: 6px;"
		>
			<?php if ( isset( $args['location'] ) && $upsell ) : ?>
				<a class="edac-fix--upsell-link" href="<?php echo esc_url( edac_generate_pro_link( [ 'fix' => $args['fix_slug'] ] ) ); ?>"><?php esc_html_e( 'Get Pro', 'accessibility-checker' ); ?></a>
			<?php endif; ?>
			<?php echo wp_kses( $args['description'], [ 'code' => [] ] ); ?>
			<?php
			if ( $args['help_id'] && $args['label'] ) :
				$link = edac_generate_link_type(
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
					aria-label="Read documentation for <?php echo esc_attr( $args['label'] ); ?>"
				>
					<span class="dashicons dashicons-info"></span>
				</a>
			<?php endif; ?>
		</label>
		<input
			type="text"
			id="<?php echo esc_attr( $args['name'] ); ?>"
			name="<?php echo esc_attr( $args['name'] ); ?>"
			value="<?php echo esc_attr( $option_value ); ?>"
			<?php echo isset( $args['condition'] ) ? 'data-condition="' . esc_attr( $args['condition'] ) . '"' : ''; ?>
			<?php echo isset( $args['required_when'] ) ? 'data-required_when="' . esc_attr( $args['required_when'] ) . '"' : ''; ?>
			<?php echo isset( $args['fix_slug'] ) ? 'data-fix-slug="' . esc_attr( $args['fix_slug'] ) . '"' : ''; ?>
		/>
		<?php
	}

	/**
	 * Sanitize a text input.
	 *
	 * @param mixed $input The input to sanitize.
	 * @return string
	 */
	public static function sanitize_text( $input ) {
		return sanitize_text_field( $input );
	}
}
