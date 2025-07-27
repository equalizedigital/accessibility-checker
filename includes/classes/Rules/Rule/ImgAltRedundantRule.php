<?php
/**
 * ImgAltRedundant Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgAltRedundant Rule class.
 */
class ImgAltRedundantRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Duplicate Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1976',
			'slug'                  => 'img_alt_redundant',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This image has alternative text that is identical to nearby content or to another image on the page.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These images have alternative text that is identical to nearby content or to other images on the page.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'When alternative text repeats nearby text or appears identically across multiple images, it creates confusion for screen reader users. Repetition provides no additional context and makes it difficult to distinguish between different elements or understand their purpose.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'If the alt text duplicates a nearby caption, heading, or visible text, remove the redundancy by shortening or omitting the alt text—especially if the surrounding text already provides the same information. If multiple images have the same alt text, revise each one to be unique and describe the purpose of that specific image in its context.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'W3C Tutorial on Images: Informative Images', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/images/informative/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.1.1',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
			],
		];
	}
}
