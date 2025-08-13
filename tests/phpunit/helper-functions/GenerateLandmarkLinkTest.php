<?php
/**
 * Class GenerateLandmarkLinkTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test case for edac_generate_landmark_link function.
 */
class GenerateLandmarkLinkTest extends WP_UnitTestCase {

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

		// Create a test post.
		$this->test_post_id = $this->factory->post->create(
			[
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			]
		);
	}

	/**
	 * Clean up test environment.
	 */
	public function tearDown(): void {
		// Clean up the test post.
		wp_delete_post( $this->test_post_id, true );
		parent::tearDown();
	}

	/**
	 * Tests the edac_generate_landmark_link function with valid landmark and selector.
	 */
	public function test_edac_generate_landmark_link_with_selector() {
		$landmark          = 'header';
		$landmark_selector = 'header.site-header';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		// Check that result contains an anchor tag.
		$this->assertStringContainsString( '<a href=', $result );
		$this->assertStringContainsString( 'class="edac-details-rule-records-record-landmark-link"', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
		$this->assertStringContainsString( '>Header</a>', $result );

		// Check that the URL contains the expected query parameters.
		$this->assertStringContainsString( 'edac_landmark=', $result );
		$this->assertStringContainsString( 'edac_nonce=', $result );

		// Check aria-label.
		$this->assertStringContainsString( 'aria-label="View Header landmark on website, opens a new window"', $result );

		// Verify the landmark selector is base64 encoded in the URL.
		$encoded_selector = base64_encode( $landmark_selector );
		$this->assertStringContainsString( "edac_landmark={$encoded_selector}", $result );
	}

	/**
	 * Tests the edac_generate_landmark_link function with only landmark (no selector).
	 */
	public function test_edac_generate_landmark_link_without_selector() {
		$landmark          = 'navigation';
		$landmark_selector = '';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		// Should return just the formatted landmark text, not a link.
		$this->assertEquals( 'Navigation', $result );
		$this->assertStringNotContainsString( '<a href=', $result );
		$this->assertStringNotContainsString( 'edac_landmark=', $result );
	}

	/**
	 * Tests the edac_generate_landmark_link function with empty landmark.
	 */
	public function test_edac_generate_landmark_link_with_empty_landmark() {
		$landmark          = '';
		$landmark_selector = 'nav.main-nav';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		// Should return empty string.
		$this->assertEquals( '', $result );
	}

	/**
	 * Tests the edac_generate_landmark_link function with custom CSS class.
	 */
	public function test_edac_generate_landmark_link_with_custom_css_class() {
		$landmark          = 'main';
		$landmark_selector = 'main.content';
		$custom_class      = 'my-custom-landmark-class';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id, $custom_class );

		$this->assertStringContainsString( "class=\"{$custom_class}\"", $result );
		$this->assertStringNotContainsString( 'class="edac-details-rule-records-record-landmark-link"', $result );
	}

	/**
	 * Tests the edac_generate_landmark_link function with target_blank disabled.
	 */
	public function test_edac_generate_landmark_link_without_target_blank() {
		$landmark          = 'footer';
		$landmark_selector = 'footer.site-footer';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id, 'edac-details-rule-records-record-landmark-link', false );

		$this->assertStringContainsString( '<a href=', $result );
		$this->assertStringNotContainsString( 'target="_blank"', $result );
		$this->assertStringContainsString( '>Footer</a>', $result );
	}

	/**
	 * Tests the edac_generate_landmark_link function with various landmark types.
	 *
	 * @dataProvider landmark_types_data_provider
	 *
	 * @param string $landmark          The landmark type.
	 * @param string $expected_display  The expected display text.
	 */
	public function test_edac_generate_landmark_link_landmark_types( $landmark, $expected_display ) {
		$landmark_selector = 'div.test';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		$this->assertStringContainsString( ">{$expected_display}</a>", $result );
		$this->assertStringContainsString( "View {$expected_display} landmark on website", $result );
	}

	/**
	 * Data provider for landmark types testing.
	 *
	 * @return array
	 */
	public function landmark_types_data_provider() {
		return [
			'header landmark'        => [ 'header', 'Header' ],
			'navigation landmark'    => [ 'navigation', 'Navigation' ],
			'main landmark'          => [ 'main', 'Main' ],
			'footer landmark'        => [ 'footer', 'Footer' ],
			'aside landmark'         => [ 'aside', 'Aside' ],
			'section landmark'       => [ 'section', 'Section' ],
			'search landmark'        => [ 'search', 'Search' ],
			'banner landmark'        => [ 'banner', 'Banner' ],
			'contentinfo landmark'   => [ 'contentinfo', 'Contentinfo' ],
			'complementary landmark' => [ 'complementary', 'Complementary' ],
			'form landmark'          => [ 'form', 'Form' ],
			'region landmark'        => [ 'region', 'Region' ],
		];
	}

	/**
	 * Tests the edac_generate_landmark_link function with special characters in landmark.
	 */
	public function test_edac_generate_landmark_link_with_special_characters() {
		$landmark          = '<script>alert("xss")</script>';
		$landmark_selector = 'div.test';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		// Check that dangerous script tags are properly escaped.
		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringNotContainsString( 'alert("xss")', $result );
		$this->assertStringContainsString( '&lt;', $result ); // Verify HTML is escaped.
	}

	/**
	 * Tests the edac_generate_landmark_link function with special characters in selector.
	 */
	public function test_edac_generate_landmark_link_with_special_selector() {
		$landmark          = 'main';
		$landmark_selector = 'div[data-test="value with spaces & symbols"]';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		// Verify base64 encoded selector is present in URL.
		$this->assertStringContainsString( 'edac_landmark=', $result );
		preg_match( '/edac_landmark=([^&"]+)/', $result, $matches );
		// Decode and compare the landmark selector values.
		$decoded_selector = base64_decode( urldecode( $matches[1] ) );
		$this->assertEquals( $landmark_selector, $decoded_selector );
		$this->assertStringContainsString( '>Main</a>', $result );
	}

	/**
	 * Tests that the nonce is properly generated.
	 */
	public function test_edac_generate_landmark_link_nonce_generation() {
		$landmark          = 'header';
		$landmark_selector = 'header.test';

		// Generate the link twice.
		$result1 = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );
		$result2 = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		// Both should contain nonce parameters.
		$this->assertStringContainsString( 'edac_nonce=', $result1 );
		$this->assertStringContainsString( 'edac_nonce=', $result2 );

		// Extract nonces (basic check that they're generated).
		preg_match( '/edac_nonce=([^&"]+)/', $result1, $matches1 );
		preg_match( '/edac_nonce=([^&"]+)/', $result2, $matches2 );

		$this->assertNotEmpty( $matches1[1] );
		$this->assertNotEmpty( $matches2[1] );

		// Verify nonces are valid for the 'edac_highlight' action.
		$this->assertTrue( wp_verify_nonce( $matches1[1], 'edac_highlight' ) !== false );
		$this->assertTrue( wp_verify_nonce( $matches2[1], 'edac_highlight' ) !== false );
	}

	/**
	 * Tests the edac_generate_landmark_link function with null values.
	 */
	public function test_edac_generate_landmark_link_with_null_values() {
		$result = edac_generate_landmark_link( null, null, $this->test_post_id );
		$this->assertEquals( '', $result );

		$result = edac_generate_landmark_link( 'main', null, $this->test_post_id );
		$this->assertEquals( 'Main', $result );

		$result = edac_generate_landmark_link( null, 'div.test', $this->test_post_id );
		$this->assertEquals( '', $result );
	}

	/**
	 * Tests the edac_generate_landmark_link function URL structure.
	 */
	public function test_edac_generate_landmark_link_url_structure() {
		$landmark          = 'navigation';
		$landmark_selector = 'nav.primary';

		$result = edac_generate_landmark_link( $landmark, $landmark_selector, $this->test_post_id );

		// Extract the href value.
		preg_match( '/href="([^"]+)"/', $result, $matches );
		$this->assertNotEmpty( $matches[1] );

		$url        = $matches[1];
		$parsed_url = wp_parse_url( html_entity_decode( $url ) );

		// Should contain the post permalink structure.
		$this->assertNotEmpty( $parsed_url['query'] );

		// Parse query string.
		parse_str( $parsed_url['query'], $query_params );

		$this->assertArrayHasKey( 'edac_landmark', $query_params );
		$this->assertArrayHasKey( 'edac_nonce', $query_params );
		// Decode and compare the landmark selector.
		$decoded_selector = base64_decode( $query_params['edac_landmark'] );
		$this->assertEquals( $landmark_selector, $decoded_selector );
	}
}
