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
	 * Test that all WCAG data items have required keys and correct data structure
	 */
	public function test_data_structure_integrity() {
		$validation_result = $this->validate_data_structure_integrity();
		$this->assertTrue( $validation_result['valid'], $validation_result['message'] );
	}

	/**
	 * Helper method to validate data structure integrity
	 *
	 * @return array Validation result with 'valid' boolean and 'message' string
	 */
	private function validate_data_structure_integrity() {
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

		$string_fields = [
			'number',
			'title', 
			'criteria_description',
			'level',
			'version',
			'guidelines',
			'principles',
			'how_to_meet_url',
			'understanding_url',
			'wcag_url',
		];

		$array_fields = [
			'impacted_populations',
			'tags',
			'also_applies_to',
		];

		foreach ( self::$wcag_data as $index => $item ) {
			// Check required keys.
			foreach ( $required_keys as $key ) {
				if ( ! array_key_exists( $key, $item ) ) {
					return [
						'valid'   => false,
						'message' => "Item at index {$index} is missing required key '{$key}'",
					];
				}
			}

			// Check string field types.
			foreach ( $string_fields as $field ) {
				if ( ! is_string( $item[ $field ] ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: '{$field}' should be a string, got " . gettype( $item[ $field ] ),
					];
				}
			}

			// Check array field types.
			foreach ( $array_fields as $field ) {
				if ( ! is_array( $item[ $field ] ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: '{$field}' should be an array, got " . gettype( $item[ $field ] ),
					];
				}
			}
		}

		return [
			'valid'   => true,
			'message' => 'All WCAG data entries have valid structure and data types',
		];
	}

	/**
	 * Test WCAG number format patterns and validation rules
	 */
	public function test_number_format_and_validation_rules() {
		$validation_result = $this->validate_number_formats_and_rules();
		$this->assertTrue( $validation_result['valid'], $validation_result['message'] );
	}

	/**
	 * Helper method to validate number formats and validation rules
	 *
	 * @return array Validation result with 'valid' boolean and 'message' string
	 */
	private function validate_number_formats_and_rules() {
		$valid_patterns = [
			'/^\d+\.\d+\.\d+$/',    // Standard WCAG format like 1.1.1.
			'/^0\.\d+$/',           // Custom format like 0.1, 0.2, 0.3.
		];

		$valid_levels     = [ 'A', 'AA', 'AAA', 'Best Practice' ];
		$valid_versions   = [ '2.0', '2.1', '2.2', '' ];
		$valid_principles = [ 'Perceivable', 'Operable', 'Understandable', 'Robust', '' ];

		$numbers = [];
		
		foreach ( self::$wcag_data as $index => $item ) {
			$number = $item['number'];
			
			// Check number format.
			$is_valid_format = false;
			foreach ( $valid_patterns as $pattern ) {
				if ( preg_match( $pattern, $number ) ) {
					$is_valid_format = true;
					break;
				}
			}
			
			if ( ! $is_valid_format ) {
				return [
					'valid'   => false,
					'message' => "Item {$index}: Number '{$number}' does not match any expected format",
				];
			}

			// Check for duplicates.
			if ( in_array( $number, $numbers, true ) ) {
				return [
					'valid'   => false,
					'message' => "Duplicate WCAG number found: '{$number}'",
				];
			}
			$numbers[] = $number;

			// Check level validity.
			if ( ! in_array( $item['level'], $valid_levels, true ) ) {
				return [
					'valid'   => false,
					'message' => "Item {$index}: Level '{$item['level']}' is not a valid WCAG level",
				];
			}

			// Check version validity.
			if ( ! in_array( $item['version'], $valid_versions, true ) ) {
				return [
					'valid'   => false,
					'message' => "Item {$index}: Version '{$item['version']}' is not a valid WCAG version",
				];
			}

			// Check principle validity.
			if ( ! in_array( $item['principles'], $valid_principles, true ) ) {
				return [
					'valid'   => false,
					'message' => "Item {$index}: Principle '{$item['principles']}' is not a valid WCAG principle",
				];
			}
		}

		return [
			'valid'   => true,
			'message' => 'All WCAG numbers, levels, versions, and principles are valid with no duplicates',
		];
	}

	/**
	 * Test URL formats and array content validity
	 */
	public function test_url_formats_and_array_content() {
		$validation_result = $this->validate_urls_and_array_content();
		$this->assertTrue( $validation_result['valid'], $validation_result['message'] );
	}

	/**
	 * Helper method to validate URL formats and array content
	 *
	 * @return array Validation result with 'valid' boolean and 'message' string
	 */
	private function validate_urls_and_array_content() {
		foreach ( self::$wcag_data as $index => $item ) {
			// Check URL formats.
			$url_fields = [ 'how_to_meet_url', 'understanding_url', 'wcag_url' ];
			foreach ( $url_fields as $url_field ) {
				if ( ! empty( $item[ $url_field ] ) && filter_var( $item[ $url_field ], FILTER_VALIDATE_URL ) === false ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: '{$url_field}' is not a valid URL: '{$item[ $url_field ]}'",
					];
				}
			}

			// Check impacted_populations array contains only strings.
			foreach ( $item['impacted_populations'] as $pop_index => $population ) {
				if ( ! is_string( $population ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: impacted_populations[{$pop_index}] should be a string, got " . gettype( $population ),
					];
				}
			}

			// Check tags array contains only strings.
			foreach ( $item['tags'] as $tag_index => $tag ) {
				if ( ! is_string( $tag ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: tags[{$tag_index}] should be a string, got " . gettype( $tag ),
					];
				}
			}

			// Check also_applies_to structure.
			foreach ( $item['also_applies_to'] as $standard_name => $criteria ) {
				if ( ! is_string( $standard_name ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: also_applies_to key should be a string, got " . gettype( $standard_name ),
					];
				}
				
				if ( ! is_array( $criteria ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: also_applies_to['{$standard_name}'] should be an array, got " . gettype( $criteria ),
					];
				}

				foreach ( $criteria as $criterion_index => $criterion ) {
					if ( ! is_string( $criterion ) ) {
						return [
							'valid'   => false,
							'message' => "Item {$index}: also_applies_to['{$standard_name}'][{$criterion_index}] should be a string, got " . gettype( $criterion ),
						];
					}
				}
			}
		}

		return [
			'valid'   => true,
			'message' => 'All URLs are valid and arrays contain only strings with proper structure',
		];
	}

	/**
	 * Test HTML safety and content validation
	 */
	public function test_html_safety_and_content_validation() {
		$validation_result = $this->validate_html_safety();
		$this->assertTrue( $validation_result['valid'], $validation_result['message'] );
	}

	/**
	 * Helper method to validate HTML safety
	 *
	 * @return array Validation result with 'valid' boolean and 'message' string
	 */
	private function validate_html_safety() {
		$string_fields = [
			'title',
			'criteria_description',
			'guidelines',
			'principles',
		];

		foreach ( self::$wcag_data as $index => $item ) {
			// Check string fields for dangerous content.
			foreach ( $string_fields as $field ) {
				if ( ! empty( $item[ $field ] ) && strpos( $item[ $field ], '<script' ) !== false ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: '{$field}' contains potentially dangerous script content",
					];
				}
			}

			// Check impacted populations for dangerous content.
			foreach ( $item['impacted_populations'] as $population ) {
				if ( ! empty( $population ) && strpos( $population, '<script' ) !== false ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: impacted_populations contains potentially dangerous script content",
					];
				}
			}
		}

		return [
			'valid'   => true,
			'message' => 'All content is safe from dangerous script elements',
		];
	}

	/**
	 * Test WCAG entry patterns for standard and custom entries
	 */
	public function test_wcag_entry_patterns() {
		$validation_result = $this->validate_wcag_entry_patterns();
		$this->assertTrue( $validation_result['valid'], $validation_result['message'] );
	}

	/**
	 * Helper method to validate WCAG entry patterns
	 *
	 * @return array Validation result with 'valid' boolean and 'message' string
	 */
	private function validate_wcag_entry_patterns() {
		foreach ( self::$wcag_data as $index => $item ) {
			$is_custom_entry = strpos( $item['number'], '0.' ) === 0;
			
			if ( $is_custom_entry ) {
				// Custom entries validation.
				$valid_custom_levels = [ 'Best Practice', 'A' ]; // 0.3 uses 'A' level.
				if ( ! in_array( $item['level'], $valid_custom_levels, true ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: Custom entry {$item['number']} should have valid custom level, found '{$item['level']}'",
					];
				}

				// Custom entries should have empty version, guidelines, principles, and also_applies_to.
				$empty_fields = [ 'version', 'guidelines', 'principles' ];
				foreach ( $empty_fields as $field ) {
					if ( ! empty( $item[ $field ] ) ) {
						return [
							'valid'   => false,
							'message' => "Item {$index}: Custom entry should have empty {$field}",
						];
					}
				}

				if ( ! empty( $item['also_applies_to'] ) ) {
					return [
						'valid'   => false,
						'message' => "Item {$index}: Custom entry should have empty also_applies_to",
					];
				}
			} else {
				// Standard WCAG entries validation.
				$required_fields = [ 'version', 'principles', 'guidelines' ];
				foreach ( $required_fields as $field ) {
					if ( empty( $item[ $field ] ) ) {
						return [
							'valid'   => false,
							'message' => "Item {$index}: Standard WCAG entry should have a non-empty {$field}",
						];
					}
				}
			}
		}

		return [
			'valid'   => true,
			'message' => 'All standard and custom WCAG entries follow expected patterns',
		];
	}

	/**
	 * Test that critical WCAG 2.1 Level AA criteria are present
	 */
	public function test_critical_wcag_criteria_present() {
		$validation_result = $this->validate_critical_wcag_criteria();
		$this->assertTrue( $validation_result['valid'], $validation_result['message'] );
	}

	/**
	 * Helper method to validate presence of critical WCAG criteria
	 *
	 * @return array Validation result with 'valid' boolean and 'message' string
	 */
	private function validate_critical_wcag_criteria() {
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
			if ( ! in_array( $criterion, $numbers, true ) ) {
				return [
					'valid'   => false,
					'message' => "Critical WCAG 2.1 AA criterion {$criterion} should be present",
				];
			}
		}

		return [
			'valid'   => true,
			'message' => 'All critical WCAG 2.1 AA criteria are present',
		];
	}
}
