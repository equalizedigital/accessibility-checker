<?php
/**
 * ImgAltMissing Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgAltMissing Rule class.
 */
class ImgAltMissingRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Image Missing Alternative Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1927',
			'slug'                  => 'img_alt_missing',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>alt=""</code>, %2$s is the <code>&lt;img&gt;</code> tag.
				esc_html__( 'This image does not have an alt attribute (%1$s) contained in the image tag (%2$s).', 'accessibility-checker' ),
				'<code>alt=""</code>',
				'<code>&lt;img&gt;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>alt=""</code>, %2$s is the <code>&lt;img&gt;</code> tag.
				esc_html__( 'These images do not have an alt attribute (%1$s) contained in their image tags (%2$s).', 'accessibility-checker' ),
				'<code>alt=""</code>',
				'<code>&lt;img&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Alternative text is used by screen readers to describe images to people who cannot see them. If the alt attribute is missing, the screen reader will only say \'Image\' or may read out the file URL. Alternative text is also used by search engines to understand the content of the image. If an image does not have alternative text, it can create a poor user experience for people with visual impairments and can negatively impact your site\'s SEO.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>alt=""</code.
				esc_html__( 'Add an alt attribute to the image with appropriate text describing the purpose of the image in the page. If the image is decorative, the alt attribute can be empty, but the HTML %1$s attribute still needs to be present.', 'accessibility-checker' ),
				'<code>alt=""</code>',
			),
			'references'            => [
				[
					'text' => __( 'HTML Standard: The img element', 'accessibility-checker' ),
					'url'  => 'https://html.spec.whatwg.org/multipage/images.html#the-img-element',
				],
				[
					'text' => __( 'Alt Decision Tree', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/images/decision-tree/',
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
		];
	}
}
