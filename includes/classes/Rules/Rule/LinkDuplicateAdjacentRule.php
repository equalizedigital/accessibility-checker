<?php
/**
 * LinkDuplicateAdjacent Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * LinkDuplicateAdjacent Rule class.
 */
class LinkDuplicateAdjacentRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Adjacent Duplicate Links', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1990',
			'slug'                  => 'link_duplicate_adjacent',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This link sits directly next to another link that points to the same destination.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These links sit directly next to other links that point to the same destination.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'When two adjacent links point to the same destination, screen reader and keyboard users encounter the same stop twice in a row with no added benefit. This commonly happens by accident when a thumbnail image and its title are wrapped in separate links instead of being combined, which adds unnecessary navigation overhead and can be confusing.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>aria-hidden="true"</code>, %2$s is <code>tabindex="-1"</code>.
				esc_html__( 'Combine the adjacent links into a single link that wraps both the image and the text. If they must remain separate, make one of them (typically the thumbnail image link) presentational by adding %1$s and %2$s so it is skipped by assistive technology and keyboard navigation, leaving only one stop for that destination.', 'accessibility-checker' ),
				'<code>aria-hidden="true"</code>',
				'<code>tabindex="-1"</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C Technique H2: Combining adjacent image and text links for the same resource', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H2',
				],
				[
					'text' => __( 'Understanding WCAG SC 2.4.4: Link Purpose (In Context)', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Understanding/link-purpose-in-context.html',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '2.4.4',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::MOBILITY,
				AffectedDisabilities::COGNITIVE,
			],
		];
	}
}
