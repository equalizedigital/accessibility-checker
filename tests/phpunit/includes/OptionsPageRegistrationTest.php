<?php
/**
 * Tests for the Accessibility Checker admin menu registration.
 *
 * @package Accessibility_Checker
 */

/**
 * Covers edac_add_options_page() and edac_default_capability_role_map() from
 * includes/options-page.php: the guards that gate the menu and the settings
 * submenu, the settings capability, the tabs it wires in, the deferred
 * deregistration of pro's admin_init registration, and the default role map.
 *
 * @covers ::edac_add_options_page
 * @covers ::edac_default_capability_role_map
 */
class OptionsPageRegistrationTest extends WP_UnitTestCase {

	/**
	 * The menu globals edac_add_options_page() writes into, snapshotted so each test starts empty.
	 *
	 * @var array<string, mixed>
	 */
	private $menu_globals = [];

	/** Snapshot the menu globals, then start them empty. */
	protected function setUp(): void {
		parent::setUp();

		// add_menu_page()/add_submenu_page() live in wp-admin/includes/plugin.php.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( [ 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages', '_wp_submenu_nopriv' ] as $name ) {
			$this->menu_globals[ $name ] = $GLOBALS[ $name ] ?? null;
			$GLOBALS[ $name ]            = [];
		}
	}

	/** Restore the menu globals and drop the fixture filter and the current user. */
	protected function tearDown(): void {
		foreach ( $this->menu_globals as $name => $value ) {
			if ( null === $value ) {
				unset( $GLOBALS[ $name ] );
			} else {
				$GLOBALS[ $name ] = $value;
			}
		}

		remove_all_filters( 'edac_filter_settings_capability' );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/** The whole menu is withheld from a user who can neither edit posts nor hold an Accessibility Checker capability, and no submenu is registered either, so nothing can be reached around the missing parent. */
	public function test_the_menu_is_withheld_without_edit_posts_or_a_dismiss_capability(): void {
		$this->become_user_with( [ 'read' ] );

		edac_add_options_page();

		$this->assertNull( $this->find_menu_entry(), 'A plain subscriber must not get the Accessibility Checker menu.' );
		$this->assertArrayNotHasKey( 'accessibility_checker', (array) $GLOBALS['submenu'], 'No submenu may be registered when the menu itself is withheld.' );
	}

	/**
	 * Any bundled Accessibility Checker capability opens the parent menu for lower-role users, including add-on pages unrelated to dismissing issues.
	 *
	 * @dataProvider provider_admin_menu_capabilities
	 * @param string $capability The bundled capability held by the fixture user.
	 */
	public function test_the_menu_is_registered_for_a_bundled_capability_without_edit_posts( string $capability ): void {
		$this->become_user_with( [ 'read', $capability ] );

		$this->assertFalse( current_user_can( 'edit_posts' ), 'Precondition: the fixture user lacks edit_posts.' );
		$this->assertContains( $capability, edac_capability_bundle(), 'Precondition: the capability is part of the Accessibility Checker bundle.' );

		edac_add_options_page();

		$this->assertNotNull( $this->find_menu_entry(), sprintf( 'Holding %s without edit_posts must open the parent menu.', $capability ) );
	}

	/** The top-level menu lands on the Welcome page and is registered with the read capability, so any logged-in user who passes the guard can reach it. */
	public function test_the_menu_is_registered_for_a_user_with_edit_posts(): void {
		$this->become_user_with( [ 'read', 'edit_posts' ] );

		edac_add_options_page();

		$entry = $this->find_menu_entry();
		$this->assertNotNull( $entry, 'A user with edit_posts must get the Accessibility Checker menu.' );
		$this->assertSame( 'Accessibility Checker', $entry[0] );
		$this->assertSame( 'read', $entry[1], 'The menu must stay reachable by any logged-in user; the guards do the gating.' );
		$this->assertSame( 'Welcome to Accessibility Checker', $entry[3] );
		$this->assertSame( 10, has_action( 'toplevel_page_accessibility_checker', 'edac_display_welcome_page' ), 'The menu must render the welcome page.' );
	}

	/**
	 * A user whose only capability is edit_posts does not get the settings submenu: the second guard withholds it from anyone who can dismiss nothing.
	 *
	 * The settings capability is filtered to one the fixture user holds, isolating the plugin's own guard from add_submenu_page()'s current_user_can() bail-out.
	 */
	public function test_the_settings_submenu_is_withheld_from_a_user_who_can_dismiss_nothing(): void {
		$this->become_user_with( [ 'read', 'edit_posts' ] );
		$this->filter_settings_capability_to_edit_posts();

		edac_add_options_page();

		$this->assertNotNull( $this->find_menu_entry(), 'Precondition: the top-level menu is registered for this user.' );
		$this->assertNull( $this->find_submenu_entry( 'accessibility_checker_settings' ), 'edit_posts alone must not be enough for the settings screen.' );
	}

	/**
	 * Either per-post dismiss capability opens the settings submenu - the guard is an OR, so a role granted just the narrower one is not locked out of the screen that manages it.
	 *
	 * @dataProvider provider_dismiss_capabilities
	 * @param string $capability The dismiss capability the fixture user holds.
	 */
	public function test_the_settings_submenu_follows_either_dismiss_capability( string $capability ): void {
		$this->become_user_with( [ 'read', 'edit_posts', $capability ] );
		$this->filter_settings_capability_to_edit_posts();

		edac_add_options_page();

		$entry = $this->find_submenu_entry( 'accessibility_checker_settings' );
		$this->assertNotNull( $entry, sprintf( 'Holding %s must open the settings screen.', $capability ) );
		$this->assertSame( 'Settings', $entry[0] );
		$this->assertSame( 'edit_posts', $entry[1], 'The stored capability must be the filtered one.' );
		$this->assertSame( 'Accessibility Checker Settings', $entry[3] );
		// WordPress derives a submenu page's hook name from the parent menu's sanitised
		// title, so deriving it here asserts the hook that is actually fired.
		$this->assertSame(
			10,
			has_action( get_plugin_page_hookname( 'accessibility_checker_settings', 'accessibility_checker' ), 'edac_display_options_page' ),
			'The settings page must render through edac_display_options_page().'
		);
	}

	/**
	 * The settings screen is registered with the edac_filter_settings_capability filter's value, defaulting to manage_options. Add-ons widen access through that filter.
	 *
	 * @dataProvider provider_settings_capabilities
	 * @param string|null $filtered The capability the fixture filter returns, or null for no filter.
	 * @param string      $expected The capability the submenu row must carry.
	 */
	public function test_the_settings_capability_is_filterable_and_defaults_to_manage_options( ?string $filtered, string $expected ): void {
		$this->become_user_with( [ 'read', 'edit_posts', 'edac_dismiss_issues', 'manage_options' ] );

		if ( null !== $filtered ) {
			add_filter(
				'edac_filter_settings_capability',
				static function () use ( $filtered ) {
					return $filtered;
				}
			);
		}

		edac_add_options_page();

		$entry = $this->find_submenu_entry( 'accessibility_checker_settings' );
		$this->assertNotNull( $entry, 'Precondition: the settings submenu is registered.' );
		$this->assertSame( $expected, $entry[1] );
	}

	/** The Fixes and Permissions pages are wired in by the same call, each contributing a settings tab and a tab-content renderer. */
	public function test_the_fixes_and_permissions_tabs_are_wired_in(): void {
		$this->become_user_with( [ 'read', 'edit_posts', 'edac_dismiss_issues' ] );
		$this->filter_settings_capability_to_edit_posts();

		$tabs_before    = $this->count_callbacks( 'edac_filter_settings_tab_items' );
		$content_before = $this->count_callbacks( 'edac_settings_tab_content' );

		edac_add_options_page();

		$this->assertSame( $tabs_before + 2, $this->count_callbacks( 'edac_filter_settings_tab_items' ), 'The Fixes and Permissions pages must each add a settings tab.' );
		$this->assertSame( $content_before + 2, $this->count_callbacks( 'edac_settings_tab_content' ), 'The Fixes and Permissions pages must each add a tab content renderer.' );
	}

	/** The deregistration of pro's admin_init setting is deferred to admin_init rather than run inline, so it still fires when pro registers later than the menu. */
	public function test_the_pro_setting_deregistration_is_deferred_to_admin_init(): void {
		$this->become_user_with( [ 'read', 'edit_posts', 'edac_dismiss_issues' ] );
		$this->filter_settings_capability_to_edit_posts();

		// Stand in for pro's registration, which is what the deferred callback removes.
		add_action( 'admin_init', 'edacp_register_setting', 11 );

		$closures_before = $this->admin_init_closures();

		edac_add_options_page();

		$deferred = array_diff_key( $this->admin_init_closures(), $closures_before );

		$this->assertCount( 1, $deferred, 'edac_add_options_page() must defer exactly one admin_init callback.' );
		$this->assertNotFalse( has_action( 'admin_init', 'edacp_register_setting' ), "Precondition: pro's registration is present before the deferred callback runs." );

		( reset( $deferred ) )();

		$this->assertFalse( has_action( 'admin_init', 'edacp_register_setting' ), "The deferred callback must remove pro's admin_init registration." );
	}

	/** The shipped registry seeds exactly the capabilities that declare a default role meeting their own floor: dismiss-own to the three roles that can edit posts, the front-end highlighter to editor and author; anything with no declared default roles is absent rather than present-and-empty. */
	public function test_the_default_role_map_is_built_from_the_registry(): void {
		$map = edac_default_capability_role_map();

		$this->assertSame( [ 'editor', 'author', 'contributor' ], $map['edac_dismiss_own_issues'] ?? null, 'dismiss-own must default to the roles that hold edit_posts.' );
		$this->assertSame( [ 'editor', 'author' ], $map['edac_view_frontend_highlighter'] ?? null, 'The front-end highlighter has no floor, so every declared default role qualifies.' );
		$this->assertArrayNotHasKey( 'edac_dismiss_issues', $map, 'A capability with no declared default roles must be absent from the map.' );

		foreach ( $map as $slug => $roles ) {
			$this->assertNotEmpty( $roles, sprintf( '%s must not be seeded with an empty role list.', $slug ) );
			$this->assertSame( array_values( $roles ), $roles, sprintf( '%s must be seeded with a list, not a keyed array.', $slug ) );
		}
	}

	/**
	 * Each default role is checked against the capability's floor, so a role that loses it is dropped from the seed rather than granted a capability it cannot exercise.
	 */
	public function test_a_default_role_that_does_not_meet_the_floor_is_dropped(): void {
		$contributor = get_role( 'contributor' );

		$this->assertNotNull( $contributor, 'Precondition: the contributor role exists.' );
		$this->assertTrue( $contributor->has_cap( 'edit_posts' ), 'Precondition: contributor holds edit_posts, the dismiss-own floor.' );

		try {
			$contributor->remove_cap( 'edit_posts' );

			$this->assertSame( [ 'editor', 'author' ], edac_default_capability_role_map()['edac_dismiss_own_issues'] ?? null, 'A default role that no longer meets the capability floor must be dropped from the map.' );
		} finally {
			$contributor->add_cap( 'edit_posts' );
		}
	}

	/**
	 * Bundled Accessibility Checker capabilities granted individually to test each menu-access path.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provider_admin_menu_capabilities(): array {
		return [
			'dismiss any post'      => [ 'edac_dismiss_issues' ],
			'dismiss own posts'     => [ 'edac_dismiss_own_issues' ],
			'front-end highlighter' => [ 'edac_view_frontend_highlighter' ],
		];
	}

	/**
	 * Dismiss capabilities the settings submenu guard accepts, one per disjunct.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function provider_dismiss_capabilities(): array {
		return [
			'dismiss any post'  => [ 'edac_dismiss_issues' ],
			'dismiss own posts' => [ 'edac_dismiss_own_issues' ],
		];
	}

	/**
	 * Settings capability cases: unfiltered, and filtered by an add-on.
	 *
	 * @return array<string, array{0: string|null, 1: string}>
	 */
	public function provider_settings_capabilities(): array {
		return [
			'unfiltered' => [ null, 'manage_options' ],
			'filtered'   => [ 'edit_posts', 'edit_posts' ],
		];
	}

	/** Force the settings capability to one a fixture user can hold, without granting manage_options this way. */
	private function filter_settings_capability_to_edit_posts(): void {
		add_filter( 'edac_filter_settings_capability', [ $this, 'return_edit_posts' ] );
	}

	/** Fixture filter callback for filter_settings_capability_to_edit_posts(). */
	public function return_edit_posts() {
		return 'edit_posts';
	}

	/**
	 * Create a subscriber with the supplied user-level grants and make them the current user. Grants go on the user object, never a shared role, so the per-test transaction is all the cleanup needed.
	 *
	 * @param string[] $capabilities Capabilities to grant directly.
	 * @return int The new user's ID.
	 */
	private function become_user_with( array $capabilities ): int {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$user    = new WP_User( $user_id );

		foreach ( $capabilities as $capability ) {
			$user->add_cap( $capability );
		}

		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Find the registered Accessibility Checker menu row.
	 *
	 * @return array|null The row, or null when it is not registered.
	 */
	private function find_menu_entry(): ?array {
		foreach ( (array) $GLOBALS['menu'] as $entry ) {
			if ( isset( $entry[2] ) && 'accessibility_checker' === $entry[2] ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Find a registered settings submenu row by its slug.
	 *
	 * @param string $slug The submenu slug.
	 * @return array|null The row, or null when it is not registered.
	 */
	private function find_submenu_entry( string $slug ): ?array {
		foreach ( (array) ( $GLOBALS['submenu']['accessibility_checker'] ?? [] ) as $entry ) {
			if ( isset( $entry[2] ) && $slug === $entry[2] ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Count the callbacks hooked to a hook, across every priority.
	 *
	 * @param string $tag The hook name.
	 * @return int
	 */
	private function count_callbacks( string $tag ): int {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $tag ] ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $wp_filter[ $tag ]->callbacks as $callbacks ) {
			$count += count( $callbacks );
		}

		return $count;
	}

	/**
	 * The closures currently hooked to admin_init, keyed by object id so a snapshot can be diffed against a later one.
	 *
	 * @return array<int, callable>
	 */
	private function admin_init_closures(): array {
		global $wp_filter;

		$closures = [];
		foreach ( ( $wp_filter['admin_init']->callbacks ?? [] ) as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( $callback['function'] instanceof Closure ) {
					$closures[ spl_object_id( $callback['function'] ) ] = $callback['function'];
				}
			}
		}

		return $closures;
	}
}
