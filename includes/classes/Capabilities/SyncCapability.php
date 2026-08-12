<?php
/**
 * Class file for WordPress capabilities synced onto roles and users from
 * option-backed assignment maps.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Capabilities;

/**
 * Keeps a bundle of real WordPress capabilities in sync with two option-backed
 * assignment maps - a per-capability role map and a per-capability user-grant
 * map - with a manage_options bypass and a plugin-version-gated migration.
 *
 * The generic primitive is sync_role_capability(): grant or revoke one
 * capability on one role. sync_matrix() applies a whole capability=>roles map on
 * top of it; sync_user_grants() does the same for capability=>user-ids using a
 * stored snapshot so unchecking a user revokes the direct grant. reconcile()
 * ties them together on init and also revokes capabilities that have left the
 * bundle (an add-on that contributed one was deactivated).
 *
 * This class only ever writes the role/capability relationship; it does not
 * answer "can the current user do X" for the rest of the plugin family - see
 * CapabilityChecker for that.
 */
class SyncCapability {

	/**
	 * The capability strings this instance manages (the current bundle).
	 *
	 * @var string[]
	 */
	private array $capabilities;

	/**
	 * Option holding the per-capability role map: [ capability => [role, …] ].
	 *
	 * @var string
	 */
	private string $role_map_option;

	/**
	 * Option holding the per-capability user-grant map:
	 * [ capability => [user_id, …] ]. Empty string disables user grants.
	 *
	 * @var string
	 */
	private string $user_grants_option;

	/**
	 * Plugin version at/after which to force a one-time re-sync (and seed the
	 * role map from the legacy option). '0' disables the version-gated migration.
	 *
	 * @var string
	 */
	private string $migration_version;

	/**
	 * Legacy single-list roles option (e.g. edacp_ignore_user_roles) used only
	 * to seed the role map on first migration. Empty string disables seeding.
	 *
	 * @var string
	 */
	private string $legacy_roles_option;

	/**
	 * Optional floor policy: `fn( string $role_slug, string $capability ): bool`
	 * returning whether the role is allowed to hold the capability (i.e. its live
	 * capabilities meet the capability's floor). When set, neither sync nor the
	 * migration ever grants a capability to a role that fails it, so a stale role
	 * map or a legacy seed can never grant a capability a role does not qualify
	 * for. Null disables floor enforcement (the map is applied verbatim).
	 *
	 * @var callable|null
	 */
	private $floor_check;

	/**
	 * Map of renamed capabilities [ old_slug => new_slug ], applied once during
	 * the version migration: role-map and user-grant entries move from the old
	 * slug to the new, and the old capability is stripped from every role/user.
	 *
	 * @var array<string, string>
	 */
	private array $capability_renames;

	/**
	 * Constructor.
	 *
	 * @param string|string[]       $capabilities        A single capability or the bundle of capabilities to manage.
	 * @param string                $role_map_option     Option holding [ capability => [role, …] ].
	 * @param string                $user_grants_option  Option holding [ capability => [user_id, …] ]. '' disables user grants.
	 * @param string                $migration_version   Plugin version to force a one-time re-sync/seed ('0' disables).
	 * @param string                $legacy_roles_option Legacy roles option to seed the map from on migration ('' disables).
	 * @param callable|null         $floor_check         Optional `fn(string $role, string $cap): bool` floor policy; null disables it.
	 * @param array<string, string> $capability_renames  [ old_slug => new_slug ] applied once during the version migration.
	 *
	 * @throws \InvalidArgumentException If $capabilities resolves to an empty array.
	 */
	public function __construct(
		$capabilities,
		string $role_map_option,
		string $user_grants_option = '',
		string $migration_version = '0',
		string $legacy_roles_option = '',
		?callable $floor_check = null,
		array $capability_renames = []
	) {
		$this->capabilities = is_array( $capabilities ) ? array_values( $capabilities ) : [ $capabilities ];

		if ( [] === $this->capabilities ) {
			throw new \InvalidArgumentException( 'SyncCapability requires at least one capability.' );
		}

		sort( $this->capabilities );

		$this->role_map_option     = $role_map_option;
		$this->user_grants_option  = $user_grants_option;
		$this->migration_version   = $migration_version;
		$this->legacy_roles_option = $legacy_roles_option;
		$this->floor_check         = $floor_check;
		$this->capability_renames  = $capability_renames;
	}

	/**
	 * Whether a role is allowed to hold a capability under the floor policy.
	 * Always true when no floor policy was supplied.
	 *
	 * @param string $role_slug  Role slug.
	 * @param string $capability Capability string.
	 * @return bool
	 */
	private function role_allowed( string $role_slug, string $capability ): bool {
		if ( null === $this->floor_check ) {
			return true;
		}

		return (bool) ( $this->floor_check )( $role_slug, $capability );
	}

	/**
	 * Wire up the admin bypass, live sync on option save, and the init
	 * reconcile. Call once, typically from plugin bootstrap.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'map_meta_cap', [ $this, 'bypass_for_admins' ], 10, 3 );

		$apply_role_map = function ( $a, $b = null ) {
			// add_option passes (option, value); update_option passes (old, new).
			$this->sync_matrix( (array) ( null === $b ? $a : $b ) );
		};
		add_action( "add_option_{$this->role_map_option}", $apply_role_map, 10, 2 );
		add_action( "update_option_{$this->role_map_option}", $apply_role_map, 10, 2 );
		add_action(
			"delete_option_{$this->role_map_option}",
			function () {
				$this->sync_matrix( [] );
			}
		);

		if ( '' !== $this->user_grants_option ) {
			$apply_user_grants = function ( $a, $b = null ) {
				$this->sync_user_grants( (array) ( null === $b ? $a : $b ) );
			};
			add_action( "add_option_{$this->user_grants_option}", $apply_user_grants, 10, 2 );
			add_action( "update_option_{$this->user_grants_option}", $apply_user_grants, 10, 2 );
			add_action(
				"delete_option_{$this->user_grants_option}",
				function () {
					$this->sync_user_grants( [] );
				}
			);
		}

		// init, not admin_init: menu (admin_menu) and REST (rest_api_init)
		// capability checks fire before admin_init on their request types, so
		// reconciling on admin_init would leave the first such request after a
		// change checking stale capabilities. init runs early enough on every
		// request type - admin, front-end, REST and cron.
		add_action( 'init', [ $this, 'reconcile' ] );
	}

	/**
	 * Whether the current user has one of this instance's capabilities.
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
	 * capabilities.
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
	 * Map_meta_cap callback: manage_options users always pass a check against
	 * any capability in this bundle, regardless of assignment.
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
	 * Add or remove one capability on one role. The generic primitive the role
	 * matrix is built on.
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
	 * Apply a per-capability role map: grant each bundle capability to the roles
	 * listed for it and revoke it from every other role.
	 *
	 * @param array $role_map [ capability => [role_slug, …] ].
	 * @return void
	 */
	public function sync_matrix( array $role_map ): void {
		$all_roles = array_keys( wp_roles()->role_objects );

		foreach ( $this->capabilities as $capability ) {
			$roles_for_cap = isset( $role_map[ $capability ] ) && is_array( $role_map[ $capability ] )
				? $role_map[ $capability ]
				: [];

			foreach ( $all_roles as $role_slug ) {
				// Grant only when the map lists the role AND the role satisfies the
				// capability's floor; otherwise revoke. This makes a stale or
				// hand-edited map incapable of granting a capability a role does not
				// qualify for.
				$should_have = in_array( $role_slug, $roles_for_cap, true )
					&& $this->role_allowed( $role_slug, $capability );

				$this->sync_role_capability( $role_slug, $capability, $should_have );
			}
		}
	}

	/**
	 * Apply a per-capability user-grant map, diffing against the snapshot of
	 * grants we last applied so that removing a user revokes their direct
	 * capability without touching grants made elsewhere.
	 *
	 * @param array $user_map [ capability => [user_id, …] ].
	 * @return void
	 */
	public function sync_user_grants( array $user_map ): void {
		if ( '' === $this->user_grants_option ) {
			return;
		}

		$snapshot = (array) get_option( $this->user_grants_synced_option_name(), [] );
		$applied  = [];

		// Union of capabilities we manage now and any we granted before, so
		// grants for a capability that has left the bundle are also revoked.
		$cap_slugs = array_unique( array_merge( $this->capabilities, array_keys( $snapshot ) ) );

		foreach ( $cap_slugs as $capability ) {
			$desired  = isset( $user_map[ $capability ] ) && is_array( $user_map[ $capability ] ) ? array_map( 'intval', $user_map[ $capability ] ) : [];
			$previous = isset( $snapshot[ $capability ] ) && is_array( $snapshot[ $capability ] ) ? array_map( 'intval', $snapshot[ $capability ] ) : [];

			// A capability no longer in the bundle should hold no grants.
			if ( ! in_array( $capability, $this->capabilities, true ) ) {
				$desired = [];
			}

			foreach ( array_diff( $desired, $previous ) as $user_id ) {
				$user = get_user_by( 'id', $user_id );
				if ( $user ) {
					$user->add_cap( $capability );
				}
			}
			foreach ( array_diff( $previous, $desired ) as $user_id ) {
				$user = get_user_by( 'id', $user_id );
				if ( $user ) {
					$user->remove_cap( $capability );
				}
			}

			if ( [] !== $desired ) {
				$applied[ $capability ] = array_values( $desired );
			}
		}

		update_option( $this->user_grants_synced_option_name(), $applied );
	}

	/**
	 * Revoke the given capabilities from every role and from every user we
	 * granted them to. Used to clean up capabilities that have left the bundle.
	 *
	 * @param string[] $capabilities Capabilities to remove everywhere.
	 * @return void
	 */
	public function revoke( array $capabilities ): void {
		if ( [] === $capabilities ) {
			return;
		}

		foreach ( array_keys( wp_roles()->role_objects ) as $role_slug ) {
			foreach ( $capabilities as $capability ) {
				$this->sync_role_capability( $role_slug, $capability, false );
			}
		}

		if ( '' === $this->user_grants_option ) {
			return;
		}

		$snapshot = (array) get_option( $this->user_grants_synced_option_name(), [] );
		$changed  = false;
		foreach ( $capabilities as $capability ) {
			if ( empty( $snapshot[ $capability ] ) ) {
				continue;
			}
			foreach ( (array) $snapshot[ $capability ] as $user_id ) {
				$user = get_user_by( 'id', (int) $user_id );
				if ( $user ) {
					$user->remove_cap( $capability );
				}
			}
			unset( $snapshot[ $capability ] );
			$changed = true;
		}
		if ( $changed ) {
			update_option( $this->user_grants_synced_option_name(), $snapshot );
		}
	}

	/**
	 * Option storing the capability set last synced, so a change to the set (an
	 * add-on contributed or was deactivated) can be detected and reconciled.
	 *
	 * @return string
	 */
	private function synced_set_option_name(): string {
		return 'edac_synced_capabilities_' . $this->role_map_option;
	}

	/**
	 * Option storing the plugin version at which the version-gated migration
	 * last ran for this bundle.
	 *
	 * @return string
	 */
	private function migration_version_option_name(): string {
		return 'edac_capability_migration_version_' . $this->role_map_option;
	}

	/**
	 * Option storing the snapshot of per-user grants we last applied.
	 *
	 * @return string
	 */
	private function user_grants_synced_option_name(): string {
		return 'edac_synced_user_grants_' . $this->role_map_option;
	}

	/**
	 * Apply the configured capability renames once. For each [ old => new ]:
	 * move the role-map and user-grant list from the old slug to the new (merging
	 * and de-duplicating), then strip the old capability off every role. The new
	 * capability is granted, and the old grant revoked from users, by the
	 * sync_matrix()/sync_user_grants() calls that follow in reconcile() (the user
	 * side self-heals from the grant snapshot; the role side does not, so the old
	 * role cap is removed explicitly here).
	 *
	 * @return void
	 */
	private function apply_renames(): void {
		$role_map    = (array) get_option( $this->role_map_option, [] );
		$user_grants = '' !== $this->user_grants_option ? (array) get_option( $this->user_grants_option, [] ) : [];
		$role_dirty  = false;
		$grant_dirty = false;

		foreach ( $this->capability_renames as $from => $to ) {
			$from = (string) $from;
			$to   = (string) $to;

			if ( isset( $role_map[ $from ] ) ) {
				$existing        = isset( $role_map[ $to ] ) && is_array( $role_map[ $to ] ) ? $role_map[ $to ] : [];
				$moved           = is_array( $role_map[ $from ] ) ? $role_map[ $from ] : [];
				$role_map[ $to ] = array_values( array_unique( array_merge( $existing, $moved ) ) );
				unset( $role_map[ $from ] );
				$role_dirty = true;
			}

			if ( isset( $user_grants[ $from ] ) ) {
				$existing           = isset( $user_grants[ $to ] ) && is_array( $user_grants[ $to ] ) ? $user_grants[ $to ] : [];
				$moved              = is_array( $user_grants[ $from ] ) ? $user_grants[ $from ] : [];
				$user_grants[ $to ] = array_values( array_unique( array_merge( $existing, $moved ) ) );
				unset( $user_grants[ $from ] );
				$grant_dirty = true;
			}

			// sync_matrix() only manages current-bundle slugs, so the retired slug
			// would otherwise linger on roles - strip it explicitly.
			foreach ( array_keys( wp_roles()->role_objects ) as $role_slug ) {
				$this->sync_role_capability( $role_slug, $from, false );
			}
		}

		if ( $role_dirty ) {
			update_option( $this->role_map_option, $role_map );
		}
		if ( $grant_dirty && '' !== $this->user_grants_option ) {
			update_option( $this->user_grants_option, $user_grants );
		}
	}

	/**
	 * Reconcile roles and users with the current bundle and assignment maps.
	 * Runs on every init but short-circuits with no writes when nothing has
	 * changed, so it is cheap to run unconditionally.
	 *
	 * Triggers: the capability set changed (grant added / revoke dropped), the
	 * role or user maps changed, or the site upgraded across the migration
	 * version boundary (forces a re-sync and seeds the role map from the legacy
	 * roles option the first time).
	 *
	 * @return void
	 */
	public function reconcile(): void {
		$current = $this->capabilities;

		$previous_set = get_option( $this->synced_set_option_name(), null );
		$set_changed  = ( null === $previous_set ) || ( array_values( (array) $previous_set ) !== $current );

		$migration_option  = $this->migration_version_option_name();
		$version_migration = '0' !== $this->migration_version
			&& version_compare( (string) get_option( $migration_option, '0' ), $this->migration_version, '<' );

		if ( ! $set_changed && ! $version_migration ) {
			// The maps are applied live on save; still re-apply user grants here
			// so a snapshot/desired drift self-heals, then bail cheaply.
			$this->sync_user_grants( (array) get_option( $this->user_grants_option, [] ) );
			return;
		}

		// Capabilities that leave the bundle (an add-on was deactivated) are
		// intentionally NOT revoked: deactivating an add-on should not strip its
		// capability off roles, so re-activating restores the prior state without
		// churn. The inert capability is only ever cleaned up when the free plugin
		// itself is uninstalled (see uninstall.php). revoke() remains available
		// for that and other callers.

		// One-time slug renames (e.g. ignore -> dismiss): move role-map and
		// user-grant entries from the old slug to the new and strip the old
		// capability off every role/user, so a renamed capability keeps its
		// existing grants without leaving the retired slug behind. Runs inside the
		// version-migration boundary so it happens exactly once per upgrade.
		if ( $version_migration && [] !== $this->capability_renames ) {
			$this->apply_renames();
		}

		$role_map = (array) get_option( $this->role_map_option, [] );

		// One-time migration: seed the role map from the legacy single-list
		// roles option so existing sites keep their effective grants - but only
		// for roles that satisfy each capability's floor, so the migration can
		// never grant a capability to a role that does not qualify for it (e.g.
		// a legacy "author" must not inherit a scan capability floored on
		// edit_others_posts).
		if ( $version_migration && [] === $role_map && '' !== $this->legacy_roles_option ) {
			$legacy_roles = array_values( (array) get_option( $this->legacy_roles_option, [] ) );
			if ( [] !== $legacy_roles ) {
				foreach ( $current as $capability ) {
					$qualified = array_values(
						array_filter(
							$legacy_roles,
							function ( $role_slug ) use ( $capability ) {
								return $this->role_allowed( (string) $role_slug, $capability );
							}
						)
					);

					if ( [] !== $qualified ) {
						$role_map[ $capability ] = $qualified;
					}
				}
				update_option( $this->role_map_option, $role_map );
			}
		}

		$this->sync_matrix( $role_map );
		$this->sync_user_grants( (array) get_option( $this->user_grants_option, [] ) );

		if ( $set_changed ) {
			update_option( $this->synced_set_option_name(), $current );
		}
		if ( $version_migration ) {
			update_option( $migration_option, $this->migration_version );
		}
	}
}
