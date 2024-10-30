<?php
/**
 * Skip Link Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Allows the user to add a skip link to the site if their theme does not already include them.
 *
 * @since 1.16.0
 */
class SkipLinkFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'skip_link';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Add Skip Links', 'accessibility-checker' );
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
	 * Registers everything needed for the skip link fix.
	 *
	 * @return void
	 */
	public function register(): void {

		add_filter(
			'edac_filter_fixes_settings_sections',
			function ( $sections ) {
				$sections['skip_link'] = [
					'title'    => esc_html__( 'Skip Link', 'accessibility-checker' ),
					'callback' => [ $this, 'skip_link_section_callback' ],
				];

				return $sections;
			}
		);

		add_filter(
			'edac_filter_fixes_settings_fields',
			[ $this, 'get_fields_array' ]
		);
	}

	/**
	 * Returns the settings fields for the skip link fix.
	 *
	 * @param array $fields The array of fields that are already registered, if any.
	 *
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {

		$fields['edac_fix_add_skip_link'] = [
			'label'       => esc_html__( 'Enable Skip Link', 'accessibility-checker' ),
			'type'        => 'checkbox',
			'labelledby'  => 'add_skip_link',
			'description' => esc_html__( 'Add a skip link to all site pages, allowing users to skip directly to the main content.', 'accessibility-checker' ),
			'section'     => 'skip_link',
			'fix_slug'    => $this->get_slug(),
			'group_name'  => $this->get_nicename(),
			'help_id'     => 8638,
		];

		$fields['edac_fix_add_skip_link_target_id'] = [
			'label'             => esc_html__( 'Main Content Target (required)', 'accessibility-checker' ),
			'type'              => 'text',
			'labelledby'        => 'skip_link_target_id',
			'description'       => esc_html__( 'Define the ID(s) of the main content area(s) to be targeted by skip links. Enter multiple IDs separated by commas; the system will cascade through the list to find the appropriate one for each page.', 'accessibility-checker' ),
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'skip_link',
			'condition'         => 'edac_fix_add_skip_link',
			'required_when'     => 'edac_fix_add_skip_link',
			'fix_slug'          => $this->get_slug(),
		];

		$fields['edac_fix_add_skip_link_nav_target_id'] = [
			'label'             => esc_html__( 'Navigation Target', 'accessibility-checker' ),
			'type'              => 'text',
			'labelledby'        => 'skip_link_nav_target_id',
			'description'       => __( 'Set the ID attribute of the navigation element. This is useful if your main navigation contains actions that most site visitors would want to take such as login or search features.', 'accessibility-checker' ),
			'sanitize_callback' => 'sanitize_text_field',
			'section'           => 'skip_link',
			'condition'         => 'edac_fix_add_skip_link',
			'fix_slug'          => $this->get_slug(),
		];

		return $fields;
	}

	/**
	 * Run the fix for adding the skip link to the site.
	 */
	public function run() {
		if ( ! get_option( 'edac_fix_add_skip_link', false ) ) {
			return null;
		}

		add_action( 'wp_body_open', [ $this, 'add_skip_link' ] );

		$targets_string = get_option( 'edac_fix_add_skip_link_target_id', '' );
		if ( ! $targets_string ) {
			return;
		}

		$targets_list = explode( ',', $targets_string );

		foreach ( $targets_list as $target ) {
			// trim whitespace and any leading '#'.
			$trimmed = ltrim( trim( $target ), '#' );
			if ( empty( $trimmed ) ) {
				continue;
			}
			$targets[] = '#' . $trimmed;
		}
		add_filter(
			'edac_filter_frontend_fixes_data',
			function ( $data ) use ( $targets ) {
				$data['skip_link'] = [
					'enabled' => true,
					'targets' => $targets,
				];
				return $data;
			}
		);
	}

	/**
	 * Injects the style rules for the skip link.
	 *
	 * @return void
	 */
	public function add_skip_link_styles() {
		?>
		<style id="edac-fix-skip-link-styles">
			.edac-bypass-block {
				border: 0;
				clip: rect(1px, 1px, 1px, 1px);
				clip-path: inset(50%);
				height: 1px;
				margin: -1px;
				overflow: hidden;
				padding: 0;
				position: absolute !important;
				width: 1px;
				word-wrap: normal !important;
			}

			.edac-bypass-block:focus-within {
				background-color: #ececec;
				clip: auto !important;
				-webkit-clip-path: none;
				clip-path: none;
				display: block;
				font-size: 1rem;
				height: auto;
				left: 5px;
				line-height: normal;
				padding: 8px 22px 10px;
				top: 5px;
				width: auto;
				z-index: 100000;
			}

			.admin-bar .edac-bypass-block,
			.admin-bar .edac-bypass-block:focus-within {
				top: 37px;
			}

			@media screen and (max-width: 782px) {
				.admin-bar .edac-bypass-block,
				.admin-bar .edac-bypass-block:focus-within {
					top: 51px;
				}
			}

			a.edac-bypass-block {
				display: block;
				margin: 0.5rem 0;
				color: #444;
				text-decoration: underline;
			}

			a.edac-bypass-block:hover,
			a.edac-bypass-block:focus {
				text-decoration: none;
				color: #006595;
			}

			a.edac-bypass-block:focus {
				outline: 2px solid #000;
				outline-offset: 2px;
			}
		</style>
		<?php
	}

	/**
	 * Callback for the skip link section.
	 *
	 * @return void
	 */
	public function skip_link_section_callback() {
		?>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					// translators: %1$s: opening anchor tag, %2$s: closing anchor tag.
					__( 'If your theme is not already adding a skip link that allows keyboard users to bypass the navigation and quickly jump to the main content, enable skip links here. %1$sLearn more about skip links.%2$s', 'accessibility-checker' ),
					'<a href="' . esc_url( 'https://equalizedigital.com/how-to-make-your-wordpress-site-more-accessible-with-skip-links/' ) . '">',
					'</a>'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Adds the skip link code to the page.
	 *
	 * @return void
	 */
	public function add_skip_link() {

		$targets_string = get_option( 'edac_fix_add_skip_link_target_id', '' );

		$nav_targets_string = get_option( 'edac_fix_add_skip_link_nav_target_id', '' );

		if ( ! $targets_string && ! $nav_targets_string ) {
			return;
		}
		?>
		<template id="skip-link-template">
				<?php if ( $targets_string ) : ?>
					<a class="edac-skip-link--content edac-bypass-block" href=""><?php esc_html_e( 'Skip to content', 'accessibility-checker' ); ?></a>
				<?php endif; ?>
			<?php
			if ( $nav_targets_string ) :
				?>
					<?php
					if ( $nav_targets_string ) :
						$nav_target = ltrim( trim( $nav_targets_string ), '#' );
						?>
						<a class="edac-skip-link--navigation edac-bypass-block" href="#<?php echo esc_attr( $nav_target ); ?>"><?php esc_html_e( 'Skip to navigation', 'accessibility-checker' ); ?></a>
					<?php endif; ?>
			<?php endif; ?>
			<?php $this->add_skip_link_styles(); ?>
		</template>
		<?php
	}
}
