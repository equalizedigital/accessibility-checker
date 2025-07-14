<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddNewWindowWarningFix;

?>
<div id="edac-fixes-page" class="wrap edac-settings <?php echo EDAC_KEY_VALID ? '' : 'pro-callout-wrapper'; ?>">
	<div class="edac-settings-general <?php echo EDAC_KEY_VALID ? '' : 'edac-show-pro-callout'; ?>">
		<form action="options.php" method="post">
			<?php
				settings_fields( 'edac_settings_dashboard' );
				do_settings_sections( 'edac_settings_dashboard' );
				submit_button();
			?>
		</form>
		<?php if ( EDAC_KEY_VALID === false ) { ?>
			<div><?php include EDAC_PLUGIN_DIR . 'partials/pro-callout.php'; ?></div>
		<?php } ?>
	</div>
</div>
