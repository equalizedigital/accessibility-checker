<?php
/**
 * Empty Search Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forces an error state when a front-end search is submitted with an empty query.
 *
 * When the `s` query parameter exists but is empty or whitespace-only, this fix
 * ensures WordPress still treats the request as a search so themes can display
 * a "no results" or "empty search" message via search.php.
 *
 * @since 1.39.0
 */
class EmptySearchFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'empty-search';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Force Error on Empty Search', 'accessibility-checker' );
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
				$sections['empty_search'] = [
					'title'    => esc_html__( 'Empty Search Handling', 'accessibility-checker' ),
					'callback' => [ $this, 'settings_section_callback' ],
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
		$fields['edac_fix_empty_search'] = [
			'label'       => esc_html__( 'Force Error on Empty Search', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'force_empty_search_error',
			'description' => esc_html__( 'When a search is submitted with an empty query, force WordPress to display the search results template so users see a meaningful response. This assumes the active theme has a search.php template.', 'accessibility-checker' ),
			'section'     => 'empty_search',
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 10540,
		];

		return $fields;
	}

	/**
	 * Callback for the fix settings section.
	 *
	 * @return void
	 */
	public function settings_section_callback() {
		echo '<p>' . esc_html__( 'Force WordPress to show a search results page when a search is submitted with an empty query.', 'accessibility-checker' ) . '</p>';
	}

	/**
	 * Run the fix.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( get_option( 'edac_fix_empty_search', false ) ) {
			add_action( 'pre_get_posts', [ $this, 'handle_empty_search' ] );
		}
	}

	/**
	 * Handle an empty search submission on the front end.
	 *
	 * When the `s` query parameter is present but empty/whitespace, this sets
	 * the search query var to a space so WordPress treats it as a search and
	 * forces `is_search` to true.
	 *
	 * @param \WP_Query $query The main query object.
	 *
	 * @return void
	 */
	public function handle_empty_search( $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check on a public search parameter.
		if ( ! isset( $_GET['s'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only comparison, not stored.
		if ( '' !== trim( wp_unslash( $_GET['s'] ) ) ) {
			return;
		}

		$query->query_vars['s'] = '&#32;';

		add_filter( 'get_search_query', [ $this, 'clear_fake_search_query' ] );
		add_action( 'template_include', [ $this, 'force_search_template' ] );
	}

	/**
	 * Clear the fake search query so it doesn't appear in the search input field.
	 *
	 * @param string $query The search query.
	 *
	 * @return string Empty string when this fix injected the placeholder value.
	 */
	public function clear_fake_search_query( $query ): string {
		if ( '&#32;' === $query ) {
			return '';
		}

		return $query;
	}

	/**
	 * Force the search template for an empty search.
	 *
	 * Uses get_search_template() to respect the full WordPress template
	 * hierarchy (search_template_hierarchy filter) and the search_template
	 * filter, falling back to the original template if none is found.
	 *
	 * @param string $template The current template path.
	 *
	 * @return string The search template path, or the original template.
	 */
	public function force_search_template( $template ): string {
		$search_template = get_search_template();
		if ( $search_template ) {
			return $search_template;
		}

		return $template;
	}
}
