/**
 * Tests for the scan context used by the page scanner.
 *
 * Elementor renders an edited page inside its edit area, with the theme
 * template around it and Elementor's editor UI inside it, so the scan has to
 * be scoped to the edit area to keep results to the page itself.
 */
import axe from 'axe-core';
import { buildScanContext, EDIT_AREA_SELECTOR, EDITOR_UI_EXCLUSIONS } from '../../../src/pageScanner/helpers/scanContext';
import { exclusionsArray } from '../../../src/pageScanner/config/exclusions';

/**
 * Markup with theme chrome either side of an Elementor page area.
 *
 * @param {string}  inner    Markup to place inside the page area.
 * @param {boolean} frontEnd Render the published wrapper rather than the edit area.
 * @return {string} Markup.
 */
const pageMarkup = ( inner = '', frontEnd = false ) => `
	<header>
		<nav aria-label="Primary">
			<button id="theme-nav-button"></button>
		</nav>
	</header>
	<div class="elementor elementor-7${ frontEnd ? '' : ' elementor-edit-area elementor-edit-mode' }" data-elementor-type="wp-page" data-elementor-id="7">
		${ inner }
	</div>
	<footer>
		<button id="theme-footer-button"></button>
	</footer>
`;

describe( 'buildScanContext', () => {
	beforeEach( () => {
		document.body.innerHTML = '';
	} );

	test( 'excludes configured selectors and leaves a published page unscoped', () => {
		document.body.innerHTML = pageMarkup( '<button id="page-button"></button>', true );

		const context = buildScanContext( exclusionsArray );

		expect( context.include ).toBeUndefined();
		expect( context.exclude ).toEqual( exclusionsArray );
	} );

	test( 'scopes the scan to the Elementor edit area and excludes its editor UI', () => {
		document.body.innerHTML = pageMarkup( '<button id="page-button"></button>' );

		const context = buildScanContext( exclusionsArray );
		const editArea = document.querySelector( EDIT_AREA_SELECTOR );

		expect( editArea ).not.toBeNull();
		expect( context.include ).toEqual( [ editArea ] );
		expect( context.exclude ).toEqual( [ ...exclusionsArray, ...EDITOR_UI_EXCLUSIONS ] );
	} );

	test( 'only reports issues in the page itself while editing', async () => {
		document.body.innerHTML = pageMarkup( `
			<button id="page-button"></button>
			<div id="elementor-add-new-section">
				<button id="editor-ui-button"></button>
			</div>
		` );

		const results = await axe.run( buildScanContext( exclusionsArray ), {
			runOnly: [ 'button-name' ],
		} );
		const violationHTML = results.violations
			.flatMap( ( violation ) => violation.nodes )
			.map( ( node ) => node.html );

		expect( violationHTML.some( ( html ) => html.includes( 'id="page-button"' ) ) ).toBe( true );
		expect( violationHTML.some( ( html ) => html.includes( 'id="theme-nav-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="theme-footer-button"' ) ) ).toBe( false );
		expect( violationHTML.some( ( html ) => html.includes( 'id="editor-ui-button"' ) ) ).toBe( false );
	} );
} );
