<?php
/**
 * ImgAnimatedGif Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ImgAnimatedGif Rule class.
 */
class ImgAnimatedGifRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Image Animated GIF', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help4428',
			'slug'                  => 'img_animated_gif',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This element is an animated image file (e.g., GIF or animated WebP).', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements are animated image files (e.g., GIFs or animated WebPs).', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Animated images can be distracting, induce seizures in some individuals, or create cognitive load. WCAG guidelines require that animations that flash or loop continuously provide a mechanism to pause, stop, or hide them.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Replace the animated image with a static image or a video that includes controls. If you must use an animated image, ensure it does not flash more than three times per second and provide pause or stop controls if it plays for more than 5 seconds. If you have confirmed the image is accessible and pauseable, you can dismiss this warning by using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'Should you use animated GIFs?', 'accessibility-checker' ),
					'url'  => 'https://theadminbar.com/accessibility-weekly/should-you-use-animated-gifs/',
				],
				[
					'text' => __( 'W3C: G152: Setting animated gif images to stop blinking after n cycles (within 5 seconds)', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/TR/WCAG20-TECHS/G152.html',
				],
				[
					'text' => __( 'Pause Animated GIFs Plugin', 'accessibility-checker' ),
					'url'  => 'https://github.com/equalizedigital/accessibility-pause-animated-gifs',
				],
			],
			'wcag'                  => '2.2.2',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::SEIZURE,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::VESTIBULAR,
			],
			'ruleset'               => 'js',
			'combines'              => [ 'img_animated' ],
		];
	}
}
