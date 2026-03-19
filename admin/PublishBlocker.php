<?php
/**
 * Class file for the Publish Blocker feature.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles blocking or warning when publishing posts that have accessibility issues.
 *
 * This is a Pro-only feature. All enforcement logic is gated behind edac_is_pro().
 * The class is instantiated for all users so that user_can_bypass() can be called
 * safely to pass bypass state to the Gutenberg sidebar.
 *
 * @since 1.40.0
 */
class PublishBlocker {

	/**
	 * The transient key prefix for storing blocked-publish notices per user.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'edac_publish_blocked_';

	/**
	 * Register hooks. Only registers enforcement hooks when Pro is active and the
	 * feature is enabled in settings.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! edac_is_pro() ) {
			return;
		}

		if ( ! get_option( 'edac_block_publish' ) ) {
			return;
		}

		add_filter( 'wp_insert_post_data', [ $this, 'maybe_block_publish' ], 10, 2 );
		add_action( 'admin_notices', [ $this, 'show_admin_notice' ] );
	}

	/**
	 * Intercepts post saves and reverts the post status to its previous value when
	 * a hard-block is configured and accessibility issues exist.
	 *
	 * Only runs in hard mode — soft mode enforcement is handled on the client side
	 * via the Gutenberg PrePublishPanel; the classic editor has no soft-mode UI.
	 *
	 * @param array $data    The post data about to be saved.
	 * @param array $postarr The raw POST data.
	 * @return array The (possibly modified) post data array.
	 */
	public function maybe_block_publish( array $data, array $postarr ): array {
		// Only enforce in hard mode; soft mode is a UI-only warning.
		if ( 'hard' !== get_option( 'edac_block_publish_mode', 'soft' ) ) {
			return $data;
		}

		// Only intercept when the new status is 'publish'.
		if ( 'publish' !== $data['post_status'] ) {
			return $data;
		}

		// Check post type.
		$block_post_types = (array) get_option( 'edac_block_publish_post_types', [] );
		if ( empty( $block_post_types ) || ! in_array( $data['post_type'], $block_post_types, true ) ) {
			return $data;
		}

		// Allow users who can bypass.
		if ( self::user_can_bypass() ) {
			return $data;
		}

		// New posts (no ID yet) cannot have scan data — allow.
		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
		if ( ! $post_id ) {
			return $data;
		}

		// Never-scanned posts are allowed — check _edac_summary meta.
		$summary = get_post_meta( $post_id, '_edac_summary', true );
		if ( empty( $summary ) || ! is_array( $summary ) ) {
			return $data;
		}

		// Check whether issue counts exceed the configured thresholds.
		if ( ! $this->has_blocking_issues( $summary ) ) {
			return $data;
		}

		// Revert to an unpublished status.
		// If the post was already published, move it to Pending Review rather than Draft:
		// the content is ready but needs the accessibility issues resolved before going live again.
		$original_status     = isset( $postarr['original_post_status'] ) ? $postarr['original_post_status'] : 'draft';
		$reverted_to         = ( 'publish' === $original_status ) ? 'pending' : $original_status;
		$data['post_status'] = $reverted_to;

		// Queue an admin notice for the next page load.
		set_transient(
			self::TRANSIENT_PREFIX . get_current_user_id(),
			[
				'post_id'       => $post_id,
				'error_count'   => ( (int) ( $summary['errors'] ?? 0 ) ) + ( (int) ( $summary['contrast_errors'] ?? 0 ) ),
				'warning_count' => (int) ( $summary['warnings'] ?? 0 ),
				'reverted_to'   => $reverted_to,
			],
			60
		);

		return $data;
	}

	/**
	 * Displays an admin notice when publishing has been blocked.
	 *
	 * Reads and deletes a per-user transient set by maybe_block_publish().
	 *
	 * @return void
	 */
	public function show_admin_notice(): void {
		$user_id = get_current_user_id();
		$blocked = get_transient( self::TRANSIENT_PREFIX . $user_id );

		if ( ! $blocked ) {
			return;
		}

		delete_transient( self::TRANSIENT_PREFIX . $user_id );

		$post_id       = (int) $blocked['post_id'];
		$error_count   = (int) $blocked['error_count'];
		$warning_count = (int) $blocked['warning_count'];
		$reverted_to   = isset( $blocked['reverted_to'] ) ? $blocked['reverted_to'] : 'draft';

		$parts = [];
		if ( $error_count > 0 ) {
			/* translators: %d: number of error-level accessibility issues */
			$parts[] = sprintf( _n( '%d error', '%d errors', $error_count, 'accessibility-checker' ), $error_count );
		}
		if ( $warning_count > 0 ) {
			/* translators: %d: number of warning-level accessibility issues */
			$parts[] = sprintf( _n( '%d warning', '%d warnings', $warning_count, 'accessibility-checker' ), $warning_count );
		}

		$issue_summary = implode(
			/* translators: used between list items, there is a space after the comma */
			_x( ', ', 'list separator', 'accessibility-checker' ),
			$parts
		);

		$status_label = ( 'pending' === $reverted_to )
			? __( 'Pending Review', 'accessibility-checker' )
			: __( 'Draft', 'accessibility-checker' );

		$edit_url = get_edit_post_link( $post_id );

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: 1: issue summary (e.g. "3 errors, 1 warning"), 2: post status label (e.g. "Pending Review"), 3: opening link tag, 4: closing link tag */
					__( 'Publishing blocked: this content has %1$s. The post has been set to %2$s. %3$sView the accessibility issues%4$s to fix or ignore them before publishing.', 'accessibility-checker' ),
					'<strong>' . esc_html( $issue_summary ) . '</strong>',
					'<strong>' . esc_html( $status_label ) . '</strong>',
					'<a href="' . esc_url( $edit_url ) . '">',
					'</a>'
				),
				[
					'strong' => [],
					'a'      => [ 'href' => [] ],
				]
			)
		);
	}

	/**
	 * Determines whether the current user can bypass the publish block.
	 *
	 * Bypass is granted if:
	 * - The user's role is NOT in the configured block roles list, OR
	 * - The user has the configured bypass capability.
	 *
	 * When no roles are configured, no roles are subject to the block (effectively
	 * no enforcement). When no bypass capability is set, only role checking applies.
	 *
	 * @return bool True if the current user should bypass the publish block.
	 */
	public static function user_can_bypass(): bool {
		if ( ! edac_is_pro() ) {
			return true;
		}

		// Check custom bypass capability first.
		$bypass_cap = get_option( 'edac_block_publish_bypass_cap', '' );
		if ( $bypass_cap && current_user_can( $bypass_cap ) ) {
			/**
			 * Filter whether the current user can bypass the publish block.
			 *
			 * @since 1.40.0
			 *
			 * @param bool $can_bypass Whether the user can bypass. Default true when cap matches.
			 */
			return (bool) apply_filters( 'edac_user_can_bypass_publish_block', true );
		}

		// Check role membership.
		$block_roles = (array) get_option( 'edac_block_publish_roles', [] );

		// If no roles are configured, nobody is blocked.
		if ( empty( $block_roles ) ) {
			return (bool) apply_filters( 'edac_user_can_bypass_publish_block', true );
		}

		$user            = wp_get_current_user();
		$user_roles      = is_array( $user->roles ) ? $user->roles : [];
		$intersect       = array_intersect( $user_roles, $block_roles );
		$role_is_blocked = ! empty( $intersect );

		// User is NOT in a blocked role → they can bypass.
		return (bool) apply_filters( 'edac_user_can_bypass_publish_block', ! $role_is_blocked );
	}

	/**
	 * Checks the post summary to determine if blocking issues exist based on settings.
	 *
	 * @param array $summary The _edac_summary post meta array.
	 * @return bool
	 */
	private function has_blocking_issues( array $summary ): bool {
		if ( get_option( 'edac_block_publish_on_errors' ) ) {
			$errors = ( (int) ( $summary['errors'] ?? 0 ) ) + ( (int) ( $summary['contrast_errors'] ?? 0 ) );
			if ( $errors > 0 ) {
				return true;
			}
		}

		if ( get_option( 'edac_block_publish_on_warnings' ) ) {
			if ( (int) ( $summary['warnings'] ?? 0 ) > 0 ) {
				return true;
			}
		}

		return false;
	}
}
