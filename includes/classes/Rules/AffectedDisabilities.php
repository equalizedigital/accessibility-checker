<?php
/**
 * Enumeration of disabilities affected by accessibility issues.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules;

/**
 * Defines constants for affected disabilities and provides translated labels.
 */
class AffectedDisabilities {
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
	 * Map of disability constants to English labels.
	 *
	 * @var array<string,string>
	 */
	private static $labels = [
		self::ADHD              => 'ADHD',
		self::BLIND             => 'Blind',
		self::COGNITIVE         => 'Cognitive',
		self::COLORBLIND        => 'Colorblind',
		self::DEAF              => 'Deaf',
		self::DEAFBLIND         => 'Deafblind',
		self::DYSLEXIA          => 'Dyslexia',
		self::HARD_OF_HEARING   => 'Hard of hearing',
		self::LANGUAGE_LEARNERS => 'Language learners',
		self::LOW_VISION        => 'Low-vision',
		self::MOBILITY          => 'Mobility',
		self::SEIZURE           => 'Seizure disorders',
		self::VESTIBULAR        => 'Vestibular disorders',
	];

	/**
	 * Cached translated labels.
	 *
	 * @var array<string,string>|null
	 */
	private static $translated_labels = null;

	/**
	 * Get translated label for a disability key.
	 *
	 * @param string $key Disability key constant.
	 * @return string Translated label.
	 */
	public static function get_label( string $key ): string {
		$labels = self::get_all_labels();
		return $labels[ $key ] ?? '';
	}

	/**
	 * Get all disability labels translated.
	 *
	 * @return array<string,string> Array of key => label pairs.
	 */
	public static function get_all_labels(): array {
		if ( null === self::$translated_labels ) {
			self::$translated_labels = [];
			foreach ( self::$labels as $key => $label ) {
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				self::$translated_labels[ $key ] = esc_html__( $label, 'accessibility-checker' );
			}
		}
		return self::$translated_labels;
	}
}
