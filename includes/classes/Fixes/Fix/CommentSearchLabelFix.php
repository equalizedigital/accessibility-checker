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
			function ( $fields ) {
				$fields['edac_fix_comment_label'] = [
					'label'       => esc_html__( 'Comment form', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'add_comment_label',
					'description' => esc_html__( 'Add missing form labels to the WordPress comment form.', 'accessibility-checker' ),
					'section'     => 'comment_search_label',
				];

				$fields['edac_fix_search_label'] = [
					'label'       => esc_html__( 'Search form', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'add_search_label',
					'description' => esc_html__( 'Add missing form label to the WordPress search form.', 'accessibility-checker' ),
					'section'     => 'comment_search_label',
				];

				return $fields;
			}
		);
	}

	/**
	 * Callback for the fix settings section.
	 *
	 * @return void
	 */
	public function comment_search_label_section_callback() {
		?>
		<p><?php esc_html_e( 'Settings related to adding missing labels to WordPress comment and search forms.', 'accessibility-checker' ); ?></p>
		<?php
	}
}
