import axe from 'axe-core';
import { nonHtmlExtensions } from '../../../src/pageScanner/checks/link-points-to-html.js';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/link-non-html-file.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/link-points-to-html.js' );

	const rule = ruleModule.default;
	const check = checkModule.default;

	axe.configure( {
		rules: [ rule ],
		checks: [ check ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';

	const style = document.createElement( 'style' );
	style.innerHTML = `
		.hidden { display: none; }
		.invisible { visibility: hidden; }
	`;
	document.head.appendChild( style );
} );

describe( 'Non-HTML File Link Rule', () => {
	test.each( [
		// ❌ Failing cases
		...nonHtmlExtensions.map( ( ext ) => ( {
			name: `flags .${ ext } link`,
			html: `<a href="file.${ ext }">Download</a>`,
			shouldPass: false,
		} ) ),

		// ✅ Passing cases
		{
			name: 'passes standard HTML link',
			html: '<a href="/about">About Us</a>',
			shouldPass: true,
		},
		{
			name: 'passes link with fragment',
			html: '<a href="#section">Jump to section</a>',
			shouldPass: true,
		},
		{
			name: 'passes link with no href',
			html: '<a>Click me</a>',
			shouldPass: true,
		},

		// ✅ Hidden elements should be skipped
		{
			name: 'display:none (inline) should be skipped',
			html: '<a href="file.rtf" style="display:none;">Hidden</a>',
			shouldPass: true,
		},
		{
			name: 'visibility:hidden (inline) should be skipped',
			html: '<a href="file.wpd" style="visibility:hidden;">Invisible</a>',
			shouldPass: true,
		},
		{
			name: 'display:none via class should be skipped',
			html: '<a href="file.odt" class="hidden">Hidden by class</a>',
			shouldPass: true,
		},
		{
			name: 'visibility:hidden via class should be skipped',
			html: '<a href="file.odp" class="invisible">Invisible by class</a>',
			shouldPass: true,
		},
		{
			name: 'aria-hidden="true" should be skipped',
			html: '<a href="file.pages" aria-hidden="true">Hidden Aria</a>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'link_non_html_file' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
