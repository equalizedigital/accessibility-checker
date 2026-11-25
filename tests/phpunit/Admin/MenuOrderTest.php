<?php
/**
 * Class MenuOrderTest
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\MenuOrder;

/**
 * Tests for the admin submenu ordering.
 */
class MenuOrderTest extends WP_UnitTestCase {

	/**
	 * Original submenu global.
	 *
	 * @var mixed
	 */
	private $original_submenu;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();
		global $submenu;
		$this->original_submenu = $submenu;
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		global $submenu;
		$submenu = $this->original_submenu; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		remove_all_filters( 'edac_filter_admin_submenu_order' );
		parent::tearDown();
	}

	/**
	 * Build a submenu array from slugs.
	 *
	 * @param string[] $slugs Slugs in registration order.
	 * @return array
	 */
	private function make_submenu( array $slugs ): array {
		return array_map(
			static function ( $slug ) {
				return [ $slug, 'read', $slug ];
			},
			$slugs
		);
	}

	/**
	 * Get the resulting slugs.
	 *
	 * @return string[]
	 */
	private function slugs(): array {
		global $submenu;
		return array_column( $submenu[ MenuOrder::PARENT_SLUG ], 2 );
	}

	/**
	 * Hooks into admin_menu late.
	 */
	public function test_init_hooks_registers_late_admin_menu_action() {
		$menu_order = new MenuOrder();
		$menu_order->init_hooks();
		$this->assertSame( 1000, has_action( 'admin_menu', [ $menu_order, 'reorder' ] ) );
	}

	/**
	 * Full Pro + add-ons set comes out in the agreed order regardless of registration order.
	 */
	public function test_reorders_full_menu() {
		global $submenu;
		$submenu[ MenuOrder::PARENT_SLUG ] = $this->make_submenu( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			[
				'accessibility_checker',
				'accessibility_checker_full_site_scan',
				'accessibility_checker_issues_explorer',
				'edit.php?post_type=edac_virtual_item',
				'accessibility_checker_audit_history',
				'accessibility_checker_settings',
				'accessibility_checker_export_data',
				'accessibility_checker_upgrade',
			]
		);

		( new MenuOrder() )->reorder();

		$this->assertSame(
			[
				'accessibility_checker',
				'accessibility_checker_issues_explorer',
				'accessibility_checker_full_site_scan',
				'accessibility_checker_audit_history',
				'accessibility_checker_export_data',
				'edit.php?post_type=edac_virtual_item',
				'accessibility_checker_settings',
				'accessibility_checker_upgrade',
			],
			$this->slugs()
		);
	}

	/**
	 * Free-only sites (missing items) still order correctly.
	 */
	public function test_handles_missing_items() {
		global $submenu;
		$submenu[ MenuOrder::PARENT_SLUG ] = $this->make_submenu( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			[ 'accessibility_checker', 'accessibility_checker_upgrade', 'accessibility_checker_settings' ]
		);

		( new MenuOrder() )->reorder();

		$this->assertSame(
			[ 'accessibility_checker', 'accessibility_checker_settings', 'accessibility_checker_upgrade' ],
			$this->slugs()
		);
	}

	/**
	 * Unlisted items go at the wildcard position and keep their relative order.
	 */
	public function test_unlisted_items_use_wildcard_position_in_stable_order() {
		global $submenu;
		$submenu[ MenuOrder::PARENT_SLUG ] = $this->make_submenu( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			[
				'accessibility_checker',
				'accessibility_checker_settings',
				'third_party_b',
				'accessibility_checker_export_data',
				'third_party_a',
			]
		);

		( new MenuOrder() )->reorder();

		$this->assertSame(
			[
				'accessibility_checker',
				'accessibility_checker_export_data',
				'third_party_b',
				'third_party_a',
				'accessibility_checker_settings',
			],
			$this->slugs()
		);
	}

	/**
	 * The order is filterable.
	 */
	public function test_order_is_filterable() {
		global $submenu;
		$submenu[ MenuOrder::PARENT_SLUG ] = $this->make_submenu( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			[ 'accessibility_checker', 'accessibility_checker_settings', 'accessibility_checker_export_data' ]
		);

		add_filter(
			'edac_filter_admin_submenu_order',
			static function () {
				return [ 'accessibility_checker', 'accessibility_checker_settings', 'accessibility_checker_export_data' ];
			}
		);

		( new MenuOrder() )->reorder();

		$this->assertSame(
			[ 'accessibility_checker', 'accessibility_checker_settings', 'accessibility_checker_export_data' ],
			$this->slugs()
		);
	}

	/**
	 * No submenu for the parent means nothing happens.
	 */
	public function test_no_submenu_is_a_noop() {
		global $submenu;
		unset( $submenu[ MenuOrder::PARENT_SLUG ] );

		( new MenuOrder() )->reorder();

		$this->assertArrayNotHasKey( MenuOrder::PARENT_SLUG, $submenu );
	}
}
