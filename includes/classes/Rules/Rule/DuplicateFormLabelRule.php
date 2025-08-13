<?php
/**
 * DuplicateFormLabel Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * DuplicateFormLabel Rule class.
 */
class DuplicateFormLabelRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Duplicate Form Label', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1954',
			'slug'                  => 'duplicate_form_label',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This form field has more than one label associated with it.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These form fields have more than one label associated with them.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Assistive technologies rely on a one-to-one relationship between form fields and labels to provide accurate guidance to users. When multiple labels are associated with the same field, it can confuse screen reader users and make form completion difficult as the screen reader may read the incorrect label for a field.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;label&gt;</code>, %2$s is <code>for=""</code>.
				esc_html__( 'Ensure each form field has only one %1$s element associated with it. If multiple labels are present, remove extras or consolidate them into a single %1$s with a clear description. Make sure each %1$s element correctly references its form field using the %2$s attribute and that all elements on the page have unique IDs. This error happens when you have the same form embedded on a page twice and can be resolved by creating unique forms for different parts of a page rather than repeating the same form throughout.', 'accessibility-checker' ),
				'<code>&lt;label&gt;</code>',
				'<code>for=""</code>'
			),
			'references'            => [
				[
					'text' => __( 'MDN: <label> – The HTML Label element', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/label',
				],
				[
					'text' => __( 'W3C: H44 – Using label elements to associate text labels with form controls', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H44',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
			],
			'combines'              => [ 'form-field-multiple-labels' ],
		];
	}
}
