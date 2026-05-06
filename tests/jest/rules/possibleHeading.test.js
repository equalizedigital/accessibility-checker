import axe from 'axe-core';

beforeAll( async () => {
	const ruleModule = await import( '../../../src/pageScanner/rules/possible-heading.js' );
	const checkModule = await import( '../../../src/pageScanner/checks/paragraph-styled-as-header.js' );

	axe.configure( {
		rules: [ ruleModule.default ],
		checks: [ checkModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Possible Heading Rule', () => {
	const testCases = [
		// ✅ Passing cases — should NOT trigger violations

		{
			name: 'passes for a normal paragraph with short plain text',
			html: '<p>Short text</p>',
			shouldPass: true,
		},
		{
			name: 'passes for a long paragraph (over 50 chars)',
			html: '<p>This is a long paragraph with plenty of text content that exceeds the fifty character limit.</p>',
			shouldPass: true,
		},
		{
			name: 'passes for a paragraph with small font size (under 16px)',
			html: '<p style="font-size: 12px; font-weight: bold;">Small bold text</p>',
			shouldPass: true,
		},
		{
			name: 'passes for a paragraph inside a blockquote',
			html: '<blockquote><p style="font-weight: bold;">Bold quote</p></blockquote>',
			shouldPass: true,
		},
		{
			name: 'passes for a paragraph inside a figcaption',
			html: '<figcaption><p style="font-weight: bold;">Bold caption</p></figcaption>',
			shouldPass: true,
		},
		{
			name: 'passes for a paragraph inside a table cell',
			html: '<table><tr><td><p style="font-weight: bold;">Bold cell text</p></td></tr></table>',
			shouldPass: true,
		},
		{
			name: 'passes for an actual h1 heading element',
			html: '<h1>Page Title</h1>',
			shouldPass: true,
		},
		{
			name: 'passes for an actual h2 heading element',
			html: '<h2>Section Title</h2>',
			shouldPass: true,
		},

		// ❌ Failing cases — should trigger violations

		{
			name: 'fails for a short bold paragraph',
			html: '<p style="font-weight: bold;">Bold heading text</p>',
			shouldPass: false,
		},
		{
			name: 'fails for a short italic paragraph',
			html: '<p style="font-style: italic;">Italic heading text</p>',
			shouldPass: false,
		},
		{
			name: 'fails for a short paragraph wrapped in a strong tag',
			html: '<p><strong>Strong heading text</strong></p>',
			shouldPass: false,
		},
		{
			name: 'fails for a short paragraph wrapped in an em tag',
			html: '<p><em>Italic heading text</em></p>',
			shouldPass: false,
		},
		{
			name: 'fails for a short paragraph with large font size',
			html: '<p style="font-size: 24px;">Large text heading</p>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'possible_heading' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'possible_heading' );
			}
		} );
	} );

	describe( 'ARIA heading elements should not be flagged as possible headings', () => {
		const ariaHeadingCases = [
			{
				name: 'passes for a paragraph with role="heading" and aria-level',
				html: '<p role="heading" aria-level="2">We value your privacy</p>',
				shouldPass: true,
			},
			{
				name: 'passes for a bold paragraph with role="heading" and aria-level',
				html: '<p role="heading" aria-level="2" style="font-weight: bold;">Bold ARIA heading</p>',
				shouldPass: true,
			},
			{
				name: 'passes for a large-font paragraph with role="heading" and aria-level',
				html: '<p role="heading" aria-level="2" style="font-size: 24px;">Large ARIA heading</p>',
				shouldPass: true,
			},
			{
				name: 'passes for a paragraph with only role="heading" (no aria-level)',
				html: '<p role="heading">Heading without level</p>',
				shouldPass: true,
			},
			{
				name: 'passes for a styled paragraph with role="heading" aria-level and inline styles',
				html: '<p class="cky-title" data-cky-tag="title" aria-level="2" role="heading" style="color: #212121; font-weight: bold;">We value your privacy</p>',
				shouldPass: true,
			},
		];

		ariaHeadingCases.forEach( ( testCase ) => {
			test( testCase.name, async () => {
				document.body.innerHTML = testCase.html;

				const results = await axe.run( document.body, {
					runOnly: [ 'possible_heading' ],
				} );

				if ( testCase.shouldPass ) {
					expect( results.violations.length ).toBe( 0 );
				} else {
					expect( results.violations.length ).toBeGreaterThan( 0 );
					expect( results.violations[ 0 ].id ).toBe( 'possible_heading' );
				}
			} );
		} );
	} );
} );
