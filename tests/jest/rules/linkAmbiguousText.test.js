import axe from 'axe-core';

beforeAll( async () => {
	const linkAmbiguousTextRuleModule = await import( '../../../src/pageScanner/rules/link-ambiguous-text.js' );
	const hasAmbiguousTextCheckModule = await import( '../../../src/pageScanner/checks/has-ambiguous-text.js' );

	axe.configure( {
		rules: [ linkAmbiguousTextRuleModule.default ],
		checks: [ hasAmbiguousTextCheckModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Link Ambiguous Text Rule', () => {
	const testCases = [
		// Passing cases — descriptive link text
		{
			name: 'should pass for descriptive link text',
			html: '<a href="/about">Learn about our team</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for "Read More About Accessibility"',
			html: '<a href="/accessibility">Read More About Accessibility</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for "Download Our Accessibility Guide"',
			html: '<a href="/guide.pdf">Download Our Accessibility Guide</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for descriptive aria-label on ambiguous link text',
			html: '<a href="/team" aria-label="Learn about our team">Read more</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for descriptive aria-labelledby',
			html: '<span id="link-label">Visit our accessibility resources</span><a href="/resources" aria-labelledby="link-label">here</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for an image link with descriptive alt text',
			html: '<a href="/home"><img src="logo.png" alt="Return to homepage" /></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for a link with no text content (empty link)',
			html: '<a href="/page"></a>',
			shouldPass: true,
		},

		// Failing cases — ambiguous link text
		{
			name: 'should fail for "click here"',
			html: '<a href="/page">click here</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "Click Here" (case-insensitive)',
			html: '<a href="/page">Click Here</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "here"',
			html: '<a href="/page">here</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "read more"',
			html: '<a href="/page">read more</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "learn more"',
			html: '<a href="/page">learn more</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "more"',
			html: '<a href="/page">more</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "download"',
			html: '<a href="/file.pdf">download</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "continue reading"',
			html: '<a href="/article">continue reading</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "details"',
			html: '<a href="/item">details</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for ambiguous aria-label',
			html: '<a href="/page" aria-label="here">Visit our page</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for ambiguous aria-labelledby',
			html: '<span id="lbl">click here</span><a href="/page" aria-labelledby="lbl">Visit page</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for image link with ambiguous alt text',
			html: '<a href="/page"><img src="icon.png" alt="here" /></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for "More..." (normalized to "more")',
			html: '<a href="/page">More...</a>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'link_ambiguous_text' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'link_ambiguous_text' );
			}
		} );
	} );
} );
