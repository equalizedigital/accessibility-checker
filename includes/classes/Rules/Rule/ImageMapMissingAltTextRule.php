<?php
/**
 * ImageMapMissingAltText Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImageMapMissingAltText Rule class.
 */
class ImageMapMissingAltTextRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Image Map Missing Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1938',
			'slug'                  => 'image_map_missing_alt_text',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;area&gt;</code.
				esc_html__( 'One or more %1$s elements in an image map are missing alternative text.', 'accessibility-checker' ),
				'<code>&lt;area&gt;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;area&gt;</code.
				esc_html__( 'One or more %1$s elements in image maps are missing alternative text.', 'accessibility-checker' ),
				'<code>&lt;area&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Image maps use area elements to define interactive regions. Without alternative text, screen reader users won\'t know what each clickable area does, making it impossible to understand or interact with the map.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;area&gt;</code>, %2$s is <code>alt=""</code.
				esc_html__( 'Add a descriptive %2$s attribute to each %1$s element in your image map. The text should explain the function or destination of the link, not what the area looks like.', 'accessibility-checker' ),
				'<code>&lt;area&gt;</code>',
				'<code>alt=""</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: HTML Image Maps', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/images/imagemap/',
				],
				[
					'text' => __( 'MDN Web Docs: <area>', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/area',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.1.1',
			'severity'              => 1, // Critical..
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
			],
			'combines'              => [
				'area-alt',
			],
		];
	}
}
