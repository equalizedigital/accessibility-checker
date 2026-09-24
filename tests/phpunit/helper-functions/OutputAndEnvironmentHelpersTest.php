<?php
/**
 * Tests for the output and environment helpers in includes/helper-functions.php.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_generate_summary_stat(), edac_link_wrapper(), edac_is_pro(),
 * edac_is_woocommerce_enabled() and edac_check_if_post_id_is_woocommerce_checkout_page().
 *
 * @covers ::edac_generate_summary_stat
 * @covers ::edac_link_wrapper
 * @covers ::edac_is_pro
 * @covers ::edac_is_woocommerce_enabled
 * @covers ::edac_check_if_post_id_is_woocommerce_checkout_page
 */
class OutputAndEnvironmentHelpersTest extends WP_UnitTestCase {

	/**
	 * Base URL used by the link wrapper tests.
	 *
	 * @var string
	 */
	private $base_url = 'https://example.com/pricing/';

	/**
	 * Removes filters added by a test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'edac_filter_generate_link_type_ref' );

		parent::tearDown();
	}

	/**
	 * Extracts the query args from a URL.
	 *
	 * @param string $url The URL to parse.
	 * @return array<string, string> Query args keyed by name.
	 */
	private function get_query_args( string $url ): array {
		$args = [];
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );

		return $args;
	}

	/**
	 * A positive count is marked as having errors and renders the count and label.
	 */
	public function test_generate_summary_stat_marks_the_item_when_the_count_is_positive(): void {
		$html = edac_generate_summary_stat( 'edac-errors', 7, 'errors' );

		$this->assertStringContainsString( 'class="edac-summary-stat edac-errors has-errors"', $html );
		$this->assertStringContainsString( '<span class="screen-reader-text">7 errors</span>', $html );
		$this->assertStringContainsString( '<div class="edac-panel-number-label" aria-hidden="true">errors</div>', $html );
		$this->assertSame( 1, preg_match( '/<div class="edac-panel-number" aria-hidden="true">\s*7\s*<\/div>/', $html ) );
		$this->assertStringStartsWith( '<li class="edac-summary-stat', trim( $html ) );
		$this->assertStringEndsWith( '</li>', trim( $html ) );
	}

	/**
	 * Counts that are not positive do not mark the item as having errors.
	 *
	 * @dataProvider provider_counts_without_errors
	 *
	 * @param int $count The count to render.
	 */
	public function test_generate_summary_stat_omits_the_has_errors_class_without_a_positive_count( int $count ): void {
		$html = edac_generate_summary_stat( 'edac-errors', $count, 'errors' );

		$this->assertStringContainsString( 'class="edac-summary-stat edac-errors"', $html );
		$this->assertStringNotContainsString( 'has-errors', $html );
		$this->assertStringContainsString( '<span class="screen-reader-text">' . $count . ' errors</span>', $html );
	}

	/**
	 * Data provider for counts that must not be flagged as errors.
	 *
	 * @return array<string, array<int>>
	 */
	public function provider_counts_without_errors(): array {
		return [
			'zero'     => [ 0 ],
			'negative' => [ -1 ],
		];
	}

	/**
	 * The default icon is the error icon and a requested icon name is used instead.
	 */
	public function test_generate_summary_stat_renders_the_requested_icon(): void {
		$this->assertStringContainsString(
			'edac-icon edac-icon--error',
			edac_generate_summary_stat( 'edac-errors', 1, 'errors' )
		);

		$this->assertStringContainsString(
			'edac-icon edac-icon--warning',
			edac_generate_summary_stat( 'edac-warnings', 1, 'warnings', 'warning' )
		);
	}

	/**
	 * An unknown icon name renders no icon markup at all.
	 */
	public function test_generate_summary_stat_renders_no_icon_for_an_unknown_icon_name(): void {
		$html = edac_generate_summary_stat( 'edac-errors', 1, 'errors', 'not-a-real-icon' );

		$this->assertStringNotContainsString( 'edac-icon', $html );
		$this->assertStringContainsString( 'class="edac-summary-stat edac-errors has-errors"', $html );
	}

	/**
	 * A returned link carries the plugin's UTM defaults plus campaign and content.
	 */
	public function test_link_wrapper_returns_a_link_with_utm_parameters(): void {
		$link = edac_link_wrapper( $this->base_url, 'settings-page', 'custom-content', false );

		$this->assertIsString( $link );
		$this->assertStringStartsWith( $this->base_url . '?', $link );
		$this->assertSame( '/pricing/', wp_parse_url( $link, PHP_URL_PATH ) );

		$args = $this->get_query_args( $link );

		$this->assertSame( 'accessibility-checker', $args['utm_source'] );
		$this->assertSame( 'software', $args['utm_medium'] );
		$this->assertSame( 'settings-page', $args['utm_campaign'] );
		$this->assertSame( 'custom-content', $args['utm_content'] );
		$this->assertSame( 'wordpress', $args['platform'] ); // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- UTM platform slug, not prose.
		$this->assertSame( $GLOBALS['wp_version'], $args['platform_version'] );
		$this->assertSame( PHP_VERSION, $args['php_version'] );
		$this->assertSame( edac_is_pro() ? 'pro' : 'free', $args['software'] );
		$this->assertSame( defined( 'EDACP_VERSION' ) ? EDACP_VERSION : EDAC_VERSION, $args['software_version'] );
		$this->assertArrayHasKey( 'days_active', $args );
		$this->assertArrayNotHasKey( 'ref', $args );
	}

	/**
	 * Only non-empty campaign and content values are added to the link.
	 *
	 * @dataProvider provider_campaign_and_content
	 *
	 * @param string      $campaign          Campaign value passed to the wrapper.
	 * @param string      $content           Content value passed to the wrapper.
	 * @param string      $expected_campaign Expected utm_campaign value.
	 * @param string|null $expected_content  Expected utm_content value, or null when absent.
	 */
	public function test_link_wrapper_only_adds_non_empty_campaign_and_content( string $campaign, string $content, string $expected_campaign, $expected_content ): void {
		$args = $this->get_query_args( edac_link_wrapper( $this->base_url, $campaign, $content, false ) );

		$this->assertSame( $expected_campaign, $args['utm_campaign'] );

		if ( null === $expected_content ) {
			$this->assertArrayNotHasKey( 'utm_content', $args );
		} else {
			$this->assertSame( $expected_content, $args['utm_content'] );
		}
	}

	/**
	 * Data provider for campaign and content combinations.
	 *
	 * With neither value set the meta campaign default from edac_generate_link_type()
	 * is left in place, and no utm_content is added at all.
	 *
	 * @return array<string, array<int, string|null>>
	 */
	public function provider_campaign_and_content(): array {
		return [
			'neither'       => [ '', '', 'wordpress-general', null ],
			'campaign only' => [ 'settings-page', '', 'settings-page', null ],
			'content only'  => [ '', 'custom-content', 'wordpress-general', 'custom-content' ],
			'both'          => [ 'settings-page', 'custom-content', 'settings-page', 'custom-content' ],
		];
	}

	/**
	 * Query args already present on the base URL are preserved.
	 */
	public function test_link_wrapper_preserves_existing_base_url_query_args(): void {
		$args = $this->get_query_args( edac_link_wrapper( 'https://example.com/page/?foo=bar', '', '', false ) );

		$this->assertSame( 'bar', $args['foo'] );
		$this->assertSame( 'accessibility-checker', $args['utm_source'] );
	}

	/**
	 * A string returned from the ref filter is added to the link.
	 */
	public function test_link_wrapper_adds_the_ref_when_the_filter_returns_a_string(): void {
		add_filter(
			'edac_filter_generate_link_type_ref',
			static function () {
				return 'settings-page';
			}
		);

		$args = $this->get_query_args( edac_link_wrapper( $this->base_url, '', '', false ) );

		$this->assertSame( 'settings-page', $args['ref'] );
	}

	/**
	 * A ref that is not a non-empty string is discarded.
	 *
	 * @dataProvider provider_unusable_refs
	 *
	 * @param mixed $ref The value the ref filter returns.
	 */
	public function test_link_wrapper_ignores_an_unusable_ref( $ref ): void {
		add_filter(
			'edac_filter_generate_link_type_ref',
			static function () use ( $ref ) {
				return $ref;
			}
		);

		$args = $this->get_query_args( edac_link_wrapper( $this->base_url, '', '', false ) );

		$this->assertArrayNotHasKey( 'ref', $args );
	}

	/**
	 * Data provider for ref values that must not be added to the link.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_unusable_refs(): array {
		return [
			'empty string' => [ '' ],
			'null'         => [ null ],
			'array'        => [ [ 'not', 'a', 'string' ] ],
			'integer'      => [ 42 ],
		];
	}

	/**
	 * By default the link is escaped and echoed instead of returned.
	 */
	public function test_link_wrapper_echoes_an_escaped_link_by_default(): void {
		$expected = edac_link_wrapper( $this->base_url, 'settings-page', 'custom-content', false );

		ob_start();
		$returned = edac_link_wrapper( $this->base_url, 'settings-page', 'custom-content' );
		$output   = ob_get_clean();

		$this->assertNull( $returned );
		$this->assertSame( esc_url( $expected ), $output );
	}

	/**
	 * A base URL that is empty or not a string returns nothing and prints nothing.
	 *
	 * @dataProvider provider_unusable_base_urls
	 *
	 * @param mixed $base_url The value passed as the base URL.
	 */
	public function test_link_wrapper_returns_nothing_for_an_unusable_base_url( $base_url ): void {
		ob_start();
		$returned = edac_link_wrapper( $base_url, 'settings-page', 'custom-content' );
		$output   = ob_get_clean();

		$this->assertNull( $returned );
		$this->assertSame( '', $output );
	}

	/**
	 * Data provider for values that are not a usable base URL.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_unusable_base_urls(): array {
		return [
			'empty string' => [ '' ],
			'null'         => [ null ],
			'zero'         => [ 0 ],
			'false'        => [ false ],
			'array'        => [ [ 'https://example.com/' ] ],
			'object'       => [ new stdClass() ],
		];
	}

	/**
	 * Without a valid Pro license key edac_is_pro() is false.
	 *
	 * EDAC_KEY_VALID is only defined, and only ever truthy, when a Pro license is
	 * valid, so the unlicensed branch is the one reachable in this environment.
	 */
	public function test_edac_is_pro_is_false_without_a_valid_pro_key(): void {
		if ( defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID ) {
			$this->markTestSkipped( 'A valid Pro license key is defined in this process.' );
		}

		$this->assertFalse( edac_is_pro() );
	}

	/**
	 * Without WooCommerce loaded edac_is_woocommerce_enabled() is false.
	 */
	public function test_edac_is_woocommerce_enabled_is_false_without_woocommerce(): void {
		if ( function_exists( 'WC' ) || class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is loaded in this process.' );
		}

		$this->assertFalse( edac_is_woocommerce_enabled() );
	}

	/**
	 * Without WooCommerce loaded no post ID can be the checkout page.
	 */
	public function test_check_if_post_id_is_woocommerce_checkout_page_is_false_without_woocommerce(): void {
		if ( function_exists( 'WC' ) || class_exists( 'WooCommerce' ) ) {
			$this->markTestSkipped( 'WooCommerce is loaded in this process.' );
		}

		$post_id = self::factory()->post->create();

		$this->assertFalse( edac_check_if_post_id_is_woocommerce_checkout_page( $post_id ) );
		$this->assertFalse( edac_check_if_post_id_is_woocommerce_checkout_page( 0 ) );
	}
}
