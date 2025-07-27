<?php
/**
 * MetaViewport Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\MetaViewportScalableFix;

/**
 * MetaViewport Rule class.
 */
class MetaViewportRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Zooming and Scaling Disabled', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help7947',
			'slug'                  => 'meta_viewport',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>user-scalable=no</code>, %2$s is <code>maximum-scale</code>.
				esc_html__( 'This page disables zooming by using a viewport meta tag with either %1$s or a %2$s value of less than 2. This prevents users from enlarging content to improve readability.', 'accessibility-checker' ),
				'<code>user-scalable=no</code>',
				'<code>maximum-scale</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>user-scalable=no</code>, %2$s is <code>maximum-scale</code>.
				esc_html__( 'These pages disable zooming by using viewport meta tags with either %1$s or a %2$s value of less than 2. This prevents users from enlarging content to improve readability.', 'accessibility-checker' ),
				'<code>user-scalable=no</code>',
				'<code>maximum-scale</code>'
			),
			'why_it_matters'        => esc_html__( 'Restricting a user\'s ability to zoom creates barriers for individuals with low vision or other visual impairments. These users may rely on browser zoom features, pinch-zooming on mobile, or magnifiers to access content comfortably.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>user-scalable=no</code>, %2$s is <code>maximum-scale</code>, %3$s is <head>.
				esc_html__( 'Edit the viewport meta tag in your theme\'s %3$s section and remove %1$s and any %2$s value less than 2. To fix this site-wide, enable the \'Make Viewport Scalable\' fix in Accessibility Checker Settings.', 'accessibility-checker' ),
				'<code>user-scalable=no</code>',
				'<code>maximum-scale</code>',
				'<code>&lt;head&gt;</code>'
			),
			'references'            => [
				[
					'text' => esc_html__( 'MDN Web Docs: <meta name="viewport">', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/meta/name/viewport',
				],
			],
			'wcag'                  => '1.4.4',
			'severity'              => 1, // Critical.
			'affected_disabilities' => [
				AffectedDisabilities::LOW_VISION,
			],
			'ruleset'               => 'js',
			'combines'              => [ 'meta-viewport' ],
			'viewable'              => false,
			'fixes'                 => [
				MetaViewportScalableFix::get_slug(),
			],
		];
	}
}
