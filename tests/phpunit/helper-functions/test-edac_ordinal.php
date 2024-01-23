<?php
/**
 * Class EDACOrdinal
 *
 * @package Accessibility_Checker
 */

/**
 * Sample test case.
 */
class EDACOrdinal extends WP_UnitTestCase {

	/**
	 * Tests the edac_ordinal function.
	 * 
	 * @dataProvider edac_ordinal_data
	 * 
	 * @param int|string $numeric_value The number we want to convert to ordinal.
	 * @param string     $ordinal_value The ordinal number that should be returned.
	 * @param string     $locale        The locale to use.
	 */
	public function test_edac_ordinal( $numeric_value, $ordinal_value, $locale ) {

		// A filter to bypass the locale.
		$filter_locale = function () use ( $locale ) {
			return $locale;
		};

		// If the locale is not `en_US`, we need to filter it.
		if ( 'en_US' !== $locale ) {
			add_filter( 'locale', $filter_locale );
		}

		// The actual test.
		$this->assertSame( $ordinal_value, edac_ordinal( $numeric_value ) );

		// Remove the filter if it was added.
		if ( 'en_US' !== $locale ) {
			remove_filter( 'locale', $filter_locale );
		}
	}

	/**
	 * Data provider for test_edac_ordinal.
	 */
	public function edac_ordinal_data() {
		return array(

			// Set of tests for the default, `en_US` locale.
			'integer 1, en_US'      => array(
				'numeric_value' => 1,
				'ordinal_value' => '1st',
				'locale'        => 'en_US',
			),
			'string 1, en_US'       => array(
				'numeric_value' => '1',
				'ordinal_value' => '1st',
				'locale'        => 'en_US',
			),
			'integer 2, en_US'      => array(
				'numeric_value' => 2,
				'ordinal_value' => '2nd',
				'locale'        => 'en_US',
			),
			'integer 3, en_US'      => array(
				'numeric_value' => 3,
				'ordinal_value' => '3rd',
				'locale'        => 'en_US',
			),
			'integer 4, en_US'      => array(
				'numeric_value' => 4,
				'ordinal_value' => '4th',
				'locale'        => 'en_US',
			),
			'integer 5, en_US'      => array(
				'numeric_value' => 5,
				'ordinal_value' => '5th',
				'locale'        => 'en_US',
			),
			'integer 101, en_US'    => array(
				'numeric_value' => 101,
				'ordinal_value' => '101st',
				'locale'        => 'en_US',
			),
			'integer 102, en_US'    => array(
				'numeric_value' => 102,
				'ordinal_value' => '102nd',
				'locale'        => 'en_US',
			),
			'integer 103, en_US'    => array(
				'numeric_value' => 103,
				'ordinal_value' => '103rd',
				'locale'        => 'en_US',
			),
			'integer 104, en_US'    => array(
				'numeric_value' => 104,
				'ordinal_value' => '104th',
				'locale'        => 'en_US',
			),
			'integer 99701, en_US'  => array(
				'numeric_value' => 99701,
				'ordinal_value' => '99,701st',
				'locale'        => 'en_US',
			),
			'invalid string, en_US' => array(
				'numeric_value' => 'foo',
				'ordinal_value' => '0th',
				'locale'        => 'en_US',
			),
			'float, en_US'          => array(
				'numeric_value' => 1.1,
				'ordinal_value' => '1st',
				'locale'        => 'en_US',
			),

			// Tests for the `fr_FR` locale.
			'integer 1, fr_FR'      => array(
				'numeric_value' => 1,
				'ordinal_value' => '1er',
				'locale'        => 'fr_FR',
			),

			// Tests for the `ar` locale.
			'integer 1, ar'         => array(
				'numeric_value' => 1,
				'ordinal_value' => '١.',
				'locale'        => 'ar',
			),

			// Tests for the `el_GR` locale.
			'integer 1, el_GR'      => array(
				'numeric_value' => 1,
				'ordinal_value' => '1.',
				'locale'        => 'el_GR',
			),
		);
	}
}
