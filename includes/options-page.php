<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Purge_Post_Data;
use EDAC\Inc\Accessibility_Statement;

/**
 * Check if user can ignore or can manage options
 *
 * @return bool
 */
function edac_user_can_ignore() {

	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	$user              = wp_get_current_user();
	$user_roles        = ( isset( $user->roles ) ) ? $user->roles : [];
	$ignore_user_roles = get_option( 'edacp_ignore_user_roles' );
	$interset          = ( $user_roles && $ignore_user_roles ) ? array_intersect( $user_roles, $ignore_user_roles ) : false;

	return ( $interset );
}

/**
 * Add an options page under the Settings submenu
 */
function edac_add_options_page() {

	// we don't want to show even the welcome page to subscribers.
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	add_menu_page(
		__( 'Welcome to Accessibility Checker', 'accessibility-checker' ),
		__( 'Accessibility Checker', 'accessibility-checker' ),
		'read',
		'accessibility_checker',
		'edac_display_welcome_page',
		'dashicons-universal-access-alt'
	);

	if ( ! edac_user_can_ignore() ) {
		return;
	}

	/**
	 * Filter the capability required to access the settings page.
	 *
	 * @since 1.4.0
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	$settings_capability = apply_filters( 'edac_filter_settings_capability', 'manage_options' );

	add_submenu_page(
		'accessibility_checker',
		__( 'Accessibility Checker Settings', 'accessibility-checker' ),
		__( 'Settings', 'accessibility-checker' ),
		$settings_capability,
		'accessibility_checker_settings',
		'edac_display_options_page'
		// The submenu doesn't typically require a separate icon.
	);
}

/**
 * Render the welcome page for plugin
 */
function edac_display_welcome_page() {
	include_once plugin_dir_path( __DIR__ ) . 'partials/welcome-page.php';
}

/**
 * Render the options page for plugin
 */
function edac_display_options_page() {
	include_once plugin_dir_path( __DIR__ ) . 'partials/settings-page.php';
}

/**
 * Register settings
 */
function edac_register_setting() {

	// Add sections.
	add_settings_section(
		'edac_general',
		__( 'General Settings', 'accessibility-checker' ),
		'edac_general_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_simplified_summary',
		__( 'Simplified Summary Settings', 'accessibility-checker' ),
		'edac_simplified_summary_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_footer_accessibility_statement',
		__( 'Footer Accessibility Statement', 'accessibility-checker' ),
		'edac_footer_accessibility_statement_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_frontend_highlighter',
		__( 'Frontend Accessibility Checker', 'accessibility-checker' ),
		'edac_frontend_highlighter_section_cb',
		'edac_settings'
	);

	// Add fields.
	add_settings_field(
		'edac_post_types',
		__( 'Post Types To Be Checked', 'accessibility-checker' ),
		'edac_post_types_cb',
		'edac_settings',
		'edac_general',
		[ 'label_for' => 'edac_post_types' ]
	);

	add_settings_field(
		'edac_delete_data',
		__( 'Delete Data', 'accessibility-checker' ),
		'edac_delete_data_cb',
		'edac_settings',
		'edac_general',
		[ 'label_for' => 'edac_delete_data' ]
	);

	add_settings_field(
		'edac_simplified_summary_prompt',
		__( 'Prompt for Simplified Summary', 'accessibility-checker' ),
		'edac_simplified_summary_prompt_cb',
		'edac_settings',
		'edac_simplified_summary',
		[ 'label_for' => 'edac_simplified_summary_prompt' ]
	);

	add_settings_field(
		'edac_simplified_summary_position',
		__( 'Simplified Summary Position', 'accessibility-checker' ),
		'edac_simplified_summary_position_cb',
		'edac_settings',
		'edac_simplified_summary',
		[ 'label_for' => 'edac_simplified_summary_position' ]
	);

	add_settings_field(
		'edac_add_footer_accessibility_statement',
		__( 'Add Footer Accessibility Statement', 'accessibility-checker' ),
		'edac_add_footer_accessibility_statement_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		[ 'label_for' => 'edac_add_footer_accessibility_statement' ]
	);

	add_settings_field(
		'edac_include_accessibility_statement_link',
		__( 'Include Link to Accessibility Policy', 'accessibility-checker' ),
		'edac_include_accessibility_statement_link_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		[ 'label_for' => 'edac_include_accessibility_statement_link' ]
	);

	add_settings_field(
		'edac_accessibility_policy_page',
		__( 'Accessibility Policy page', 'accessibility-checker' ),
		'edac_accessibility_policy_page_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		[ 'label_for' => 'edac_accessibility_policy_page' ]
	);

	add_settings_field(
		'edac_accessibility_statement_preview',
		__( 'Accessibility Statement Preview', 'accessibility-checker' ),
		'edac_accessibility_statement_preview_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		[ 'label_for' => 'edac_accessibility_statement_preview' ]
	);

	add_settings_field(
		'edac_frontend_highlighter_position',
		__( 'Frontend Accessibility Checker Position', 'accessibility-checker' ),
		'edac_frontend_highlighter_position_cb',
		'edac_settings',
		'edac_frontend_highlighter',
		[ 'label_for' => 'edac_frontend_highlighter_position' ]
	);


	// Register settings.
	register_setting( 'edac_settings', 'edac_post_types', 'edac_sanitize_post_types' );
	register_setting( 'edac_settings', 'edac_delete_data', 'edac_sanitize_checkbox' );
	register_setting(
		'edac_settings',
		'edac_simplified_summary_prompt',
		[
			'type'              => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_prompt',
			'default'           => 'when required',
		]
	);
	register_setting(
		'edac_settings',
		'edac_simplified_summary_position',
		[
			'type'              => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_position',
			'default'           => 'after',
		]
	);
	register_setting( 'edac_settings', 'edac_add_footer_accessibility_statement', 'edac_sanitize_checkbox' );
	register_setting( 'edac_settings', 'edac_include_accessibility_statement_link', 'edac_sanitize_checkbox' );
	register_setting( 'edac_settings', 'edac_accessibility_policy_page', 'edac_sanitize_accessibility_policy_page' );

	register_setting( 'edac_settings', 'edac_frontend_highlighter_position', 'edac_sanitize_frontend_highlighter_position' );
}

/**
 * Render the text for the general section
 */
function edac_general_cb() {
	echo '<p>';

	printf(
		/* translators: %1$s: link to the plugin documentation website. */
		esc_html__( 'Use the settings below to configure Accessibility Checker. Additional information about each setting can be found in the %1$s.', 'accessibility-checker' ),
		'<a href="https://a11ychecker.com/" target="_blank" aria-label="' . esc_attr__( 'plugin documentation (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'plugin documentation', 'accessibility-checker' ) . '</a>'
	);

	if ( EDAC_KEY_VALID === false ) {
		printf(
			/* translators: %1$s: link to the "Accessibility Checker Pro" website. */
			' ' . esc_html__( 'More features and email support is available with %1$s.', 'accessibility-checker' ),
			'<a href="https://equalizedigital.com/accessibility-checker/pricing/" target="_blank" aria-label="' . esc_attr__( 'Accessibility Checker Pro (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'Accessibility Checker Pro', 'accessibility-checker' ) . '</a>'
		);
	}

	echo '</p>';
}

/**
 * Render the copy used to explain the frontend highlighter section.
 *
 * @return void
 */
function edac_frontend_highlighter_section_cb() {
	echo '<p>';
	esc_html_e( 'Use the settings below to configure the frontend accessibility checker.', 'accessibility-checker' );
	echo '</p>';
}

/**
 * Render the text for the simplified summary section
 */
function edac_simplified_summary_cb() {
	printf(
		'<p>%1$s %2$s</p>',
		esc_html__( 'Web Content Accessibility Guidelines (WCAG) at the AAA level require any content with a reading level above 9th grade to have an alternative that is easier to read. Simplified summary text is added on the readability tab in the Accessibility Checker meta box on each post\'s or page\'s edit screen.', 'accessibility-checker' ),
		'<a href="https://a11ychecker.com/help3265" target="_blank" aria-label="' . esc_attr__( 'Learn more about simplified summaries and readability requirements (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'Learn more about simplified summaries and readability requirements.', 'accessibility-checker' ) . '</a>'
	);
}

/**
 * Render the text for the footer accessiblity statement section
 */
function edac_footer_accessibility_statement_cb() {
	echo '<p>';
	echo esc_html__( 'Are you thinking "Wow, this plugin is amazing" and is it helping you make your website more accessible? Share your efforts to make your website more accessible with your customers and let them know you\'re using Accessibility Checker to ensure all people can use your website. Add a small text-only link and statement in the footer of your website.', 'accessibility-checker' );
	echo '</p>';
}

/**
 * Render the radio input field for position option
 */
function edac_simplified_summary_position_cb() {
	$position = get_option( 'edac_simplified_summary_position' );
	?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" id="<?php echo 'edac_simplified_summary_position'; ?>" value="before" <?php checked( $position, 'before' ); ?>>
				<?php esc_html_e( 'Before the content', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" value="after" <?php checked( $position, 'after' ); ?>>
				<?php esc_html_e( 'After the content', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" value="none" <?php checked( $position, 'none' ); ?>>
				<?php esc_html_e( 'Insert manually', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<div id="ac-simplified-summary-option-code">
			<p><?php esc_html_e( 'Use this function to manually add the simplified summary to your theme within the loop.', 'accessibility-checker' ); ?></p>
			<kbd>edac_get_simplified_summary();</kbd>
			<p><?php esc_html_e( 'The function optionally accepts the post ID as a parameter.', 'accessibility-checker' ); ?><p>
			<kbd>edac_get_simplified_summary($post);</kbd>
		</div>
		<p class="edac-description"><?php echo esc_html__( 'Set where you would like simplified summaries to appear in relation to your content if filled in.', 'accessibility-checker' ); ?></p>
	<?php
}

/**
 * Renders radio inputs for the frontend highlighter position option.
 *
 * @return void
 */
function edac_frontend_highlighter_position_cb() {
	$position = get_option( 'edac_frontend_highlighter_position', 'right' );
	?>
		<fieldset>
			<label>
				<input type="radio" name="edac_frontend_highlighter_position" value="right" <?php checked( $position, 'right' ); ?>>
				<?php esc_html_e( 'Bottom Right Corner (default)', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="edac_frontend_highlighter_position" value="left" <?php checked( $position, 'left' ); ?>>
				<?php esc_html_e( 'Bottom Left Corner', 'accessibility-checker' ); ?>
			</label>
			<br>
		</fieldset>
		<p class="edac-description"><?php echo esc_html__( 'Set where you would like the frontend accessibility checker to appear on the page.', 'accessibility-checker' ); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 *
 * @param array $position Position value.
 *
 * @return string
 */
function edac_sanitize_simplified_summary_position( $position ) {
	if ( in_array( $position, [ 'before', 'after', 'none' ], true ) ) {
		return $position;
	}
}

/**
 * Sanitize the frontend highlighter position value before being saved to database.
 *
 * @param string $position the position to save. Can only be 'right' or 'left'.
 *
 * @return string
 */
function edac_sanitize_frontend_highlighter_position( string $position ): string {
	if ( in_array( $position, [ 'right', 'left' ], true ) ) {
		return $position;
	}
	return 'right';
}

/**
 * Render the radio input field for position option
 */
function edac_simplified_summary_prompt_cb() {
	$prompt = get_option( 'edac_simplified_summary_prompt' );
	?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" id="<?php echo 'edac_simplified_summary_prompt'; ?>" value="when required" <?php checked( $prompt, 'when required' ); ?>>
				<?php esc_html_e( 'When Required', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="always" <?php checked( $prompt, 'always' ); ?>>
				<?php esc_html_e( 'Always', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="none" <?php checked( $prompt, 'none' ); ?>>
				<?php esc_html_e( 'Never', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<p class="edac-description"><?php echo esc_html__( 'Should Accessibility Checker only ask for a simplified summary when the reading level of your post or page is above 9th grade, always ask for it regardless of reading level, or never ask for it regardless of reading level?', 'accessibility-checker' ); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 *
 * @param array $prompt The text.
 *
 * @return string
 */
function edac_sanitize_simplified_summary_prompt( $prompt ) {
	if ( in_array( $prompt, [ 'when required', 'always', 'none' ], true ) ) {
		return $prompt;
	}
}

/**
 * Render the checkbox input field for post_types option
 */
function edac_post_types_cb() {

	$selected_post_types = get_option( 'edac_post_types' ) ? get_option( 'edac_post_types' ) : [];
	$post_types          = edac_post_types();
	$custom_post_types   = edac_custom_post_types();
	$all_post_types      = ( is_array( $post_types ) && is_array( $custom_post_types ) ) ? array_merge( $post_types, $custom_post_types ) : [];
	?>
		<fieldset>
			<?php
			if ( $all_post_types ) {
				foreach ( $all_post_types as $post_type ) {
					$disabled = in_array( $post_type, $post_types, true ) ? '' : 'disabled';
					?>
					<label>
						<input type="checkbox" name="<?php echo 'edac_post_types[]'; ?>" value="<?php echo esc_attr( $post_type ); ?>"
																<?php
																checked( in_array( $post_type, $selected_post_types, true ), 1 );
																echo esc_attr( $disabled );
																?>
						>
						<?php echo esc_html( $post_type ); ?>
					</label>
					<br>
					<?php
				}
			}
			?>
		</fieldset>
		<?php if ( EDAC_KEY_VALID === false ) { ?>
			<p class="edac-description">
				<?php
				echo esc_html__( 'To check content other than posts and pages, please ', 'accessibility-checker' );
				?>
				<a href="https://my.equalizedigital.com/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'upgrade to pro', 'accessibility-checker' ); ?></a>
				<?php esc_html_e( ' (opens in a new window)', 'accessibility-checker' ); ?>
			</p>
		<?php } else { ?>
			<p class="edac-description">
				<?php
				esc_html_e( 'Choose which post types should be checked during a scan. Please note, removing a previously selected post type will remove its scanned information and any custom ignored warnings that have been setup.', 'accessibility-checker' );
				?>
			</p>
			<?php
		}
}

/**
 * Sanitize the post type value before being saved to database
 *
 * @param array $selected_post_types Post types to sanitize.
 * @return array
 */
function edac_sanitize_post_types( $selected_post_types ) {

	$post_types = edac_post_types();

	if ( $selected_post_types ) {
		foreach ( $selected_post_types as $key => $post_type ) {
			if ( ! in_array( $post_type, $post_types, true ) ) {
				unset( $selected_post_types[ $key ] );
			}
		}
	}

	// get unselected post types.
	$unselected_post_types = $post_types;
	if ( $selected_post_types ) {
		$unselected_post_types = array_diff( $post_types, $selected_post_types );
	}

	// delete unselected post type issues.
	if ( $unselected_post_types ) {
		foreach ( $unselected_post_types as $unselected_post_type ) {
			Purge_Post_Data::delete_cpt_posts( $unselected_post_type );
		}
	}

	// clear cached stats if selected posts types change.
	if ( get_option( 'edac_post_types' ) !== $selected_post_types ) {
		$scan_stats = new \EDAC\Admin\Scans_Stats();
		$scan_stats->clear_cache();

		// EDACP\Scans is the old namespace, kept for back compat but should be removed after a few releases.
		if ( class_exists( '\EDACP\Scans' ) || class_exists( '\EqualizeDigital\AccessibilityCheckerPro\Admin\Scans' ) ) {
			delete_option( 'edacp_fullscan_completed_at' );
		}
	}

	return $selected_post_types;
}

/**
 * Render the checkbox input field for add footer accessibility statement option
 */
function edac_add_footer_accessibility_statement_cb() {

	$option = get_option( 'edac_add_footer_accessibility_statement' ) ? get_option( 'edac_add_footer_accessibility_statement' ) : false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="edac_add_footer_accessibility_statement" value="1" <?php checked( $option, 1 ); ?>>
			<?php esc_html_e( 'Add Footer Accessibility Statement', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Render the checkbox input field for add footer accessibility statement option
 */
function edac_include_accessibility_statement_link_cb() {

	$option   = get_option( 'edac_include_accessibility_statement_link' ) ? get_option( 'edac_include_accessibility_statement_link' ) : false;
	$disabled = get_option( 'edac_add_footer_accessibility_statement' ) ? get_option( 'edac_add_footer_accessibility_statement' ) : false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="<?php echo 'edac_include_accessibility_statement_link'; ?>" value="<?php echo '1'; ?>"
													<?php
													checked( $option, 1 );
													disabled( $disabled, false );
													?>
			>
			<?php esc_html_e( 'Include Link to Accessibility Policy', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Render the select field for accessibility policy page option
 */
function edac_accessibility_policy_page_cb() {

	$policy_page = get_option( 'edac_accessibility_policy_page' );
	$policy_page = is_numeric( $policy_page ) ? get_page_link( $policy_page ) : $policy_page;
	?>

	<input style="width: 100%;" type="text" name="edac_accessibility_policy_page" id="edac_accessibility_policy_page" value="<?php echo esc_attr( $policy_page ); ?>">

	<?php
}

/**
 * Sanitize accessibility policy page values before being saved to database
 *
 * @param string $page Page to sanitize.
 * @return string
 */
function edac_sanitize_accessibility_policy_page( $page ) {
	if ( $page ) {
		return esc_url( $page );
	}
}

/**
 * Render the accessibility statement preview
 */
function edac_accessibility_statement_preview_cb() {
	echo wp_kses_post(
		( new Accessibility_Statement() )->get_accessibility_statement()
	);
}

/**
 * Render the checkbox input field for delete data option
 */
function edac_delete_data_cb() {

	$option = get_option( 'edac_delete_data' ) ? get_option( 'edac_delete_data' ) : false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="edac_delete_data" value="1" <?php checked( $option, 1 ); ?>>
			<?php esc_html_e( 'Delete all Accessibility Checker data when the plugin is uninstalled.', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Sanitize checkbox values before being saved to database
 *
 * These are passed in as strings, but we will save them as integers.
 *
 * @since 1.11.0
 *
 * @param string $input Input to sanitize.
 * @return int either 1 for checked or 0 for unchecked
 */
function edac_sanitize_checkbox( $input ) {
	return ( isset( $input ) && '1' === $input ) ? 1 : 0;
}
