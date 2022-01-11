<div class="wrap">

	<div class="edac-welcome">
		<div class="edac-welcome-header">
			<div class="edac-welcome-header-right">
				<a href="https://equalizedigital.com/" target="_blank">
					<img src="<?php echo plugin_dir_url( __DIR__ ); ?>assets/images/edac-logo.png" alt="Link to Equalize Digital Website">
				</a>
			</div>
			<div class="edac-welcome-header-left">
				<h1 class="edac-welcome-title">
					<?php
					if(edac_check_plugin_active('accessibility-checker-pro/accessibility-checker-pro.php') == true && get_transient( 'edacp_license_valid' ) == true){
						$title = esc_html( get_admin_page_title() ).' Pro';
						$version = EDACP_VERSION;
					}else{
						$title = esc_html( get_admin_page_title() );
						$version = EDAC_VERSION;
					}
					
					echo $title.' - '.$version;
					?>
				</h1>
				<h2 class="edac-welcome-subtitle">WordPress Accessibility Auditing by <a href="https://equalizedigital.com/" target="_blank">Equalize Digital</a></h2>
			</div>
		</div>
		<div class="edac-welcome-section">
			<div class="edac-welcome-quick-start">
				<h3 class="edac-welcome-section-title">Quick Start Guide</h3>
				<p>Accessibility Checker is here to help you make your website more accessible, whether you're new to website accessibility or a seasoned pro. If you've installed the plugin for the first time, follow these steps to get started checking your content:</p>
				<ol>
					<li>Visit the <a href="<?php echo get_bloginfo('url'); ?>/wp-admin/admin.php?page=accessibility_checker_settings">Settings Page</a> to configure checking settings and simplified summary position.</li>
					<li>Go to the edit screen for the post or page that you would like to check; in the free version of Accessibility Checker, scan results are only visible on post and page edit screens.</li>
					<li>Find the Accessibility Checker meta box on your edit screen. It is typically located below your content. If you are using a front-end page builder, you will need to visit the backend edit screen to view Accessibility Checker results as they are not visible on the front end.</li>
					<li>If there are errors or warnings present on your page or post, open the details tab in Accessibility Checker to see more information about those errors or warnings and how to find them on the page.</li>
					<li>On the details tab, you can expand each item to see the code that triggered an error or warning. If you don't know what an error or warning means, click the "i" icon to link over to our documentation for additional information about the problem and how to fix it.</li>
					<li>After you have fixed or ignored each open error or warning, update your page or post to see the accessibility report change. Your goal is to get every page to say 100% Passed Tests.</li>
				</ol>
				<p>Watch the video if you would like to see an overview of how to get started with Accessibility Checker.</p>
			</div>
			<div class="edac-welcome-video">
				<div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/486599947" style="position:absolute;top:0;left:0;width:100%;height:100%;" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>
				<p><a href="https://equalizedigital.com/accessibility-checker/getting-started-quick-guide/#vidtranscript">Read Video Transcript</a></p>
			</div>
		</div>
		<div class="edac-welcome-section edac-welcome-section-documentation-support <?php if(get_transient( 'edacp_license_valid' ) == false) echo 'edac-show-pro-callout'; ?>">
			<?php if(get_transient( 'edacp_license_valid' ) == false){ ?>
				<div class="edac-welcome-pro-callout">
					<?php include('pro-callout.php'); ?>
				</div>
			<?php } ?>
			<div class="edac-welcome-documentation-support">
				<h3 class="edac-welcome-section-title">Documentation and FAQs</h3>
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

				<div class="edac-welcome-section-divider"></div>

				<h3 class="edac-welcome-section-title">Support Information</h3>
				<p>We provide support for the free version of Accessibility Checker via the WordPress.org support forum. You will need to create a WordPress.org account and then can open a new support thread.</p>
				<p><a class="button" href="https://wordpress.org/support/plugin/accessibility-checker/" target="_blank">Go to Support Forum</a></p>
				<p>If you would like additional support, there are two options for receiving more personalized support:</p>
				<ol>
					<li><strong>Purchase Accessibility Checker Pro:</strong> Accessibility Checker Pro includes a number of additional features to support auditing your full website and includes personalized email-based support on plugin features and usage. Learn more about <a href="https://my.equalizedigital.com/" target="_blank">Accessibility Checker Pro</a>.</li>
					<li><strong>Purchase a Priority Support Plan:</strong> Our priority support packages give you direct access to expert accessibility specialists and developers ready to help you resolve the accessibility errors and warnings on your website. Priority support plans include email, phone, and Zoom support, as desired. Priority support goes beyond plugin usage and can include consulting or custom coding to resolve accessibility problems on your website. Learn more about <a href="https://my.equalizedigital.com/priority-support/" target="_blank">Priority Support Plans</a>.</li>
				</ol>
			</div>
		</div>
	</div>

</div>