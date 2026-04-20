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
<div id="edac-rules-page" class="wrap edac-settings <?php echo esc_attr( edac_is_pro() ? '' : 'pro-callout-wrapper' ); ?>">
	<div class="edac-settings-general <?php echo esc_attr( edac_is_pro() ? '' : 'edac-show-pro-callout' ); ?>">
		<form action="options.php" method="post">
			<?php
			settings_fields( 'edac_settings_rules' );
			do_settings_sections( 'edac_settings_rules' );
			submit_button();
			?>
		</form>
		<?php if ( ! edac_is_pro() ) { ?>
			<div><?php include EDAC_PLUGIN_DIR . 'partials/pro-callout.php'; ?></div>
		<?php } ?>
	</div>
</div>
