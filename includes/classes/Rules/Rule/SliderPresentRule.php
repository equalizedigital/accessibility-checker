<?php
/**
 * SliderPresent Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * SliderPresent Rule class.
 */
class SliderPresentRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'A Slider is Present', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help3264',
			'slug'                  => 'slider_present',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This element is a slider or carousel. Because many accessibility issues with sliders require manual review, this warning appears any time a slider is detected on a post or page.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements are sliders or carousels. Because many accessibility issues with sliders require manual review, this warning appears any time one or more sliders are detected on a post or page.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Sliders are often difficult to use with screen readers and keyboards. Inaccessible sliders can interfere with navigation, trap focus, or move too quickly for users to engage with the content.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Review the slider on the front end of your website. Ensure it is keyboard accessible, pauseable, has proper ARIA roles and labels, and works well with screen readers. After confirming the slider is accessible or remediating issues, you can dismiss this warning by using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'W3C: Carousels Tutorial', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/carousels/',
				],
				[
					'text' => __( 'W3C ARIA Authoring Practices Guide: Carousel (Slide Show or Image Rotator) Pattern', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/ARIA/apg/patterns/carousel/',
				],
			],
			'wcag'                  => '0.3',
			'severity'              => 1, // Critical..
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::MOBILITY,
			],
			'ruleset'               => 'js',
		];
	}
}
