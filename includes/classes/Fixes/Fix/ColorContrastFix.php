<?php
/**
 * Color Contrast Fix Class
 *
 * @package accessibility-checker
 */

namespace EqualizeDigital\AccessibilityChecker\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies per-element color overrides to fix color contrast issues.
 *
 * Stores user-defined color overrides as post meta and outputs them as
 * inline CSS in wp_head so they apply to all site visitors. The original
 * detected colors are preserved in the issue's extra_data and shown alongside
 * the user-modified values in the frontend highlighter panel.
 *
 * @since 1.17.0
 */
class ColorContrastFix implements FixInterface {

	/**
	 * Post meta key used to store color contrast fixes per post.
	 *
	 * @var string
	 */
	const META_KEY = '_edac_color_contrast_fixes';

	/**
	 * The slug of the fix.
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return 'color_contrast';
	}

	/**
	 * The nicename for the fix.
	 *
	 * @return string
	 */
	public static function get_nicename(): string {
		return __( 'Color Contrast Fix', 'accessibility-checker' );
	}

	/**
	 * The type of fix.
	 *
	 * @return string
	 */
	public static function get_type(): string {
		return 'frontend';
	}

	/**
	 * Register REST routes and frontend CSS output.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'wp_head', [ $this, 'output_color_fix_css' ] );
	}

	/**
	 * Register REST API routes for saving and deleting color fixes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'edac/v1',
			'/color-fix',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_color_fix' ],
				'permission_callback' => function ( $request ) {
					$post_id = absint( $request->get_param( 'post_id' ) );
					return $post_id && current_user_can( 'edit_post', $post_id );
				},
				'args'                => [
					'issue_id'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'post_id'     => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'new_fg'      => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_hex_color',
						'validate_callback' => function ( $value ) {
							return (bool) preg_match( '/^#[0-9A-Fa-f]{6}$/', (string) $value );
						},
					],
					'new_bg'      => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_hex_color',
						'validate_callback' => function ( $value ) {
							return (bool) preg_match( '/^#[0-9A-Fa-f]{6}$/', (string) $value );
						},
					],
					'original_fg' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'original_bg' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'selector'    => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			'edac/v1',
			'/color-fix/(?P<id>[0-9]+)',
			[
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_color_fix' ],
				'permission_callback' => function ( $request ) {
					$post_id = absint( $request->get_param( 'post_id' ) );
					return $post_id && current_user_can( 'edit_post', $post_id );
				},
				'args'                => [
					'id'      => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'post_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Save a color fix for a specific issue.
	 *
	 * Stores the new foreground and background colors alongside the original
	 * detected colors in post meta so they can be output as CSS and used to
	 * populate the editor UI.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_color_fix( \WP_REST_Request $request ) {
		$issue_id    = absint( $request->get_param( 'issue_id' ) );
		$post_id     = absint( $request->get_param( 'post_id' ) );
		$new_fg      = sanitize_hex_color( $request->get_param( 'new_fg' ) );
		$new_bg      = sanitize_hex_color( $request->get_param( 'new_bg' ) );
		$original_fg = sanitize_text_field( $request->get_param( 'original_fg' ) ?? '' );
		$original_bg = sanitize_text_field( $request->get_param( 'original_bg' ) ?? '' );
		$selector    = sanitize_text_field( $request->get_param( 'selector' ) );

		if ( ! $new_fg || ! $new_bg ) {
			return new \WP_Error(
				'invalid_color',
				__( 'Invalid color value provided.', 'accessibility-checker' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! $selector || ! $this->is_safe_css_selector( $selector ) ) {
			return new \WP_Error(
				'invalid_selector',
				__( 'A valid CSS selector is required.', 'accessibility-checker' ),
				[ 'status' => 400 ]
			);
		}

		$fixes = get_post_meta( $post_id, self::META_KEY, true );
		if ( ! is_array( $fixes ) ) {
			$fixes = [];
		}

		$fixes[ $issue_id ] = [
			'issue_id'    => $issue_id,
			'selector'    => $selector,
			'original_fg' => $original_fg,
			'original_bg' => $original_bg,
			'new_fg'      => $new_fg,
			'new_bg'      => $new_bg,
		];

		update_post_meta( $post_id, self::META_KEY, $fixes );

		return rest_ensure_response(
			[
				'success' => true,
				'fix'     => $fixes[ $issue_id ],
			]
		);
	}

	/**
	 * Delete a color fix for a specific issue, reverting to the original colors.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_color_fix( \WP_REST_Request $request ) {
		$issue_id = absint( $request['id'] );
		$post_id  = absint( $request->get_param( 'post_id' ) );

		$fixes = get_post_meta( $post_id, self::META_KEY, true );
		if ( ! is_array( $fixes ) ) {
			return rest_ensure_response( [ 'success' => true ] );
		}

		unset( $fixes[ $issue_id ] );
		update_post_meta( $post_id, self::META_KEY, $fixes );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Output inline CSS in wp_head for all saved color contrast fixes on the current page.
	 *
	 * Each fix overrides the foreground (color) and background-color of the
	 * targeted element so that the corrected contrast is visible to all visitors.
	 *
	 * @return void
	 */
	public function output_color_fix_css(): void {
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$fixes = get_post_meta( $post_id, self::META_KEY, true );
		if ( empty( $fixes ) || ! is_array( $fixes ) ) {
			return;
		}

		$css_rules = [];
		foreach ( $fixes as $fix ) {
			if ( empty( $fix['selector'] ) || empty( $fix['new_fg'] ) || empty( $fix['new_bg'] ) ) {
				continue;
			}

			$selector = $fix['selector'];
			$new_fg   = sanitize_hex_color( $fix['new_fg'] );
			$new_bg   = sanitize_hex_color( $fix['new_bg'] );

			if ( ! $new_fg || ! $new_bg || ! $this->is_safe_css_selector( $selector ) ) {
				continue;
			}

			$css_rules[] = sprintf(
				'%s { color: %s !important; background-color: %s !important; }',
				// Selector was validated on save; output directly.
				$selector, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_attr( $new_fg ),
				esc_attr( $new_bg )
			);
		}

		if ( empty( $css_rules ) ) {
			return;
		}
		?>
		<style id="edac-color-contrast-fixes">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo implode( "\n\t\t", $css_rules );
		?>

		</style>
		<?php
	}

	/**
	 * Get all saved color contrast fixes for a given post.
	 *
	 * Keys are issue IDs.
	 *
	 * @param int $post_id The post ID.
	 * @return array<int, array<string, string>>
	 */
	public static function get_color_fixes( int $post_id ): array {
		$fixes = get_post_meta( $post_id, self::META_KEY, true );
		return is_array( $fixes ) ? $fixes : [];
	}

	/**
	 * Get the settings fields for the fix.
	 *
	 * Color contrast fixes are per-issue and have no global settings fields.
	 *
	 * @param array $fields Existing fields.
	 * @return array
	 */
	public function get_fields_array( array $fields = [] ): array {
		return $fields;
	}

	/**
	 * Run the fix – no-op; output is handled via register().
	 *
	 * @return void
	 */
	public function run(): void {}

	/**
	 * Check that a CSS selector string contains only safe characters.
	 *
	 * Prevents CSS injection when stored selectors are output in wp_head.
	 *
	 * @param string $selector The selector to validate.
	 * @return bool
	 */
	private function is_safe_css_selector( string $selector ): bool {
		return (bool) preg_match( '/^[a-zA-Z0-9\s\-_#:.[\]=",\'*>+~^$|()%@]+$/', $selector );
	}
}
