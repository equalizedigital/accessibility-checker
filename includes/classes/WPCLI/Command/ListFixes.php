<?php
/**
 * WP-CLI command to list all available accessibility fixes.
 *
 * @since 1.16.0
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;
use WP_CLI;

/**
 * Lists all available accessibility fixes and their current status.
 *
 * @since 1.16.0
 */
class ListFixes implements CLICommandInterface {

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
		return 'accessibility-checker list-fixes';
	}

	/**
	 * Get the short name of the command.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	public static function get_shortname(): string {
		return 'edac list-fixes';
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
			'shortdesc' => __( 'List all available accessibility fixes and their current status.', 'accessibility-checker' ),
		];
	}

	/**
	 * List all available accessibility fixes and their status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp accessibility-checker list-fixes
	 *
	 * @since 1.16.0
	 *
	 * @param array $options   Positional args (unused).
	 * @param array $arguments Associative args (unused).
	 *
	 * @return void
	 */
	public function __invoke( array $options = [], array $arguments = [] ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$fixes_manager  = FixesManager::get_instance();
		$fixes_settings = $fixes_manager->get_fixes_settings();

		if ( empty( $fixes_settings ) ) {
			$this->wp_cli::warning( esc_html__( 'No fixes found.', 'accessibility-checker' ) );
			return;
		}

		$items = [];
		foreach ( $fixes_settings as $slug => $settings ) {
			$fix         = $fixes_manager->get_fix( $slug );
			$main_option = $this->get_primary_option( $fix );
			$is_upsell   = $this->is_upsell_fix( $fix );

			// Skip upsell (pro-only) fixes when the fix is not available in the current installation.
			if ( $is_upsell ) {
				continue;
			}

			$enabled = $main_option ? (bool) get_option( $main_option, false ) : false;

			$items[] = [
				'slug'   => $slug,
				'name'   => $fix ? $fix::get_nicename() : $slug,
				'status' => $enabled ? 'enabled' : 'disabled',
				'type'   => ( isset( $fix->is_pro ) && $fix->is_pro ) ? 'pro' : 'free',
			];
		}

		if ( empty( $items ) ) {
			$this->wp_cli::warning( esc_html__( 'No fixes found.', 'accessibility-checker' ) );
			return;
		}

		$this->wp_cli::success( wp_json_encode( $items, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Get the primary option key for a fix.
	 *
	 * Returns the first checkbox field key that has no conditional dependency.
	 *
	 * @since 1.16.0
	 *
	 * @param \EqualizeDigital\AccessibilityChecker\Fixes\FixInterface|null $fix The fix instance.
	 *
	 * @return string|null The option key, or null if not found.
	 */
	private function get_primary_option( $fix ): ?string {
		if ( ! $fix ) {
			return null;
		}

		foreach ( $fix->get_fields_array() as $option_key => $field ) {
			if ( isset( $field['type'] ) && 'checkbox' === $field['type'] && ! isset( $field['condition'] ) ) {
				return $option_key;
			}
		}

		return null;
	}

	/**
	 * Check whether the fix is an upsell (pro-only) item not available in the current installation.
	 *
	 * @since 1.16.0
	 *
	 * @param \EqualizeDigital\AccessibilityChecker\Fixes\FixInterface|null $fix The fix instance.
	 *
	 * @return bool True if the fix is an upsell item, false otherwise.
	 */
	private function is_upsell_fix( $fix ): bool {
		if ( ! $fix ) {
			return false;
		}

		foreach ( $fix->get_fields_array() as $field ) {
			if ( isset( $field['type'] ) && 'checkbox' === $field['type'] && ! isset( $field['condition'] ) ) {
				return ! empty( $field['upsell'] );
			}
		}

		return false;
	}
}
