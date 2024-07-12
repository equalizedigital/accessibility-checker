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
					'description' => esc_html__( 'The ID of the post to delete stats for.', 'accessibility-checker' ),
					'optional'    => true,
					'default'     => 0,
					'repeating'   => false,
				],
			],
		];
	}

	/**
	 * Delete the accessibility-checker stats for a given post ID.
	 *
	 * @param array $options This is the positional argument, the post ID in this case.
	 * @param array $arguments This is the associative argument, not used in this command but kept for consistency with cli commands using this pattern.
	 *
	 * @return void
	 * @throws ExitException If the post ID is not provided, does not exist, or the class we need isn't available.
	 */
	public function __invoke( array $options = [], array $arguments = [] ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$post_id = $options[0] ?? 0;

		if ( 0 === $post_id ) {
			$this->wp_cli::error( esc_html__( 'No Post ID provided.', 'accessibility-checker' ) );
		}

		$post_exists = (bool) get_post( $post_id );

		if ( ! $post_exists ) {
			$this->wp_cli::error(
				sprintf(
					// translators: 1: a post ID.
					esc_html__( 'Post ID %1$s does not exist.', 'accessibility-checker' ),
					$post_id
				)
			);
			return;
		}

		Purge_Post_Data::delete_post( $post_id );
		$this->wp_cli::success(
			sprintf(
				// translators: 1: a post ID.
				esc_html__( 'Stats of %1$s deleted.', 'accessibility-checker' ),
				$post_id
			)
		);
	}
}
