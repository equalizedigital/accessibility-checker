<?php
/**
 * LongDescriptionInvalid Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * LongDescriptionInvalid Rule class.
 */
class LongDescriptionInvalidRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Long Description Invalid', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1948',
			'slug'                  => 'long_description_invalid',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %s is <code>longdesc=""</code.
				esc_html__( 'This element uses the %s attribute with an invalid or unsupported value.', 'accessibility-checker' ),
				'<code>longdesc=""</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %s is <code>longdesc=""</code.
				esc_html__( 'These elements use the %s attribute with invalid or unsupported values.', 'accessibility-checker' ),
				'<code>longdesc=""</code>'
			),
			'why_it_matters'        => esc_html__( 'The longdesc attribute is intended to provide a URL to a long description of an image for screen reader users. However, it is no longer supported in HTML5 and is not reliably recognized by browsers or assistive technologies. Invalid values or blank longdesc attributes may confuse users or fail to convey important information.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %s is <code>longdesc</code.
				esc_html__( 'Remove the %s attribute from the image element. If a long description is needed, include it in nearby visible text like a caption, use a link to a separate description page, or use ARIA techniques such as aria-describedby.', 'accessibility-checker' ),
				'<code>longdesc</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Techniques for WCAG 2.1 – H45: Using longdesc', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H45',
				],
				[
					'text' => __( 'MDN Web Docs: longdesc – HTML attribute', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#longdesc',
				],
				[
					'text' => __( 'MDN Web Docs: ARIA: aria-describedby attribute', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-describedby',
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
