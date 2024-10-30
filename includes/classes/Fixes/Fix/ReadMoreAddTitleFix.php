<?php
/**
 * Skip Link Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Allows the user to add the post title to their read more links.
 *
 * @since 1.16.0
 */
class ReadMoreAddTitleFix implements FixInterface {
	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'read_more';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add "Read" Link with Post Title', 'accessibility-checker' );
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_fancyname(): string {
		return __( 'Add Title to Read More Link', 'accessibility-checker' );
	}

	/**
	 * The type of the fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'everywhere';
	}

	/**
	 * Registers everything needed for the skip link fix.
	 *
	 * @return void
	 */
	public function register(): void {

		add_filter(
			'edac_filter_fixes_settings_sections',
			function ( $sections ) {
				$sections['read_more_links'] = [
					'title'       => esc_html__( 'Read More links', 'accessibility-checker' ),
					'description' => esc_html__( 'Add the post title and links ', 'accessibility-checker' ),
					'callback'    => [ $this, 'read_more_section_callback' ],
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
		$fields['edac_fix_add_read_more_title'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'Add Post Title To "Read More"', 'accessibility-checker' ),
			'labelledby'  => 'add_read_more_title',
			'description' => esc_html__( 'Add the post title to "Read More" links in post lists when your theme outputs those links.', 'accessibility-checker' ),
			'section'     => 'read_more_links',
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 8663,
		];

		$fields['edac_fix_add_read_more_title_screen_reader_only'] = [
			'type'        => 'checkbox',
			'label'       => esc_html__( 'For Screen Readers Only', 'accessibility-checker' ),
			'labelledby'  => 'add_read_more_title_screen_reader_only',
			'description' => esc_html__( 'Makes the post title added to "Read More" links visible only to screen readers.', 'accessibility-checker' ),
			'condition'   => 'edac_fix_add_read_more_title',
			'section'     => 'read_more_links',
			'fix_slug'    => $this->get_slug(),
		];

		return $fields;
	}

	/**
	 * Run the fix.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_add_read_more_title', false ) ) {
			return;
		}

		add_action( 'the_content_more_link', [ $this, 'add_title_to_read_more' ], 100, 2 );
		add_filter( 'get_the_excerpt', [ $this, 'add_title_link_to_excerpts' ], 100 );
		add_filter( 'excerpt_more', [ $this, 'add_title_to_excerpt_more' ], 100 );

		if ( get_option( 'edac_fix_add_read_more_title_screen_reader_only', false ) ) {
			add_action( 'wp_head', [ $this, 'add_screen_reader_styles' ] );
		}
	}

	/**
	 * Add the post title to the read more link.
	 *
	 * @param string $link The read more link.
	 * @param string $text The link text.
	 * @return string
	 */
	public function add_title_to_read_more( $link, $text ): string {

		global $id;

		// If the $text already contains the title, we won't add it again.
		if ( str_contains( strtolower( $text ), strtolower( get_the_title( $id ) ) ) ) {
			return $link;
		}

		return str_replace(
			$text,
			$text . ' ' . $this->generate_read_more_string( $id ),
			$link
		);
	}

	/**
	 * Add the post title to the excerpt.
	 *
	 * @param string $excerpt The excerpt.
	 * @return string
	 */
	public function add_title_link_to_excerpts( $excerpt ): string {
		if ( has_excerpt() && ! is_attachment() ) {
			global $id;

			$post_title = get_the_title( $id );

			// If the last part of the excerpt contains the post title, we won't add it again.
			$exceprt_fragment = strtolower( substr( $excerpt, ( -100 - strlen( $post_title ) ) ) );
			if ( str_contains( $exceprt_fragment, strtolower( $post_title ) ) ) {
				return $excerpt;
			}

			$excerpt .= ' ' . $this->generate_read_more_string( $id, true );
		}

		return $excerpt;
	}

	/**
	 * Add the post title to the excerpt more link.
	 *
	 * @return string
	 */
	public function add_title_to_excerpt_more(): string {
		global $id;
		return '&hellip; <a href="' . get_the_permalink( $id ) . '">' . $this->generate_read_more_string( $id ) . '</a>';
	}


	/**
	 * Add the screen reader styles.
	 *
	 * @return void
	 */
	public function add_screen_reader_styles() {
		?>
		<style>
			.edac-screen-reader-text {
				position: absolute;
				clip: rect(1px, 1px, 1px, 1px);
				clip-path: polygon(0 0, 0 0, 0 0);
				height: 1px;
				width: 1px;
				overflow: hidden;
				white-space: nowrap;
			}
		</style>
		<?php
	}

	/**
	 * Generate the read more string to add to the links.
	 *
	 * @param int  $id The post ID.
	 * @param bool $never_use_screen_reader Whether to never use the screen reader styles.
	 * @return string
	 */
	private function generate_read_more_string( $id, $never_use_screen_reader = false ) {

		return sprintf(
			'<span class="edac-content-more-title%s">%s</span>',
			! $never_use_screen_reader && get_option( 'edac_fix_add_read_more_title_screen_reader_only', false ) ? ' edac-screen-reader-text' : '',
			wp_kses_post( get_the_title( $id ) )
		);
	}

	/**
	 * Callback for the read more section.
	 *
	 * @return void
	 */
	public function read_more_section_callback() {
		FixesManager::maybe_show_accessibility_ready_conflict_notice();
		?>
		<p><?php esc_html_e( 'This fix adds the post title to the "Read More" links in post lists at the "More" block and in excerpts.', 'accessibility-checker' ); ?></p>
		<?php
	}
}
