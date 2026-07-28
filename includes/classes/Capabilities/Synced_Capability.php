<?php
/**
 * Class file for a WordPress capability synced onto roles from an option.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Capabilities;

/**
 * Registers a real WordPress capability, kept in sync with the role list
 * stored in a plugin option, with a manage_options bypass and a
 * version-gated migration for sites that already had the option set
 * before the capability existed.
 *
 * Extracted from the hand-rolled edac_ignore_issues wiring in
 * includes/options-page.php so other gated features (Explorer REST
 * routes, future role-configurable features) can reuse the same pattern
 * instead of re-implementing the sync/bypass/migration trio each time.
 */
class Synced_Capability {

	/**
	 * The capability string, e.g. 'edac_ignore_issues'.
	 *
	 * @var string
	 */
	private string $capability;

	/**
	 * Name of the option holding the array of role slugs that should have
	 * this capability.
	 *
	 * @var string
	 */
	private string $option_name;

	/**
	 * Role slugs to sync the capability onto if the option has never been
	 * set (used only by the migration, not as a fallback for a set-but-empty
	 * option).
	 *
	 * @var string[]
	 */
	private array $default_roles;

	/**
	 * Bumped when the default_roles for this capability change; sites whose
	 * stored migration version is lower get re-synced once on their next
	 * admin_init, even if they already ran an earlier version's migration.
	 *
	 * @var int
	 */
	private int $version;

	/**
	 * Constructor.
	 *
	 * @param string $capability    The capability string to register, e.g. 'edac_ignore_issues'.
	 * @param string $option_name   Option holding the array of role slugs allowed this capability.
	 * @param array  $default_roles Roles to grant on first-ever sync (site had the option unset).
	 * @param int    $version       Bump to re-run the migration when default_roles changes.
	 */
	public function __construct( string $capability, string $option_name, array $default_roles = [], int $version = 1 ) {
		$this->capability    = $capability;
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

		add_action( 'admin_init', [ $this, 'maybe_migrate' ] );
	}

	/**
	 * Whether the current user has this capability.
	 *
	 * @return bool
	 */
	public function user_can(): bool {
		return current_user_can( $this->capability ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capability, synced by this class.
	}

	/**
	 * A REST route permission_callback closure for this capability, so
	 * routes can pass this directly instead of wrapping current_user_can()
	 * in their own inline closure.
	 *
	 * @return callable
	 */
	public function permission_callback(): callable {
		return function () {
			return $this->user_can();
		};
	}

	/**
	 * Map_meta_cap callback: manage_options users always pass a check
	 * against this capability, regardless of role sync.
	 *
	 * @param array  $caps    Required primitive capabilities.
	 * @param string $cap     Capability being checked.
	 * @param int    $user_id User ID.
	 * @return array
	 */
	public function bypass_for_admins( $caps, $cap, $user_id ) {
		if ( $this->capability === $cap && user_can( $user_id, 'manage_options' ) ) {
			return [];
		}
		return $caps;
	}

	/**
	 * Add or remove this capability on every role so it matches exactly
	 * the role list passed in.
	 *
	 * @param mixed $roles Role slugs that should have the capability.
	 * @return void
	 */
	public function sync( $roles ): void {
		$roles = is_array( $roles ) ? $roles : [];

		foreach ( wp_roles()->role_objects as $role_slug => $role ) {
			if ( in_array( $role_slug, $roles, true ) ) {
				$role->add_cap( $this->capability );
			} else {
				$role->remove_cap( $this->capability );
			}
		}
	}

	/**
	 * Run the sync once per migration version. Covers two cases: a site
	 * that already had option_name set before this capability existed
	 * (needs an initial sync), and a site whose stored version predates a
	 * default_roles change (needs a re-sync even though it already ran an
	 * earlier version's migration once).
	 *
	 * @return void
	 */
	public function maybe_migrate(): void {
		$version_option = "edac_capability_version_{$this->capability}";
		$stored_version = (int) get_option( $version_option, 0 );

		if ( $stored_version >= $this->version ) {
			return;
		}

		$this->sync( get_option( $this->option_name, $this->default_roles ) );
		update_option( $version_option, $this->version );
	}
}
