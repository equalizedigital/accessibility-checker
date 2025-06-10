import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/broken-anchor-link.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/anchor-exists.js' );

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

describe( 'Broken Anchor Link Rule', () => {
	test.each( [
		// ❌ Failing cases - links with no matching targets
		{
			name: 'fails when anchor link points to non-existent ID',
			html: '<a href="#nonexistent">Link to nowhere</a>',
			shouldPass: false,
		},
		{
			name: 'fails when anchor link points to non-existent name',
			html: '<a href="#missing">Link to missing anchor</a>',
			shouldPass: false,
		},

		// ✅ Passing cases - links with valid targets
		{
			name: 'passes when anchor link points to element with matching ID',
			html: '<a href="#section1">Go to section</a><div id="section1">Content</div>',
			shouldPass: true,
		},
		{
			name: 'passes when anchor link points to anchor with matching name attribute',
			html: '<a href="#footnote1">Footnote reference</a><a name="footnote1"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when anchor link points to existing heading with ID',
			html: '<a href="#heading">Go to heading</a><h2 id="heading">Main Heading</h2>',
			shouldPass: true,
		},
		{
			name: 'passes when multiple anchors point to same name target',
			html: '<a href="#note1">First ref</a><a href="#note1">Second ref</a><a name="note1"></a>',
			shouldPass: true,
		},
		{
			name: 'passes when anchor targets both ID and name (should find ID first)',
			html: '<a href="#target">Link</a><div id="target">ID target</div><a name="target"></a>',
			shouldPass: true,
		},
		{
			name: 'passes for complex footnote pattern',
			html: `
				<p>This is some text with a footnote reference <a href="#footnote1" aria-label="Footnote 1">[1]</a>.</p>
				<div>
					<a name="footnote1"></a>
					<p>This is the footnote text.</p>
				</div>
			`,
			shouldPass: true,
		},

		// Edge cases
		{
			name: 'ignores links with href="#" (empty fragment)',
			html: '<a href="#">Empty anchor</a>',
			shouldPass: true, // This should be ignored by the selector
		},
		{
			name: 'ignores links with role="button"',
			html: '<a href="#nowhere" role="button">Button link</a>',
			shouldPass: true, // This should be ignored by the selector
		},
		{
			name: 'handles special characters in anchor names',
			html: '<a href="#special-chars_123">Link</a><a name="special-chars_123"></a>',
			shouldPass: true,
		},
	] )( '$name', async ( { html, shouldPass } ) => {
		document.body.innerHTML = html;

		const results = await axe.run( document.body, {
			runOnly: [ 'broken_skip_anchor_link' ],
		} );

		if ( shouldPass ) {
			expect( results.violations.length ).toBe( 0 );
		} else {
			expect( results.violations.length ).toBeGreaterThan( 0 );
		}
	} );
} );
