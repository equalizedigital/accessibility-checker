<?php
/**
 * Tests for the Permissions tab wiring and the role-picker payload.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage;

/**
 * Covers the wiring of the Permissions tab and the picker's eligibility matrix.
 *
 * PermissionsPageCapabilityTest and PermissionsPageConcurrencyTest already
 * cover the capability gating and the optimistic-concurrency save, so what is
 * left here is how the tab, its assets and its save handler are wired, and the
 * shape of the payload the picker JavaScript reads.
 *
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage::add_page
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage::register_request_handlers
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage::enqueue_assets
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage::build_matrix_payload
 */
class PermissionsPageWiringTest extends WP_UnitTestCase {

	/**
	 * The page under test.
	 *
	 * @var PermissionsPage
	 */
	private $page;

	/**
	 * Starts each test with an empty asset registry and a fresh page.
	 *
	 * The enqueue tests also assert what is not enqueued, so the registry has to
	 * start empty instead of carrying another test's handles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wp_scripts, $wp_styles;
		$wp_scripts = new WP_Scripts();
		$wp_styles  = new WP_Styles();

		$this->page = new PermissionsPage( 'manage_options' );
	}

	/**
	 * Restores the global state a test may have changed.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		wp_set_current_user( 0 );
		remove_all_filters( 'edac_capability_is_editable' );
		unset( $_GET['page'], $_GET['tab'] );

		parent::tearDown();

		global $wp_scripts, $wp_styles;
		unset( $wp_scripts, $wp_styles );
	}

	/**
	 * Calls the private build_matrix_payload() method.
	 *
	 * @return array
	 */
	private function build_matrix_payload(): array {
		$reflection = new ReflectionClass( PermissionsPage::class );
		$method     = $reflection->getMethod( 'build_matrix_payload' );
		$method->setAccessible( true );

		return $method->invoke( $this->page );
	}

	/**
	 * Exposes the page and tab query args the picker asset gate reads.
	 *
	 * Empty values are left out, so '' stands for "no such query arg".
	 *
	 * @param string $page Page slug for $_GET, or '' for none.
	 * @param string $tab  Tab slug for $_GET, or '' for none.
	 * @return void
	 */
	private function set_request( string $page, string $tab ): void {
		$_GET = array_filter(
			[
				'page' => $page,
				'tab'  => $tab,
			]
		);
	}

	/**
	 * The tab, its callback and its assets are wired by add_page(), and the
	 * save handler is registered separately by register_request_handlers()
	 * because admin-post.php never builds the admin menu add_page() hangs off.
	 *
	 * @return void
	 */
	public function test_the_tab_wiring_and_the_save_handler_are_registered_separately(): void {
		$this->page->add_page();

		$this->assertNotFalse( has_filter( 'edac_filter_settings_tab_items', [ $this->page, 'add_permissions_tab' ] ) );
		$this->assertSame( 12, has_action( 'edac_settings_tab_content', [ $this->page, 'add_permissions_tab_content' ] ) );
		$this->assertSame( 10, has_action( 'admin_enqueue_scripts', [ $this->page, 'enqueue_assets' ] ) );
		$this->assertFalse( has_action( 'admin_post_' . PermissionsPage::SAVE_ACTION, [ $this->page, 'handle_save' ] ) );

		$on_admin_post = new PermissionsPage( 'manage_options' );
		$on_admin_post->register_request_handlers();

		$this->assertSame( 10, has_action( 'admin_post_' . PermissionsPage::SAVE_ACTION, [ $on_admin_post, 'handle_save' ] ) );
		$this->assertFalse( has_filter( 'edac_filter_settings_tab_items', [ $on_admin_post, 'add_permissions_tab' ] ) );
		$this->assertFalse( has_action( 'edac_settings_tab_content', [ $on_admin_post, 'add_permissions_tab_content' ] ) );
		$this->assertFalse( has_action( 'admin_enqueue_scripts', [ $on_admin_post, 'enqueue_assets' ] ) );
	}

	/**
	 * The picker offers every assignable role and never an administrator, who
	 * already passes every edac_* capability through the admin bypass.
	 *
	 * @return void
	 */
	public function test_matrix_payload_lists_every_assignable_role_but_not_administrators(): void {
		$payload = $this->build_matrix_payload();

		$expected = [];
		foreach ( wp_roles()->roles as $role_slug => $role ) {
			if ( empty( $role['capabilities']['manage_options'] ) ) {
				$expected[] = $role_slug;
			}
		}

		$this->assertSame( $expected, array_column( $payload['roles'], 'slug' ) );
		$this->assertContains( 'editor', $expected, 'Precondition: the editor role is assignable.' );
		$this->assertContains( 'subscriber', $expected, 'Precondition: the subscriber role is assignable.' );
		$this->assertNotContains( 'administrator', array_column( $payload['roles'], 'slug' ) );

		$roles = array_column( $payload['roles'], null, 'slug' );
		$this->assertSame( [ 'slug', 'name' ], array_keys( $roles['editor'] ) );
		$this->assertSame( 'Editor', $roles['editor']['name'] );
		$this->assertSame( 'Subscriber', $roles['subscriber']['name'] );
	}

	/**
	 * Every registered capability reaches the payload with the metadata the
	 * picker renders, and nothing else is carried along.
	 *
	 * @return void
	 */
	public function test_matrix_payload_describes_every_registered_capability(): void {
		$payload = $this->build_matrix_payload();

		$this->assertSame( array_keys( edac_capability_metadata() ), array_column( $payload['caps'], 'slug' ) );

		$caps = array_column( $payload['caps'], null, 'slug' );

		$this->assertSame(
			[
				'slug'        => EDAC_CAPABILITY_DISMISS_OWN_ISSUES,
				'label'       => 'Dismiss own issues',
				'description' => 'Dismiss and reopen accessibility issues on posts the user can edit (their own posts; editors can edit any).',
				'group'       => 'Accessibility Checker',
				'owner'       => 'accessibility-checker',
			],
			$caps[ EDAC_CAPABILITY_DISMISS_OWN_ISSUES ]
		);

		// A second entry, to prove the mapping is per capability.
		$this->assertSame( 'Front-end highlighter', $caps[ EDAC_CAPABILITY_VIEW_FRONTEND_HIGHLIGHTER ]['label'] );
		$this->assertSame( 'View the front-end accessibility highlighter on published content.', $caps[ EDAC_CAPABILITY_VIEW_FRONTEND_HIGHLIGHTER ]['description'] );
	}

	/**
	 * The matrix carries a cell for every role and capability pair, so the grid
	 * is complete rather than merely non-empty.
	 *
	 * @return void
	 */
	public function test_matrix_payload_has_a_state_cell_for_every_role_and_capability_pair(): void {
		$payload = $this->build_matrix_payload();

		$roles = array_column( $payload['roles'], 'slug' );
		$caps  = array_column( $payload['caps'], 'slug' );

		$this->assertSame( $roles, array_keys( $payload['state'] ) );

		$found    = [];
		$expected = [];
		foreach ( $payload['state'] as $role_slug => $cells ) {
			$found[]    = $role_slug . ':' . implode( ',', array_keys( $cells ) );
			$expected[] = $role_slug . ':' . implode( ',', $caps );
		}

		$this->assertSame( $expected, $found );
	}

	/**
	 * A state cell reports whether the capability can be granted to the role,
	 * and why not when it cannot.
	 *
	 * @dataProvider provider_role_capability_state
	 *
	 * @param string $role_slug  Role the cell is for.
	 * @param string $capability Capability the cell is for.
	 * @param bool   $enabled    Expected enabled flag.
	 * @param string $reason     Expected reason.
	 * @return void
	 */
	public function test_matrix_state_reports_whether_a_role_can_be_granted_a_capability( string $role_slug, string $capability, bool $enabled, string $reason ): void {
		$payload = $this->build_matrix_payload();

		$this->assertSame(
			[
				'enabled' => $enabled,
				'reason'  => $reason,
			],
			$payload['state'][ $role_slug ][ $capability ]
		);
	}

	/**
	 * State cells for a met floor, a missed floor and a capability with no floor.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_role_capability_state(): array {
		return [
			'editor meets the floor'      => [ 'editor', EDAC_CAPABILITY_DISMISS_OWN_ISSUES, true, '' ],
			'contributor meets the floor' => [ 'contributor', EDAC_CAPABILITY_DISMISS_OWN_ISSUES, true, '' ],
			'subscriber misses the floor' => [ 'subscriber', EDAC_CAPABILITY_DISMISS_OWN_ISSUES, false, 'Requires the ability to edit posts.' ],
			'no floor is always met'      => [ 'subscriber', EDAC_CAPABILITY_VIEW_FRONTEND_HIGHLIGHTER, true, '' ],
		];
	}

	/**
	 * A capability the license filter locks is disabled with the license reason,
	 * even for a role that meets its floor.
	 *
	 * @return void
	 */
	public function test_matrix_state_marks_a_license_locked_capability_as_disabled(): void {
		add_filter(
			'edac_capability_is_editable',
			static function ( $editable, $slug ) {
				return EDAC_CAPABILITY_DISMISS_ISSUES === $slug ? false : $editable;
			},
			10,
			2
		);

		$payload = $this->build_matrix_payload();

		$this->assertSame(
			[
				'enabled' => false,
				'reason'  => 'Requires a valid license.',
			],
			$payload['state']['editor'][ EDAC_CAPABILITY_DISMISS_ISSUES ]
		);

		// The lock is per capability: the unlocked sibling is untouched.
		$this->assertSame( true, $payload['state']['editor'][ EDAC_CAPABILITY_DISMISS_OWN_ISSUES ]['enabled'] );
	}

	/**
	 * On the Permissions tab the picker assets load and the script is given the
	 * matrix and the strings it renders.
	 *
	 * @return void
	 */
	public function test_enqueue_assets_enqueues_the_picker_assets_and_their_payload(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_request( 'accessibility_checker_settings', PermissionsPage::PAGE_TAB_SLUG );

		$this->page->enqueue_assets();

		global $wp_scripts, $wp_styles;

		$this->assertTrue( wp_style_is( 'edac-permissions', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'edac-permissions', 'enqueued' ) );

		$this->assertStringContainsString( 'admin/css/permissions.css', (string) $wp_styles->registered['edac-permissions']->src );
		$this->assertStringContainsString( 'admin/js/permissions.js', (string) $wp_scripts->registered['edac-permissions']->src );

		$localized = $wp_scripts->get_data( 'edac-permissions', 'data' );
		$this->assertIsString( $localized );
		$this->assertStringContainsString( 'edacPermissions', $localized );
		$this->assertStringContainsString( '"matrix"', $localized );
		$this->assertStringContainsString( '"slug":"editor"', $localized );
		$this->assertStringContainsString( '"strings"', $localized );
		$this->assertStringContainsString( 'No capabilities are available.', $localized );
	}

	/**
	 * The picker assets load only on the Permissions tab of the settings screen,
	 * and only for a user who can manage options.
	 *
	 * @dataProvider provider_requests_that_skip_the_picker_assets
	 *
	 * @param string $role Role to sign in as.
	 * @param string $page Page slug for $_GET, or '' for none.
	 * @param string $tab  Tab slug for $_GET, or '' for none.
	 * @return void
	 */
	public function test_enqueue_assets_skips_requests_that_are_not_the_permissions_tab( string $role, string $page, string $tab ): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => $role ] ) );
		$this->set_request( $page, $tab );

		// Only the administrator cases reach the capability gate, not the page gate.
		$this->assertSame( 'administrator' === $role, current_user_can( 'manage_options' ) );

		$this->page->enqueue_assets();

		$this->assertFalse( wp_style_is( 'edac-permissions', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'edac-permissions', 'enqueued' ) );
	}

	/**
	 * Admin requests the picker must not load on.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function provider_requests_that_skip_the_picker_assets(): array {
		return [
			'no query args'      => [ 'administrator', '', '' ],
			'page without a tab' => [ 'administrator', 'accessibility_checker_settings', '' ],
			'tab without a page' => [ 'administrator', '', 'permissions' ],
			'another tab'        => [ 'administrator', 'accessibility_checker_settings', 'fixes' ],
			'another admin page' => [ 'administrator', 'edit.php', 'permissions' ],
			'editor on the tab'  => [ 'editor', 'accessibility_checker_settings', 'permissions' ],
		];
	}
}
