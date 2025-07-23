<?php
/**
 * ImgLinkedAltEmpty Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgLinkedAltEmpty Rule class.
 */
class ImgLinkedAltEmptyRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Linked Image Empty Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1930',
			'slug'                  => 'img_linked_alt_empty',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This image is inside a link and has an empty alt attribute and there is no other text within the link.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These images are inside links and have empty alt attributes and there is no other text within the links.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'An empty alt attribute on a linked image causes screen reader users to hear just "link" with no context. This makes navigation confusing and may cause them to skip important content.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Replace the empty alt attribute with meaningful alternative text that clearly describes the link’s destination or purpose.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'W3C Tutorial on Images: Functional Images', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/images/functional/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.1.1',
			'severity'              => 1, // Critical.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
			],
		];
	}
}
