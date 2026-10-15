<?php
/**
 * Tests for the ignore/dismiss panel rendering helpers.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\IgnoreUI;

/**
 * Tests for IgnoreUI::get_reasons(), ::render_ignore_panel() and ::render_reason_fieldset().
 *
 * The methods are string assembly over the input they are handed, so the tests assert the emitted
 * fragments rather than snapshotting the markup. The `Manage Globally Dismissed` branch needs Pro
 * loaded (edac_is_pro() requires EDACP_VERSION, which the free plugin never defines), so the
 * no-Pro fall-through is driven with ignore_global set instead.
 *
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\IgnoreUI::get_reasons
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\IgnoreUI::render_ignore_panel
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\IgnoreUI::render_reason_fieldset
 */
class IgnoreUiTest extends WP_UnitTestCase {

	/**
	 * Drops the filtered reasons these tests register.
	 */
	public function tearDown(): void {
		remove_all_filters( 'edac_dismiss_reasons' );

		parent::tearDown();
	}

	/**
	 * Renders a panel, overriding only the args a test cares about.
	 *
	 * @param array $overrides Panel args to override.
	 * @return string The rendered panel.
	 */
	private function panel( array $overrides = [] ): string {
		$defaults = [
			'issue_id'          => 7,
			'is_ignored'        => false,
			'ignore_type'       => 'error',
			'ignore_permission' => true,
		];

		return IgnoreUI::render_ignore_panel( array_merge( $defaults, $overrides ) );
	}

	/**
	 * Maps every rendered reason radio to its checked and disabled state.
	 *
	 * @param string $html Fieldset HTML.
	 * @return array<string, array{checked: bool, disabled: bool}> State by reason slug.
	 */
	private function radio_states( string $html ): array {
		preg_match_all( '/<input type="radio"[^>]*>/', $html, $matches );

		$states = [];
		foreach ( $matches[0] as $input ) {
			preg_match( '/value="([^"]+)"/', $input, $value );
			$states[ $value[1] ?? '' ] = [
				'checked'  => false !== strpos( $input, 'checked' ),
				'disabled' => false !== strpos( $input, 'disabled' ),
			];
		}

		return $states;
	}

	/**
	 * The published defaults come back, and a filter can extend them.
	 */
	public function test_get_reasons_returns_the_defaults_and_honours_the_filter(): void {
		$reasons = IgnoreUI::get_reasons();

		$this->assertSame( [ 'accessible', 'false_positive', 'remediated' ], array_keys( $reasons ) );
		$this->assertSame( 'Confirmed accessible', $reasons['accessible']['label'] );
		$this->assertSame( 'Remediated', $reasons['remediated']['label'] );

		add_filter(
			'edac_dismiss_reasons',
			static function ( $filtered ) {
				$filtered['custom'] = [
					'label'       => 'Custom',
					'description' => 'Added by a filter.',
				];
				return $filtered;
			}
		);

		$reasons = IgnoreUI::get_reasons();

		$this->assertSame( [ 'accessible', 'false_positive', 'remediated', 'custom' ], array_keys( $reasons ) );
		$this->assertSame( 'Added by a filter.', $reasons['custom']['description'] );
	}

	/**
	 * Only the malformed entries are stripped from a filtered set.
	 */
	public function test_get_reasons_strips_only_malformed_entries(): void {
		$valid = [
			'label'       => 'Valid',
			'description' => 'Text',
		];

		add_filter(
			'edac_dismiss_reasons',
			static function () use ( $valid ) {
				return array_merge( [ 'valid_one' => $valid ], [ 'bad' => 'nope' ] );
			}
		);

		$this->assertSame( [ 'valid_one' ], array_keys( IgnoreUI::get_reasons() ) );
	}

	/**
	 * Filtered reasons that cannot be used leave the defaults in place.
	 *
	 * @dataProvider provider_filtered_reasons
	 *
	 * @param mixed    $filtered Value the filter hands back.
	 * @param string[] $expected Expected reason slugs.
	 */
	public function test_get_reasons_falls_back_for_unusable_filtered_reasons( $filtered, array $expected ): void {
		add_filter(
			'edac_dismiss_reasons',
			static function () use ( $filtered ) {
				return $filtered;
			}
		);

		$this->assertSame( $expected, array_keys( IgnoreUI::get_reasons() ) );
	}

	/**
	 * Data provider for filtered reason values, usable and not.
	 *
	 * @return array<string, array{mixed, string[]}> Filtered value and expected slugs.
	 */
	public function provider_filtered_reasons(): array {
		$defaults = [ 'accessible', 'false_positive', 'remediated' ];

		return [
			'false'               => [ false, $defaults ],
			'empty array'         => [ [], $defaults ],
			'entry not an array'  => [ [ 'bad' => 'Confirmed accessible' ], $defaults ],
			'entry without label' => [ [ 'bad' => [ 'description' => 'Text' ] ], $defaults ],
		];
	}

	/**
	 * The fieldset lists one described radio per reason, scoped to the issue.
	 */
	public function test_render_reason_fieldset_lists_one_radio_per_reason(): void {
		$html = IgnoreUI::render_reason_fieldset( 7 );

		$this->assertStringContainsString( '<legend>Dismiss issue as:</legend>', $html );
		$this->assertSame( [ 'accessible', 'false_positive', 'remediated' ], array_keys( $this->radio_states( $html ) ) );
		$this->assertStringContainsString( 'name="edac-ignore-reason-7"', $html );
		$this->assertStringContainsString( 'id="edac-ignore-reason-7-false_positive-description"', $html );
		$this->assertStringContainsString( '<label for="edac-ignore-reason-7-remediated">Remediated</label>', $html );
	}

	/**
	 * The selected and disabled radios follow the saved reason and the issue state.
	 *
	 * @dataProvider provider_reason_selection
	 *
	 * @param string $current Saved reason.
	 * @param bool   $ignored Whether the issue is dismissed.
	 * @param bool[] $checked Expected checked state, in reason order.
	 */
	public function test_render_reason_fieldset_selects_the_expected_radio( string $current, bool $ignored, array $checked ): void {
		$states = $this->radio_states( IgnoreUI::render_reason_fieldset( 7, $current, $ignored ) );

		$this->assertSame( $checked, array_column( $states, 'checked' ) );
		foreach ( $states as $state ) {
			$this->assertSame( $ignored, $state['disabled'] );
		}
	}

	/**
	 * Data provider for the radio selection matrix.
	 *
	 * @return array<string, array{string, bool, bool[]}> Selection cases.
	 */
	public function provider_reason_selection(): array {
		return [
			'no saved reason' => [ '', false, [ true, false, false ] ],
			'saved reason'    => [ 'remediated', false, [ false, false, true ] ],
			'dismissed issue' => [ '', true, [ false, false, false ] ],
		];
	}

	/**
	 * An open issue the current user can act on renders the whole panel, and a global ignore
	 * without Pro falls through to the standard button.
	 */
	public function test_render_ignore_panel_renders_the_default_panel(): void {
		$html = $this->panel( [ 'ignore_global' => 1 ] );

		$this->assertStringContainsString( '<div id="edac-details-rule-records-record-ignore-7" class="edac-details-rule-records-record-ignore">', $html );
		$this->assertStringContainsString( '<fieldset', $html );
		$this->assertStringContainsString( 'data-id="7" data-action="enable" data-type="error"', $html );
		$this->assertStringContainsString( '<span class="edac-details-rule-records-record-ignore-submit-label">Dismiss Issue</span>', $html );
		$this->assertStringContainsString( '<span class="edac-details-rule-records-record-ignore-info-user"></span>', $html );
		$this->assertStringNotContainsString( 'Manage Globally Dismissed', $html );

		$empty = IgnoreUI::render_ignore_panel( [] );

		$this->assertStringContainsString( 'record-ignore-0"', $empty );
		$this->assertStringContainsString( 'data-id="0" data-action="enable"', $empty );
	}

	/**
	 * The dismissing user's login is shown while that user still exists.
	 */
	public function test_render_ignore_panel_shows_the_dismissing_user(): void {
		$user_id = self::factory()->user->create( [ 'user_login' => 'edac_ignorer' ] );

		$this->assertStringContainsString(
			'<span class="edac-details-rule-records-record-ignore-info-user"><strong>Username:</strong> edac_ignorer</span>',
			$this->panel( [ 'ignore_user' => $user_id ] )
		);
	}

	/**
	 * The dismissal date renders in the site timezone, and stays empty for placeholder values.
	 *
	 * @dataProvider provider_ignore_dates
	 *
	 * @param string $date   Stored date value.
	 * @param string $needle Expected contents of the info-date span.
	 */
	public function test_render_ignore_panel_renders_the_dismissal_date( string $date, string $needle ): void {
		$saved = [
			'date_format'     => get_option( 'date_format' ),
			'time_format'     => get_option( 'time_format' ),
			'timezone_string' => get_option( 'timezone_string' ),
		];

		update_option( 'date_format', 'Y-m-d' );
		update_option( 'time_format', 'H:i' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$this->assertStringContainsString(
				'<span class="edac-details-rule-records-record-ignore-info-date">' . $needle . '</span>',
				$this->panel( [ 'ignore_date' => $date ] )
			);
		} finally {
			foreach ( $saved as $option => $value ) {
				update_option( $option, $value );
			}
		}
	}

	/**
	 * Data provider for stored dismissal dates.
	 *
	 * @return array<string, array{string, string}> Date value and expected span contents.
	 */
	public function provider_ignore_dates(): array {
		return [
			'utc date'  => [ '2024-07-01 12:00:00', '<strong>Date:</strong> 2024-07-01 08:00' ],
			'empty'     => [ '', '' ],
			'zero date' => [ '0000-00-00 00:00:00', '' ],
		];
	}

	/**
	 * The submit control flips between dismiss and reopen with the issue state.
	 *
	 * @dataProvider provider_issue_states
	 *
	 * @param bool   $is_ignored Whether the issue is dismissed.
	 * @param string $action     Expected data-action value.
	 * @param string $label      Expected button label.
	 * @param string $textarea   Expected textarea fragment.
	 */
	public function test_render_ignore_panel_labels_the_submit_control( bool $is_ignored, string $action, string $label, string $textarea ): void {
		$html = $this->panel( [ 'is_ignored' => $is_ignored ] );

		$this->assertStringContainsString( 'data-action="' . $action . '"', $html );
		$this->assertStringContainsString( '>' . $label . '</span>', $html );
		$this->assertStringContainsString( $textarea, $html );
	}

	/**
	 * Data provider for the two issue states.
	 *
	 * @return array<string, array{bool, string, string, string}> Issue state expectations.
	 */
	public function provider_issue_states(): array {
		return [
			'open issue'      => [ false, 'enable', 'Dismiss Issue', 'record-ignore-comment-7" >' ],
			'dismissed issue' => [ true, 'disable', 'Reopen Issue', 'record-ignore-comment-7" disabled>' ],
		];
	}

	/**
	 * Without the permission only the notice and any stored comment survive.
	 *
	 * @dataProvider provider_without_permission
	 *
	 * @param bool   $is_ignored Whether the issue is dismissed.
	 * @param string $comment    Stored comment.
	 * @param string $rendered   Expected escaped comment, when one is stored.
	 * @param bool   $notice     Whether the permission notice is expected.
	 * @param bool   $textarea   Whether the comment textarea is expected.
	 */
	public function test_render_ignore_panel_without_permission( bool $is_ignored, string $comment, string $rendered, bool $notice, bool $textarea ): void {
		$html = $this->panel(
			[
				'ignore_permission' => false,
				'is_ignored'        => $is_ignored,
				'ignore_comment'    => $comment,
			]
		);

		$this->assertStringNotContainsString( '<fieldset', $html );
		$this->assertStringNotContainsString( 'data-action="', $html );
		$this->assertSame( $notice, (bool) strpos( $html, 'have permission to dismiss this issue.' ) );
		$this->assertSame( $textarea, (bool) strpos( $html, 'record-ignore-comment-7' ) );

		if ( '' !== $rendered ) {
			$this->assertStringContainsString( '>' . $rendered . '</textarea>', $html );
		}
	}

	/**
	 * Data provider for the no-permission rendering cases.
	 *
	 * @return array<string, array{bool, string, string, bool, bool}> Expectations.
	 */
	public function provider_without_permission(): array {
		return [
			'open issue'      => [ false, '', '', true, false ],
			'stored comment'  => [ false, 'Handled <b>by</b> the team.', 'Handled &lt;b&gt;by&lt;/b&gt; the team.', true, true ],
			'dismissed issue' => [ true, '', '', false, false ],
		];
	}
}
