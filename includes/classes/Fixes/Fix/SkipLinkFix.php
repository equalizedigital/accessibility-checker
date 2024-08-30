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
 * @since x.x.x
 */
class SkipLinkFix implements FixInterface {

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'skip-link';
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
			'edac_filter_fixes_settings_fields',
			function ( $fields ) {
				$fields['edac_fix_add_skip_link'] = [
					'label'       => esc_html__( 'Add Skip Link', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'add_skip_link',
					'description' => esc_html__( 'Add a skip link to all of your site pages.', 'accessibility-checker' ),
				];

				$fields['edac_fix_add_skip_link_target_id'] = [
					'label'             => esc_html__( 'Skip Link Target ID', 'accessibility-checker' ),
					'type'              => 'text',
					'labelledby'        => 'skip_link_target_id',
					'description'       => esc_html__( 'The ID for the skip links to target. Enter multiple ids seporated by commas and it will cascade through the list to find the appropriate one for that page.', 'accessibility-checker' ),
					'sanitize_callback' => 'sanitize_text_field',
					'condition'         => 'edac_fix_add_skip_link',
				];

				return $fields;
			}
		);

		if ( get_option( 'edac_fix_add_skip_link', false ) ) {
			add_action( 'wp_body_open', [ $this, 'add_skip_link' ] );

			$targets_string = get_option( 'edac_fix_add_skip_link_target_id', '' );
			if ( ! $targets_string ) {
				return;
			}

			$targets_list = explode( ',', $targets_string );

			foreach ( $targets_list as $target ) {
				// trim whitespace the target.
				$trimmed = trim( ltrim( $target, '#' ) );
				if ( empty( $trimmed ) ) {
					continue;
				}
				$targets[] = '#' . trim( ltrim( $target, '#' ) );
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
	}

	/**
	 * Injects the style rules for the skip link.
	 *
	 * @return void
	 */
	public function add_skip_link_styles() {
		?>
		<style id="edac-fix-skip-link-styles">
			.edac-skip-link {
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
			.edac-skip-link:focus {
				background-color: #ddd;
				clip: auto !important;
				-webkit-clip-path: none;
				clip-path: none;
				color: #444;
				display: block;
				font-size: 1em;
				height: auto;
				left: 5px;
				line-height: normal;
				padding: 15px 23px 14px;
				text-decoration: none;
				top: 5px;
				width: auto;
				z-index: 100000;
			}
		</style>
		<?php
	}

	/**
	 * Adds the skip link code to the page.
	 *
	 * @return void
	 */
	public function add_skip_link() {

		$targets_string = get_option( 'edac_fix_add_skip_link_target_id', '' );
		if ( ! $targets_string ) {
			return;
		}
		?>
		<template id="skip-link-template">
			<a class="edac-skip-link" href=""><?php esc_html_e( 'Skip to content', 'accessibility-checker' ); ?></a>
			<?php $this->add_skip_link_styles(); ?>
		</template>

		<?php
	}
}
