<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

?>

<div class="wrap">

	<div class="edac-cols">
	<div class="edac-welcome edac-cols-left">
		<div class="edac-welcome-header">
			<div class="edac-welcome-header-right">
				<a href="https://equalizedigital.com/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" target="_blank">
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>assets/images/Accessibility Checker logo transparent bg.svg" alt="Link to Equalize Digital Website">
				</a>
			</div>
			<div class="edac-welcome-header-left">
				<h1 class="edac-welcome-title">
					<?php
					if ( edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) === true && EDAC_KEY_VALID === true ) {
						$welcome_title = 'Accessibility Checker Pro';
						$version       = EDACP_VERSION;
					} else {
						$welcome_title = 'Accessibility Checker';
						$version       = EDAC_VERSION;
					}

					echo $welcome_title;
		
					?>
				</h1>
				<p>
					<?php 
					echo 'version ' . $version;
					?>
				</p>			
			</div>
		</div>


		<?php \EDAC\Welcome_Page::render_summary(); ?>


		<section class="edac-welcome-section">
			<div class="edac-welcome-quick-start">
				<h2>Quick Start Guide</h2>
				<p>Follow these steps to get started checking your content:</p>
				<ol>
					<li>On the <a href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ); ?>">Settings Page</a>, choose which post types you want to scan.</li>
					<li>Go to the edit screen for the post you want to check.</li>
					<li>Find the Accessibility Checker meta box below your content. If using a front-end page builder, you must visit the backend edit screen to view Accessibility Checker results.</li>
					<li>If errors or warnings are present on your post, open the details tab in Accessibility Checker for more information.</li>
					<li>Expand each issue to see the code, or click "view on page" if you need help finding the element that needs fixing.</li>
					<li>If you don't know what an error or warning means, click the "i" icon to read the documentation and how to fix it.</li>
					<li>If an issue is a false positive and the element is accessible, you can remove issues from reports with the "Ignore" feature.</li>
					<li>After fixing each issue, update the post to see the accessibility report change. Your goal is to get every page to say 100% Passed Tests.</li>
				</ol>
				<p>
					<a href="https://equalizedigital.com/accessibility-checker/getting-started-quick-guide/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page#demo">Watch a video of Accessibility Checker in use.</a>
				</p>
			</div>

			<div class="edac-welcome-documentation">
				<h2>Documentation and FAQs</h2>
				<p>We’ve done our best to create helpful articles that address frequently asked questions and provide the information you need to make your website accessible, faster. If you're just getting started, you may want to review these articles:</p>
				<ul>
					<li><a href="https://a11ychecker.com/help4279" target="_blank">Why do we say 100% Passed Tests, Not 100% Accessible?</a></li>
					<li><a href="https://a11ychecker.com/help4280" target="_blank">How to Manually Check Your Website for Accessibility</a></li>
					<li><a href="https://a11ychecker.com/help4206" target="_blank">When to Ignore Accessibility Errors</a></li>
					<li><a href="https://a11ychecker.com/help4114" target="_blank">What to do if a Plugin You’re Using has Accessibility Errors</a></li>
					<li><a href="https://a11ychecker.com/help4386" target="_blank">What to do if there are Accessibility Errors in Your Theme</a></li>
					<li><a href="https://a11ychecker.com/help4293" target="_blank">Can I Hire Equalize Digital to Fix Accessibility Issues on My Website?</a></li>
					<li><a href="https://a11ychecker.com/help4285" target="_blank">Additional Resources for Learning About Accessibility</a></li>
				</ul>
				<p><a class="button" href="https://a11ychecker.com/" target="_blank">Read Full Documentation</a></p>
			</div>
		</section>

		<section class="edac-support-section">
			<h2>Support Information</h2>
		
			<div class="edac-flex-container">
		<?php
		if ( ! edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) || ! EDAC_KEY_VALID ) {
			?>
				<div class="edac-flex-item edac-flex-item-33 edac-background-white edac-dark-border">
					<h3>Plugin Support</h3>
					<p>
						Active license holders of paid Accessibility Checker plans
						get unlimited email support on plugin usage and troubleshooting.
					</p>
					<p>
						<a href="https://my.equalizedigital.com/support/pro-support/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" class="button">Open Support Ticket</a>
					</p>
				</div>	
			<?php
		} else {
			?>
				<div class="edac-flex-item edac-flex-item-33 edac-background-light">
					<h3>Free Plugin Support</h3>
					<p>
						Free plugin support is available via the WordPress.org forums. You'll need to create an account
						then you can open a new support thread.
					</p>
					<p>
						<a href="https://wordpress.org/support/plugin/accessibility-checker/" class="button">Go to Support Forum</a>
					</p>
				</div>	
			<?php
		}
		?>
			
				<div class="edac-flex-item edac-flex-item-33 edac-background-light">
					<h3>Office Hours</h3>
					<p>
						Open Q&A on Zoom every other week to help you remediate your website.
						<a href="https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page">Included in Small Business and Agency plans</a>.
					</p>
					<p>
						<a href="https://my.equalizedigital.com/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" class="button">Register for Office Hours</a>
					</p>
				</div>	
			
				<div class="edac-flex-item edac-flex-item-33 edac-background-light">
					<h3>Auditing and Remediation</h3>
					<p>
						Get help making your website accessible. Expert auditing, user testing,
						and dev support. Conformance letters available.
					</p>
					<p>
						<a href="https://equalizedigital.com/services/website-accessibility-remediation/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=welcome-page" class="button">Get Remediation Help</a>
					</p>
				</div>	
			</div>		
	</section>

	</div>

	<?php
	if ( ! edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) || ! EDAC_KEY_VALID ) {
		echo '<div class="edac-cols-right">
			<div class="edac-has-cta">';
	} else {
		echo '<div class="edac-cols-right">
			<div>';
	}

	if ( ! edac_check_plugin_active( 'accessibility-checker-pro/accessibility-checker-pro.php' ) || ! EDAC_KEY_VALID ) {
		?>
		<div class="edac-pro-callout edac-mt-3 edac-mb-3">
			<img class="edac-pro-callout-icon" src="<?php echo esc_url( EDAC_PLUGIN_URL ); ?>assets/images/edac-emblem.png" alt="Equalize Digital Logo">
			<h4 class="edac-pro-callout-title">Upgrade to Accessibility Checker Pro</h4>
			<div>
				<ul class="edac-pro-callout-list">
					<li>Scan all post types</li>
					<li>Admin columns to see accessibility status at a glance</li>
					<li>Centralized list of all open issues</li>
					<li>Ignore log</li>
					<li>Rename simplified summary</li>
					<li>User restrictions on ignoring issues</li>
					<li>Email support</li>
					<li>...and more</li>
				</ul>
			</div>
			<a class="edac-pro-callout-button" href="https://equalizedigital.com/accessibility-checker/pricing/" target="_blank">Get Accessibility Checker Pro</a>';
		<?php	
		if ( edac_check_plugin_installed( 'accessibility-checker-pro/accessibility-checker-pro.php' ) ) {
			?>
			<br /><a class="edac-pro-callout-activate" href="<?php echo esc_url( admin_url( 'admin.php?page=accessibility_checker_settings&tab=license' ) ); ?>">Or activate your license key here.</a>
			<?php
		}
		?>
		</div>
		<?php
	}


	?>

		<div class="edac-panel">
			<h2 class="edac-summary-header">
				Learn Accessibility
			</h2>
			<?php
			echo edac_get_upcoming_meetups_html( 'wordpress-accessibility-meetup-group', 2 );
			?>
		</div>
	</div>
</div>

	</div>
</div>
