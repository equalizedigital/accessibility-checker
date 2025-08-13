<?php
/**
 * VideoPresent Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * VideoPresent Rule class.
 */
class VideoPresentRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'A Video is Present', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help4414',
			'slug'                  => 'video_present',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This element is a video. Because many accessibility issues with video content require manual review, this warning appears any time a video is detected on a post or page.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements are videos. Because many accessibility issues with video content require manual review, this warning appears any time one or more videos are detected on a post or page.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Videos must include accurate captions, transcripts, and audio descriptions (or enhanced transcripts) to be fully accessible to users who are deaf, hard of hearing, blind, or have cognitive disabilities.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Review the video on the front end of your website. Ensure that it includes accurate (not auto-generated) synchronized captions, a transcript, and an audio description if needed. After verifying accessibility or making necessary updates, you can dismiss this warning by using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'W3C: Media Alternatives', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/quickref/#provide-alternatives-for-time-based-media',
				],
				[
					'text' => __( 'W3C: Understanding Guideline 1.2', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Understanding/time-based-media.html',
				],
			],
			'wcag'                  => '0.3',
			'severity'              => 1, // Critical..
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::LANGUAGE_LEARNERS,
				AffectedDisabilities::DEAF,
				AffectedDisabilities::HARD_OF_HEARING,
			],
			'ruleset'               => 'js',
		];
	}
}
