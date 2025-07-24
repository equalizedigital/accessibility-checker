<?php
/**
 * WP-CLI command to run orphaned issues cleanup.
 *
 * @since 1.15.0
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

use EDAC\Admin\Orphaned_Issues_Cleanup;
use WP_CLI;

/**
 * Runs the orphaned issues cleanup process.
 */
class CleanupOrphanedIssues implements CLICommandInterface {
	/**
	 * The WP-CLI instance.
	 *
	 * @var mixed|WP_CLI
	 */
	private $wp_cli;

	/**
	 * Constructor.
	 *
	 * @param mixed|WP_CLI $wp_cli The WP-CLI instance.
	 */
	public function __construct( $wp_cli = null ) {
		$this->wp_cli = $wp_cli ?? new WP_CLI();
	}

	/**
	 * Get the name of the command.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'accessibility-checker cleanup-orphaned-issues';
	}

	/**
	 * Get the arguments for the command.
	 *
	 * @return array
	 */
	public static function get_args(): array {
		return [
			'batch' => [
				'type'        => 'assoc',
				'description' => 'Number of orphaned posts to process in one batch.',
				'optional'    => true,
				'default'     => null,
			],
			'sleep' => [
				'type'        => 'assoc',
				'description' => 'Seconds to sleep between deletions (default: 0).',
				'optional'    => true,
				'default'     => 0,
			],
		];
	}

	/**
	 * Run the orphaned issues cleanup process with feedback.
	 *
	 * ## EXAMPLES
	 *
	 *     wp accessibility-checker cleanup-orphaned-issues
	 *
	 * @param array $options    Positional args passed to the command.
	 * @param array $arguments  Associative args passed to the command.
	 *
	 * @return void
	 */
	public function __invoke( array $options = [], array $arguments = [] ) {
		$cleanup = new Orphaned_Issues_Cleanup();
		if ( isset( $arguments['batch'] ) && is_numeric( $arguments['batch'] ) && (int) $arguments['batch'] > 0 ) {
			$cleanup->set_batch_size( (int) $arguments['batch'] );
		}
		$sleep    = ( isset( $arguments['sleep'] ) && is_numeric( $arguments['sleep'] ) && $arguments['sleep'] >= 0 ) ? (float) $arguments['sleep'] : 0.0;
		$orphaned = $cleanup->get_orphaned_post_ids();

		if ( empty( $orphaned ) ) {
			$this->wp_cli::success( 'No orphaned issues found.' );
			return;
		}

		$this->wp_cli::log( sprintf( 'Found %d orphaned post IDs', count( $orphaned ) ) );
		// wait 2 seconds before starting the cleanup to avoid overwhelming the server.
		sleep( 2 );

		foreach ( $orphaned as $post_id ) {
			$this->wp_cli::log( " - Deleting issues for post ID: $post_id" );
			$cleanup->delete_orphaned_post( (int) $post_id );
			if ( $sleep > 0 ) {
				usleep( (int) ( $sleep * 1000000 ) ); // Convert seconds (float) to microseconds.
			}
		}
		$this->wp_cli::success( sprintf( 'Orphaned issues cleanup complete. %d post(s) processed.', count( $orphaned ) ) );
	}
}
