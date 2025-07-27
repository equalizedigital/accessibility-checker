<?php
/**
 * ImgAltEmpty Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgAltEmpty Rule class.
 */
class ImgAltEmptyRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Image Empty Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help4991',
			'slug'                  => 'img_alt_empty',
			'rule_type'             => 'warning',
			'summary'               => sprintf(
			// translators: %s is <code>alt=""</code>.
				esc_html__( 'This image has an empty alt attribute (%s).', 'accessibility-checker' ),
				'<code>alt=""</code>',
			),
			'summary_plural'        => sprintf(
			// translators: %s is <code>alt=""</code>.
				esc_html__( 'These images have empty alt attributes (%s).', 'accessibility-checker' ),
				'<code>alt=""</code>',
			),
			'why_it_matters'        => esc_html__( 'Screen readers rely on alternative text to describe images to users who cannot see them. If the alt attribute is empty, it signals that the image is decorative and should be skipped. However, if a meaningful image has an empty alt attribute, users with visual impairments will miss important information. Proper use of alternative text improves accessibility and ensures all users can understand the content.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>alt=""</code> and %2$s is <code>role="presentation"</code>.
				esc_html__( 'Review the image to determine if it is decorative. If it is decorative, it is correct to use an empty %1$s attribute and you can dismiss this warning by using the "Ignore" feature in Accessibility Checker or adding %2$s to the image and rescanning the page. If the image conveys information, add descriptive alt text that communicates the image\'s purpose or meaning.', 'accessibility-checker' ),
				'<code>alt=""</code>',
				'<code>role="presentation"</code>',
			),
			'references'            => [
				[
					'text' => __( 'W3C Tutorial on Images: Decorative Images', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/images/decorative/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.1.1',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
			],
		];
	}
}
