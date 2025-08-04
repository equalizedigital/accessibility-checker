<?php
/**
 * Class WcagDataTest
 *
 * Tests for the WCAG data structure from wcag.php
 *
 * @package Accessibility_Checker
 */

/**
 * Test case for WCAG data validation.
 */
class WcagDataTest extends WP_UnitTestCase {

	/**
	 * The WCAG data array loaded from wcag.php
	 *
	 * @var array
	 */
	private static $wcag_data;

	/**
	 * Set up the test class
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		
		// Load the WCAG data from the file.
		// The file path needs to be relative to the plugin directory.
		$plugin_dir = dirname( dirname( dirname( __DIR__ ) ) );
		$wcag_file  = $plugin_dir . '/includes/wcag.php';
		
		if ( file_exists( $wcag_file ) ) {
			self::$wcag_data = include $wcag_file;
		} else {
			// Fallback for testing.
			self::$wcag_data = [];
		}
	}

	/**
	 * Test that the WCAG data file loads and returns an array
	 */
	public function test_wcag_data_loads_as_array() {
		$this->assertIsArray( self::$wcag_data, 'WCAG data should be an array' );
		$this->assertNotEmpty( self::$wcag_data, 'WCAG data should not be empty' );
	}

	/**
	 * Test that all WCAG data items have required keys
	 */
	public function test_all_items_have_required_keys() {
		$required_keys = [
			'number',
			'title',
			'criteria_description',
			'level',
			'version',
			'guidelines',
			'principles',
			'impacted_populations',
			'how_to_meet_url',
			'understanding_url',
			'wcag_url',
			'tags',
			'also_applies_to',
		];

		foreach ( self::$wcag_data as $index => $item ) {
			foreach ( $required_keys as $key ) {
				$this->assertArrayHasKey(
					$key,
					$item,
					"Item at index {$index} is missing required key '{$key}'"
				);
			}
		}
	}

	/**
	 * Test that all items have the correct data types
	 */
	public function test_data_types_are_correct() {
		foreach ( self::$wcag_data as $index => $item ) {
			// String fields.
			$this->assertIsString( $item['number'], "Item {$index}: 'number' should be a string" );
			$this->assertIsString( $item['title'], "Item {$index}: 'title' should be a string" );
			$this->assertIsString( $item['criteria_description'], "Item {$index}: 'criteria_description' should be a string" );
			$this->assertIsString( $item['level'], "Item {$index}: 'level' should be a string" );
			$this->assertIsString( $item['version'], "Item {$index}: 'version' should be a string" );
			$this->assertIsString( $item['guidelines'], "Item {$index}: 'guidelines' should be a string" );
			$this->assertIsString( $item['principles'], "Item {$index}: 'principles' should be a string" );
			$this->assertIsString( $item['how_to_meet_url'], "Item {$index}: 'how_to_meet_url' should be a string" );
			$this->assertIsString( $item['understanding_url'], "Item {$index}: 'understanding_url' should be a string" );
			$this->assertIsString( $item['wcag_url'], "Item {$index}: 'wcag_url' should be a string" );

			// Array fields.
			$this->assertIsArray( $item['impacted_populations'], "Item {$index}: 'impacted_populations' should be an array" );
			$this->assertIsArray( $item['tags'], "Item {$index}: 'tags' should be an array" );
			$this->assertIsArray( $item['also_applies_to'], "Item {$index}: 'also_applies_to' should be an array" );
		}
	}

	/**
	 * Test WCAG number format patterns
	 */
	public function test_number_format_patterns() {
		$valid_patterns = [
			'/^\d+\.\d+\.\d+$/',    // Standard WCAG format like 1.1.1.
			'/^0\.\d+$/',           // Custom format like 0.1, 0.2, 0.3.
		];

		foreach ( self::$wcag_data as $index => $item ) {
			$number   = $item['number'];
			$is_valid = false;

			foreach ( $valid_patterns as $pattern ) {
				if ( preg_match( $pattern, $number ) ) {
					$is_valid = true;
					break;
				}
			}

			$this->assertTrue(
				$is_valid,
				"Item {$index}: Number '{$number}' does not match any expected format"
			);
		}
	}

	/**
	 * Test that level values are valid
	 */
	public function test_valid_level_values() {
		$valid_levels = [ 'A', 'AA', 'AAA', 'Best Practice' ];

		foreach ( self::$wcag_data as $index => $item ) {
			$this->assertContains(
				$item['level'],
				$valid_levels,
				"Item {$index}: Level '{$item['level']}' is not a valid WCAG level"
			);
		}
	}

	/**
	 * Test that version values are valid
	 */
	public function test_valid_version_values() {
		$valid_versions = [ '2.0', '2.1', '2.2', '' ];

		foreach ( self::$wcag_data as $index => $item ) {
			$this->assertContains(
				$item['version'],
				$valid_versions,
				"Item {$index}: Version '{$item['version']}' is not a valid WCAG version"
			);
		}
	}

	/**
	 * Test that principle values are valid
	 */
	public function test_valid_principle_values() {
		$valid_principles = [ 'Perceivable', 'Operable', 'Understandable', 'Robust', '' ];

		foreach ( self::$wcag_data as $index => $item ) {
			$this->assertContains(
				$item['principles'],
				$valid_principles,
				"Item {$index}: Principle '{$item['principles']}' is not a valid WCAG principle"
			);
		}
	}

	/**
	 * Test URL format validation
	 */
	public function test_url_formats() {
		foreach ( self::$wcag_data as $index => $item ) {
			// URLs can be empty strings, but if not empty, should be valid URLs.
			if ( ! empty( $item['how_to_meet_url'] ) ) {
				$this->assertTrue(
					filter_var( $item['how_to_meet_url'], FILTER_VALIDATE_URL ) !== false,
					"Item {$index}: 'how_to_meet_url' is not a valid URL: '{$item['how_to_meet_url']}'"
				);
			}

			if ( ! empty( $item['understanding_url'] ) ) {
				$this->assertTrue(
					filter_var( $item['understanding_url'], FILTER_VALIDATE_URL ) !== false,
					"Item {$index}: 'understanding_url' is not a valid URL: '{$item['understanding_url']}'"
				);
			}

			if ( ! empty( $item['wcag_url'] ) ) {
				$this->assertTrue(
					filter_var( $item['wcag_url'], FILTER_VALIDATE_URL ) !== false,
					"Item {$index}: 'wcag_url' is not a valid URL: '{$item['wcag_url']}'"
				);
			}
		}
	}

	/**
	 * Test that impacted_populations array contains only strings
	 */
	public function test_impacted_populations_array_contains_strings() {
		foreach ( self::$wcag_data as $index => $item ) {
			foreach ( $item['impacted_populations'] as $pop_index => $population ) {
				$this->assertIsString(
					$population,
					"Item {$index}: impacted_populations[{$pop_index}] should be a string"
				);
			}
		}
	}

	/**
	 * Test that tags array contains only strings
	 */
	public function test_tags_array_contains_strings() {
		foreach ( self::$wcag_data as $index => $item ) {
			foreach ( $item['tags'] as $tag_index => $tag ) {
				$this->assertIsString(
					$tag,
					"Item {$index}: tags[{$tag_index}] should be a string"
				);
			}
		}
	}

	/**
	 * Test also_applies_to structure
	 */
	public function test_also_applies_to_structure() {
		foreach ( self::$wcag_data as $index => $item ) {
			$also_applies_to = $item['also_applies_to'];
			
			// also_applies_to should be an associative array.
			foreach ( $also_applies_to as $standard_name => $criteria ) {
				$this->assertIsString(
					$standard_name,
					"Item {$index}: also_applies_to key should be a string"
				);
				$this->assertIsArray(
					$criteria,
					"Item {$index}: also_applies_to['{$standard_name}'] should be an array"
				);

				// Each criteria should be a string.
				foreach ( $criteria as $criterion_index => $criterion ) {
					$this->assertIsString(
						$criterion,
						"Item {$index}: also_applies_to['{$standard_name}'][{$criterion_index}] should be a string"
					);
				}
			}
		}
	}

	/**
	 * Test that all strings appear to be properly escaped for HTML output
	 */
	public function test_html_escaping() {
		foreach ( self::$wcag_data as $index => $item ) {
			// Test that strings don't contain unescaped HTML characters that should be escaped.
			$string_fields = [
				'title',
				'criteria_description',
				'guidelines',
				'principles',
			];

			foreach ( $string_fields as $field ) {
				if ( ! empty( $item[ $field ] ) ) {
					// Check that the string doesn't contain dangerous unescaped content.
					$this->assertStringNotContainsString(
						'<script',
						$item[ $field ],
						"Item {$index}: '{$field}' contains potentially dangerous script content"
					);
				}
			}

			// Test impacted populations.
			foreach ( $item['impacted_populations'] as $population ) {
				if ( ! empty( $population ) ) {
					$this->assertStringNotContainsString(
						'<script',
						$population,
						"Item {$index}: impacted_populations contains potentially dangerous script content"
					);
				}
			}
		}
	}

	/**
	 * Test that there are no duplicate WCAG numbers
	 */
	public function test_no_duplicate_numbers() {
		$numbers        = array_column( self::$wcag_data, 'number' );
		$unique_numbers = array_unique( $numbers );

		$this->assertCount(
			count( $unique_numbers ),
			$numbers,
			'There should be no duplicate WCAG numbers'
		);
	}

	/**
	 * Test that standard WCAG entries follow expected patterns
	 */
	public function test_standard_wcag_entries_follow_patterns() {
		foreach ( self::$wcag_data as $index => $item ) {
			// Skip custom entries (those starting with 0.).
			if ( strpos( $item['number'], '0.' ) === 0 ) {
				continue;
			}

			// Standard WCAG entries should have non-empty version.
			$this->assertNotEmpty(
				$item['version'],
				"Item {$index}: Standard WCAG entry should have a version"
			);

			// Standard WCAG entries should have principles.
			$this->assertNotEmpty(
				$item['principles'],
				"Item {$index}: Standard WCAG entry should have principles"
			);

			// Standard WCAG entries should have guidelines.
			$this->assertNotEmpty(
				$item['guidelines'],
				"Item {$index}: Standard WCAG entry should have guidelines"
			);
		}
	}

	/**
	 * Test that custom entries follow their own patterns
	 */
	public function test_custom_entries_follow_patterns() {
		foreach ( self::$wcag_data as $index => $item ) {
			// Only check custom entries (those starting with 0.).
			if ( strpos( $item['number'], '0.' ) !== 0 ) {
				continue;
			}

			// Custom entries should have valid level values.
			$valid_custom_levels = [ 'Best Practice', 'A' ]; // 0.3 uses 'A' level
			$this->assertContains(
				$item['level'],
				$valid_custom_levels,
				"Item {$index}: Custom entry {$item['number']} should have valid custom level, found '{$item['level']}'"
			);

			// Custom entries should have empty version.
			$this->assertEmpty(
				$item['version'],
				"Item {$index}: Custom entry should have empty version"
			);

			// Custom entries should have empty guidelines and principles.
			$this->assertEmpty(
				$item['guidelines'],
				"Item {$index}: Custom entry should have empty guidelines"
			);
			$this->assertEmpty(
				$item['principles'],
				"Item {$index}: Custom entry should have empty principles"
			);

			// Custom entries should have empty also_applies_to.
			$this->assertEmpty(
				$item['also_applies_to'],
				"Item {$index}: Custom entry should have empty also_applies_to"
			);
		}
	}

	/**
	 * Test that all expected WCAG 2.1 Level AA criteria are present
	 */
	public function test_wcag_21_aa_criteria_present() {
		$numbers = array_column( self::$wcag_data, 'number' );

		// Sample of critical WCAG 2.1 Level AA criteria that should be present.
		$required_aa_criteria = [
			'1.3.4', // Orientation.
			'1.3.5', // Identify Input Purpose.
			'1.4.10', // Reflow.
			'1.4.11', // Non-text Contrast.
			'1.4.12', // Text Spacing.
			'1.4.13', // Content on Hover or Focus.
			'2.5.1', // Pointer Gestures.
			'2.5.2', // Pointer Cancellation.
			'2.5.3', // Label in Name.
			'2.5.4', // Motion Actuation.
			'4.1.3', // Status Messages.
		];

		foreach ( $required_aa_criteria as $criterion ) {
			$this->assertContains(
				$criterion,
				$numbers,
				"Critical WCAG 2.1 AA criterion {$criterion} should be present"
			);
		}
	}
}
