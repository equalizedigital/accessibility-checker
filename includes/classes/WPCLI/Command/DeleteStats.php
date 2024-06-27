<?php
/**
 * Delete stats for a post.
 *
 * @since 1.15.0
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\WPCLI\Command;

use EDAC\Admin\Purge_Post_Data;
use WP_CLI;
use WP_CLI\ExitException;

/**
 * Deletes stats for a given post ID.
 *
 * @package PattonWebz\AccessibilityCheckerCLI\Command
 */
class DeleteStats implements CLICommandInterface {

	/**
	 * Get the name of the command
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return 'accessibility-checker delete-stats';
	}

	/**
	 * Get the arguments for the command
	 *
	 * @return array
	 */
	public static function get_args(): array {
		return [
			'synopsis' => [
				[
					'type'        => 'positional',
					'name'        => 'post_id',
					'description' => 'The ID of the post to delete stats for.',
					'optional'    => true,
					'default'     => 0,
					'repeating'   => false,
				],
			],
		];
	}

	/**
	 * Run the command to delete stats for a given post id.
	 *
	 * @param array $options This is the positional argument, the post ID in this case.
	 * @param array $arguments This is the associative argument, not used in this command but kept for consistency with cli commands using this pattern.
	 *
	 * @return void
	 * @throws ExitException If the post ID is not provided, does not exist, or the class we need isn't available.
	 */
	public function __invoke( array $options = [], array $arguments = [] ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$post_id = $options[0] ?? null;

		if ( 0 === $post_id ) {
			WP_CLI::error( "No Post ID provided, getting all stats not implemented yet.\n" );
		}

		$post_exists = (bool) get_post( $post_id );

		if ( ! $post_exists ) {
			WP_CLI::error( "Post ID {$post_id} does not exist.\n" );
		}

		if ( class_exists( 'EDAC\Admin\Purge_Post_Data' ) === false ) {
			WP_CLI::error( "Purge_Post_Data class not found, is Accessibility Checker installed and activated?\n" );
		}

		Purge_Post_Data::delete_post( $post_id );
		WP_CLI::success( "Stats of {$post_id} deleted! \n" );
	}
}
