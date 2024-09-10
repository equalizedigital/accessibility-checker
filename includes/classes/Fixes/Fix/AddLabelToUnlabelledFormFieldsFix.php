<?php
/**
 * Comment Search Label Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Try to add labels to unlabeled form fields.
 *
 * @since 1.16.0
 */
class AddLabelToUnlabeledFormFieldsFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'add_label_to_unlabeled_form_fields';
	}

	/**
	 * The type of the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'frontend';
	}

	/**
	 * Registers everything needed for the fix.
	 *
	 * @return void
	 */
	public function register(): void {

		add_filter(
			'edac_filter_fixes_settings_sections',
			function ( $sections ) {
				$sections[ $this->get_slug() ] = [
					'title'       => esc_html__( 'Unlabled Form Fields', 'accessibility-checker' ),
					'description' => esc_html__( 'Add labels to unlabeled form fields', 'accessibility-checker' ),
					'callback'    => [ $this, $this->get_slug() . '_section_callback' ],
				];

				return $sections;
			}
		);

		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {
				$fields[ 'edac_' . $this->get_slug() ] = [
					'label'       => esc_html__( 'Unlabeled Form Fields', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => $this->get_slug(),
					'description' => esc_html__( 'Try add labels to unlabeled form fields.', 'accessibility-checker' ),
					'section'     => $this->get_slug(),
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix for adding the comment and search form labels.
	 */
	public function run(): void {

		if ( ! get_option( 'edac_' . $this->get_slug(), false ) ) {
			return;
		}

		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data[ $this->get_slug() ] = [
					'enabled' => true,
				];
				return $data;
			}
		);
	}

	/**
	 * Callback for the fix settings section.
	 *
	 * @return void
	 */
	public function add_label_to_unlabeled_form_fields_section_callback() {
		?>
		<p>
			<?php
			printf(
				// translators: %1$s: a CSS class name wrapped in a <code> tag.
				esc_html__( 'Try add labels to unlabelled form fields. You may need to add styles targeting the %1$s class if adding labeles affects the layout of your forms.', 'accessibility-checker' ),
				'<code>.edac-generated-label</code>'
			);
			?>
		</p>
		<?php
	}
}
