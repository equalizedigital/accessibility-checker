<?php
/**
 * Class file for WordPress capabilities synced onto roles from an
 * option-backed assignment map.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Capabilities;

/**
 * Keeps a bundle of real WordPress capabilities in sync with an option-backed
 * per-capability role map, with a manage_options bypass and a plugin-version-gated
 * migration.
 *
 * The generic primitive is sync_role_capability(): grant or revoke one
 * capability on one role. sync_matrix() applies a whole capability=>roles map on
 * top of it. reconcile() ties it together on init and re-applies the map when the
 * bundle or the map has changed, or the site crossed the migration boundary.
 *
 * Capabilities are only ever assigned to roles. There is no per-user grant
 * surface in the plugin; a site that needs to grant a capability to one specific
 * user uses WordPress's own $user->add_cap(), which lives on the user object and
 * is untouched by this class.
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
	 * the version migration: role-map entries move from the old slug to the new,
	 * and the old capability is stripped from every role.
	 *
	 * @var array<string, string>
	 */
	private array $capability_renames;

	/**
	 * Capabilities the legacy single-list roles option seeds on migration. The
	 * legacy "Ignore Permissions" setting only ever granted the ignore/dismiss
	 * family, so a legacy role must not inherit the whole current bundle (audit,
	 * export, full-site scan, …). Empty means "the whole current bundle" for
	 * back-compat with callers that do not scope it.
	 *
	 * @var string[]
	 */
	private array $legacy_capabilities;

	/**
	 * Constructor.
	 *
	 * @param string|string[]       $capabilities        A single capability or the bundle of capabilities to manage.
	 * @param string                $role_map_option     Option holding [ capability => [role, …] ].
	 * @param string                $migration_version   Plugin version to force a one-time re-sync/seed ('0' disables).
	 * @param string                $legacy_roles_option Legacy roles option to seed the map from on migration ('' disables).
	 * @param callable|null         $floor_check         Optional `fn(string $role, string $cap): bool` floor policy; null disables it.
	 * @param array<string, string> $capability_renames  [ old_slug => new_slug ] applied once during the version migration.
	 * @param string[]              $legacy_capabilities Capabilities the legacy roles option seeds ([] = the whole bundle).
	 *
	 * @throws \InvalidArgumentException If $capabilities resolves to an empty array.
	 */
	public function __construct(
		$capabilities,
		string $role_map_option,
		string $migration_version = '0',
		string $legacy_roles_option = '',
		?callable $floor_check = null,
		array $capability_renames = [],
		array $legacy_capabilities = []
	) {
		$this->capabilities = is_array( $capabilities ) ? array_values( $capabilities ) : [ $capabilities ];

		if ( [] === $this->capabilities ) {
			throw new \InvalidArgumentException( 'SyncCapability requires at least one capability.' );
		}

		sort( $this->capabilities );

		$this->role_map_option     = $role_map_option;
		$this->migration_version   = $migration_version;
		$this->legacy_roles_option = $legacy_roles_option;
		$this->floor_check         = $floor_check;
		$this->capability_renames  = $capability_renames;
		$this->legacy_capabilities = $legacy_capabilities;
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

		// init, not admin_init: menu (admin_menu) and REST (rest_api_init)
		// capability checks fire before admin_init on their request types, so
		// reconciling on admin_init would leave the first such request after a
		// change checking stale capabilities. init runs early enough on every
		// request type - admin, front-end, REST and cron.
		add_action( 'init', [ $this, 'reconcile' ] );
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

		// add_cap()/remove_cap() each persist the whole wp_user_roles option on
		// every call, regardless of whether anything actually changed - skip the
		// write when the role's raw capability grant already matches, so a
		// reconcile() that changes nothing performs zero option writes instead of
		// one per role x capability pair.
		if ( $role->has_cap( $capability ) === $should_have ) {
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
	 * Revoke the given capabilities from every role. Used to clean up
	 * capabilities that have left the bundle.
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
	 * Apply the configured capability renames once. For each [ old => new ]:
	 * move the role-map list from the old slug to the new (merging and
	 * de-duplicating), then strip the old capability off every role. The new
	 * capability is granted by the sync_matrix() call that follows in reconcile();
	 * the old role cap is removed explicitly here because sync_matrix() only
	 * manages current-bundle slugs.
	 *
	 * @return void
	 */
	private function apply_renames(): void {
		$role_map   = (array) get_option( $this->role_map_option, [] );
		$role_dirty = false;

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

			// sync_matrix() only manages current-bundle slugs, so the retired slug
			// would otherwise linger on roles - strip it explicitly.
			foreach ( array_keys( wp_roles()->role_objects ) as $role_slug ) {
				$this->sync_role_capability( $role_slug, $from, false );
			}
		}

		if ( $role_dirty ) {
			update_option( $this->role_map_option, $role_map );
		}
	}

	/**
	 * Reconcile roles with the current bundle and role map. Runs on every init
	 * but short-circuits with no writes when nothing has changed, so it is cheap
	 * to run unconditionally.
	 *
	 * Triggers: the capability set changed (grant added / revoke dropped), the
	 * role map changed, or the site upgraded across the migration version
	 * boundary (forces a re-sync and seeds the role map from the legacy roles
	 * option the first time).
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
			// The role map is applied live on save, so there is nothing to
			// reconcile when neither the set nor the migration boundary changed.
			return;
		}

		// Capabilities that leave the bundle (an add-on was deactivated) are
		// intentionally NOT revoked: deactivating an add-on should not strip its
		// capability off roles, so re-activating restores the prior state without
		// churn. The inert capability is only ever cleaned up when the free plugin
		// itself is uninstalled (see uninstall.php). revoke() remains available
		// for that and other callers.

		// One-time slug renames (e.g. ignore -> dismiss): move role-map entries
		// from the old slug to the new and strip the old capability off every
		// role, so a renamed capability keeps its existing grants without leaving
		// the retired slug behind. Runs inside the version-migration boundary so it
		// happens exactly once per upgrade.
		if ( $version_migration && [] !== $this->capability_renames ) {
			$this->apply_renames();
		}

		$role_map = (array) get_option( $this->role_map_option, [] );

		// One-time migration: seed the role map from the legacy single-list
		// roles option so existing sites keep their effective grants - but only
		// for the capabilities the legacy setting actually granted (its ignore/
		// dismiss family, not the whole current bundle), and only for roles that
		// satisfy each capability's floor, so the migration can never grant a
		// capability to a role that does not qualify for it (e.g. a legacy
		// "author" must not inherit a scan capability floored on
		// edit_others_posts, nor an export capability it never had).
		if ( $version_migration && [] === $role_map && '' !== $this->legacy_roles_option ) {
			$legacy_roles = array_values( (array) get_option( $this->legacy_roles_option, [] ) );
			// Restrict the seed to the configured legacy capabilities, intersected
			// with what is actually in the current bundle (so floor metadata is
			// available and inactive add-on caps are not seeded blind). An empty
			// legacy set falls back to the whole bundle for unscoped callers.
			$legacy_targets = [] !== $this->legacy_capabilities
				? array_values( array_intersect( $this->legacy_capabilities, $current ) )
				: $current;
			if ( [] !== $legacy_roles ) {
				foreach ( $legacy_targets as $capability ) {
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

		if ( $set_changed ) {
			update_option( $this->synced_set_option_name(), $current );
		}
		if ( $version_migration ) {
			update_option( $migration_option, $this->migration_version );
		}
	}
}
