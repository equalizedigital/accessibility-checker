<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

?>
<div id="edac_editor_app"></div>

<div id="edac-tabs">
	<ul class="edac-tabs">
		<li class="edac-tab"><a role="button" aria-current="true" class="edac-tab-summary active" href="#edac-summary"><?php esc_html_e( 'Summary', 'accessibility-checker' ); ?></a></li>
		<li class="edac-tab"><a role="button" class="edac-tab-details" href="#edac-details"><?php esc_html_e( 'Details', 'accessibility-checker' ); ?></a></li>
		<li class="edac-tab"><a role="button" class="edac-tab-readability" href="#edac-readability"><?php esc_html_e( 'Readability', 'accessibility-checker' ); ?></a></li>
	</ul>
	<div class="edac-panel edac-summary" id="edac-summary"></div>
	<div class="edac-panel edac-details hidden" id="edac-details"></div>
	<div class="edac-panel edac-readability hidden" id="edac-readability"></div>
</div>

