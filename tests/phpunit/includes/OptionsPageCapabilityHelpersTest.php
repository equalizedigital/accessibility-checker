<?php
/**
 * Tests for the capability editability and role floor helpers.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_capability_is_editable(), edac_role_meets_floor() and
 * edac_floor_requirement_label().
 *
 * @covers ::edac_capability_is_editable
 * @covers ::edac_role_meets_floor
 * @covers ::edac_floor_requirement_label
 */
class OptionsPageCapabilityHelpersTest extends WP_UnitTestCase {

	/**
	 * Role capabilities the tests modify, by role slug.
	 */
	private const MODIFIED_CAPS = [
		'subscriber' => [ 'edit_posts' ],
		'editor'     => [ 'edit_others_posts' ],
	];

	/**
	 * The capability state of the roles above, captured before each test so
	 * tearDown() can restore it rather than assuming a stock role set.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $original_caps = [];

	/**
	 * Captures the role capabilities the tests may modify.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( self::MODIFIED_CAPS as $role_slug => $caps ) {
			$role = wp_roles()->get_role( $role_slug );

			foreach ( $caps as $cap ) {
				$this->original_caps[ $role_slug ][ $cap ] = array_key_exists( $cap, $role->capabilities ) ? $role->capabilities[ $cap ] : null;
			}
		}
	}

	/**
	 * Removes the filters and puts the role capabilities back as they were.
	 */
	public function tearDown(): void {
		remove_all_filters( 'edac_capability_is_editable' );

		foreach ( $this->original_caps as $role_slug => $caps ) {
			$role = wp_roles()->get_role( $role_slug );

			foreach ( $caps as $cap => $original ) {
				if ( null === $original ) {
					$role->remove_cap( $cap );

					continue;
				}

				$role->add_cap( $cap, $original );
			}
		}

		parent::tearDown();
	}

	/**
	 * A capability with no owner metadata is editable.
	 */
	public function test_capability_without_metadata_is_editable(): void {
		$this->assertTrue( edac_capability_is_editable( 'edac_export_data' ) );
	}

	/**
	 * A capability owned by an add-on other than Pro is editable.
	 */
	public function test_capability_owned_by_another_add_on_is_editable(): void {
		$this->assertTrue( edac_capability_is_editable( 'edac_export_data', [ 'owner' => 'some-add-on' ] ) );
	}

	/**
	 * A Pro capability is locked while the Pro license is not valid.
	 *
	 * EDAC_KEY_VALID is defined once at load by the plugin's own main file from
	 * the edacp_license_status option, so the licensed branch cannot be reached
	 * from a test in this process. The guard makes this skip rather than fail in
	 * an environment that has Pro loaded and licensed.
	 */
	public function test_pro_capability_is_locked_without_a_valid_license(): void {
		if ( defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID ) {
			$this->markTestSkipped( 'EDAC_KEY_VALID is true in this process, so the locked branch is unreachable from a test here.' );
		}

		$this->assertFalse( edac_capability_is_editable( 'edac_issues_explorer_access', [ 'owner' => 'accessibility-checker-pro' ] ) );
	}

	/**
	 * The filter can lock a capability that would otherwise be editable, and
	 * leaves other capabilities alone.
	 */
	public function test_the_filter_can_lock_a_single_capability(): void {
		add_filter(
			'edac_capability_is_editable',
			static function ( $editable, $slug ) {
				return 'edac_export_data' === $slug ? false : $editable;
			},
			10,
			2
		);

		$this->assertFalse( edac_capability_is_editable( 'edac_export_data' ) );
		$this->assertTrue( edac_capability_is_editable( 'edac_view_audit_history' ) );
	}

	/**
	 * The filter receives the computed default, the slug and the metadata.
	 */
	public function test_the_filter_receives_the_default_slug_and_metadata(): void {
		$received = [];

		add_filter(
			'edac_capability_is_editable',
			static function ( $editable, $slug, $meta ) use ( &$received ) {
				$received = [
					'editable' => $editable,
					'slug'     => $slug,
					'meta'     => $meta,
				];

				return $editable;
			},
			10,
			3
		);

		$meta = [
			'owner' => 'some-add-on',
			'floor' => 'edit_posts',
		];

		edac_capability_is_editable( 'edac_export_data', $meta );

		$this->assertSame(
			[
				'editable' => true,
				'slug'     => 'edac_export_data',
				'meta'     => $meta,
			],
			$received
		);
	}

	/**
	 * A filter returning a truthy non-boolean makes the capability editable.
	 */
	public function test_a_truthy_filter_value_is_cast_to_true(): void {
		add_filter( 'edac_capability_is_editable', static fn() => 'yes' );

		$this->assertTrue( edac_capability_is_editable( 'edac_export_data', [ 'owner' => 'some-add-on' ] ) );
	}

	/**
	 * A filter returning a falsy non-boolean locks the capability.
	 */
	public function test_a_falsy_filter_value_is_cast_to_false(): void {
		add_filter( 'edac_capability_is_editable', static fn() => 0 );

		$this->assertFalse( edac_capability_is_editable( 'edac_export_data', [ 'owner' => 'some-add-on' ] ) );
	}

	/**
	 * An empty floor is met without looking at the role at all.
	 */
	public function test_an_empty_floor_is_always_met(): void {
		$this->assertTrue( edac_role_meets_floor( 'subscriber', '' ) );
		$this->assertTrue( edac_role_meets_floor( 'edac_no_such_role', '' ) );
	}

	/**
	 * A role that holds the floor capability meets the floor.
	 */
	public function test_a_role_holding_the_floor_capability_meets_it(): void {
		$this->assertTrue( edac_role_meets_floor( 'editor', 'edit_posts' ) );
		$this->assertTrue( edac_role_meets_floor( 'editor', 'edit_others_posts' ) );
	}

	/**
	 * The role's live capabilities are used, not an assumption about the role.
	 */
	public function test_a_role_granted_the_capability_elsewhere_meets_the_floor(): void {
		wp_roles()->get_role( 'subscriber' )->add_cap( 'edit_posts' );

		$this->assertTrue( edac_role_meets_floor( 'subscriber', 'edit_posts' ) );
	}

	/**
	 * A role that does not hold the floor capability does not meet the floor.
	 */
	public function test_a_role_without_the_floor_capability_does_not_meet_it(): void {
		$this->assertFalse( edac_role_meets_floor( 'subscriber', 'edit_posts' ) );
	}

	/**
	 * A capability explicitly set to false does not meet the floor.
	 */
	public function test_a_capability_set_to_false_does_not_meet_the_floor(): void {
		wp_roles()->get_role( 'editor' )->add_cap( 'edit_others_posts', false );

		$this->assertFalse( edac_role_meets_floor( 'editor', 'edit_others_posts' ) );
	}

	/**
	 * An unknown role does not meet a floor it cannot be checked against.
	 */
	public function test_an_unknown_role_does_not_meet_a_floor(): void {
		$this->assertFalse( edac_role_meets_floor( 'edac_no_such_role', 'edit_posts' ) );
	}

	/**
	 * An empty floor has no label.
	 */
	public function test_an_empty_floor_has_no_label(): void {
		$this->assertSame( '', edac_floor_requirement_label( '' ) );
	}

	/**
	 * Known floors are described in a sentence.
	 *
	 * @dataProvider provider_known_floors
	 *
	 * @param string $floor    The floor capability.
	 * @param string $expected The expected label.
	 */
	public function test_a_known_floor_has_a_description( string $floor, string $expected ): void {
		$this->assertSame( $expected, edac_floor_requirement_label( $floor ) );
	}

	/**
	 * Data provider for the described floors.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_known_floors(): array {
		return [
			'edit_posts'        => [ 'edit_posts', 'Requires the ability to edit posts.' ],
			'edit_others_posts' => [ 'edit_others_posts', "Requires the ability to edit other users' posts." ],
		];
	}

	/**
	 * A floor with no description falls back to the raw capability name.
	 */
	public function test_an_unknown_floor_falls_back_to_the_capability_name(): void {
		$this->assertSame( 'Requires manage_options.', edac_floor_requirement_label( 'manage_options' ) );
	}
}
