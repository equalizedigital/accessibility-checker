<?php
/**
 * Admin page that holds the active rules setting.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

use EqualizeDigital\AccessibilityChecker\Rules\RuleRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Rules page and its settings.
 *
 * @since 1.37.0
 */
class RulesPage implements PageInterface {

	/**
	 * The capability required to access the settings page.
	 *
	 * @var string
	 */
	private $settings_capability;

	const PAGE_TAB_SLUG = 'rules';

	/**
	 * The settings page slug which all sections, settings and fields are registered to.
	 *
	 * @var string
	 */
	const SETTINGS_SLUG = 'edac_settings_rules';

	/**
	 * Constructor.
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	public function __construct( $settings_capability ) {
		$this->settings_capability = $settings_capability;
	}

	/**
	 * Add the settings sections and fields and setup its tabs and filter in for the content.
	 */
	public function add_page() {

		$this->register_settings_sections();
		$this->register_fields_and_settings();

		add_filter( 'edac_filter_admin_scripts_slugs', [ $this, 'add_slug_to_admin_scripts' ] );
		add_filter( 'edac_filter_remove_admin_notices_screens', [ $this, 'add_slug_to_admin_notices' ] );
		add_filter( 'edac_filter_settings_tab_items', [ $this, 'add_rules_tab' ], 8 );
		add_action( 'edac_settings_tab_content', [ $this, 'add_rules_tab_content' ], 12, 1 );

		// Run early so other filters can refilter after us.
		add_filter( 'edac_filter_register_rules', [ $this, 'apply_disabled_rules_setting' ], 5 );
	}

	/**
	 * Add rules tab to settings page.
	 *
	 * @param  array $settings_tab_items Array of tab items.
	 * @return array
	 */
	public function add_rules_tab( $settings_tab_items ) {

		$rules_tab = [
			'slug'  => 'rules',
			'label' => __( 'Rules', 'accessibility-checker' ),
			'order' => 3,
		];
		array_push( $settings_tab_items, $rules_tab );

		return $settings_tab_items;
	}

	/**
	 * Render the rules tab content on the settings page.
	 *
	 * @param  string $tab Name of the active tab.
	 * @return void
	 */
	public function add_rules_tab_content( $tab ) {
		if ( 'rules' === $tab ) {
			include EDAC_PLUGIN_DIR . '/partials/admin-page/rules-page.php';
		}
	}

	/**
	 * Add the rules slug to the admin scripts.
	 *
	 * @param array $slugs The slugs that are already added.
	 * @return array
	 */
	public function add_slug_to_admin_scripts( $slugs ) {
		$slugs[] = 'accessibility_checker_' . self::PAGE_TAB_SLUG;
		return $slugs;
	}

	/**
	 * Add the rules slug to the admin notices.
	 *
	 * @param array $slugs The slugs that are already added.
	 * @return array
	 */
	public function add_slug_to_admin_notices( $slugs ) {
		$slugs[] = 'accessibility-checker_page_accessibility_checker_' . self::PAGE_TAB_SLUG;
		return $slugs;
	}

	/**
	 * Render the page.
	 */
	public function render_page() {
		include_once EDAC_PLUGIN_DIR . 'partials/admin-page/rules-page.php';
	}

	/**
	 * Register the settings sections for this options page.
	 */
	public function register_settings_sections() {

		add_settings_section(
			'edac_rules_general',
			__( 'Active Rules', 'accessibility-checker' ),
			[ $this, 'rules_section_general_cb' ],
			self::SETTINGS_SLUG,
		);
	}

	/**
	 * Register the settings fields and settings for this options page.
	 */
	public function register_fields_and_settings() {

		add_settings_field(
			'edac_reset_rules',
			'',
			[ $this, 'reset_rules_cb' ],
			self::SETTINGS_SLUG,
			'edac_rules_general',
		);

		add_settings_field(
			'edac_disabled_rules',
			__( 'Active Rules', 'accessibility-checker' ),
			[ $this, 'disabled_rules_cb' ],
			self::SETTINGS_SLUG,
			'edac_rules_general',
			[ 'label_for' => 'edac_disabled_rules' ]
		);

		register_setting( self::SETTINGS_SLUG, 'edac_disabled_rules', [ $this, 'sanitize_disabled_rules' ] );
	}

	/**
	 * Callback for the general rules section that renders a description for it.
	 */
	public function rules_section_general_cb() {
		echo '<p>' . esc_html__( 'Choose which rules are active during a scan. Unchecked rules will not be processed.', 'accessibility-checker' ) . '</p>';
	}

	/**
	 * Checks whether any callback other than our own is hooked onto edac_filter_register_rules.
	 *
	 * When an external filter is present the UI should be read-only and saves
	 * should preserve the existing stored value.
	 *
	 * @return bool
	 */
	private function has_external_filter(): bool {
		global $wp_filter;
		if ( ! isset( $wp_filter['edac_filter_register_rules'] ) ) {
			return false;
		}
		foreach ( $wp_filter['edac_filter_register_rules']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$fn = $callback['function'];
				if ( ! ( is_array( $fn ) && $fn[0] instanceof self && 'apply_disabled_rules_setting' === $fn[1] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Render the reset all rules button.
	 */
	public function reset_rules_cb() {
		?>
		<button
			type="button"
			id="edac-reset-rules"
			class="button"
			<?php disabled( ! edac_is_pro() || $this->has_external_filter() ); ?>
		><?php esc_html_e( 'Reset all rules to active', 'accessibility-checker' ); ?></button>
		<script>
		( function() {
			var btn = document.getElementById( 'edac-reset-rules' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function() {
				if ( ! window.confirm( <?php echo wp_json_encode( __( 'Are you sure you want to reset all rules to active? This will enable any rules you have disabled.', 'accessibility-checker' ) ); ?> ) ) {
					return;
				}
				document.querySelectorAll( '#edac-rules-list input[type="checkbox"]' ).forEach( function( cb ) {
					cb.checked = true;
				} );
				var form = btn.closest( 'form' );
				if ( form ) {
					var submitBtn = form.querySelector( 'input[type="submit"], button[type="submit"]' );
					if ( submitBtn ) {
						submitBtn.click();
					} else if ( form.requestSubmit ) {
						form.requestSubmit();
					} else {
						form.submit();
					}
				}
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Render the checkbox list for the active rules option.
	 */
	public function disabled_rules_cb() {

		$has_external_filter = $this->has_external_filter();
		$is_pro              = edac_is_pro();
		$all_rules           = RuleRegistry::load_rules();
		$disabled            = get_option( 'edac_disabled_rules', [] );
		$disabled            = is_array( $disabled ) ? $disabled : [];

		$groups = [
			'error'   => __( 'Problems', 'accessibility-checker' ),
			'warning' => __( 'Needs Review', 'accessibility-checker' ),
		];

		$group_icons = [
			'error'   => 'error',
			'warning' => 'warning',
		];

		$list_attrs = $is_pro ? '' : 'class="edac-setting--upsell"';
		?>
		<div id="edac-rules-list" <?php echo $list_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		$position = 0;
		foreach ( $groups as $rule_type => $group_label ) :
			$group_rules = array_filter( $all_rules, fn( $r ) => ( $r['rule_type'] ?? '' ) === $rule_type );
			if ( empty( $group_rules ) ) {
				continue;
			}
			?>
			<fieldset class="edac-rules-group" data-group="<?php echo esc_attr( $rule_type ); ?>">
				<legend class="screen-reader-text"><?php echo esc_html( $group_label ); ?></legend>
				<?php
				foreach ( $group_rules as $rule ) :
					$slug     = $rule['slug'];
					$title    = $rule['title'] ?? $slug;
					$field_id = ( 0 === $position ) ? 'edac_disabled_rules' : 'edac_active_rules_' . $slug;
					++$position;
					?>
					<span class="edac-rule-item">
						<label>
							<input
								type="checkbox"
								id="<?php echo esc_attr( $field_id ); ?>"
								name="edac_active_rules[]"
								value="<?php echo esc_attr( $slug ); ?>"
								<?php checked( ! in_array( $slug, $disabled, true ) ); ?>
								<?php disabled( ! $is_pro || $has_external_filter ); ?>
							>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- edac_icon returns safe SVG markup.
							echo edac_icon( $group_icons[ $rule_type ] );
							echo esc_html( $title );
							?>
						</label>
					</span>
				<?php endforeach; ?>
			</fieldset>
		<?php endforeach; ?>
		</div>

		<p class="edac-description">
			<?php esc_html_e( 'Choose which rules are active during a scan. Unchecked rules will not be processed. Note: disabling a rule does not remove previously stored results for that rule.', 'accessibility-checker' ); ?>
		</p>

		<?php
		if ( $has_external_filter ) {
			?>
			<p class="edac-description">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s is a code tag with the name of the filter hook. */
						__( 'The rule list is read-only because another plugin or theme is filtering it via the %s hook.', 'accessibility-checker' ),
						'<code>edac_filter_register_rules</code>'
					),
					[
						'code' => [],
					]
				);
				?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize the active rules submission and store disabled rule slugs.
	 *
	 * The form submits active rule slugs via edac_active_rules[]. We compute
	 * disabled = all registered slugs − submitted active slugs and store that.
	 *
	 * @return array Array of disabled rule slugs.
	 */
	public function sanitize_disabled_rules(): array {

		// When an external filter controls the rules list, preserve whatever is
		// already stored rather than allowing the (disabled) form to overwrite it.
		if ( $this->has_external_filter() ) {
			$existing = get_option( 'edac_disabled_rules', [] );
			return is_array( $existing ) ? $existing : [];
		}

		$all_slugs = array_column( RuleRegistry::load_rules(), 'slug' );

		$submitted_active = isset( $_POST['edac_active_rules'] ) ? (array) $_POST['edac_active_rules'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- sanitized below with array_filter and in_array and nonce verified by the Setting API.

		// Only accept values that exactly match a registered rule slug.
		$valid_active = array_filter(
			$submitted_active,
			fn( $slug ) => in_array( $slug, $all_slugs, true )
		);

		return array_values( array_diff( $all_slugs, $valid_active ) );
	}

	/**
	 * Filter callback that removes disabled rules from the registered rules list.
	 *
	 * Hooked onto edac_filter_register_rules at priority 5 so that other filters
	 * can further modify the list after us.
	 *
	 * @param array $rules The full list of registered rules.
	 * @return array Rules with disabled entries removed.
	 */
	public function apply_disabled_rules_setting( array $rules ): array {

		if ( ! edac_is_pro() ) {
			return $rules;
		}

		$disabled = get_option( 'edac_disabled_rules', [] );

		if ( empty( $disabled ) || ! is_array( $disabled ) ) {
			return $rules;
		}

		return array_values(
			array_filter(
				$rules,
				fn( $rule ) => ! in_array( $rule['slug'] ?? '', $disabled, true )
			)
		);
	}
}
