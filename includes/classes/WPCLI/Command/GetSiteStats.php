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
					'description' => 'Keys to show in the results. Defaults to all keys.',
					'optional'    => true,
					'default'     => null,
					'repeating'   => true,
				],
			],
		];
	}

	/**
	 * Run the command that gets the stats for the whole site.
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
		$all_stats = $this->get_all_stats();

		if ( ! empty( $arguments['stat'] ) ) {
			$items_to_return = [];
			$requested_stats = explode( ',', $arguments['stat'] );
			foreach ( $requested_stats as $key ) {
				$stats_key = trim( $key );
				if ( ! isset( $all_stats[ $stats_key ] ) ) {
					WP_CLI::error( "Stat key: {$stats_key} not found in stats." );
				}
				$items_to_return[ $stats_key ] = $all_stats[ $stats_key ];
			}
		}

		if ( $items_to_return ) {
			WP_CLI::success( wp_json_encode( $items_to_return, JSON_PRETTY_PRINT ) );
			return;
		}

		WP_CLI::success( wp_json_encode( $all_stats, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Gets the sites from the entire site.
	 *
	 * A limitation is this can only provide the stats for scanned pages, if
	 * some pages are not scanned they are not reflected in the stats. Use the
	 * 'scannable_posts_count' and the 'posts_scanned' values to determine if
	 * the whole site is reflected or not.
	 *
	 * @since 1.15.0
	 *
	 * @throws ExitException If ScanStats class is not found or no stats are found.
	 */
	private function get_all_stats(): array {

		if ( class_exists( 'EDAC\Admin\Scans_Stats' ) === false ) {
			WP_CLI::error( "Scans_Stats class not found, is Accessibility Checker installed and activated?.\n" );
		}

		$stats = ( new Scans_Stats() )->summary();

		if ( empty( $stats ) ) {
			WP_CLI::error( "No stats found.\n" );
		}

		return $stats;
	}
}