<?php
/**
 * Tests for the settings render callbacks defined in includes/options-page.php.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests the edac_*_cb() callbacks that render the settings screen.
 *
 * Each callback reads one option and prints a single settings control, so the
 * tests below assert the stored-value branch and the empty/default branch of
 * each one. Pro-only controls are asserted in their not-Pro state, the only
 * state observable here because Pro is not loaded when this suite runs.
 *
 * @covers ::edac_general_cb
 * @covers ::edac_frontend_highlighter_section_cb
 * @covers ::edac_simplified_summary_cb
 * @covers ::edac_footer_accessibility_statement_cb
 * @covers ::edac_system_cb
 * @covers ::edac_full_site_scan_speed_cb
 * @covers ::edac_enable_archive_scanning_cb
 * @covers ::edac_simplified_summary_position_cb
 * @covers ::edac_frontend_highlighter_position_cb
 * @covers ::edac_simplified_summary_prompt_cb
 * @covers ::edac_post_types_cb
 * @covers ::edac_add_footer_accessibility_statement_cb
 * @covers ::edac_include_accessibility_statement_link_cb
 * @covers ::edac_delete_data_cb
 * @covers ::edac_show_metabox_in_block_editor_cb
 * @covers ::edac_scan_all_taxonomy_terms_cb
 * @covers ::edac_simplified_summary_heading_cb
 * @covers ::edac_accessibility_policy_page_cb
 * @covers ::edac_accessibility_statement_preview_cb
 */
class OptionsPageRenderCallbacksTest extends WP_UnitTestCase {

	/**
	 * A public custom post type used to cover the disabled control branch.
	 *
	 * @var string
	 */
	private const CUSTOM_POST_TYPE = 'edac_test_type';

	/**
	 * Options written by these tests, removed on teardown.
	 *
	 * @var string[]
	 */
	private $options_to_clean = [
		'edac_post_types',
		'edac_simplified_summary_position',
		'edac_frontend_highlighter_position',
		'edac_simplified_summary_prompt',
		'edac_add_footer_accessibility_statement',
		'edac_include_accessibility_statement_link',
		'edac_delete_data',
		'edac_show_metabox_in_block_editor',
		'edacp_full_site_scan_speed',
		'edacp_enable_archive_scanning',
		'edacp_scan_all_taxonomies',
		'edacp_simplified_summary_heading',
		'edac_accessibility_policy_page',
	];

	/**
	 * Removes the options and post type these tests create.
	 */
	public function tearDown(): void {
		foreach ( $this->options_to_clean as $option ) {
			delete_option( $option );
		}

		if ( post_type_exists( self::CUSTOM_POST_TYPE ) ) {
			unregister_post_type( self::CUSTOM_POST_TYPE );
		}

		parent::tearDown();
	}

	/**
	 * Renders a settings callback and returns everything it printed.
	 *
	 * @param callable $callback The render callback to invoke.
	 * @return string
	 */
	private function render( callable $callback ): string {
		ob_start();
		$callback();

		return (string) ob_get_clean();
	}

	/**
	 * Returns the first opening tag of the given name that contains a fragment.
	 *
	 * @param string $html     The rendered markup.
	 * @param string $tag_name The tag name to look for, without brackets.
	 * @param string $fragment A substring that must appear inside the tag.
	 * @return string The matched opening tag, or an empty string when there is none.
	 */
	private function tag( string $html, string $tag_name, string $fragment ): string {
		preg_match_all( '/<' . preg_quote( $tag_name, '/' ) . '\b[^>]*>/', $html, $matches );

		foreach ( $matches[0] as $tag ) {
			if ( false !== strpos( $tag, $fragment ) ) {
				return $tag;
			}
		}

		return '';
	}

	/**
	 * The section description callbacks render their explanatory prose.
	 *
	 * @dataProvider provider_section_callbacks
	 *
	 * @param string $callback The render callback to invoke.
	 * @param string $expected A distinctive substring of the expected output.
	 * @return void
	 */
	public function test_section_callbacks_render_their_prose( string $callback, string $expected ): void {
		$this->assertStringContainsString( $expected, $this->render( $callback ) );
	}

	/**
	 * Provides the section callbacks and a distinctive substring of their output.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_section_callbacks(): array {
		return [
			'general'                      => [ 'edac_general_cb', 'Configure the types of content that should be checked for accessibility issues.' ],
			'frontend highlighter section' => [ 'edac_frontend_highlighter_section_cb', 'Use the settings below to configure the frontend accessibility checker.' ],
			'simplified summary section'   => [ 'edac_simplified_summary_cb', 'Web Content Accessibility Guidelines (WCAG) at the AAA level' ],
			'footer statement section'     => [ 'edac_footer_accessibility_statement_cb', 'Add a small text-only link and statement in the footer of your website.' ],
			'system section'               => [ 'edac_system_cb', 'Configure system-level settings for the Accessibility Checker plugin.' ],
		];
	}

	/**
	 * The sections that link out render the upgrade and help links, opening in a new window.
	 *
	 * @dataProvider provider_section_links
	 *
	 * @param string   $callback The render callback to invoke.
	 * @param string[] $needles  Substrings the rendered markup must contain.
	 * @return void
	 */
	public function test_section_callbacks_render_their_links( string $callback, array $needles ): void {
		$html = $this->render( $callback );

		foreach ( $needles as $needle ) {
			$this->assertStringContainsString( $needle, $html );
		}
	}

	/**
	 * Provides the linking section callbacks and the substrings they must render.
	 *
	 * @return array<string, array{0: string, 1: string[]}>
	 */
	public function provider_section_links(): array {
		return [
			'general promotes pro'  => [ 'edac_general_cb', [ 'More features and email support is available with', 'Accessibility Checker Pro', 'target="_blank"' ] ],
			'summary help document' => [ 'edac_simplified_summary_cb', [ 'a11ychecker.com/help', 'target="_blank"', 'Learn more about simplified summaries and readability requirements.' ] ],
		];
	}

	/**
	 * Checkbox settings callbacks reflect the stored option in both directions.
	 *
	 * @dataProvider provider_checkbox_callbacks
	 *
	 * @param string $callback      The render callback to invoke.
	 * @param string $option        The option the callback reads.
	 * @param string $input_id      The id of the control it renders.
	 * @param bool   $on_by_default Whether the control is checked with no option stored.
	 * @return void
	 */
	public function test_checkbox_callbacks_reflect_the_stored_option( string $callback, string $option, string $input_id, bool $on_by_default ): void {
		$unset = $this->tag( $this->render( $callback ), 'input', 'id="' . $input_id . '"' );

		if ( $on_by_default ) {
			$this->assertStringContainsString( "checked='checked'", $unset );
			update_option( $option, 0 );
		} else {
			$this->assertStringNotContainsString( "checked='checked'", $unset );
			update_option( $option, 1 );
		}

		$stored = $this->tag( $this->render( $callback ), 'input', 'id="' . $input_id . '"' );

		if ( $on_by_default ) {
			$this->assertStringNotContainsString( "checked='checked'", $stored );
		} else {
			$this->assertStringContainsString( "checked='checked'", $stored );
			$this->assertStringContainsString( 'value="1"', $stored );
		}
	}

	/**
	 * Provides the checkbox callbacks with the option and control they render.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: bool}>
	 */
	public function provider_checkbox_callbacks(): array {
		return [
			'archive scanning' => [ 'edac_enable_archive_scanning_cb', 'edacp_enable_archive_scanning', 'edacp_enable_archive_scanning', false ],
			'footer statement' => [ 'edac_add_footer_accessibility_statement_cb', 'edac_add_footer_accessibility_statement', 'edac_add_footer_accessibility_statement', false ],
			'delete data'      => [ 'edac_delete_data_cb', 'edac_delete_data', 'edac_delete_data', false ],
			'editor metabox'   => [ 'edac_show_metabox_in_block_editor_cb', 'edac_show_metabox_in_block_editor', 'edac_show_metabox_in_block_editor', true ],
		];
	}

	/**
	 * The statement link checkbox is only usable once the footer statement is on.
	 */
	public function test_include_accessibility_statement_link_cb_follows_the_footer_statement(): void {
		$disabled = $this->tag( $this->render( 'edac_include_accessibility_statement_link_cb' ), 'input', 'id="edac_include_accessibility_statement_link"' );

		$this->assertStringContainsString( "disabled='disabled'", $disabled );

		update_option( 'edac_add_footer_accessibility_statement', 1 );
		update_option( 'edac_include_accessibility_statement_link', 1 );

		$enabled = $this->tag( $this->render( 'edac_include_accessibility_statement_link_cb' ), 'input', 'id="edac_include_accessibility_statement_link"' );

		$this->assertStringNotContainsString( "disabled='disabled'", $enabled );
		$this->assertStringContainsString( "checked='checked'", $enabled );
	}

	/**
	 * Radio settings callbacks check the stored value, and only that value.
	 *
	 * @dataProvider provider_radio_callbacks
	 *
	 * @param string $callback  The render callback to invoke.
	 * @param string $option    The option to store, or an empty string to leave it unset.
	 * @param string $field     The shared name attribute of the radio group.
	 * @param string $checked   The group value that should be checked.
	 * @param string $unchecked Another group value that should not be.
	 * @return void
	 */
	public function test_radio_callbacks_check_the_stored_value( string $callback, string $option, string $field, string $checked, string $unchecked ): void {
		if ( '' !== $option ) {
			update_option( $option, $checked );
		}

		$html = $this->render( $callback );

		$is_checked = $this->tag( $html, 'input', 'name="' . $field . '" value="' . $checked . '"' );
		$is_not     = $this->tag( $html, 'input', 'name="' . $field . '" value="' . $unchecked . '"' );

		$this->assertStringContainsString( "checked='checked'", $is_checked );
		$this->assertStringNotContainsString( "checked='checked'", $is_not );
	}

	/**
	 * Provides the radio callbacks, their option and one checked/unchecked pair.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
	 */
	public function provider_radio_callbacks(): array {
		return [
			'summary position'               => [ 'edac_simplified_summary_position_cb', 'edac_simplified_summary_position', 'edac_simplified_summary_position', 'after', 'before' ],
			'highlighter position (default)' => [ 'edac_frontend_highlighter_position_cb', '', 'edac_frontend_highlighter_position', 'right', 'left' ],
			'highlighter position (stored)'  => [ 'edac_frontend_highlighter_position_cb', 'edac_frontend_highlighter_position', 'edac_frontend_highlighter_position', 'left', 'right' ],
			'summary prompt'                 => [ 'edac_simplified_summary_prompt_cb', 'edac_simplified_summary_prompt', 'edac_simplified_summary_prompt', 'always', 'none' ],
		];
	}

	/**
	 * The scan speed select selects the stored speed, and is an upsell without Pro.
	 *
	 * @dataProvider provider_scan_speeds
	 *
	 * @param string $stored   The stored option value, or an empty string for the default.
	 * @param string $selected The option value that should be selected.
	 * @return void
	 */
	public function test_full_site_scan_speed_cb_selects_the_stored_speed( string $stored, string $selected ): void {
		if ( '' !== $stored ) {
			update_option( 'edacp_full_site_scan_speed', $stored );
		}

		$html           = $this->render( 'edac_full_site_scan_speed_cb' );
		$other_selected = '1000' === $selected ? '250' : '1000';

		$this->assertStringContainsString( "selected='selected'", $this->tag( $html, 'option', 'value="' . $selected . '"' ) );
		$this->assertStringNotContainsString( "selected='selected'", $this->tag( $html, 'option', 'value="' . $other_selected . '"' ) );
		$this->assertStringContainsString( 'edac-setting--upsell', $html );
		$this->assertStringContainsString( "disabled='disabled'", $this->tag( $html, 'select', 'name="edacp_full_site_scan_speed"' ) );
	}

	/**
	 * Provides a stored scan speed and the option that should end up selected.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_scan_speeds(): array {
		return [
			'default' => [ '', '1000' ],
			'fast'    => [ '250', '250' ],
			'slowest' => [ '30000', '30000' ],
		];
	}

	/**
	 * A checkbox per available post type; selected ones checked, custom ones disabled.
	 */
	public function test_post_types_cb_renders_and_checks_each_post_type(): void {
		$html = $this->render( 'edac_post_types_cb' );

		$this->assertStringContainsString( 'Post Types To Be Checked', $html );
		$this->assertStringContainsString( 'name="edac_post_types[]"', $html );
		$this->assertStringNotContainsString( "checked='checked'", $html );

		foreach ( edac_post_types() as $post_type ) {
			$tag = $this->tag( $html, 'input', 'id="edac_post_types_' . $post_type . '"' );

			$this->assertStringContainsString( 'value="' . $post_type . '"', $tag );
			$this->assertStringNotContainsString( 'disabled', $tag );
		}

		register_post_type(
			self::CUSTOM_POST_TYPE,
			[
				'label'              => 'Test Types',
				'public'             => true,
				'publicly_queryable' => true,
			]
		);
		$this->assertTrue( post_type_exists( self::CUSTOM_POST_TYPE ) );
		update_option( 'edac_post_types', [ 'page' ] );

		$html = $this->render( 'edac_post_types_cb' );

		$this->assertStringContainsString( "checked='checked'", $this->tag( $html, 'input', 'id="edac_post_types_page"' ) );
		$this->assertStringNotContainsString( "checked='checked'", $this->tag( $html, 'input', 'id="edac_post_types_post"' ) );
		$this->assertStringContainsString( 'disabled', $this->tag( $html, 'input', 'id="edac_post_types_' . self::CUSTOM_POST_TYPE . '"' ) );
	}

	/**
	 * The scan-all-taxonomy-terms checkbox reflects the stored option and is a
	 * disabled upsell without Pro.
	 *
	 * Pro is not loaded in this suite, so the control is disabled whether or not
	 * archive scanning is enabled: the `! $enable_archives` half of the
	 * disabled() condition is not observable here.
	 */
	public function test_scan_all_taxonomy_terms_cb_is_a_disabled_upsell_without_pro(): void {
		$html  = $this->render( 'edac_scan_all_taxonomy_terms_cb' );
		$input = $this->tag( $html, 'input', 'id="edacp_scan_all_taxonomies"' );

		$this->assertStringContainsString( 'edac-setting--upsell', $html );
		$this->assertStringContainsString( "disabled='disabled'", $input );
		$this->assertStringNotContainsString( "checked='checked'", $input );

		update_option( 'edacp_enable_archive_scanning', 1 );
		update_option( 'edacp_scan_all_taxonomies', 1 );

		$html  = $this->render( 'edac_scan_all_taxonomy_terms_cb' );
		$input = $this->tag( $html, 'input', 'id="edacp_scan_all_taxonomies"' );

		// Still disabled: enabling archive scanning is not enough without Pro.
		$this->assertStringContainsString( "disabled='disabled'", $input );
		$this->assertStringContainsString( "checked='checked'", $input );
	}

	/**
	 * The simplified summary heading input renders the stored value, or the
	 * default label when unset, and is a disabled upsell without Pro.
	 *
	 * @dataProvider provider_summary_headings
	 *
	 * @param string $stored   The stored option value, or '' to leave it unset.
	 * @param string $expected The value attribute the input should render.
	 * @return void
	 */
	public function test_simplified_summary_heading_cb_renders_the_heading( string $stored, string $expected ): void {
		if ( '' !== $stored ) {
			update_option( 'edacp_simplified_summary_heading', $stored );
		}

		$input = $this->tag( $this->render( 'edac_simplified_summary_heading_cb' ), 'input', 'id="edacp_simplified_summary_heading"' );

		$this->assertStringContainsString( 'value="' . $expected . '"', $input );
		$this->assertStringContainsString( 'edac-setting--upsell', $input );
		$this->assertStringContainsString( "disabled='disabled'", $input );
	}

	/**
	 * Provides a stored summary heading and the value the input should render.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_summary_headings(): array {
		return [
			'default' => [ '', 'Simplified Summary' ],
			'stored'  => [ 'Page Summary', 'Page Summary' ],
		];
	}

	/**
	 * The accessibility policy page input renders the stored value.
	 *
	 * @dataProvider provider_policy_pages
	 *
	 * @param string $stored   The stored option value, or '' to leave it unset.
	 * @param string $expected The value attribute the input should render.
	 * @return void
	 */
	public function test_accessibility_policy_page_cb_renders_the_stored_value( string $stored, string $expected ): void {
		if ( '' !== $stored ) {
			update_option( 'edac_accessibility_policy_page', $stored );
		}

		$input = $this->tag( $this->render( 'edac_accessibility_policy_page_cb' ), 'input', 'id="edac_accessibility_policy_page"' );

		$this->assertStringContainsString( 'value="' . $expected . '"', $input );
	}

	/**
	 * Provides a stored policy page value and the attribute it should render.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_policy_pages(): array {
		return [
			'unset' => [ '', '' ],
			'url'   => [ 'https://example.com/accessibility-policy', 'https://example.com/accessibility-policy' ],
		];
	}

	/**
	 * A stored value is escaped into the attribute rather than echoed raw.
	 */
	public function test_accessibility_policy_page_cb_escapes_the_stored_value(): void {
		update_option( 'edac_accessibility_policy_page', 'https://example.com/?q="1"' );

		$input = $this->tag( $this->render( 'edac_accessibility_policy_page_cb' ), 'input', 'id="edac_accessibility_policy_page"' );

		$this->assertStringContainsString( '&quot;1&quot;', $input );
		$this->assertStringNotContainsString( 'q="1"', $input );
	}

	/**
	 * The accessibility statement preview prints the statement body.
	 */
	public function test_accessibility_statement_preview_cb_prints_the_statement(): void {
		update_option( 'edac_add_footer_accessibility_statement', 1 );

		$html = $this->render( 'edac_accessibility_statement_preview_cb' );

		$this->assertStringContainsString( (string) get_bloginfo( 'name' ), $html );
		$this->assertStringContainsString( 'Accessibility Checker', $html );
	}

	/**
	 * With no statement configured the preview prints nothing.
	 */
	public function test_accessibility_statement_preview_cb_renders_nothing_when_empty(): void {
		$this->assertSame( '', $this->render( 'edac_accessibility_statement_preview_cb' ) );
	}
}
