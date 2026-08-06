<?php
/**
 * Tests that grouped settings fields are labelled with a <legend>.
 *
 * @package Accessibility_Checker
 */

/**
 * Grouped checkbox and radio settings must expose their group label as a
 * <legend> inside the <fieldset> rather than as a <label> pointing at the first
 * control in the group.
 *
 * @see https://github.com/equalizedigital/accessibility-checker/issues/1753
 */
class OptionsPageGroupLabelsTest extends WP_UnitTestCase {

	/**
	 * The settings fields registered before this test class ran.
	 *
	 * @var array|null
	 */
	private $original_settings_fields;

	/**
	 * Reset the registered settings fields before each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		global $wp_settings_fields;
		$this->original_settings_fields = $wp_settings_fields;
		$wp_settings_fields             = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- resetting a WP registry between tests.

		edac_register_setting();
	}

	/**
	 * Restore the settings fields registry so later test classes still see the
	 * fields registered during bootstrap. WP_UnitTestCase does not back this up.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_settings_fields;
		$wp_settings_fields = $this->original_settings_fields; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the registry saved in set_up().

		parent::tear_down();
	}

	/**
	 * Every grouped field, as [ section, field ].
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function grouped_field_provider(): array {
		return [
			'post types'                  => [ 'edac_general', 'edac_post_types' ],
			'dismiss permissions'         => [ 'edac_permissions', 'edacp_ignore_user_roles' ],
			'simplified summary prompt'   => [ 'edac_simplified_summary', 'edac_simplified_summary_prompt' ],
			'simplified summary position' => [ 'edac_simplified_summary', 'edac_simplified_summary_position' ],
			'highlighter position'        => [ 'edac_frontend_highlighter', 'edac_frontend_highlighter_position' ],
		];
	}

	/**
	 * Every grouped field's render callback, as [ callback, group label ].
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function grouped_field_callback_provider(): array {
		return [
			'post types'                  => [ 'edac_post_types_cb', 'Post Types To Be Checked' ],
			'dismiss permissions'         => [ 'edac_ignore_user_roles_cb', 'Dismiss Permissions' ],
			'simplified summary prompt'   => [ 'edac_simplified_summary_prompt_cb', 'Prompt for Simplified Summary' ],
			'simplified summary position' => [ 'edac_simplified_summary_position_cb', 'Simplified Summary Position' ],
			'highlighter position'        => [ 'edac_frontend_highlighter_position_cb', 'Frontend Accessibility Checker Position' ],
		];
	}

	/**
	 * A grouped field must not use label_for, which would tie the group label to
	 * the first control in the group only.
	 *
	 * @dataProvider grouped_field_provider
	 *
	 * @param string $section Section the field is registered in.
	 * @param string $field   Field id.
	 *
	 * @return void
	 */
	public function test_grouped_field_has_no_label_for( string $section, string $field ) {
		global $wp_settings_fields;

		$this->assertArrayHasKey( $field, $wp_settings_fields['edac_settings'][ $section ] );
		$this->assertArrayNotHasKey( 'label_for', $wp_settings_fields['edac_settings'][ $section ][ $field ]['args'] );
	}

	/**
	 * A grouped field is wrapped in a fieldset whose first child is a legend
	 * carrying the group label.
	 *
	 * @dataProvider grouped_field_callback_provider
	 *
	 * @param string $callback Render callback for the field.
	 * @param string $label    Expected group label.
	 *
	 * @return void
	 */
	public function test_grouped_field_fieldset_is_labelled_by_a_legend( string $callback, string $label ) {
		ob_start();
		$callback();
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression(
			'#<fieldset[^>]*>\s*<legend[^>]*>\s*<span>' . preg_quote( $label, '#' ) . '</span>#',
			$output,
			"The {$label} fieldset should open with a legend naming the group."
		);
	}

	/**
	 * Controls within a group must not share an id. Each control carries its own
	 * wrapping <label>, so a duplicate id would tie two controls to one label and
	 * leave the markup invalid.
	 *
	 * @dataProvider grouped_field_callback_provider
	 *
	 * @param string $callback Render callback for the field.
	 * @param string $label    Expected group label.
	 *
	 * @return void
	 */
	public function test_grouped_field_control_ids_are_unique( string $callback, string $label ) {
		ob_start();
		$callback();
		$output = ob_get_clean();

		preg_match_all( '#<input[^>]*\sid="([^"]+)"#', $output, $matches );
		$ids = $matches[1];

		$this->assertCount(
			count( array_unique( $ids ) ),
			$ids,
			"Controls in the {$label} group should not share an id. Rendered ids: " . implode( ', ', $ids )
		);
	}

	/**
	 * Every grouped field's render callback, as [ callback, control name ].
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function grouped_field_name_provider(): array {
		return [
			'post types'                  => [ 'edac_post_types_cb', 'edac_post_types[]' ],
			'dismiss permissions'         => [ 'edac_ignore_user_roles_cb', 'edacp_ignore_user_roles[]' ],
			'simplified summary prompt'   => [ 'edac_simplified_summary_prompt_cb', 'edac_simplified_summary_prompt' ],
			'simplified summary position' => [ 'edac_simplified_summary_position_cb', 'edac_simplified_summary_position' ],
			'highlighter position'        => [ 'edac_frontend_highlighter_position_cb', 'edac_frontend_highlighter_position' ],
		];
	}

	/**
	 * The radio groups carry no id, so the admin JS addresses them by name
	 * instead: src/admin/index.js reads and watches
	 * input[type=radio][name=edac_simplified_summary_position] to toggle the
	 * manual-insertion code block. Renaming a control would break that wiring
	 * silently, so pin the name every control in each group renders with.
	 *
	 * @dataProvider grouped_field_name_provider
	 *
	 * @param string $callback Render callback for the field.
	 * @param string $name     Name attribute every control in the group shares.
	 *
	 * @return void
	 */
	public function test_grouped_field_controls_are_addressable_by_name( string $callback, string $name ) {
		ob_start();
		$callback();
		$output = ob_get_clean();

		preg_match_all( '#<input[^>]*\sname="([^"]+)"#', $output, $matches );

		$this->assertNotEmpty(
			$matches[1],
			"The {$callback} group should render at least one control."
		);
		$this->assertSame(
			[ $name ],
			array_values( array_unique( $matches[1] ) ),
			"Every control in the {$callback} group should be addressable as name=\"{$name}\"."
		);
	}
}
