<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

	use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddNewWindowWarningFix;

?>
<div id="edac-fixes-page" class="wrap edac-settings <?php echo EDAC_KEY_VALID ? '' : 'pro-callout-wrapper'; ?>">
	<?php if ( get_transient( 'edac_fixes_settings_saved' ) ) : ?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( "Settings Saved. To confirm and reflect these updates in your site's statistics, please rescan your site. If you are using a caching plugin, make sure to clear the cache to ensure the updated fixes are applied correctly.", 'accessibility-checker' ); ?></p>
		</div>
		<?php
		delete_transient( 'edac_fixes_settings_saved' );
	endif;
	?>
	<?php if ( class_exists( '\ANWW' ) && defined( 'ANWW_VERSION' ) && get_option( 'edac_fix_' . AddNewWindowWarningFix::get_slug() ) ) : ?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'The Accessibility New Window Warnings plugin is installed. The fix for links opening in a new window included here will override its functionality. You can safely remove the plugin and use these fixes instead.', 'accessibility-checker' ); ?></p>
		</div>
	<?php endif; ?>
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
