import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/link-improper.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/link-has-valid-href-or-role.js' );

	axe.configure( {
		rules: [ ruleModule.default ],
		checks: [ checkModule.default ],
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

describe( 'Link Improper Rule', () => {
	test.each( [
		// ❌ Failing cases
		{
			name: 'Fails when anchor has no href and no role',
			html: '<a>Click me</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has href="#" and no role',
			html: '<a href="#">Link</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has javascript:void(0) href',
			html: '<a href="javascript:void(0)">Bad practice</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has empty href and no role',
			html: '<a href="">Empty href</a>',
			shouldPass: false,
		},
		{
			name: 'Fails when anchor has malformed URL',
			html: '<a href="http://example.com:invalid-port">Invalid URL</a>',
			shouldPass: false,
		},

		// ✅ Passing cases
		{
			name: 'Passes with valid absolute URL',
			html: '<a href="https://example.com/page?param=value">Valid URL</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with valid relative URL',
			html: '<a href="/path/to/page?query=string">Valid relative URL</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with valid href',
			html: '<a href="/about">About</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with href="#" and role="button"',
			html: '<a href="#" role="button">Click</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when hidden with display:none inline',
			html: '<a style="display:none;">Hidden link</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when hidden with visibility:hidden inline',
			html: '<a style="visibility:hidden;">Invisible link</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when hidden via class',
			html: '<a class="hidden">Class hidden</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when visually hidden via class',
			html: '<a class="invisible">Invisible class</a>',
			shouldPass: true,
		},
		{
			name: 'Passes with aria-hidden="true"',
			html: '<a aria-hidden="true">Aria hidden</a>',
			shouldPass: true,
		},
		{
			name: 'Passes when aria-hidden is dynamically added',
			html: '<a id="dynamic-aria">Dynamic aria</a>',
			shouldPass: true,
			setup: () => {
				const element = document.getElementById( 'dynamic-aria' );
				element.setAttribute( 'aria-hidden', 'true' );
			},
		},
	] )( '$name', async ( { html, shouldPass, setup } ) => {
		document.body.innerHTML = html;

		if ( setup ) {
			setup();
		}

		const results = await axe.run( document.body, {
			runOnly: [ 'link_improper' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
