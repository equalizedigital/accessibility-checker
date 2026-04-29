<?php
/**
 * WP-CLI command to toggle an accessibility fix on or off.
 *
 * @since 1.16.0
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
use WP_CLI;

/**
 * Toggles an accessibility fix on or off.
 *
 * @since 1.16.0
 */
class ToggleFix implements CLICommandInterface {

	/**
	 * The WP-CLI instance.
	 *
	 * This lets a mock be passed in for testing.
	 *
	 * @var mixed|WP_CLI
	 */
	private $wp_cli;

	/**
	 * Constructor.
	 *
	 * @since 1.16.0
	 *
	 * @param mixed|WP_CLI $wp_cli The WP-CLI instance.
	 */
	public function __construct( $wp_cli = null ) {
		$this->wp_cli = $wp_cli ?? new WP_CLI();
	}

	/**
	 * Get the name of the command.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'accessibility-checker toggle-fix';
	}

	/**
	 * Get the short name of the command.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	public static function get_shortname(): string {
		return 'edac toggle-fix';
	}

	/**
	 * Get the arguments for the command.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public static function get_args(): array {
		return [
			'shortdesc' => __( 'Toggle an accessibility fix on or off.', 'accessibility-checker' ),
			'synopsis'  => [
				[
					'type'        => 'positional',
					'name'        => 'slug',
					'description' => __( 'The slug of the fix to toggle.', 'accessibility-checker' ),
					'optional'    => false,
					'repeating'   => false,
				],
				[
					'type'        => 'flag',
					'name'        => 'enable',
					'description' => __( 'Explicitly enable the fix instead of toggling.', 'accessibility-checker' ),
					'optional'    => true,
				],
				[
					'type'        => 'flag',
					'name'        => 'disable',
					'description' => __( 'Explicitly disable the fix instead of toggling.', 'accessibility-checker' ),
					'optional'    => true,
				],
			],
		];
	}

	/**
	 * Toggle an accessibility fix on or off.
	 *
	 * ## EXAMPLES
	 *
	 *     wp accessibility-checker toggle-fix skip_link
	 *     wp accessibility-checker toggle-fix skip_link --enable
	 *     wp accessibility-checker toggle-fix skip_link --disable
	 *
	 * @since 1.16.0
	 *
	 * @param array $options   Positional args. The first element should be the fix slug.
	 * @param array $arguments Associative args. May include 'enable' or 'disable' flags.
	 *
	 * @return void
	 */
	public function __invoke( array $options = [], array $arguments = [] ) {
		$slug = $options[0] ?? '';

		if ( empty( $slug ) ) {
			$this->wp_cli::error( esc_html__( 'No fix slug provided.', 'accessibility-checker' ) );
			return;
		}

		$fixes_manager = FixesManager::get_instance();
		$fix           = $fixes_manager->get_fix( $slug );

		if ( ! $fix ) {
			$this->wp_cli::error(
				sprintf(
					// translators: %s: a fix slug that was not found.
					esc_html__( 'Fix "%s" not found. Use the list-fixes command to see available fixes.', 'accessibility-checker' ),
					$slug
				)
			);
			return;
		}

		$main_options = $this->get_main_checkboxes( $fix );

		if ( empty( $main_options ) ) {
			$this->wp_cli::error(
				sprintf(
					// translators: %s: a fix slug.
					esc_html__( 'No toggleable settings found for fix "%s".', 'accessibility-checker' ),
					$slug
				)
			);
			return;
		}

		// Check whether this fix is an upsell (pro-only) item.
		if ( $this->is_upsell_fix( $fix ) ) {
			$this->wp_cli::error(
				sprintf(
					// translators: %s: a fix slug.
					esc_html__( 'Fix "%s" requires the pro version of Accessibility Checker.', 'accessibility-checker' ),
					$slug
				)
			);
			return;
		}

		// Determine the desired state using the first main option's current value.
		$current_value = (bool) get_option( $main_options[0], false );

		if ( ! empty( $arguments['enable'] ) ) {
			$new_value = true;
		} elseif ( ! empty( $arguments['disable'] ) ) {
			$new_value = false;
		} else {
			// Default behaviour: toggle the current state.
			$new_value = ! $current_value;
		}

		foreach ( $main_options as $option_key ) {
			update_option( $option_key, $new_value ? '1' : '0' );
		}

		$state = $new_value
			? esc_html__( 'enabled', 'accessibility-checker' )
			: esc_html__( 'disabled', 'accessibility-checker' );

		$this->wp_cli::success(
			sprintf(
				// translators: 1: a fix slug, 2: 'enabled' or 'disabled'.
				esc_html__( 'Fix "%1$s" has been %2$s.', 'accessibility-checker' ),
				$slug,
				$state
			)
		);
	}

	/**
	 * Get all main checkbox option keys for a fix (those without a conditional dependency).
	 *
	 * @since 1.16.0
	 *
	 * @param \EqualizeDigital\AccessibilityChecker\Fixes\FixInterface $fix The fix instance.
	 *
	 * @return array<int, string> Array of option keys.
	 */
	private function get_main_checkboxes( $fix ): array {
		$main_options = [];
		foreach ( $fix->get_fields_array() as $option_key => $field ) {
			if ( isset( $field['type'] ) && 'checkbox' === $field['type'] && ! isset( $field['condition'] ) ) {
				$main_options[] = $option_key;
			}
		}
		return $main_options;
	}

	/**
	 * Check whether the fix is an upsell (pro-only) item not available in the current installation.
	 *
	 * @since 1.16.0
	 *
	 * @param \EqualizeDigital\AccessibilityChecker\Fixes\FixInterface $fix The fix instance.
	 *
	 * @return bool True if the fix is an upsell item, false otherwise.
	 */
	private function is_upsell_fix( $fix ): bool {
		foreach ( $fix->get_fields_array() as $field ) {
			if ( isset( $field['type'] ) && 'checkbox' === $field['type'] && ! isset( $field['condition'] ) ) {
				return ! empty( $field['upsell'] );
			}
		}

		return false;
	}
}
