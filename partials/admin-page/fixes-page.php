<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

?>
<div class="wrap edac-settings <?php echo EDAC_KEY_VALID ? '' : 'pro-callout-wrapper'; ?>">
	<h1><?php esc_html_e( 'Accessibility Fixes', 'accessibility-checker' ); ?></h1>

	<div class="tab-content">
		<div class="edac-settings-general <?php echo EDAC_KEY_VALID ? '' : 'edac-show-pro-callout'; ?>">
			<form action="options.php" method="post">
				<?php
				settings_fields( 'edac_settings_fixes' );
				do_settings_sections( 'edac_settings_fixes' );
				submit_button();
				?>
			</form>
			<?php if ( EDAC_KEY_VALID === false ) { ?>
				<div><?php include EDAC_PLUGIN_DIR . 'partials/pro-callout.php'; ?></div>
			<?php } ?>
		</div>
	</div>
</div>
