
/**
 * Test for scanner context exclusions
 *
 * This test verifies that elements with the selectors in the exclude array
 * are properly excluded from accessibility scans, including Query Monitor's
 * fallback output.
 */
import axe from 'axe-core';
import { exclusionsArray } from '../../../src/pageScanner/config/exclusions';

describe( 'Scanner Context Exclusions', ( ) => {
	beforeEach( ( ) => {
		// Reset the DOM before each test
		document.body.innerHTML = '';
	} );

	test( 'should exclude configured containers from scan', async ( ) => {
		// Create test HTML with empty buttons (accessibility violation)
		// Some in regular content, others in containers that should be excluded
		document.body.innerHTML = `
			<!-- Control button that should be scanned -->
			<button id="control-button"></button>

			<!-- Containers that should be excluded from scan -->
			<div id="qm-icon-container">
				<button id="qm-icon-button"></button>
			</div>
			<div id="wpadminbar">
				<button id="wpadminbar-button"></button>
			</div>
			<div id="query-monitor-main">
				<button id="query-monitor-button"></button>
			</div>
			<div id="query-monitor-container">
				<button id="query-monitor-container-button"></button>
			</div>
			<div id="edac-highlight-panel">
				<button id="edac-panel-button"></button>
			</div>

			<!-- Query Monitor fallback output, as rendered on WP VIP -->
			<div id="query-monitor-fallbacks">
				<div class="qm-panel-container" id="qm-alloptions">
					<button id="query-monitor-fallback-button"></button>
				</div>
			</div>

			<!-- A panel container on its own, covering the class selector
			     independently of the wrapper it usually sits inside -->
			<div class="qm-panel-container" id="qm-db_queries-container">
				<button id="query-monitor-panel-button"></button>
			</div>
		`;

		// Define context with exclude list matching src/pageScanner/index.js
		const context = {
			exclude: exclusionsArray,
		};

		// Run axe with button-name rule to detect empty buttons
		const results = await axe.run( context, {
			runOnly: [ 'button-name' ],
		} );

		// Get all HTML from the violation nodes
		const violationHTML = results.violations
			.flatMap( ( violation ) => violation.nodes )
			.map( ( node ) => node.html );

		// Control button should appear in violations
		expect( violationHTML.some( ( html ) => html.includes( 'id="control-button"' ) ) ).toBe( true );

		// Buttons in excluded containers should not appear in violations
		expect( violationHTML.some( ( html ) => html.includes( 'id="qm-icon-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="wpadminbar-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="query-monitor-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="query-monitor-container-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="query-monitor-fallback-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="query-monitor-panel-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="edac-panel-button"' ) ) ).toBe( false );
	} );
} );
