<?php
/**
 * BrokenAriaReference Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * BrokenAriaReference Rule class.
 */
class BrokenAriaReferenceRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Broken ARIA Reference', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1956',
			'slug'                  => 'broken_aria_reference',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This element uses an ARIA attribute that references another element which does not exist or is not properly labeled.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements use ARIA attributes that reference other elements which do not exist or are not properly labeled.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'ARIA attributes like aria-labelledby and aria-describedby are used to improve accessibility by connecting elements to their labels or descriptions. If the reference target does not exist, users of assistive technology will miss important context or instructions.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Inspect the ARIA attributes (such as aria-labelledby or aria-describedby) on the flagged elements and ensure that each one points to a valid, correctly labeled ID that exists in the document.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'MDN Web Docs: ARIA: aria-labelledby attribute', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-labelledby',
				],
				[
					'text' => __( 'MDN Web Docs: ARIA: aria-describedby attribute', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-describedby',
				],
				[
					'text' => __( 'ARIA for Beginners (Webinar)', 'accessibility-checker' ),
					'url'  => 'https://equalizedigital.com/aria-for-beginners-maria-maldonado/',
				],
			],
			'wcag'                  => '4.1.2',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::MOBILITY,
			],
			'combines'              => [ 'aria_broken_reference' ],
			'ruleset'               => 'js',
		];
	}
}
