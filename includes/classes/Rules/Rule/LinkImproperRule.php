<?php
/**
 * LinkImproper Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * LinkImproper Rule class.
 */
class LinkImproperRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Improper Use of Link', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help6473',
			'slug'                  => 'link_improper',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>href</code>, %2$s is <code>#</code>, %3$s is <code>role="button"</code>.
				esc_html__( 'This element is an anchor tag missing a valid %1$s attribute or is linked to %2$s without having %3$s.', 'accessibility-checker' ),
				'<code>href</code>',
				'<code>#</code>',
				'<code>role="button"</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>href</code>, %2$s is <code>#</code>, %3$s is <code>role="button"</code>.
				esc_html__( 'These elements are anchor tags missing valid %1$s attributes or are linked to %2$s without having %3$s.', 'accessibility-checker' ),
				'<code>href</code>',
				'<code>#</code>',
				'<code>role="button"</code>'
			),
			'why_it_matters'        => esc_html__( 'Anchor tags (a.k.a. links) are intended for navigation to a new page or a different place on the same page. When they are used to trigger actions (such as expanding accordions or opening modals) without the correct roles or behavior, they confuse users, particularly those using screen readers or keyboards, who expect links to navigate rather than perform actions. They also are likely not to function with the space bar, which is an expectation of a button.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;button&gt;</code>, %2$s is <code>role="button"</code>.
				esc_html__( 'If the element is used to trigger an action, replace the anchor tag with a %1$s. If you cannot replace it, ensure that %2$s is added to the link, along with JavaScript that adds support for triggering it with the space bar key, and that appropriate ARIA attributes are used for toggle states or other functionality.', 'accessibility-checker' ),
				'<code>&lt;button&gt;</code>',
				'<code>role="button"</code>'
			),
			'references'            => [
				[
					'text' => __( 'MDN Web Docs: ARIA button role', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Roles/button_role',
				],
				[
					'text' => __( 'W3 Schools: Accessibility Buttons & Links', 'accessibility-checker' ),
					'url'  => 'https://www.w3schools.com/accessibility/accessibility_buttons_links.php',
				],
			],
			'wcag'                  => '4.1.2',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::MOBILITY,
			],
			'ruleset'               => 'js',
		];
	}
}
