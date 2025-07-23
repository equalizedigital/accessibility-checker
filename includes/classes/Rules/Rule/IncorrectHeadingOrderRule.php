<?php
/**
 * IncorrectHeadingOrder Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * IncorrectHeadingOrder Rule class.
 */
class IncorrectHeadingOrderRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Incorrect Heading Order', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1940',
			'slug'                  => 'incorrect_heading_order',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is the found heading level (e.g., <h3>), %2$s is the previous heading level (e.g., <h1>.
				esc_html__( 'This page uses headings out of order. This is an %1$s after an %2$s, skipping one or more heading levels.', 'accessibility-checker' ),
				'<code>&lt;h3&gt;</code>',
				'<code>&lt;h1&gt;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h3&gt;</code>, %3$s is <code>&lt;h2&gt;</code.
				esc_html__( 'These pages use headings out of order, skipping one or more heading levels, such as going from %1$s to %2$s without an %3$s between.', 'accessibility-checker' ),
				'<code>&lt;h1&gt;</code>',
				'<code>&lt;h3&gt;</code>',
				'<code>&lt;h2&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Headings help all users—especially those using screen readers or keyboard navigation—understand the structure of the page. Skipping heading levels can create confusion and make it harder to follow the content hierarchy or navigate efficiently.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h3&gt;</code>, %3$s is <code>&lt;h2&gt;</code.
				esc_html__( 'Revise your headings to follow a logical order without skipping levels. For example, if a %1$s is followed by a %2$s, change the %2$s to a %3$s or add an intervening %3$s section to preserve proper structure. If needed, use CSS or block/page builder settings to style headings rather than setting the incorrect level.', 'accessibility-checker' ),
				'<code>&lt;h1&gt;</code>',
				'<code>&lt;h3&gt;</code>',
				'<code>&lt;h2&gt;</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C Page Structure Tutorial: Headings', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/page-structure/headings/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::MOBILITY,
			],
			'combines'              => [
				'heading-order',
			],
		];
	}
}
