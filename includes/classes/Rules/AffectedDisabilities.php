<?php
/**
 * Enumeration of disabilities affected by accessibility issues.
 *
 * Defines constants for affected disabilities and provides translated labels.
 * This class is a static utility and should not be extended or instantiated.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules;

/**
 * Enumeration of disabilities affected by accessibility issues.
 *
 * Defines constants for affected disabilities and provides translated labels.
 * This class is a static utility and should not be extended or instantiated.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */
final class AffectedDisabilities {
	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}

	public const BLIND             = 'blind';
	public const LOW_VISION        = 'low_vision';
	public const DEAFBLIND         = 'deafblind';
	public const MOBILITY          = 'mobility';
	public const COLORBLIND        = 'colorblind';
	public const COGNITIVE         = 'cognitive';
	public const SEIZURE           = 'seizure_disorders';
	public const VESTIBULAR        = 'vestibular_disorders';
	public const DEAF              = 'deaf';
	public const HARD_OF_HEARING   = 'hard_of_hearing';
	public const LANGUAGE_LEARNERS = 'language_learners';
	public const ADHD              = 'adhd';
	public const DYSLEXIA          = 'dyslexia';

	/**
	 * Get translated label for a disability key.
	 *
	 * @param string $key Disability key constant.
	 * @return string Translated label.
	 */
	public static function get_label( string $key ): string {
		switch ( $key ) {
			case self::BLIND:
				return esc_html__( 'Blind', 'accessibility-checker' );
			case self::LOW_VISION:
				return esc_html__( 'Low-vision', 'accessibility-checker' );
			case self::DEAFBLIND:
				return esc_html__( 'Deafblind', 'accessibility-checker' );
			case self::MOBILITY:
				return esc_html__( 'Mobility', 'accessibility-checker' );
			case self::COLORBLIND:
				return esc_html__( 'Colorblind', 'accessibility-checker' );
			case self::COGNITIVE:
				return esc_html__( 'Cognitive', 'accessibility-checker' );
			case self::SEIZURE:
				return esc_html__( 'Seizure disorders', 'accessibility-checker' );
			case self::VESTIBULAR:
				return esc_html__( 'Vestibular disorders', 'accessibility-checker' );
			case self::DEAF:
				return esc_html__( 'Deaf', 'accessibility-checker' );
			case self::HARD_OF_HEARING:
				return esc_html__( 'Hard of hearing', 'accessibility-checker' );
			case self::LANGUAGE_LEARNERS:
				return esc_html__( 'Language learners', 'accessibility-checker' );
			case self::ADHD:
				return esc_html__( 'ADHD', 'accessibility-checker' );
			case self::DYSLEXIA:
				return esc_html__( 'Dyslexia', 'accessibility-checker' );
			default:
				return '';
		}
	}
}
