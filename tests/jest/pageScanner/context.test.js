
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
		document.body.className = '';
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

	test( 'should exclude Elementor editor UI but still scan widget content', async ( ) => {
		// Markup modelled on what Elementor injects into its live-preview iframe.
		document.body.className = 'elementor-editor-active';
		document.body.innerHTML = `
			<div class="elementor-element elementor-widget elementor-widget-button">
				<div class="elementor-element-overlay">
					<ul class="elementor-editor-element-settings">
						<li class="elementor-editor-element-setting elementor-editor-element-edit"><i id="el-handle" role="button"></i></li>
					</ul>
				</div>
				<div class="elementor-widget-container">
					<button id="widget-button"></button>
				</div>
			</div>
			<div class="elementor-add-section elementor-add-section-inline">
				<button id="el-add-inline-button"></button>
			</div>
			<div id="elementor-add-new-section" class="elementor-add-section">
				<button id="el-add-new-button"></button>
			</div>
			<div class="elementor-first-add"><button id="el-first-add-button"></button></div>
			<div class="elementor-empty-view"><button id="el-empty-view-button"></button></div>
			<div class="elementor-sortable-placeholder"><button id="el-placeholder-button"></button></div>
			<div class="elementor-document-handle"><button id="el-document-handle-button"></button></div>
			<div class="pen-menu"><button id="el-pen-menu-button"></button></div>
		`;

		const results = await axe.run( { exclude: exclusionsArray }, {
			runOnly: [ 'button-name' ],
		} );

		const violationHTML = results.violations
			.flatMap( ( violation ) => violation.nodes )
			.map( ( node ) => node.html );

		// Real page content inside an Elementor widget is still scanned.
		expect( violationHTML.some( ( html ) => html.includes( 'id="widget-button"' ) ) ).toBe( true );

		[
			'el-handle',
			'el-add-inline-button',
			'el-add-new-button',
			'el-first-add-button',
			'el-empty-view-button',
			'el-placeholder-button',
			'el-document-handle-button',
			'el-pen-menu-button',
		].forEach( ( id ) => {
			expect( violationHTML.some( ( html ) => html.includes( `id="${ id }"` ) ) ).toBe( false );
		} );
	} );

	test( 'should still scan a generic .pen-menu outside the Elementor editor', async ( ) => {
		document.body.innerHTML = `
			<div class="pen-menu"><button id="pen-menu-button"></button></div>
		`;

		const results = await axe.run( { exclude: exclusionsArray }, {
			runOnly: [ 'button-name' ],
		} );

		const violationHTML = results.violations
			.flatMap( ( violation ) => violation.nodes )
			.map( ( node ) => node.html );

		expect( violationHTML.some( ( html ) => html.includes( 'id="pen-menu-button"' ) ) ).toBe( true );
	} );
} );
