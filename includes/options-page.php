<?php

/**
 * Add an options page under the Settings submenu
 */
function edac_add_options_page(){
	add_menu_page(
		__( 'Welcome to Accessibility Checker', 'edac' ),
		__( 'Accessibility Checker', 'edac' ),
		'manage_options',
		'accessibility_checker',
		'edac_display_welcome_page',
		'dashicons-universal-access-alt	'
	);

	add_submenu_page(
		'accessibility_checker',
		__( 'Accessibility Checker Settings', 'edac' ),
		__( 'Settings', 'edac' ),
		'manage_options',
		'accessibility_checker_settings',
		'edac_display_options_page',
		1,
		'dashicons-universal-access-alt	'
	);
}

/**
 * Render the welcome page for plugin
 */
function edac_display_welcome_page(){
	include_once plugin_dir_path( __DIR__ ).'partials/welcome-page.php';
}

/**
 * Render the options page for plugin
 */
function edac_display_options_page(){
	include_once plugin_dir_path( __DIR__ ).'partials/settings-page.php';
}

/**
 * Register settings
 */
function edac_register_setting() {

	// Add sections
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

	// Add fields
	add_settings_field(
		'edac_post_types',
		__( 'Post Types To Be Checked', 'edac' ),
		'edac_post_types_cb',
		'edac_settings',
		'edac_general',
		array( 'label_for' => 'edac_post_types' )
	);

	add_settings_field(
		'edac_authorization',
		__( 'Password Protection Login', 'edac' ),
		'edac_authorization_cb',
		'edac_settings',
		'edac_general',
		array( 'label_for' => 'edac_authorization' )
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

	// Register settings
	register_setting( 'edac_settings', 'edac_post_types', 'edac_sanitize_post_types');
	register_setting( 'edac_settings', 'edac_authorization_password', 'edac_sanitize_authorization_password');
	register_setting( 'edac_settings', 'edac_authorization_username', 'edac_sanitize_authorization_username');
	register_setting( 'edac_settings', 'edac_simplified_summary_prompt', 
		[
			'type' => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_prompt',
			'default' => 'when required',
		]
	);
	register_setting( 'edac_settings', 'edac_simplified_summary_position', 
		[
			'type' => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_position',
			'default' => 'after',
		]
	);
	
}

/**
 * Render the text for the general section
 */
function edac_general_cb(){
	
	$pro_text = '';
	if (get_transient( 'edacp_license_valid' ) == false){
		$pro_text = ' More features and email support is available with <a href="https://products.equalizedigital.com/" target="_blank">Accessibility Checker Pro</a>.';
	}

	echo '<p>' . __( 'Use the settings below to configure Accessibility Checker. Additional information about each setting can be found in the <a href="https://a11ychecker.com/" target="_blank">plugin documentation</a>.'.$pro_text, 'edac' ) . '</p>';
}

/**
 * Render the text for the simplified summary section
 */
function edac_simplified_summary_cb(){
	echo '<p>' . __( 'Web Content Accessibility Guidelines (WCAG) at the AAA level require any content with a reading level above 9th grade to have an alternative that is easier to read. Simplified summary text is added on the readability tab in the Accessibility Checker meta box on each post\'s or page\'s edit screen. <a href="https://a11ychecker.com/help3265" target="_blank">Learn more about simplified summaries and readability requirements.', 'edac' ) . '</a></p>';
}

/**
 * Render the radio input field for position option
 */
function edac_simplified_summary_position_cb(){
	$position = get_option( 'edac_simplified_summary_position');
	?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position' ?>" id="<?php echo 'edac_simplified_summary_position' ?>" value="before" <?php checked( $position, 'before' ); ?>>
				<?php _e( 'Before the content', 'edac' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position' ?>" value="after" <?php checked( $position, 'after' ); ?>>
				<?php _e( 'After the content', 'edac' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position' ?>" value="none" <?php checked( $position, 'none' ); ?>>
				<?php _e( 'Insert manually', 'edac' ); ?>
			</label>
		</fieldset>
		<div id="ac-simplified-summary-option-code">
			<p>Use this function to manually add the simplified summary to your theme within the loop.</p>
			<kbd>edac_get_simplified_summary();</kbd>
			<p>The function optionally accepts the post ID as a parameter.<p>
			<kbd>edac_get_simplified_summary($post);</kbd>
		</div>
		<p class="edac-description"><?php echo __('Set where you would like simplified summaries to appear in relation to your content if filled in.','edac'); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 */
function edac_sanitize_simplified_summary_position( $position ) {
	if ( in_array( $position, array( 'before', 'after', 'none' ), true ) ) {
		return $position;
	}
}

/**
 * Render the radio input field for position option
 */
function edac_simplified_summary_prompt_cb(){
	$prompt = get_option( 'edac_simplified_summary_prompt');
	?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt' ?>" id="<?php echo 'edac_simplified_summary_prompt' ?>" value="when required" <?php checked( $prompt, 'when required' ); ?>>
				<?php _e( 'When Required', 'edac' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt' ?>" value="always" <?php checked( $prompt, 'always' ); ?>>
				<?php _e( 'Always', 'edac' ); ?>
			</label>
		</fieldset>
		<p class="edac-description"><?php echo __('Should Accessibility Checker only ask for a simplified summary when the reading level of your post or page is above 9th grade or always ask for it regardless of reading level?','edac'); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 */
function edac_sanitize_simplified_summary_prompt( $prompt ) {
	if ( in_array( $prompt, array( 'when required', 'always'), true ) ) {
		return $prompt;
	}
}

/**
 * Render the checkbox input field for post_types option
 */
function edac_post_types_cb(){

	$selected_post_types = get_option( 'edac_post_types') ?: [];
	$post_types = edac_post_types();
	$custom_post_types = edac_custom_post_types();
	$all_post_types = (is_array($post_types) && is_array($custom_post_types)) ? array_merge($post_types,$custom_post_types) : [];
	?>
		<fieldset>
			<?php
			if($all_post_types){
				foreach ($all_post_types as $post_type) {
					$disabled = in_array($post_type,$post_types) ?: 'disabled';
					?>
					<label>
						<input type="checkbox" name="<?php echo 'edac_post_types[]'; ?>" value="<?php echo $post_type; ?>" <?php checked( in_array( $post_type, $selected_post_types ), 1 ); echo $disabled; ?>>
						<?php _e( $post_type, 'edac' ); ?>
					</label>
					<br>
					<?php
				}
			}
			?>
			
		</fieldset>
		<p class="edac-description"><?php echo __('To check content other than posts and pages, please ','edac'); ?><a href="https://products.equalizedigital.com/" target="_blank"><?php echo __('upgrade to pro','edac'); ?></a>.</p>
	<?php

}

/**
 * Sanitize the post type value before being saved to database
 */
function edac_sanitize_post_types( $selected_post_types ) {

	$post_types = edac_post_types();

	if($selected_post_types){
		foreach ($selected_post_types as $key => $post_type) {
			if(!in_array($post_type, $post_types)){
				unset($selected_post_types[$key]);
			}
		}
	}
	return $selected_post_types;
}

/**
 * Render the fields for site authorization
 */
function edac_authorization_cb(){
	$password = get_option( 'edac_authorization_password');
	$username = get_option( 'edac_authorization_username');
	?>
		<fieldset>
			<label for="edac_authorization_username">Username</label>
			<input type="text" name="edac_authorization_username" id="edac_authorization_username" value="<?php echo $username; ?>">
			<label for="edac_authorization_password">Password</label>
			<input type="password" name="edac_authorization_password" id="edac_authorization_password" value="<?php echo $password; ?>">
		</fieldset>
		<p class="edac-description"><?php echo __('If your website is on a password protected URL such as a staging site, Accessibility Checker may need the username and password in order to scan your website.','edac'); ?></p>
	<?php
}

/**
 * Sanitize authorization values before being saved to database
 */
function edac_sanitize_authorization_username( $username ) {
	return sanitize_text_field($username);
}

/**
 * Sanitize authorization values before being saved to database
 */
function edac_sanitize_authorization_password( $password ) {
	return sanitize_text_field($password);
}