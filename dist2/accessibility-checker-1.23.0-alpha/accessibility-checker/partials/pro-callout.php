<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

?>
<div class="edac-pro-callout">
	<img
		class="edac-pro-callout-icon"
		src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>assets/images/edac-emblem.png"
		alt="<?php esc_attr_e( 'Equalize Digital Logo', 'accessibility-checker' ); ?>"
	>
	<h4 class="edac-pro-callout-title">
		<?php esc_html_e( 'Upgrade to Accessibility Checker Pro', 'accessibility-checker' ); ?>
	</h4>
	<div>
		<ul class="edac-pro-callout-list">
			<li><?php esc_html_e( 'Scan all post types', 'accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'Admin columns to see accessibility status at a glance', 'accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'Centralized list of all open issues', 'accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'Ignore log', 'accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'Rename simplified summary', 'accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'User restrictions on ignoring issues', 'accessibility-checker' ); ?></li>
			<li><?php esc_html_e( 'Email support', 'accessibility-checker' ); ?></li>
			<li><?php esc_html_e( '...and more', 'accessibility-checker' ); ?></li>
		</ul>
	</div>
	<div class="edac-pro-callout-button--wrapper">
		<a
			class="edac-pro-callout-button"
			href="https://equalizedigital.com/accessibility-checker/pricing/"
			target="_blank"
		>
			<?php esc_html_e( 'Get Accessibility Checker Pro', 'accessibility-checker' ); ?>
		</a>
	</div>

	<?php if ( is_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ) : ?>
		<br />
		<a
			class="edac-pro-callout-activate"
			href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=license' ) ); ?>"
		>
			<?php esc_html_e( 'Or activate your license key here.', 'accessibility-checker' ); ?>
		</a>
	<?php endif; ?>
</div>


