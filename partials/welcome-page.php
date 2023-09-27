<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

?>

<div class="wrap edac-welcome-container">

	<div class="edac-cols">
	<div class="edac-cols-left edac-welcome">
		<div class="edac-welcome-header">
			<div class="edac-welcome-header-left">
				<h1 class="edac-welcome-title">
					<?php
					if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) === true && EDAC_KEY_VALID === true ) {
						$welcome_title = __( 'Accessibility Checker Pro', 'accessibility-checker' );
						$version       = EDACP_VERSION;
					} else {
						$welcome_title = __( 'Accessibility Checker', 'accessibility-checker' );
						$version       = EDAC_VERSION;
					}

					echo esc_html( $welcome_title );
					?>
				</h1>
				<p>
					<?php 
					echo( esc_html__( 'version ', 'accessibility-checker' ) . esc_html( $version ) );
					?>
				</p>
			</div>

		<div class="edac-welcome-header-right">
				<a href="https://equalizedigital.com/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" target="_blank">
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>assets/images/Accessibility Checker logo transparent bg.svg" alt="<?php _e( 'Link to Equalize Digital Website', 'accessibility-checker' ); ?>">
				</a>
			</div>
		</div>

		<?php \EDAC\Welcome_Page::render_summary(); ?>

		<section class="edac-welcome-section">
			<div class="edac-welcome-quick-start">
				<h2><?php _e( 'Quick Start Guide', 'accessibility-checker' ); ?></h2>
				<p><?php _e( 'Follow these steps to get started checking your content:', 'accessibility-checker' ); ?></p>
				<ol>
					<li>
						<?php 
						// translators: %s: path to settings page.
						printf( __( 'On the <a href="%s">Settings Page</a>, choose which post types you want to scan.', 'accessibility-checker' ), esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ) ); 
						?>
					</li>
					<li><?php _e( 'Go to the edit screen for the post you want to check.', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'Find the Accessibility Checker meta box below your content. If using a front-end page builder, you must visit the backend edit screen to view Accessibility Checker results.', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'If errors or warnings are present on your post, open the details tab in Accessibility Checker for more information.', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'Expand each issue to see the code, or click "view on page" if you need help finding the element that needs fixing.', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'If you don\'t know what an error or warning means, click the "i" icon to read the documentation and how to fix it.', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'If an issue is a false positive and the element is accessible, you can remove issues from reports with the "Ignore" feature.', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'After fixing each issue, update the post to see the accessibility report change. Your goal is to get every page to say 100% Passed Tests.', 'accessibility-checker' ); ?></li>
				</ol>
				<p>
					<a href="https://equalizedigital.com/accessibility-checker/getting-started-quick-guide/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page#demo">
						<?php esc_html_e( 'Watch a video of Accessibility Checker in use.', 'accessibility-checker' ); ?>
					</a>
				</p>
			</div>

			<div class="edac-welcome-documentation">
				<h2><?php _e( 'Documentation and FAQs', 'accessibility-checker' ); ?></h2>
				<ul>
					<li><a href="https://a11ychecker.com/help4279" target="_blank"><?php _e( 'Why do we say 100% Passed Tests, Not 100% Accessible?', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></li>
					<li><a href="https://a11ychecker.com/help4280" target="_blank"><?php _e( 'How to Manually Check Your Website for Accessibility', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></li>
					<li><a href="https://a11ychecker.com/help4206" target="_blank"><?php _e( 'When to Ignore Accessibility Errors', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></li>
					<li><a href="https://a11ychecker.com/help4114" target="_blank"><?php _e( 'What to do if a Plugin You’re Using has Accessibility Errors', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></li>
					<li><a href="https://a11ychecker.com/help4386" target="_blank"><?php _e( 'What to do if there are Accessibility Errors in Your Theme', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></li>
					<li><a href="https://a11ychecker.com/help4293" target="_blank"><?php _e( 'Can I Hire Equalize Digital to Fix Accessibility Issues on My Website?', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></li>
					<li><a href="https://a11ychecker.com/help4285" target="_blank"><?php _e( 'Additional Resources for Learning About Accessibility', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></li>
				</ul>
				<p><a class="button" href="https://a11ychecker.com/" target="_blank"><?php _e( 'Read Full Documentation', 'accessibility-checker' ); ?> <span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span></a></p>
			</div>
		</section>

		<section class="edac-support-section">
			<h2><?php _e( 'Support Information', 'accessibility-checker' ); ?></h2>
		
			<div class="edac-flex-container">
		<?php
		if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) && EDAC_KEY_VALID ) {
			?>
				<div class="edac-flex-item edac-flex-item-33 edac-background-light">
					<h3><?php _e( 'Plugin Support', 'accessibility-checker' ); ?></h3>
					<p>
						<?php _e( 'Active license holders of paid Accessibility Checker plans get unlimited email support on plugin usage and troubleshooting.', 'accessibility-checker' ); ?>
					</p>
					<p>
						<a href="https://my.equalizedigital.com/support/pro-support/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" class="button"><?php _e( 'Open Support Ticket', 'accessibility-checker' ); ?></a>
					</p>
				</div>
			<?php
		} else {
			?>
				<div class="edac-flex-item edac-flex-item-33 edac-background-light">
					<h3><?php _e( 'Free Plugin Support', 'accessibility-checker' ); ?></h3>
					<p>
						<?php _e( 'Free plugin support is available via the WordPress.org forums. You\'ll need to create an account then you can open a new support thread.', 'accessibility-checker' ); ?>
					</p>
					<p>
						<a href="https://wordpress.org/support/plugin/accessibility-checker/" class="button"><?php _e( 'Go to Support Forum', 'accessibility-checker' ); ?></a>
					</p>
				</div>
			<?php
		}
		?>
			
				<div class="edac-flex-item edac-flex-item-33 edac-background-light">
					<h3><?php _e( 'Office Hours', 'accessibility-checker' ); ?></h3>
					<p>
						<?php _e( 'Open Q&A on Zoom every other week to help you remediate your website.', 'accessibility-checker' ); ?>
						<a href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page">
						<?php _e( 'Included in Small Business and Agency plans', 'accessibility-checker' ); ?></a>.
					</p>
					<p>
						<a href="https://my.equalizedigital.com/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" class="button">
						<?php _e( 'Register for Office Hours', 'accessibility-checker' ); ?></a>
					</p>
				</div>

				<div class="edac-flex-item edac-flex-item-33 edac-background-light">
					<h3><?php _e( 'Auditing and Remediation', 'accessibility-checker' ); ?></h3>
					<p>
						<?php _e( 'Get help making your website accessible. Expert auditing, user testing, and dev support. Conformance letters available.', 'accessibility-checker' ); ?>
					</p>
					<p>
						<a href="https://equalizedigital.com/services/website-accessibility-remediation/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" class="button">
						<?php _e( 'Get Remediation Help', 'accessibility-checker' ); ?></a>
					</p>
				</div>
	
			</div>		
	</section>

	</div>

	<?php
	if ( ! edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) || ! EDAC_KEY_VALID ) {
		echo '<div class="edac-cols-right edac-welcome-aside">
			<div class="edac-has-cta">';
	} else {
		echo '<div class="edac-cols-right edac-welcome-aside">
			<div>';
	}

	if ( ! edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) || ! EDAC_KEY_VALID ) {
		?>
		<div class="edac-pro-callout edac-mt-3 edac-mb-3">
			<img class="edac-pro-callout-icon" src="<?php echo esc_url( EDAC_PLUGIN_URL ); ?>assets/images/edac-emblem.png" alt="<?php _e( 'Equalize Digital Logo', 'accessibility-checker' ); ?>">
			<h4 class="edac-pro-callout-title"><?php _e( 'Upgrade to Accessibility Checker Pro', 'accessibility-checker' ); ?></h4>
			<div>
				<ul class="edac-pro-callout-list">
					<li><?php _e( 'Scan all post types', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'Admin columns to see accessibility status at a glance', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'Centralized list of all open issues', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'Ignore log', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'Rename simplified summary', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'User restrictions on ignoring issues', 'accessibility-checker' ); ?></li>
					<li><?php _e( 'Email support', 'accessibility-checker' ); ?></li>
					<li><?php _e( '...and more', 'accessibility-checker' ); ?></li>
				</ul>
			</div>
			<a class="edac-pro-callout-button" href="https://equalizedigital.com/accessibility-checker/pricing/" target="_blank">
				<?php _e( 'Get Accessibility Checker Pro', 'accessibility-checker' ); ?> 
				<span class="screen-reader-text"><?php _e( '(opens in a new window)', 'accessibility-checker' ); ?></span>
			</a>
		<?php	
		if ( edac_check_plugin_installed( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ) {
			?>
			<br /><a class="edac-pro-callout-activate" href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=license' ) ); ?>">
				<?php _e( 'Or activate your license key here.', 'accessibility-checker' ); ?>
			</a>
			<?php
		}
		?>
		</div>
		<?php
	}


	?>

		<div class="edac-panel">
			<h2 class="edac-summary-header">
				<?php _e( 'Learn Accessibility', 'accessibility-checker' ); ?>
			</h2>
			<?php
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo edac_get_upcoming_meetups_html( 'wordpress-accessibility-meetup-group', 2 );
			?>
		</div>

		<?php 
		
		
		if ( true !== boolval( get_user_meta( get_current_user_id(), 'edac_email_optin', true ) ) ) {
			\EDAC\Welcome_Page::render_email_opt_in( 
				'1273a5c7a',
				'1a0796bc2303c2cb', 
				'', 
				'3zdf37c20714225fe975e2772c61e00bf3a196e8e7f12fdcb55c14b48b8778764e' 
			); 
		}
		?>
	</div>
</div>

	</div>
</div>
