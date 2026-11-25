<?php
/**
 * Orders the Accessibility Checker admin submenu.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reorders the Accessibility Checker submenu from a single, filterable list.
 *
 * The free plugin, Pro and each add-on register their own submenu pages, and each
 * uses a different position mechanism (hard-coded integers, a computed position,
 * or simply appending), so the resulting order depended on hook order. This class
 * runs after all of them and applies one explicit order instead.
 */
class MenuOrder {

	/**
	 * Slug of the top-level Accessibility Checker menu.
	 *
	 * @var string
	 */
	const PARENT_SLUG = 'accessibility_checker';

	/**
	 * Placeholder in the order list marking where unlisted submenu items go.
	 *
	 * @var string
	 */
	const WILDCARD = '*';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init_hooks(): void {
		// After the Upgrade item (999) and every add-on registration.
		add_action( 'admin_menu', [ $this, 'reorder' ], 1000 );
	}

	/**
	 * Get the ordered list of submenu slugs.
	 *
	 * Slugs for pages that aren't active are ignored. Items whose slug isn't in the
	 * list are placed where the '*' placeholder sits, keeping their relative order.
	 *
	 * @return string[]
	 */
	public function get_order(): array {
		$order = [
			self::PARENT_SLUG, // The first item WordPress creates automatically.
			'accessibility_checker_issues_explorer',
			'accessibility_checker_full_site_scan',
			'accessibility_checker_audit_history',
			'accessibility_checker_export_data',
			self::WILDCARD,
			'edit.php?post_type=edac_virtual_item', // Archive Pages.
			'accessibility_checker_settings',
			'accessibility_checker_upgrade',
		];

		/**
		 * Filter the order of the Accessibility Checker admin submenu.
		 *
		 * @since x.x.x
		 *
		 * @param string[] $order Submenu slugs in display order. Include '*' to mark where
		 *                        items not in the list should appear.
		 */
		$order = apply_filters( 'edac_filter_admin_submenu_order', $order );

		return is_array( $order ) ? array_values( $order ) : [];
	}

	/**
	 * Sort items into the shared order.
	 *
	 * Used for the admin submenu and the admin toolbar so both follow one list.
	 * The sort is stable: items with the same rank keep their relative order.
	 *
	 * @param array    $items    Items to sort.
	 * @param callable $get_slug Receives an item and returns its menu slug.
	 * @return array Sorted items, re-indexed.
	 */
	public function sort( array $items, callable $get_slug ): array {
		$order = $this->get_order();
		if ( empty( $order ) ) {
			return array_values( $items );
		}

		$positions = array_flip( $order );
		$wildcard  = $positions[ self::WILDCARD ] ?? count( $order );

		$ranked = [];
		foreach ( array_values( $items ) as $index => $item ) {
			$ranked[] = [
				'rank'  => $positions[ $get_slug( $item ) ] ?? $wildcard,
				'index' => $index, // Tie-breaker keeps the sort stable on every PHP version.
				'item'  => $item,
			];
		}

		usort(
			$ranked,
			static function ( $a, $b ) {
				return [ $a['rank'], $a['index'] ] <=> [ $b['rank'], $b['index'] ];
			}
		);

		return array_column( $ranked, 'item' );
	}

	/**
	 * Reorder the submenu.
	 *
	 * @return void
	 */
	public function reorder(): void {
		global $submenu;

		if ( empty( $submenu[ self::PARENT_SLUG ] ) || ! is_array( $submenu[ self::PARENT_SLUG ] ) ) {
			return;
		}

		$sorted = $this->sort(
			array_values( $submenu[ self::PARENT_SLUG ] ),
			static function ( $item ) {
				return $item[2] ?? '';
			}
		);

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reordering the menu is the purpose of this class.
		$submenu[ self::PARENT_SLUG ] = $sorted;
	}
}
