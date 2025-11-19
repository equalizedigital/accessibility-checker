<?php
/**
 * ImgLinkedAltMissing Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgLinkedAltMissing Rule class.
 */
class ImgLinkedAltMissingRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Linked Image Missing Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1930',
			'slug'                  => 'img_linked_alt_missing',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This image is inside a link but does not have an alt attribute and there is no other text within the link.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These images are inside links but do not have alt attributes and there is no other text within the links.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Screen reader users rely on alternative text to understand the purpose of linked images. Without it, they cannot determine where the link leads, making navigation difficult or impossible.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			/* translators: %s is <code>aria-label</code>. */
				esc_html__( 'Add an alt attribute that describes the purpose of the link. Focus on where the link goes, not what the image looks like. Alternatively, add an %s to the link.', 'accessibility-checker' ),
				'<code>aria-label</code>',
			),
			'references'            => [
				[
					'text' => __( 'W3C Tutorial on Images: Functional Images', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/images/functional/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.1.1',
			'severity'              => 1, // Critical..
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
			],
		];
	}
}
