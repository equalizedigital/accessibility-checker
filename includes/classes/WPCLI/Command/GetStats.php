<?php
/**
 * Get stats for a give post ID or all posts.
 *
 * @since 1.15.0
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

use EDAC\Admin\Scans_Stats;
use EDAC\Inc\Summary_Generator;
use WP_CLI;
use WP_CLI\ExitException;

/**
 * Get stats for a give post ID or all posts.
 *
 * @since 1.15.0
 *
 * @package AccessibilityCheckerCLI
 */
class GetStats implements CLICommandInterface {

	/**
	 * An array of valid stats keys.
	 *
	 * @since 1.15.0
	 *
	 * @var array|string[]
	 */
	private array $valid_stats = [
		'passed_tests',
		'errors',
		'warnings',
		'ignored',
		'contrast_errors',
		'content_grade',
		'readability',
		'simplified_summary',
	];

	/**
	 * Get the name of the command.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'accessibility-checker get-stats';
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
					'type'        => 'positional',
					'name'        => 'post_id',
					'description' => 'The ID of the post to get stats for.',
					'optional'    => true,
					'default'     => 0,
					'repeating'   => false,
				],
				[
					'type'        => 'assoc',
					'name'        => 'stat',
					'description' => 'Keys to show in the results. Defaults to all keys. Pass items in as a comma separated list if you want multiple. Valid keys are: ' . implode( ', ', ( new self() )->valid_stats ) . '.',
					'optional'    => true,
					'default'     => null,
					'repeating'   => true,
				],
			],
		];
	}

	/**
	 * Run the command that gets the stats for a post ID or the stats for the whole site.
	 *
	 * @since 1.15.0
	 *
	 * @param array $options The positional argument, the post ID in this case.
	 * @param array $arguments The associative argument, the stat key in this case.
	 *
	 * @return void
	 * @throws ExitException If the post ID does not exist, or the class we need isn't available.
	 */
	public function __invoke( array $options = [], array $arguments = [] ) {
		$post_id = $options[0] ?? null;

		$post_exists = (bool) get_post( $post_id );

		if ( ! $post_exists ) {
			WP_CLI::error( "Post ID {$post_id} does not exist." );
		}

		if ( class_exists( 'EDAC\Inc\Summary_Generator' ) === false ) {
			WP_CLI::error( "Summary_Generator class not found, is Accessibility Checker installed and activated?.\n" );
		}

		$stats = ( new Summary_Generator( $post_id ) )->generate_summary();

		if ( empty( $stats ) ) {
			WP_CLI::error( "No stats found for post ID {$post_id}." );
		}

		if ( 100 === (int) $stats['passed_tests'] && 0 === (int) $stats['ignored'] ) {
			WP_CLI::success( "Either the post is not yet scanned or all tests passed for post ID {$post_id}." );
			return;
		}

		if ( ! empty( $arguments['stat'] ) ) {
			$items_to_return = [];
			$requested_stats = explode( ',', $arguments['stat'] );
			foreach ( $requested_stats as $key ) {
				$stats_key = trim( $key );
				if ( ! in_array( $stats_key, $this->valid_stats, true ) ) {
					WP_CLI::error( "Invalid stat key: {$stats_key}. Valid keys are: " . implode( ', ', $this->valid_stats ) . '.' );
				}
				if ( ! isset( $stats[ $stats_key ] ) ) {
					WP_CLI::error( "Stat key: {$stats_key} not found in stats." );
				}
				$items_to_return[ $stats_key ] = $stats[ $stats_key ];
			}

			if ( $items_to_return ) {
				WP_CLI::success( wp_json_encode( $items_to_return, JSON_PRETTY_PRINT ) );
				return;
			}
		}

		WP_CLI::success( wp_json_encode( $stats, JSON_PRETTY_PRINT ) );
	}
}
