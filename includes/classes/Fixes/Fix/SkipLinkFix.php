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
					'label'       => esc_html__( 'Enable Skip Link', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'add_skip_link',
					'description' => esc_html__( 'Add a skip link to all of your site pages.', 'accessibility-checker' ),
				];

				$fields['edac_fix_add_skip_link_always_visible'] = [
					'label'       => esc_html__( 'Always Visible Skip Link', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'add_skip_link_always_visible',
					'description' => esc_html__( 'Make the skip link always visible.', 'accessibility-checker' ),
					'condition'   => 'edac_fix_add_skip_link',
				];

				$fields['edac_fix_add_skip_link_target_id'] = [
					'label'             => esc_html__( 'Skip Link Target ID', 'accessibility-checker' ),
					'type'              => 'text',
					'labelledby'        => 'skip_link_target_id',
					'description'       => esc_html__( 'The ID for the skip links to target the main content, starting with "#". Enter multiple ids seporated by commas and it will cascade through the list to find the appropriate one for that page if you have several different main content areas on your site.', 'accessibility-checker' ),
					'sanitize_callback' => 'sanitize_text_field',
					'condition'         => 'edac_fix_add_skip_link',
				];

				$fields['edac_fix_add_skip_link_nav_target_id'] = [
					'label'             => esc_html__( 'Skip Link Target ID', 'accessibility-checker' ),
					'type'              => 'text',
					'labelledby'        => 'skip_link_nav_target_id',
					'description'       => esc_html__( 'ID attribute for the navigation, starting with "#"', 'accessibility-checker' ),
					'sanitize_callback' => 'sanitize_text_field',
					'condition'         => 'edac_fix_add_skip_link',
				];

				$fields['edac_fix_disable_skip_link_styles'] = [
					'label'       => esc_html__( 'Disable Skip Link Bundled Styles', 'accessibility-checker' ),
					'type'        => 'checkbox',
					'labelledby'  => 'disable_skip_link_styles',
					'description' => esc_html__( 'Disable output of the bundled styles. This makes the "Always Visible Skip Link" setting above irrelevent.', 'accessibility-checker' ),
					'condition'   => 'edac_fix_add_skip_link',
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

			.edac-bypass-block:focus-within,
			.edac-bypass-block-always-visible {
				background-color: #ddd;
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

			.edac-bypass-block > a {
				display: block;
				margin: 0.5rem 0;
				color: #444;
				text-decoration: underline;
			}

			.edac-bypass-block > a:hover,
			.edac-bypass-block > a:focus {
				text-decoration: none;
				color: #006595;
			}

			.edac-bypass-block > a:focus {
				outline: 2px solid #000;
				outline-offset: 2px;
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

		$nav_targets_string = get_option( 'edac_fix_add_skip_link_nav_target_id', '' );

		if ( ! $targets_string && ! $nav_targets_string ) {
			return;
		}
		?>
		<template id="skip-link-template">
			<div class="edac-bypass-block <?php echo get_option( 'edac_fix_add_skip_link_always_visible', false ) ? 'edac-bypass-block-always-visible' : ''; ?>">
				<?php if ( $targets_string ) : ?>
					<a class="edac-skip-link--content" href=""><?php esc_html_e( 'Skip to content', 'accessibility-checker' ); ?></a>
				<?php endif; ?>
				<?php
				if ( $nav_targets_string ) :
					$nav_target = ltrim( trim( $nav_targets_string ), '#' );
					?>
					<a class="edac-skip-link--navigation" href="#<?php echo esc_attr( $nav_target ); ?>"><?php esc_html_e( 'Skip to navigation', 'accessibility-checker' ); ?></a>
				<?php endif; ?>
				<?php get_option( 'edac_fix_disable_skip_link_styles', false ) ? '' : $this->add_skip_link_styles(); ?>
			</div>
		</template>
		<?php
	}
}
