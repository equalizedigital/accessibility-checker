<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="edac-rules-page" class="wrap edac-settings <?php echo EDAC_KEY_VALID ? '' : 'pro-callout-wrapper'; ?>">
	<div class="edac-settings-general <?php echo EDAC_KEY_VALID ? '' : 'edac-show-pro-callout'; ?>">
		<form action="options.php" method="post">
			<?php
			settings_fields( 'edac_settings_rules' );
			do_settings_sections( 'edac_settings_rules' );
			submit_button();
			?>
		</form>
		<?php if ( EDAC_KEY_VALID === false ) { ?>
			<div><?php include EDAC_PLUGIN_DIR . 'partials/pro-callout.php'; ?></div>
		<?php } ?>
	</div>
</div>
