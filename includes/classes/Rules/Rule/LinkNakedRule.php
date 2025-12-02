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
			'title'                 => esc_html__( 'Link is Naked', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help10283',
			'slug'                  => 'link_naked',
			'rule_type'             => 'error',
			'summary'               => esc_html__(
				'A Naked Links warning appears when there are links on your post or page that are not descriptive of where they will take a user if clicked. This commonly occurs with links that are just a URL, such as "https://example.com" or "www.example.com". To fix a Naked Links warning, you need to change the link text to be descriptive of where the link will take a user if clicked. For example, instead of linking to "https://example.com", you could link to "Visit Example".',
				'accessibility-checker'
			),
			// Plural form: used when multiple naked links are found.
			'summary_plural'        => esc_html__(
				'These links are not descriptive of their destination (for example they use the raw URL as link text). Provide meaningful link text that describes where the link will take the user.',
				'accessibility-checker'
			),
			'why_it_matters'        => esc_html__(
				'Naked links (links whose visible text is just a URL) do not provide meaningful context about their destination. Users of screen readers and other assistive technologies rely on descriptive link text to understand where a link will take them; non‑descriptive links reduce usability, increase cognitive load, and can be confusing or misleading.',
				'accessibility-checker'
			),
			'how_to_fix'            => esc_html__(
				'Replace the URL text with concise, descriptive link text that explains the destination or purpose of the link (for example, "Read the accessibility guide" instead of "https://example.com/guide"). If the URL is needed for context, include it in nearby visible text rather than as the link text itself.',
				'accessibility-checker'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Techniques for WCAG 2.1 - Descriptive Link Text', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/general/G91',
				],
				[
					'text' => __( 'WCAG: Master the Art of Accessible Link Text', 'accessibility-checker' ),
					'url'  => 'https://www.wcag.com/blog/writing-meaningful-link-text/',
				],
			],
			'ruleset'               => 'js',
			// WCAG success criterion related to link purpose in context.
			'wcag'                  => '2.4.4',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
			],
		];
	}
}
