<?php
/**
 * ImgAltInvalid Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgAltInvalid Rule class.
 */
class ImgAltInvalidRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Low-quality Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1977',
			'slug'                  => 'img_alt_invalid',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This image has alternative text that may be vague, redundant, or include unnecessary words or file names.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These images have alternative text that may be vague, redundant, or include unnecessary words or file names.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Alternative text helps people using screen readers understand the purpose of an image. When alt text includes words like “image,” “graphic,” or a file extension, it adds no useful information and can create confusion or distraction. Clear and relevant alt text improves comprehension and user experience.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Rewrite the alternative text to accurately and concisely describe the purpose of the image in context. Avoid including words like “image,” “graphic,” file names or extensions (e.g., .jpg, .png), or placeholder terms like “spacer” or “arrow.” If the image is decorative, leave the alt attribute empty.', 'accessibility-checker' ),
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
