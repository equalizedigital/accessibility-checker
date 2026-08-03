<?php
/**
 * Class file for WordPress capabilities synced onto roles from an option.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Capabilities;

/**
 * Registers one or more real WordPress capabilities, kept in sync with the
 * role list stored in a single plugin option, with a manage_options bypass
 * and a version-gated migration for sites that already had the option set
 * before the capability existed.
 *
 * The generic primitive is sync_role_capability(): grant or revoke one
 * capability on one role. Everything else (sync() looping roles x
 * capabilities, the admin bypass, the option-watching/migration machinery)
 * is built on top of that primitive, so a single option can drive a bundle
 * of capabilities that always travel together (e.g. ignore-issues,
 * ignore-issues-globally, and issues-explorer-access, all granted to
 * whichever roles are configured for "can ignore issues").
 *
 * This class only ever syncs; it does not answer "can the current user do
 * X" for the rest of the plugin family - see CapabilityChecker for that.
 */
class SyncCapability {

	/**
	 * The capability strings this instance keeps in sync, e.g.
	 * [ 'edac_ignore_issues' ] or a multi-capability bundle.
	 *
	 * @var string[]
	 */
	private array $capabilities;

	/**
	 * Name of the option holding the array of role slugs that should have
	 * these capabilities.
	 *
	 * @var string
	 */
	private string $option_name;

	/**
	 * Role slugs to sync the capabilities onto if the option has never been
	 * set (used only by the migration, not as a fallback for a set-but-empty
	 * option).
	 *
	 * @var string[]
	 */
	private array $default_roles;

	/**
	 * Bumped when the default_roles (or the capability list) for this bundle
	 * change; sites whose stored migration version is lower get re-synced
	 * once on their next init, even if they already ran an earlier
	 * version's migration.
	 *
	 * @var int
	 */
	private int $version;

	/**
	 * Constructor.
	 *
	 * @param string|string[] $capabilities  A single capability string, or an array of capability
	 *                                       strings that should all be synced together from the
	 *                                       same option (accepting a single string keeps existing
	 *                                       single-capability callers working unchanged).
	 * @param string          $option_name   Option holding the array of role slugs allowed these capabilities.
	 * @param array           $default_roles Roles to grant on first-ever sync (site had the option unset).
	 * @param int             $version       Bump to re-run the migration when default_roles/capabilities change.
	 *
	 * @throws \InvalidArgumentException If $capabilities is an empty array - there is nothing for this
	 *                                   instance to sync/check, and user_can()'s no-argument default
	 *                                   would otherwise silently check an undefined (null) capability.
	 */
	public function __construct( $capabilities, string $option_name, array $default_roles = [], int $version = 1 ) {
		$this->capabilities = is_array( $capabilities ) ? array_values( $capabilities ) : [ $capabilities ];

		if ( [] === $this->capabilities ) {
			throw new \InvalidArgumentException( 'SyncCapability requires at least one capability.' );
		}

		$this->option_name   = $option_name;
		$this->default_roles = $default_roles;
		$this->version       = $version;
	}

	/**
	 * Wire up the bypass filter, live sync on option save, and the
	 * version-gated migration. Call once, typically from plugin bootstrap.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'map_meta_cap', [ $this, 'bypass_for_admins' ], 10, 3 );

		add_action(
			"add_option_{$this->option_name}",
			function ( $option, $value ) {
				$this->sync( $value );
			},
			10,
			2
		);
		add_action(
			"update_option_{$this->option_name}",
			function ( $old_value, $value ) {
				$this->sync( $value );
			},
			10,
			2
		);
		// Whatever deleted the option (typically an uninstall routine, gated
		// behind the "delete data" preference) intends for the roles it
		// granted to lose these capabilities too - without this, sync()
		// would only ever run again on the next add_option/update_option,
		// leaving the capabilities stuck on whichever roles had them at
		// deletion time indefinitely.
		add_action(
			"delete_option_{$this->option_name}",
			function () {
				$this->sync( [] );
			}
		);

		// init, not admin_init: admin_menu (where menu capability checks happen)
		// and rest_api_init (where REST permission_callbacks are registered) both
		// fire before admin_init on their respective request types, so migrating
		// on admin_init would leave the very first request after a version bump
		// building a menu, or serving a REST request, against pre-migration
		// capabilities. init fires early enough on every request type - admin,
		// front-end, REST, and cron alike - to have already run by the time any
		// of those capability checks happen.
		add_action( 'init', [ $this, 'maybe_migrate' ] );
	}

	/**
	 * Whether the current user has one of this instance's capabilities.
	 * Defaults to the first (or only) capability in the bundle so existing
	 * single-capability callers can keep calling user_can() with no argument.
	 *
	 * @param string|null $capability Which capability to check; defaults to the first in the bundle.
	 * @return bool
	 */
	public function user_can( ?string $capability = null ): bool {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability, synced by this class.
		return current_user_can( $capability ?? $this->capabilities[0] );
	}

	/**
	 * A REST route permission_callback closure for one of this instance's
	 * capabilities, so routes can pass this directly instead of wrapping
	 * current_user_can() in their own inline closure.
	 *
	 * @param string|null $capability Which capability to check; defaults to the first in the bundle.
	 * @return callable
	 */
	public function permission_callback( ?string $capability = null ): callable {
		return function () use ( $capability ) {
			return $this->user_can( $capability );
		};
	}

	/**
	 * Map_meta_cap callback: manage_options users always pass a check
	 * against any capability in this bundle, regardless of role sync.
	 *
	 * @param array  $caps    Required primitive capabilities.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @return array
	 */
	public function bypass_for_admins( $caps, $cap, $user_id ) {
		if ( in_array( $cap, $this->capabilities, true ) && user_can( $user_id, 'manage_options' ) ) {
			return [];
		}
		return $caps;
	}

	/**
	 * Add or remove one capability on one role. The generic primitive
	 * sync() is built on. Deliberately private: calling it directly for a
	 * single capability out of a multi-capability bundle would grant/revoke
	 * that one capability while leaving the rest of the bundle untouched
	 * for that role, breaking the "these capabilities always travel
	 * together" guarantee this class exists to provide. Always go through
	 * sync() (or the option it's wired to) so every capability in the
	 * bundle stays in lockstep.
	 *
	 * @param string $role_slug   Role slug, e.g. 'editor'.
	 * @param string $capability  Capability string.
	 * @param bool   $should_have Whether the role should have this capability.
	 * @return void
	 */
	private function sync_role_capability( string $role_slug, string $capability, bool $should_have ): void {
		$role = wp_roles()->get_role( $role_slug );

		if ( ! $role ) {
			return;
		}

		if ( $should_have ) {
			$role->add_cap( $capability );
		} else {
			$role->remove_cap( $capability );
		}
	}

	/**
	 * Add or remove every capability in this bundle on every role so each
	 * capability matches exactly the role list passed in.
	 *
	 * @param mixed $roles Role slugs that should have the capabilities.
	 * @return void
	 */
	public function sync( $roles ): void {
		$roles = is_array( $roles ) ? $roles : [];

		foreach ( array_keys( wp_roles()->role_objects ) as $role_slug ) {
			$should_have = in_array( $role_slug, $roles, true );

			foreach ( $this->capabilities as $capability ) {
				$this->sync_role_capability( $role_slug, $capability, $should_have );
			}
		}
	}

	/**
	 * Name of the option this bundle's migration-version marker is stored
	 * under. Includes a hash of the capability list, not just option_name,
	 * so two different SyncCapability instances that happen to point at the
	 * same option (e.g. a future feature layered onto an existing option)
	 * can never collide on one shared version counter and silently skip
	 * each other's migration.
	 *
	 * @return string
	 */
	private function version_option_name(): string {
		$capabilities = $this->capabilities;
		sort( $capabilities );

		return 'edac_capability_version_' . $this->option_name . '_' . md5( implode( '|', $capabilities ) );
	}

	/**
	 * Run the sync once per migration version. Covers two cases: a site
	 * that already had option_name set before this bundle existed (needs an
	 * initial sync), and a site whose stored version predates a
	 * default_roles/capabilities change (needs a re-sync even though it
	 * already ran an earlier version's migration).
	 *
	 * Versioned per (option, capability set) pair, not per capability,
	 * since every capability in the bundle is always granted together and
	 * shares one migration.
	 *
	 * @return void
	 */
	public function maybe_migrate(): void {
		$version_option = $this->version_option_name();
		$stored_version = (int) get_option( $version_option, 0 );

		if ( $stored_version >= $this->version ) {
			return;
		}

		$this->sync( get_option( $this->option_name, $this->default_roles ) );
		update_option( $version_option, $this->version );
	}
}
