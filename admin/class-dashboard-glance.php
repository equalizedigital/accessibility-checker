<?php
/**
 * Class file for adding Accessibility Checker info to the At a Glance widget.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\Helpers;
use EDAC\Admin\Scans_Stats;

/**
 * Class that hooks into the At a Glance dashboard widget.
 */
class Dashboard_Glance {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_filter( 'dashboard_glance_items', [ $this, 'add_glance_item' ] );
	}

	/**
	 * Adds an accessibility summary item to the At a Glance widget.
	 *
	 * @param array $items Existing glance items.
	 * @return array
	 */
	public function add_glance_item( $items ) {
		if ( ! Helpers::current_user_can_see_widgets_and_notices() ) {
			return $items;
		}

		$scans_stats = new Scans_Stats();
		$summary     = $scans_stats->summary();

		$issues = (int) $summary['distinct_errors'] + (int) $summary['distinct_warnings'];
		$count  = Helpers::format_number( $issues );

		$text = sprintf(
			/* translators: %s: number of issues */
			_n(
				'Accessibility: %s issue open.',
				'Accessibility: %s issues open.',
				$issues,
				'accessibility-checker'
			),
			$count
		);

		if ( ! ( defined( 'EDACP_VERSION' ) && EDAC_KEY_VALID ) ) {
			$link  = edac_generate_link_type(
				[
					'utm-campaign' => 'glance-widget',
					'utm-content'  => 'upgrade-to-edacp',
				],
				'pro'
			);
			$text .= ' <a href="' . esc_url( $link ) . '">' . esc_html__( 'Fix with Accessibility Checker Pro', 'accessibility-checker' ) . '</a>';
		}

		$items[] = $text;
		return $items;
	}
}
