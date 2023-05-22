<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

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
	$user_roles        = ( isset( $user->roles ) ) ? $user->roles : array();
	$ignore_user_roles = get_option( 'edacp_ignore_user_roles' );
	$interset          = ( $user_roles && $ignore_user_roles ) ? array_intersect( $user_roles, $ignore_user_roles ) : null;

	if ( $interset ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Add an options page under the Settings submenu
 */
function edac_add_options_page() {

	add_menu_page(
		__( 'Welcome to Accessibility Checker', 'edac' ),
		__( 'Accessibility Checker', 'edac' ),
		'read',
		'accessibility_checker',
		'edac_display_welcome_page',
		'dashicons-universal-access-alt	'
	);

	if ( ! edac_user_can_ignore() ) {
		return;
	}

	// settings panel filter.
	$settings_capability = 'manage_options';
	if ( has_filter( 'edac_filter_settings_capability' ) ) {
		$settings_capability = apply_filters( 'edac_filter_settings_capability', $settings_capability );
	}

	add_submenu_page(
		'accessibility_checker',
		__( 'Accessibility Checker Settings', 'edac' ),
		__( 'Settings', 'edac' ),
		$settings_capability,
		'accessibility_checker_settings',
		'edac_display_options_page',
		1,
		'dashicons-universal-access-alt	'
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
		__( 'General Settings', 'edac' ),
		'edac_general_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_simplified_summary',
		__( 'Simplified Summary Settings', 'edac' ),
		'edac_simplified_summary_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_footer_accessibility_statement',
		__( 'Footer Accessibility Statement', 'edac' ),
		'edac_footer_accessibility_statement_cb',
		'edac_settings'
	);

	// Add fields.
	add_settings_field(
		'edac_post_types',
		__( 'Post Types To Be Checked', 'edac' ),
		'edac_post_types_cb',
		'edac_settings',
		'edac_general',
		array( 'label_for' => 'edac_post_types' )
	);

	add_settings_field(
		'edac_delete_data',
		__( 'Delete Data', 'edac' ),
		'edac_delete_data_cb',
		'edac_settings',
		'edac_general',
		array( 'label_for' => 'edac_delete_data' )
	);

	add_settings_field(
		'edac_simplified_summary_prompt',
		__( 'Prompt for Simplified Summary', 'edac' ),
		'edac_simplified_summary_prompt_cb',
		'edac_settings',
		'edac_simplified_summary',
		array( 'label_for' => 'edac_simplified_summary_prompt' )
	);

	add_settings_field(
		'edac_simplified_summary_position',
		__( 'Simplified Summary Position', 'edac' ),
		'edac_simplified_summary_position_cb',
		'edac_settings',
		'edac_simplified_summary',
		array( 'label_for' => 'edac_simplified_summary_position' )
	);

	add_settings_field(
		'edac_add_footer_accessibility_statement',
		__( 'Add Footer Accessibility Statement', 'edac' ),
		'edac_add_footer_accessibility_statement_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		array( 'label_for' => 'edac_add_footer_accessibility_statement' )
	);

	add_settings_field(
		'edac_include_accessibility_statement_link',
		__( 'Include Link to Accessibility Policy', 'edac' ),
		'edac_include_accessibility_statement_link_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		array( 'label_for' => 'edac_include_accessibility_statement_link' )
	);

	add_settings_field(
		'edac_accessibility_policy_page',
		__( 'Accessibility Policy page', 'edac' ),
		'edac_accessibility_policy_page_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		array( 'label_for' => 'edac_accessibility_policy_page' )
	);

	add_settings_field(
		'edac_accessibility_statement_preview',
		__( 'Accessibility Statement Preview', 'edac' ),
		'edac_accessibility_statement_preview_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		array( 'label_for' => 'edac_accessibility_statement_preview' )
	);

	// Register settings.
	register_setting( 'edac_settings', 'edac_post_types', 'edac_sanitize_post_types' );
	register_setting( 'edac_settings', 'edac_delete_data', 'edac_sanitize_delete_data' );
	register_setting(
		'edac_settings',
		'edac_simplified_summary_prompt',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_prompt',
			'default'           => 'when required',
		)
	);
	register_setting(
		'edac_settings',
		'edac_simplified_summary_position',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_position',
			'default'           => 'after',
		)
	);
	register_setting( 'edac_settings', 'edac_add_footer_accessibility_statement', 'edac_sanitize_add_footer_accessibility_statement' );
	register_setting( 'edac_settings', 'edac_include_accessibility_statement_link', 'edac_sanitize_include_accessibility_statement_link' );
	register_setting( 'edac_settings', 'edac_accessibility_policy_page', 'edac_sanitize_accessibility_policy_page' );

}

/**
 * Render the text for the general section
 */
function edac_general_cb() {
	echo '<p>';
	echo esc_html__( 'Use the settings below to configure Accessibility Checker. Additional information about each setting can be found in the ', 'edac' ) . '<a href="https://a11ychecker.com/" target="_blank">' . esc_html__( 'plugin documentation', 'edac' ) . '</a>.';

	if ( EDAC_KEY_VALID === false ) {
		echo esc_html__( ' More features and email support is available with ', 'edac' ) . '<a href="https://my.equalizedigital.com/" target="_blank">' . esc_html__( 'Accessibility Checker Pro', 'edac' ) . '</a>.';
	}
	echo '</p>';
}

/**
 * Render the text for the simplified summary section
 */
function edac_simplified_summary_cb() {
	echo '<p>' . esc_html__( 'Web Content Accessibility Guidelines (WCAG) at the AAA level require any content with a reading level above 9th grade to have an alternative that is easier to read. Simplified summary text is added on the readability tab in the Accessibility Checker meta box on each post\'s or page\'s edit screen. ', 'edac' ) . '<a href="https://a11ychecker.com/help3265" target="_blank">' . esc_html__( 'Learn more about simplified summaries and readability requirements.', 'edac' ) . '</a></p>';
}

/**
 * Render the text for the footer accessiblity statement section
 */
function edac_footer_accessibility_statement_cb() {
	echo '<p>' . esc_html__( 'Are you thinking "Wow, this plugin is amazing" and is it helping you make your website more accessible? Share your efforts to make your website more accessible with your customers and let them know you\'re using Accessibility Checker to ensure all people can use your website. Add a small text-only link and statement in the footer of your website.', 'edac' ) . '</p>';
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
				<?php esc_html_e( 'Before the content', 'edac' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" value="after" <?php checked( $position, 'after' ); ?>>
				<?php esc_html_e( 'After the content', 'edac' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" value="none" <?php checked( $position, 'none' ); ?>>
				<?php esc_html_e( 'Insert manually', 'edac' ); ?>
			</label>
		</fieldset>
		<div id="ac-simplified-summary-option-code">
			<p>Use this function to manually add the simplified summary to your theme within the loop.</p>
			<kbd>edac_get_simplified_summary();</kbd>
			<p>The function optionally accepts the post ID as a parameter.<p>
			<kbd>edac_get_simplified_summary($post);</kbd>
		</div>
		<p class="edac-description"><?php echo esc_html__( 'Set where you would like simplified summaries to appear in relation to your content if filled in.', 'edac' ); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 *
 * @param array $position Position value.
 * @return array
 */
function edac_sanitize_simplified_summary_position( $position ) {
	if ( in_array( $position, array( 'before', 'after', 'none' ), true ) ) {
		return $position;
	}
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
				<?php esc_html_e( 'When Required', 'edac' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="always" <?php checked( $prompt, 'always' ); ?>>
				<?php esc_html_e( 'Always', 'edac' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="none" <?php checked( $prompt, 'none' ); ?>>
				<?php esc_html_e( 'Never', 'edac' ); ?>
			</label>
		</fieldset>
		<p class="edac-description"><?php echo esc_html__( 'Should Accessibility Checker only ask for a simplified summary when the reading level of your post or page is above 9th grade, always ask for it regardless of reading level, or never ask for it regardless of reading level?', 'edac' ); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 *
 * @param array $prompt The text.
 * @return array
 */
function edac_sanitize_simplified_summary_prompt( $prompt ) {
	if ( in_array( $prompt, array( 'when required', 'always', 'none' ), true ) ) {
		return $prompt;
	}
}

/**
 * Render the checkbox input field for post_types option
 */
function edac_post_types_cb() {

	$selected_post_types = get_option( 'edac_post_types' ) ?: array();
	$post_types          = edac_post_types();
	$custom_post_types   = edac_custom_post_types();
	$all_post_types      = ( is_array( $post_types ) && is_array( $custom_post_types ) ) ? array_merge( $post_types, $custom_post_types ) : array();
	?>
		<fieldset>
			<?php
			if ( $all_post_types ) {
				foreach ( $all_post_types as $post_type ) {
					$disabled = in_array( $post_type, $post_types, true ) ?: 'disabled';
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
			<p class="edac-description"><?php echo esc_html__( 'To check content other than posts and pages, please ', 'edac' ); ?><a href="https://my.equalizedigital.com/" target="_blank"><?php echo esc_html__( 'upgrade to pro', 'edac' ); ?></a>.</p>
		<?php } ?>
	<?php

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
	if ( $selected_post_types ) {
		$unselected_post_types = array_diff( $post_types, $selected_post_types );
	} else {
		$unselected_post_types = $post_types;
	}

	// delete unselected post type issues.
	if ( $unselected_post_types ) {
		foreach ( $unselected_post_types as $unselected_post_type ) {
			edac_delete_cpt_posts( $unselected_post_type );
		}
	}

	return $selected_post_types;
}

/**
 * Render the checkbox input field for add footer accessibility statement option
 */
function edac_add_footer_accessibility_statement_cb() {

	$option = get_option( 'edac_add_footer_accessibility_statement' ) ?: false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="<?php echo 'edac_add_footer_accessibility_statement'; ?>" value="<?php echo '1'; ?>" <?php checked( $option, 1 ); ?>>
			<?php esc_html_e( 'Add Footer Accessibility Statement', 'edac' ); ?>
		</label>
	</fieldset>
	<?php

}

/**
 * Sanitize add footer accessibility statement values before being saved to database
 *
 * @param int $option Option value to sanitize.
 * @return int
 */
function edac_sanitize_add_footer_accessibility_statement( $option ) {
	if ( 1 === intval( $option ) ) {
		return $option;
	}
}

/**
 * Render the checkbox input field for add footer accessibility statement option
 */
function edac_include_accessibility_statement_link_cb() {

	$option   = get_option( 'edac_include_accessibility_statement_link' ) ?: false;
	$disabled = get_option( 'edac_add_footer_accessibility_statement' ) ?: false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="<?php echo 'edac_include_accessibility_statement_link'; ?>" value="<?php echo '1'; ?>" 
													<?php
													checked( $option, 1 );
													disabled( $disabled, false );
													?>
			>
			<?php esc_html_e( 'Include Link to Accessibility Policy', 'edac' ); ?>
		</label>
	</fieldset>
	<?php

}

/**
 * Sanitize add footer accessibility statement values before being saved to database
 *
 * @param int $option Option to sanitize.
 * @return int
 */
function edac_sanitize_include_accessibility_statement_link( $option ) {
	if ( 1 === intval( $option ) ) {
		return $option;
	}
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

	echo edac_get_accessibility_statement();

}

/**
 * Render the checkbox input field for delete data option
 */
function edac_delete_data_cb() {

	$option = get_option( 'edac_delete_data' ) ?: false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="<?php echo 'edac_delete_data'; ?>" value="<?php echo '1'; ?>" <?php checked( $option, 1 ); ?>>
			<?php esc_html_e( 'Delete all Accessibility Checker data when the plugin is uninstalled.', 'edac' ); ?>
		</label>
	</fieldset>
	<?php

}

/**
 * Sanitize delete data values before being saved to database
 *
 * @param int $option Option to sanitize.
 * @return int
 */
function edac_sanitize_delete_data_cb( $option ) {
	if ( 1 === $option ) {
		return $option;
	}
}
