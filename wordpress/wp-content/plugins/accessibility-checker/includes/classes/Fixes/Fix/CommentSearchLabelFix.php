<?php
/**
 * Comment Search Label Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Fixes missing or incorrect labels in the comment form and search form.
 *
 * @since 1.16.0
 */
class CommentSearchLabelFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'comment-search-label';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add Labels to Comment and Search Forms', 'accessibility-checker' );
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Label Comment/Search Fields', 'accessibility-checker' );
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
				$sections['comment_search_label'] = [
					'title'       => esc_html__( 'Comment and Search Form Labels', 'accessibility-checker' ),
					'description' => esc_html__( 'Add missing labels to WordPress comment and search forms.', 'accessibility-checker' ),
					'callback'    => [ $this, 'comment_search_label_section_callback' ],
				];

				return $sections;
			}
		);

		add_filter(
			'edac_filter_fixes_settings_fields',
			[ $this, 'get_fields_array' ],
		);
	}

	/**
	 * Get the settings fields for the fix.
	 *
	 * @param array $fields The array of fields that are already registered, if any.
	 *
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {
		$fields['edac_fix_comment_label'] = [
			'label'       => esc_html__( 'Label Comment Form', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'add_comment_label',
			'description' => esc_html__( 'Add missing labels to the WordPress comment form.', 'accessibility-checker' ),
			'section'     => 'comment_search_label',
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 8658,
		];

		$fields['edac_fix_search_label'] = [
			'label'       => esc_html__( 'Label Search Form', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'add_search_label',
			'description' => esc_html__( 'Add a missing label to the WordPress search form.', 'accessibility-checker' ),
			'section'     => 'comment_search_label',
			'fix_slug'    => $this->get_slug(),
			'help_id'     => 8659,
		];

		return $fields;
	}

	/**
	 * Run the fix for adding the comment and search form labels.
	 */
	public function run(): void {

		// Add the actual fixes if enabled in settings.
		if ( get_option( 'edac_fix_comment_label', false ) ) {
			add_filter( 'comment_form_defaults', [ $this, 'fix_comment_form_labels' ], PHP_INT_MAX );
		}

		if ( get_option( 'edac_fix_search_label', false ) ) {
			add_filter( 'get_search_form', [ $this, 'fix_search_form_label' ], PHP_INT_MAX );
		}
	}

	/**
	 * Fixes labels in the comments form.
	 *
	 * @param array $defaults The default comment form arguments.
	 * @return array Modified comment form arguments.
	 */
	public function fix_comment_form_labels( $defaults ): array {

		// Check if the comment label is set correctly; if not, fix it.
		if ( empty( $defaults['comment_field'] ) || ! strpos( $defaults['comment_field'], '<label' ) ) {
			$defaults['comment_field'] = '<p class="comment-form-comment"><label for="comment" class="edac-generated-label">' . esc_html__( 'Comment', 'accessibility-checker' ) . '</label><textarea id="comment" name="comment" rows="4" required></textarea></p>';
		}

		// Check the author field label.
		if ( isset( $defaults['fields']['author'] ) && ! strpos( $defaults['fields']['author'], '<label' ) ) {
			$defaults['fields']['author'] = '<p class="comment-form-author"><label for="author" class="edac-generated-label">' . esc_html__( 'Name', 'accessibility-checker' ) . '</label><input id="author" name="author" type="text" value="" size="30" required /></p>';
		}

		// Check the email field label.
		if ( isset( $defaults['fields']['email'] ) && ! strpos( $defaults['fields']['email'], '<label' ) ) {
			$defaults['fields']['email'] = '<p class="comment-form-email"><label for="email" class="edac-generated-label">' . esc_html__( 'Email', 'accessibility-checker' ) . '</label><input id="email" name="email" type="email" value="" size="30" required /></p>';
		}

		// Check the website field label.
		if ( isset( $defaults['fields']['url'] ) && ! strpos( $defaults['fields']['url'], '<label' ) ) {
			$defaults['fields']['url'] = '<p class="comment-form-url"><label for="url" class="edac-generated-label">' . esc_html__( 'Website', 'accessibility-checker' ) . '</label><input id="url" name="url" type="url" value="" size="30" /></p>';
		}

		return $defaults;
	}

	/**
	 * Fixes the search form label.
	 *
	 * @param string $form The HTML of the search form.
	 * @return string Modified search form HTML.
	 */
	public function fix_search_form_label( $form ): string {
		// Check if the form already contains a visible <label> with a matching "for" attribute for the search input's id.
		if ( ! preg_match( '/<label[^>]*for=["\']([^"\']*)["\'][^>]*>.*<\/label>/', $form, $label_matches ) ||
			! preg_match( '/<input[^>]*id=["\']([^"\']*)["\'][^>]*name=["\']s["\'][^>]*>/', $form, $input_matches ) ||
			$label_matches[1] !== $input_matches[1] ) {

			// Extract the existing input field to preserve its attributes, or set a default if none found.
			if ( isset( $input_matches[0] ) ) {
				$input_field = $input_matches[0];
				$input_id    = $input_matches[1]; // Use the existing id of the input field.
			} else {
				$input_id    = 'search-form-' . uniqid(); // Generate a unique ID if the input field doesn't have one.
				$input_field = '<input type="search" id="' . esc_attr( $input_id ) . '" class="search-field edac-generated-label" placeholder="' . esc_attr__( 'Search …', 'accessibility-checker' ) . '" value="' . get_search_query() . '" name="s" />';
			}

			// Rebuild the form with a visible <label> and ensure the "for" attribute matches the input's id.
			$form = '<form role="search" method="get" class="search-form" action="' . esc_url( home_url( '/' ) ) . '">
			<label for="' . esc_attr( $input_id ) . '" class="edac-generated-label" >' . esc_html__( 'Search for:', 'accessibility-checker' ) . '</label>
			' . $input_field . '
			<button type="submit" class="search-submit">' . esc_attr__( 'Search', 'accessibility-checker' ) . '</button>
			</form>';
		}

		return $form;
	}

	/**
	 * Callback for the fix settings section.
	 *
	 * @return void
	 */
	public function comment_search_label_section_callback() {
		?>
		<p><?php esc_html_e( 'Settings to add missing labels to WordPress comment and search forms.', 'accessibility-checker' ); ?></p>
		<?php
	}
}
