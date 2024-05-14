<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

?>
<div id="edac-tabs">
	<p id="edac-tabs-label" class="screen-reader-text"><?php esc_html_e( 'Accessibility Checker issues panels', 'accessibility-checker' ); ?></p>
	<ul class="edac-tabs" role="tablist" aria-labelledby="edac-tabs-label">
		<li class="edac-tab">
			<button
				role="tab"
				aria-selected="true"
				aria-controls="edac-summary-panel"
				id="edac-summary-tab"
				class="active"
			>
				<?php esc_html_e( 'Summary', 'accessibility-checker' ); ?>
			</button>
		</li>
		<li class="edac-tab">
			<button
				role="tab"
				aria-selected="false"
				aria-controls="edac-details-panel"
				id="edac-details-tab"
			>
				<?php esc_html_e( 'Details', 'accessibility-checker' ); ?>
			</button>
		</li>
		<li class="edac-tab">
			<button
				role="tab"
				aria-selected="false"
				aria-controls="edac-readability-panel"
				id="edac-readability-tab"
			>
				<?php esc_html_e( 'Readability', 'accessibility-checker' ); ?>
			</button>
		</li>
	</ul>
	<div
		role="tabpanel"
		aria-labelledby="edac-summary-tab"
		id="edac-summary-panel"
		class="edac-panel edac-summary"
	></div>
	<div
		role="tabpanel"
		aria-labelledby="edac-details-tab"
		id="edac-details-panel"
		class="edac-panel edac-details"
		style="display: none;"
	></div>
	<div
		role="tabpanel"
		aria-labelledby="edac-readability-tab"
		id="edac-readability-panel"
		class="edac-panel edac-readability"
		style="display: none;"
	></div>
</div>
