<?php
/**
 * Get stats for the entire site.
 *
 * @since 1.15.0
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

use EDAC\Admin\Scans_Stats;
use WP_CLI;
use WP_CLI\ExitException;

/**
 * Get stats for the entire site.
 *
 * @since 1.15.0
 *
 * @package AccessibilityCheckerCLI
 */
class GetSiteStats implements CLICommandInterface {

	/**
	 * The WP-CLI instance.
	 *
	 * This lets a mock be passed in for testing.
	 *
	 * @var mixed|WP_CLI
	 */
	private $wp_cli;

	/**
	 * GetStats constructor.
	 *
	 * @param mixed|WP_CLI $wp_cli The WP-CLI instance.
	 */
	public function __construct( $wp_cli = null ) {
		$this->wp_cli = $wp_cli ?? new WP_CLI();
	}

	/**
	 * Get the name of the command.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'accessibility-checker get-site-stats';
	}

	/**
	 * Get the arguments for the command
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	public static function get_args(): array {
		return [
			'synopsis' => [
				[
					'type'        => 'assoc',
					'name'        => 'stat',
					'description' => esc_html__( 'Keys to show in the results. Defaults to all keys.', 'accessibility-checker' ),
					'optional'    => true,
					'default'     => null,
					'repeating'   => true,
				],
				[
					'type'        => 'flag',
					'name'        => 'clear-cache',
					'description' => esc_html__( 'Clear the cache before retrieving the stats (can be intensive).', 'accessibility-checker' ),
					'repeating'   => false,
					'optional'    => true,
				],
			],
		];
	}

	/**
	 * Gets the accessibility-checker stats for the whole site. Use the --clear-cache flag to clear the cache before retrieving the stats.
	 *
	 * @since 1.15.0
	 *
	 * @param array $options The positional argument, none is this case.
	 * @param array $arguments The associative arguments, the stat keys in this case.
	 *
	 * @return void
	 * @throws ExitException If the post ID does not exist, or the class we need isn't available.
	 */
	public function __invoke( array $options = [], array $arguments = [] ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! empty( $arguments['clear-cache'] ) ) {
			// Clear the cache.
			( new Scans_Stats() )->clear_cache();
		}

		$all_stats = ( new Scans_Stats() )->summary();

		if ( ! empty( $arguments['stat'] ) ) {
			$items_to_return = [];
			$requested_stats = explode( ',', $arguments['stat'] );
			foreach ( $requested_stats as $key ) {
				$stats_key = trim( $key );
				if ( ! isset( $all_stats[ $stats_key ] ) ) {
					$this->wp_cli::error(
						sprintf(
							// translators: 1: a stat key that was requested but not found.
							esc_html__( 'Stat key: %1$s not found in stats.', 'accessibility-checker' ),
							$stats_key
						)
					);
					return;
				}
				$items_to_return[ $stats_key ] = $all_stats[ $stats_key ];
			}
		}

		if ( isset( $items_to_return ) ) {
			$this->wp_cli::success( wp_json_encode( $items_to_return, JSON_PRETTY_PRINT ) );
			return;
		}

		$this->wp_cli::success( wp_json_encode( $all_stats, JSON_PRETTY_PRINT ) );
	}
}
