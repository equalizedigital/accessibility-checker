<?php
/**
 * Accessibility Checker scan page partial.
 *
 * @package Accessibility_Checker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap">
	<h1><?php echo esc_html__( 'Scan This Page', 'accessibility-checker' ); ?></h1>
	
	<div class="edac-scan-page-container">
		<h2><?php echo esc_html__( 'Page Accessibility Scanner', 'accessibility-checker' ); ?></h2>
		<p><?php echo esc_html__( 'This page will scan the current admin page for accessibility issues. The results will be displayed in the browser console.', 'accessibility-checker' ); ?></p>
		
		<div id="edac-scan-results">
			<p><strong><?php echo esc_html__( 'Scan Status:', 'accessibility-checker' ); ?></strong> <span id="edac-scan-status"><?php echo esc_html__( 'Initializing...', 'accessibility-checker' ); ?></span></p>
		</div>
		
		<button type="button" id="edac-run-scan" class="button button-primary" disabled>
			<?php echo esc_html__( 'Run Scan Again', 'accessibility-checker' ); ?>
		</button>
		
		<div class="edac-scan-instructions">
			<h3><?php echo esc_html__( 'Instructions', 'accessibility-checker' ); ?></h3>
			<ol>
				<li><?php echo esc_html__( 'Open your browser\'s developer console (F12 or right-click → Inspect → Console tab)', 'accessibility-checker' ); ?></li>
				<li><?php echo esc_html__( 'The accessibility scan will run automatically when this page loads', 'accessibility-checker' ); ?></li>
				<li><?php echo esc_html__( 'Check the console for scan results and any violations found', 'accessibility-checker' ); ?></li>
				<li><?php echo esc_html__( 'You can run the scan again by clicking the "Run Scan Again" button above', 'accessibility-checker' ); ?></li>
			</ol>
		</div>
	</div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	const statusElement = document.getElementById('edac-scan-status');
	const runScanButton = document.getElementById('edac-run-scan');
	
	function updateStatus(message) {
		if (statusElement) {
			statusElement.textContent = message;
		}
	}
	
	function runAccessibilityScan() {
		updateStatus('<?php echo esc_js( __( 'Running scan...', 'accessibility-checker' ) ); ?>');
		
		if (typeof window.runAccessibilityScan === 'function') {
			console.log('Starting accessibility scan...');
			
			window.runAccessibilityScan({
				onComplete: function(result, error) {
					if (error) {
						console.error('Accessibility scan error:', error);
						updateStatus('<?php echo esc_js( __( 'Scan failed. Check console for details.', 'accessibility-checker' ) ); ?>');
						return;
					}
					
					console.log('Accessibility scan completed:', result);
					
					if (result && result.violations) {
						console.log(`Found ${result.violations.length} accessibility violations:`);
						result.violations.forEach((violation, index) => {
							console.log(`${index + 1}. ${violation.ruleId} (${violation.impact}):`);
							console.log('   Selector:', violation.selector);
							console.log('   HTML:', violation.html);
							if (violation.landmark) {
								console.log('   Landmark:', violation.landmark);
							}
						});
						updateStatus(`<?php echo esc_js( __( 'Scan completed. Found', 'accessibility-checker' ) ); ?> ${result.violations.length} <?php echo esc_js( __( 'violations. Check console for details.', 'accessibility-checker' ) ); ?>`);
					} else {
						console.log('No accessibility violations found!');
						updateStatus('<?php echo esc_js( __( 'Scan completed. No violations found!', 'accessibility-checker' ) ); ?>');
					}
					
					if (runScanButton) {
						runScanButton.disabled = false;
					}
				}
			});
		} else {
			console.error('runAccessibilityScan function not available. The pageScanner bundle may not be loaded.');
			updateStatus('<?php echo esc_js( __( 'Scanner not available. Bundle may not be loaded.', 'accessibility-checker' ) ); ?>');
		}
	}
	
	// Run scan automatically when page loads
	setTimeout(runAccessibilityScan, 1000);
	
	// Allow manual re-scan
	if (runScanButton) {
		runScanButton.addEventListener('click', function() {
			runScanButton.disabled = true;
			runAccessibilityScan();
		});
	}
});
</script>