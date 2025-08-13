<?php
/**
 * Class GenerateLandmarkLinkTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_generate_landmark_link() function.
 */
class GenerateLandmarkLinkExtendedTest extends WP_UnitTestCase {

	/**
	 * Test post ID for testing.
	 *
	 * @var int
	 */
	private $test_post_id;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a test post for linking.
		// Use a basic post ID if WordPress factory is not available.
		if ( method_exists( $this, 'factory' ) && $this->factory() ) {
			$this->test_post_id = $this->factory()->post->create(
				[
					'post_title'   => 'Test Post',
					'post_content' => 'Test content',
					'post_status'  => 'publish',
				]
			);
		} else {
			// Use a default test post ID when factory is not available.
			$this->test_post_id = 1;
		}
	}

	/**
	 * Tests the edac_generate_landmark_link function with various inputs.
	 *
	 * @dataProvider generate_landmark_link_data
	 *
	 * @param string $landmark          The landmark type.
	 * @param string $landmark_selector The CSS selector.
	 * @param string $css_class         The CSS class.
	 * @param bool   $target_blank      Whether to open in new window.
	 * @param string $expected_pattern  Pattern to match in result.
	 */
	public function test_edac_generate_landmark_link(
		$landmark,
		$landmark_selector,
		$css_class,
		$target_blank,
		$expected_pattern
	) {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'esc_html' ) || ! function_exists( 'wp_create_nonce' ) ||
			! function_exists( 'get_the_permalink' ) || ! function_exists( 'add_query_arg' ) ) {
			$this->markTestSkipped( 'WordPress functions not available in test environment.' );
		}

		$result = edac_generate_landmark_link(
			$landmark,
			$landmark_selector,
			$this->test_post_id,
			$css_class,
			$target_blank
		);

		// The result should always be a string.
		$this->assertIsString( $result );

		// Check specific patterns based on expected type.
		switch ( $expected_pattern ) {
			case 'empty':
				$this->assertEmpty( $result );
				break;
			case 'text_only':
				$this->assertStringNotContainsString( '<a', $result );
				$this->assertStringContainsString( ucwords( $landmark ), $result );
				break;
			case 'link_with_target':
				$this->assertStringContainsString( '<a href=', $result );
				$this->assertStringContainsString( 'target="_blank"', $result );
				$this->assertStringContainsString( ucwords( $landmark ), $result );
				break;
			case 'link_without_target':
				$this->assertStringContainsString( '<a href=', $result );
				$this->assertStringNotContainsString( 'target="_blank"', $result );
				$this->assertStringContainsString( ucwords( $landmark ), $result );
				break;
			case 'link_with_class':
				$this->assertStringContainsString( '<a href=', $result );
				$this->assertStringContainsString( 'class="' . $css_class . '"', $result );
				break;
		}
	}

	/**
	 * Data provider for test_edac_generate_landmark_link.
	 */
	public function generate_landmark_link_data() {
		return [
			'empty landmark'                              => [
				'landmark'          => '',
				'landmark_selector' => 'header',
				'css_class'         => 'test-class',
				'target_blank'      => true,
				'expected_pattern'  => 'empty',
			],
			'landmark without selector (text only)'       => [
				'landmark'          => 'navigation',
				'landmark_selector' => '',
				'css_class'         => 'test-class',
				'target_blank'      => true,
				'expected_pattern'  => 'text_only',
			],
			'landmark with selector and target blank'     => [
				'landmark'          => 'header',
				'landmark_selector' => 'header.main-header',
				'css_class'         => 'landmark-link',
				'target_blank'      => true,
				'expected_pattern'  => 'link_with_target',
			],
			'landmark with selector without target blank' => [
				'landmark'          => 'main',
				'landmark_selector' => 'main#content',
				'css_class'         => 'landmark-link',
				'target_blank'      => false,
				'expected_pattern'  => 'link_without_target',
			],
			'landmark with custom CSS class'              => [
				'landmark'          => 'footer',
				'landmark_selector' => 'footer.site-footer',
				'css_class'         => 'custom-landmark-class',
				'target_blank'      => true,
				'expected_pattern'  => 'link_with_class',
			],
		];
	}

	/**
	 * Test landmark link with proper URL structure.
	 */
	public function test_landmark_link_url_structure() {
		// Skip test if WordPress functions not available.
		if ( ! function_exists( 'wp_create_nonce' ) || ! function_exists( 'get_the_permalink' ) || ! function_exists( 'add_query_arg' ) || ! function_exists( 'wp_parse_url' ) ) {
			$this->markTestSkipped( 'WordPress functions not available in test environment.' );
		}

		$landmark = 'navigation';
		$selector = 'nav.main-nav';

		$result = edac_generate_landmark_link( $landmark, $selector, $this->test_post_id );

		// Should contain a proper URL structure.
		$this->assertStringContainsString( '<a href=', $result );

		// Extract the URL from the result.
		preg_match( '/href="([^"]*)"/', $result, $matches );
		$this->assertNotEmpty( $matches[1] );

		$url = $matches[1];
		
		// Decode HTML entities in URL for proper parsing.
		$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5 );

		// Parse the URL to check query parameters.
		$parsed_url = wp_parse_url( $url );
		$this->assertNotEmpty( $parsed_url['query'] );

		parse_str( $parsed_url['query'], $query_params );

		// Should have the landmark parameter.
		$this->assertArrayHasKey( 'edac_landmark', $query_params );
		$this->assertEquals( base64_encode( $selector ), $query_params['edac_landmark'] );

		// Should have a nonce parameter.
		$this->assertArrayHasKey( 'edac_nonce', $query_params );
		$this->assertNotEmpty( $query_params['edac_nonce'] );
	}

	/**
	 * Test landmark text formatting.
	 */
	public function test_landmark_text_formatting() {
		// Test lowercase input.
		$result = edac_generate_landmark_link( 'navigation', '', $this->test_post_id );
		$this->assertStringContainsString( 'Navigation', $result );

		// Test mixed case input.
		$result = edac_generate_landmark_link( 'mainContent', '', $this->test_post_id );
		$this->assertStringContainsString( 'MainContent', $result ); // ucwords behavior.

		// Test all caps input.
		$result = edac_generate_landmark_link( 'HEADER', '', $this->test_post_id );
		$this->assertStringContainsString( 'HEADER', $result );
	}

	/**
	 * Test ARIA label generation.
	 */
	public function test_aria_label_generation() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'esc_html' ) || ! function_exists( 'wp_create_nonce' ) ||
			! function_exists( 'get_the_permalink' ) || ! function_exists( 'add_query_arg' ) ) {
			$this->markTestSkipped( 'WordPress functions not available in test environment.' );
		}

		$landmark = 'header';
		$selector = 'header.site-header';

		$result = edac_generate_landmark_link( $landmark, $selector, $this->test_post_id );

		// Should contain aria-label attribute.
		$this->assertStringContainsString( 'aria-label=', $result );

		// Extract the aria-label value.
		preg_match( '/aria-label="([^"]*)"/', $result, $matches );
		$this->assertNotEmpty( $matches[1] );

		$aria_label = $matches[1];

		// Should contain the landmark name and descriptive text.
		$this->assertStringContainsString( 'Header', $aria_label );
		$this->assertStringContainsString( 'landmark', $aria_label );
		$this->assertStringContainsString( 'website', $aria_label );
		$this->assertStringContainsString( 'new window', $aria_label );
	}

	/**
	 * Test HTML escaping and security.
	 */
	public function test_html_escaping_security() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'esc_html' ) || ! function_exists( 'wp_create_nonce' ) ||
			! function_exists( 'get_the_permalink' ) || ! function_exists( 'add_query_arg' ) ) {
			$this->markTestSkipped( 'WordPress functions not available in test environment.' );
		}

		$malicious_landmark = '<script>alert("xss")</script>';
		$malicious_selector = 'header"><script>alert("xss")</script>';
		$malicious_class    = 'class"><script>alert("xss")</script>';

		$result = edac_generate_landmark_link(
			$malicious_landmark,
			$malicious_selector,
			$this->test_post_id,
			$malicious_class
		);

		// Should not contain unescaped script tags.
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringNotContainsString( 'alert("xss")', $result );

		// Should properly escape HTML entities.
		$this->assertStringContainsString( '&lt;', $result );
		$this->assertStringContainsString( '&gt;', $result );
	}

	/**
	 * Test default CSS class usage.
	 */
	public function test_default_css_class() {
		$result = edac_generate_landmark_link( 'main', 'main#content', $this->test_post_id );

		// Should contain the default CSS class.
		$this->assertStringContainsString(
			'class="edac-details-rule-records-record-landmark-link"',
			$result
		);
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		if ( $this->test_post_id ) {
			wp_delete_post( $this->test_post_id, true );
		}
		parent::tearDown();
	}
}
