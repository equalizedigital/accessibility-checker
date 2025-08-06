<?php
/**
 * Class EDACOrdinal
 *
 * @package Accessibility_Checker
 */

/**
 * Sample test case.
 */
class OrdinalTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_ordinal function.
	 *
	 * @dataProvider edac_ordinal_data
	 *
	 * @param int|string $numeric_value The number we want to convert to ordinal.
	 * @param string     $ordinal_value The ordinal number that should be returned.
	 * @param string     $locale        The locale to use.
	 * @param string     $ordinal_value_no_php_intl The ordinal number that should be returned when php-intl is not available.
	 */
	public function test_edac_ordinal( $numeric_value, $ordinal_value, $locale, $ordinal_value_no_php_intl = null ) {

		// A filter to bypass the locale.
		$filter_locale = function () use ( $locale ) {
			return $locale;
		};

		// If the locale is not `en_US`, we need to filter it.
		if ( 'en_US' !== $locale ) {
			add_filter( 'locale', $filter_locale );
		}

		// If NumberFormatter (php-intl) is not available we would get only English values, and sometimes for English
		// they will differ from what NumberFormatter would return.
		$value_to_test = ! class_exists( 'NumberFormatter' ) && $ordinal_value_no_php_intl
			? $ordinal_value_no_php_intl
			: $ordinal_value;

		// Some php-intl locales return ordinals with a period at the end, so we need to account for that.
		$this->assertMatchesRegularExpression( '/^' . preg_quote( $value_to_test, '/' ) . '\.?$/u', edac_ordinal( $numeric_value ) );
		// Remove the filter if it was added.
		if ( 'en_US' !== $locale ) {
			remove_filter( 'locale', $filter_locale );
		}
	}

	/**
	 * Data provider for test_edac_ordinal.
	 */
	public function edac_ordinal_data() {
		return [

			// Set of tests for the default, `en_US` locale.
			'integer 1, en_US'      => [
				'numeric_value' => 1,
				'ordinal_value' => '1st',
				'locale'        => 'en_US',
			],
			'string 1, en_US'       => [
				'numeric_value' => '1',
				'ordinal_value' => '1st',
				'locale'        => 'en_US',
			],
			'integer 2, en_US'      => [
				'numeric_value' => 2,
				'ordinal_value' => '2nd',
				'locale'        => 'en_US',
			],
			'integer 3, en_US'      => [
				'numeric_value' => 3,
				'ordinal_value' => '3rd',
				'locale'        => 'en_US',
			],
			'integer 4, en_US'      => [
				'numeric_value' => 4,
				'ordinal_value' => '4th',
				'locale'        => 'en_US',
			],
			'integer 5, en_US'      => [
				'numeric_value' => 5,
				'ordinal_value' => '5th',
				'locale'        => 'en_US',
			],
			'integer 101, en_US'    => [
				'numeric_value' => 101,
				'ordinal_value' => '101st',
				'locale'        => 'en_US',
			],
			'integer 102, en_US'    => [
				'numeric_value' => 102,
				'ordinal_value' => '102nd',
				'locale'        => 'en_US',
			],
			'integer 103, en_US'    => [
				'numeric_value' => 103,
				'ordinal_value' => '103rd',
				'locale'        => 'en_US',
			],
			'integer 104, en_US'    => [
				'numeric_value' => 104,
				'ordinal_value' => '104th',
				'locale'        => 'en_US',
			],
			'integer 99701, en_US'  => [
				'numeric_value'             => 99701,
				'ordinal_value'             => '99,701st',
				'locale'                    => 'en_US',
				'ordinal_value_no_php-intl' => '99701st',
			],
			'invalid string, en_US' => [
				'numeric_value' => 'foo',
				'ordinal_value' => '0th',
				'locale'        => 'en_US',
			],
			'float, en_US'          => [
				'numeric_value' => 1.1,
				'ordinal_value' => '1st',
				'locale'        => 'en_US',
			],

			// Tests for the `fr_FR` locale.
			'integer 1, fr_FR'      => [
				'numeric_value'             => 1,
				'ordinal_value'             => '1er',
				'locale'                    => 'fr_FR',
				'ordinal_value_no_php-intl' => '1st',
			],

			// Tests for the `ar` locale.
			'integer 1, ar'         => [
				'numeric_value'             => 1,
				'ordinal_value'             => '١',
				'locale'                    => 'ar',
				'ordinal_value_no_php-intl' => '1st',
			],

			// Tests for the `el_GR` locale.
			'integer 1, el_GR'      => [
				'numeric_value'             => 1,
				'ordinal_value'             => '1',
				'locale'                    => 'el_GR',
				'ordinal_value_no_php-intl' => '1st',
			],
		];
	}
}
