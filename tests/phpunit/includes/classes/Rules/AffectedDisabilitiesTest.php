<?php
/**
 * Tests for the AffectedDisabilities rule helper.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * Tests for AffectedDisabilities::get_label().
 *
 * @covers \EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities::get_label
 */
class AffectedDisabilitiesTest extends WP_UnitTestCase {

	/**
	 * Every disability constant maps to its translated label.
	 *
	 * @dataProvider provider_affected_disabilities
	 *
	 * @param string $key      Disability constant value.
	 * @param string $expected Expected label.
	 */
	public function test_get_label_returns_expected_label( string $key, string $expected ): void {
		$this->assertSame( $expected, AffectedDisabilities::get_label( $key ) );
	}

	/**
	 * Data provider mapping constant values to their labels.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_affected_disabilities(): array {
		return [
			'blind'             => [ AffectedDisabilities::BLIND, 'Blind' ],
			'low vision'        => [ AffectedDisabilities::LOW_VISION, 'Low-vision' ],
			'deafblind'         => [ AffectedDisabilities::DEAFBLIND, 'Deafblind' ],
			'mobility'          => [ AffectedDisabilities::MOBILITY, 'Mobility' ],
			'colorblind'        => [ AffectedDisabilities::COLORBLIND, 'Colorblind' ],
			'cognitive'         => [ AffectedDisabilities::COGNITIVE, 'Cognitive' ],
			'seizure'           => [ AffectedDisabilities::SEIZURE, 'Seizure disorders' ],
			'vestibular'        => [ AffectedDisabilities::VESTIBULAR, 'Vestibular disorders' ],
			'deaf'              => [ AffectedDisabilities::DEAF, 'Deaf' ],
			'hard of hearing'   => [ AffectedDisabilities::HARD_OF_HEARING, 'Hard of hearing' ],
			'language learners' => [ AffectedDisabilities::LANGUAGE_LEARNERS, 'Language learners' ],
			'adhd'              => [ AffectedDisabilities::ADHD, 'ADHD' ],
			'dyslexia'          => [ AffectedDisabilities::DYSLEXIA, 'Dyslexia' ],
		];
	}

	/**
	 * An unknown key returns an empty string.
	 */
	public function test_get_label_returns_empty_string_for_unknown_key(): void {
		$this->assertSame( '', AffectedDisabilities::get_label( 'not_a_disability' ) );
	}
}
