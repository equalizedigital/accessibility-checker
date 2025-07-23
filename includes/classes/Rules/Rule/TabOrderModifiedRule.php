<?php
/**
 * TabOrderModified Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\TabindexFix;

/**
 * TabOrderModified Rule class.
 */
class TabOrderModifiedRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Tab Order Modified', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1974',
			'slug'                  => 'tab_order_modified',
			'rule_type'             => 'warning',
			'summary'               => sprintf(
			// translators: %1$s is <code>tabindex="1"</code> and %2$s is <code>tabindex</code>.
				esc_html__( 'This page contains an element with %1$s or another positive %2$s, which modifies the natural tab order.', 'accessibility-checker' ),
				'<code>tabindex="1"</code>',
				'<code>tabindex</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>tabindex="1"</code> and %2$s is <code>tabindex</code>.
				esc_html__( 'These pages contain elements with %1$s or other positive %2$s, which modify the natural tab order.', 'accessibility-checker' ),
				'<code>tabindex="1"</code>',
				'<code>tabindex</code>'
			),
			'why_it_matters'        => esc_html__( 'The natural tab order of a page follows the structure of the HTML. Changing this order using positive tabindex values can cause confusion for keyboard-only users and screen reader users, especially if the focus moves in an unexpected way or skips important content.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %s is <code>tabindex</code>.
				esc_html__( 'Remove positive %s values (greater than 0) from elements unless there is a very specific, user-tested reason to change the focus order. If needed, use tabindex="0" to include custom elements in the natural tab flow without disrupting order. To fix this and other elements site-wide, enable the \'Remove Tab Index\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
				'<code>tabindex</code>'
			),
			'references'            => [
				[
					'text' => __( 'ARIA Authoring Practices Guide: Developing a Keyboard Interface', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/ARIA/apg/practices/keyboard-interface/',
				],
				[
					'text' => __( 'W3C: Focus Order and Keyboard Navigation', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/general/G59',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '2.4.3',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::MOBILITY,
			],
			'combines'              => [ 'tabindex' ],
			'fixes'                 => [
				TabindexFix::get_slug(),
			],
		];
	}
}
