<?php
/**
 * Class file for directly granting a capability to an individual user, with
 * attribution.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Capabilities;

/**
 * Grants (or revokes) a capability on one specific user, independent of
 * their role, and records who granted it and when.
 *
 * Deliberately separate from Synced_Capability rather than a change to it:
 * Synced_Capability's sync() only ever calls add_cap()/remove_cap() on role
 * objects (stored in the wp_user_roles option), never on an individual
 * user's own capability list (stored in that user's wp_capabilities user
 * meta) — those are different storage, so a role-level sync can never
 * clobber a capability granted directly to one user, and this class never
 * needs to know anything about how (or whether) a capability is synced
 * onto roles elsewhere. It works with any capability string, custom or
 * core.
 *
 * WordPress's own current_user_can()/user_can() already merges role
 * capabilities with a user's own directly-added capabilities — that part
 * needs no new code. What WordPress has no concept of is *who* granted a
 * capability and *when*; that attribution is what this class adds.
 */
class User_Capability_Grant {

	/**
	 * User meta key prefix under which grant attribution is stored, per
	 * capability.
	 *
	 * @var string
	 */
	private const META_PREFIX = 'edac_capability_grant_';

	/**
	 * Grant a capability directly to a user, recording who granted it.
	 *
	 * @param int    $user_id    User to grant the capability to.
	 * @param string $capability Capability string to grant.
	 * @param int    $granted_by User ID of the granter. Defaults to the current user.
	 * @return bool True if the user was found and the grant was recorded.
	 */
	public static function grant( int $user_id, string $capability, int $granted_by = 0 ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$user->add_cap( $capability );

		update_user_meta(
			$user_id,
			self::META_PREFIX . $capability,
			[
				'granted_by' => $granted_by ? $granted_by : get_current_user_id(),
				'granted_at' => time(),
			]
		);

		return true;
	}

	/**
	 * Revoke a capability that was granted directly to a user (does not
	 * affect a capability the user has via their role).
	 *
	 * @param int    $user_id    User to revoke the capability from.
	 * @param string $capability Capability string to revoke.
	 * @return bool True if the user was found and the revoke was applied.
	 */
	public static function revoke( int $user_id, string $capability ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$user->remove_cap( $capability );
		delete_user_meta( $user_id, self::META_PREFIX . $capability );

		return true;
	}

	/**
	 * Get attribution for a capability directly granted to a user via
	 * grant(), for display in an admin UI (e.g. "Granted by X on Y").
	 * Returns null if the capability was never granted through this class
	 * (including if the user only has it via their role).
	 *
	 * @param int    $user_id    User to check.
	 * @param string $capability Capability string to check.
	 * @return array{granted_by: int, granted_at: int}|null
	 */
	public static function get_grant_info( int $user_id, string $capability ): ?array {
		$meta = get_user_meta( $user_id, self::META_PREFIX . $capability, true );

		return $meta ? $meta : null;
	}

	/**
	 * Whether a capability was added directly to this user (as opposed to
	 * inherited from one of their roles).
	 *
	 * $user->caps holds only what was assigned directly to this user (role
	 * slugs plus any directly-added capabilities); $user->allcaps is the
	 * merged result including everything resolved from their roles. Using
	 * $user->caps here is what makes this "direct grant," not "has it at
	 * all," correctly distinct from user_can()/current_user_can().
	 *
	 * @param int    $user_id    User to check.
	 * @param string $capability Capability string to check.
	 * @return bool
	 */
	public static function is_individually_granted( int $user_id, string $capability ): bool {
		$user = get_userdata( $user_id );

		return $user instanceof \WP_User && ! empty( $user->caps[ $capability ] );
	}
}
