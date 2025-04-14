import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/missing-headings.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/has-subheadings-if-long-content.js' );

	axe.configure( {
		rules: [ ruleModule.default ],
		checks: [ checkModule.default ],
	} );
} );

describe( 'Missing Headings Rule', () => {
	test.each( [
		// ✅ Passing cases
		{
			name: 'Passes with short content (under 400 words)',
			html: '<p>Short content.</p>',
			shouldPass: true,
		},
		{
			name: 'Passes with long content and h2 heading',
			html: `<div>
				<h2>Section Title</h2>
				${ 'Lorem ipsum '.repeat( 500 ) }
			</div>`,
			shouldPass: true,
		},
		{
			name: 'Passes with long content and ARIA heading',
			html: `<div>
				<div role="heading" aria-level="2">Section Title</div>
				${ 'Lorem ipsum '.repeat( 500 ) }
			</div>`,
			shouldPass: true,
		},

		// ❌ Failing cases
		{
			name: 'Fails with long content and no headings',
			html: `<div>${ 'Lorem ipsum '.repeat( 500 ) }</div>`,
			shouldPass: false,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'missing_headings' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
