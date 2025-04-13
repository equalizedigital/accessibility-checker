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

		// ✅ Passing cases
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
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

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
