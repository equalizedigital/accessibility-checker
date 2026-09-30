<?php
/**
 * Class file for a capability-string-agnostic permission reader.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Capabilities;

/**
 * Thin, stateless "can this user do X" reader that every menu/AJAX/REST
 * consumer in the plugin family should call instead of reaching into
 * SyncCapability or a hand-rolled current_user_can() closure directly.
 *
 * Deliberately has no knowledge of SyncCapability, option-backed bundles, or
 * how a capability came to be true for a given user - a role-level sync, a
 * capability granted directly to one user (by a role-editor plugin, custom
 * code, etc.), or a plain core WP capability all answer identically here.
 * That's the point of the split: SyncCapability owns writing the
 * role/capability relationship, this class only ever reads it.
 */
class CapabilityChecker {

	/**
	 * Whether a user has the given capability.
	 *
	 * @param string   $capability Capability string to check.
	 * @param int|null $user_id    User ID to check; defaults to the current user.
	 * @return bool
	 */
	public static function user_can( string $capability, ?int $user_id = null ): bool {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- May be a custom capability synced elsewhere; this class doesn't know or care.
		return null === $user_id ? current_user_can( $capability ) : user_can( $user_id, $capability );
	}
}
