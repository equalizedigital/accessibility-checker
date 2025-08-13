<?php
/**
 * MissingTranscript Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * MissingTranscript Rule class.
 */
class MissingTranscriptRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Missing Transcript', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1947',
			'slug'                  => 'missing_transcript',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This element contains or links to audio or video content that does not have a properly labeled or positioned transcript, or may not have a transcript at all.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements contain or link to audio or video content that do not have properly labeled or positioned transcripts, or may not have a transcript at all.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Transcripts provide access to audio and video content for individuals who are deaf, hard of hearing, or prefer to read rather than listen. Without a transcript, important information may be missed.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Create a transcript for each flagged audio or video clip. Include the transcript on the same page or link to a file that contains it. The word “transcript” should appear in a heading before the transcript or in the link text, and must be within 25 characters of the audio or video element.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'W3C: Transcripts Documentation', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/media/av/transcripts/',
				],
				[
					'text' => __( 'Practical Advice for Meeting Caption, Transcript, and Sign Language Requirements (Webinar)', 'accessibility-checker' ),
					'url'  => 'https://equalizedigital.com/practical-advice-for-meeting-caption-transcript-and-sign-language-requirements-amber-hinds/',
				],
			],
			'wcag'                  => '1.2.1',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::DEAF,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::HARD_OF_HEARING,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::LANGUAGE_LEARNERS,
			],
			'ruleset'               => 'js',
		];
	}
}
