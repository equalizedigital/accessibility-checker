<?php
/**
 * LinkNaked.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * Link Naked Rule class.
 */
class LinkNakedRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {

		return [
			'title'                 => esc_html__( 'Link Text is URL', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help10283',
			'slug'                  => 'link_naked',
			'rule_type'             => 'warning',
			'summary'               => esc_html__(
				'This link uses a URL for its anchor text rather than a meaningful word or phrase.',
				'accessibility-checker'
			),
			'summary_plural'        => esc_html__(
				'These links use URLs for their anchor text rather than meaningful words or phrases.',
				'accessibility-checker'
			),
			'why_it_matters'        => esc_html__(
				'Link text that is just a URL may not provide meaningful context about the link destination. Additionally, long URLs can be difficult to understand when read by a screen reader which may announce every letter, number, and character individually. Users rely on descriptive link text to understand where a link will take them; using URLs for your link text reduces usability, increases cognitive load, and can be confusing or misleading.',
				'accessibility-checker'
			),
			'how_to_fix'            => esc_html__(
				'Replace the URL in the link text with a concise, descriptive word or phrase that explains the destination or purpose of the link (for example, "Read the accessibility guide" instead of "https://example.com/guide"). If the URL is necessary for context and is very short, you can dismiss this warning by using the "Ignore" feature in Accessibility Checker.',
				'accessibility-checker'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Technique G91: Providing link text that describes the purpose of a link', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG22/Techniques/general/G91',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '2.4.4',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
			],
		];
	}
}
