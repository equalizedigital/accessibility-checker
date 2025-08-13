<?php
/**
 * ImgAltLong Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgAltLong Rule class.
 */
class ImgAltLongRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Image Long Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1966',
			'slug'                  => 'img_alt_long',
			'rule_type'             => 'warning',
			'summary'               => sprintf(
			// translators: %s is the maximum character count for alt text.
				esc_html__( 'This image has alternative text longer than %s characters.', 'accessibility-checker' ),
				'300'
			),
			'summary_plural'        => sprintf(
			// translators: %s is the maximum character count for alt text.
				esc_html__( 'These images have alternative text longer than %s characters.', 'accessibility-checker' ),
				'300'
			),
			'why_it_matters'        => esc_html__( 'Alternative text should be concise and focused on describing the purpose or meaning of the image. Overly long alt text may overwhelm screen reader users, reduce readability, and distract from other content on the page.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %s is the maximum character count for alt text.
				esc_html__( 'Shorten the alt text to fewer than %s characters while still describing the image\'s function or purpose. Keep descriptions simple and avoid repeating surrounding content. If the image\'s alt text does not need to be changed, dismiss this warning using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
				'300'
			),
			'references'            => [
				[
					'text' => __( 'W3C Tutorial: Informative Images', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/images/informative/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.1.1',
			'severity'              => 4, // Low.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
			],
		];
	}
}
