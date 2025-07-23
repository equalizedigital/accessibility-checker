<?php
/**
 * LinkBlank Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\{
	PreventLinksOpeningNewWindowFix,
	AddNewWindowWarningFix
};

/**
 * LinkBlank Rule class.
 */
class LinkBlankRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Link Opens New Window or Tab', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1982',
			'slug'                  => 'link_blank',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This link is set to open in a new browser tab or window.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These links are set to open in new browser tabs or windows.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'When a link opens in a new tab or window without warning, it can be disorienting, especially for users with cognitive disabilities, screen reader users, or anyone relying on keyboard navigation. They may not realize a new context has opened or understand how to return.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>target="_blank"</code>.
				esc_html__( 'Avoid using %1$s unless absolutely necessary. If a link must open in a new tab or window, add a visible icon and screen reader text such as "opens in a new window" or "opens in a new tab" to inform users. To automate this and dismiss the warning sitewide, you can activate the \'Add Label To Links That Open A New Tab/Window\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
				'<code>target="_blank"</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C Technique H83: Using the target attribute to open a new window on user request and indicating this in link text', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H83',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '3.2.2',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::MOBILITY,
			],
			'combines'              => [
				'link_blank',
			],
			'fixes'                 => [
				PreventLinksOpeningNewWindowFix::get_slug(),
				AddNewWindowWarningFix::get_slug(),
			],
		];
	}
}
