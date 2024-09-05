<?php
/**
 * Fix missing or empty page titles.
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Allows the user to add a title to the page <title> tag if empty or missing.
 *
 * @since 1.16.0
 */
class AddMissingOrEmptyPageTitleFix implements FixInterface {

	/**
	 * The full title of the page.
	 *
	 * @var string
	 */
	private $full_title = '';

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'missing_or_empty_page_title';
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
	 * Registers everything needed for the lang and dir attributes on the html element.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter(
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {

				$fields['edac_fix_add_missing_or_empty_page_title'] = [
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Add missing page title', 'accessibility-checker' ),
					'labelledby'  => 'add_missing_or_empty_page_title',
					'description' => esc_html__( 'Adds a `<title>` tag to the page if it is missing or empty.', 'accessibility-checker' ),
				];

				return $fields;
			}
		);
	}

	/**
	 * Run the fix for adding a missing or empty page title.
	 *
	 * @return void
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_add_missing_or_empty_page_title', false ) ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		add_filter( 'document_title', [ $this, 'add_page_title_if_missing_or_empty' ], 100, 2 );

		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) {
				$data[ $this->get_slug() ] = [
					'enabled'          => true,
					'title_seporator'  => apply_filters( 'document_title_separator', '-' ),
					'site_name'        => get_bloginfo( 'name' ),
					'site_description' => get_bloginfo( 'description' ),
					'post_title'       => $this->full_title ? $this->full_title : '',
				];
				return $data;
			}
		);
	}

	/**
	 * Add a page title if missing or empty.
	 *
	 * Trys to find a method that will create unique titles rather than just using the site title.
	 *
	 * @param string $title The title parts.
	 * @param string $sep The separator.
	 *
	 * @return array
	 */
	public function add_page_title_if_missing_or_empty( $title, $sep = '-' ) {

		// If we have a title, nothing is needing done.
		if ( $title && ! empty( $title ) ) {
			$this->full_title = $title;
			return $title;
		}

		// The home page and front page needs special handling.
		if ( is_home() || is_front_page() ) {
			$site_and_desc = get_bloginfo( 'name' );

			$description = get_bloginfo( 'description' );

			if ( $description ) {
				$site_and_desc .= " {$sep} {$description}";
			}

			// Trim it so that it's not more than 80 characters.
			if ( strlen( $site_and_desc ) > 80 ) {
				$site_and_desc = substr( $site_and_desc, 0, 80 ) . '&hellip;';
			}

			$this->full_title = $site_and_desc;
			return $this->full_title;
		}

		// If we have a post title, use it as the title.
		$post_title = get_the_title();
		if ( $post_title ) {
			$this->full_title = $post_title;
			return $this->full_title;
		}

		// Try find a heading in the content to use as a title.
		$content = get_post_field( 'post_content', get_the_ID() );
		$matches = [];
		preg_match( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches );
		if ( isset( $matches[1] ) ) {
			$content_header_title = wp_strip_all_tags( $matches[1] ) . " {$sep} " . get_bloginfo( 'name' );
			// Trim it so that it's not more than 80 characters.
			if ( strlen( $content_header_title ) > 80 ) {
				$content_header_title = substr( $content_header_title, 0, 80 ) . '&hellip;';
			}
			$this->full_title = $content_header_title;
			return $content_header_title;
		}

		// By this point we haven't been able to find any useful title, will need to rely on JS to find an appropriate title.
		return $title;
	}
}
