<?php
/**
 * BrokenSkipAnchorLink Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * BrokenSkipAnchorLink Rule class.
 */
class BrokenSkipAnchorLinkRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Broken Skip or Anchor Link', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1962',
			'slug'                  => 'broken_skip_anchor_link',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This link points to an anchor target on the same page, but no element with the referenced ID exists.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These links point to anchor targets on the same page, but no elements with the referenced IDs exist.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Skip and anchor links are important for efficient keyboard and screen reader navigation. When these links are broken, users may be confused or lose their place on the page, leading to a frustrating experience—especially for people who rely on keyboard shortcuts or assistive technology.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Confirm that the link is pointing to a valid ID on the page. If the target element exists, make sure it has a matching id attribute. To automatically fix broken skip links throughout your site, enable the \'Enable Skip Link\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'How to Make Your WordPress Site More Accessible With Skip Links', 'accessibility-checker' ),
					'url'  => 'https://equalizedigital.com/how-to-make-your-wordpress-site-more-accessible-with-skip-links/',
				],
				[
					'text' => __( 'How to Fix Broken Skip Links in Elementor', 'accessibility-checker' ),
					'url'  => 'https://equalizedigital.com/how-to-fix-broken-skip-links-in-elementor/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '2.4.1',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::MOBILITY,
			],
		];
	}
}
