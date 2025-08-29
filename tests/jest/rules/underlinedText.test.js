import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the custom rule
	const underlinedTextRuleModule = await import( '../../../src/pageScanner/rules/underlined-text.js' );
	const elementWithUnderlineCheckModule = await import( '../../../src/pageScanner/checks/element-with-underline.js' );
	const elementIsUTagCheckModule = await import( '../../../src/pageScanner/checks/element-is-u-tag.js' );
	const underlinedTextRule = underlinedTextRuleModule.default;
	const elementWithUnderlineCheck = elementWithUnderlineCheckModule.default;
	const elementIsUTagCheck = elementIsUTagCheckModule.default;

	// Configure axe with the custom rule
	axe.configure( {
		rules: [ underlinedTextRule ],
		checks: [ elementWithUnderlineCheck, elementIsUTagCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Underlined Text Validation', () => {
	const testCases = [
		// PASSING CASES - These should NOT trigger violations
		{
			name: 'should pass for normal text without underline',
			html: '<p>This is normal text</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for text inside a regular link',
			html: '<a href="https://example.com">This is a link</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for underlined text inside a link',
			html: '<a href="#" style="text-decoration: underline;">This is an underlined link</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for u tag inside a link',
			html: '<a href="#"><u>This is underlined text in a link</u></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for nested elements inside a link with underline',
			html: '<a href="#"><span style="text-decoration: underline;">Nested underlined text</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for element with role="link" and underlined text',
			html: '<div role="link" tabindex="0" style="text-decoration: underline;">Link-like div</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for u tag inside element with role="link"',
			html: '<div role="link" tabindex="0"><u>Underlined text in link role</u></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for text with other text decorations (not underline)',
			html: '<p style="text-decoration: line-through;">Strike-through text</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for text with overline decoration',
			html: '<span style="text-decoration: overline;">Overlined text</span>',
			shouldPass: true,
		},
		{
			name: 'should pass for bold text without underline',
			html: '<strong>Bold text</strong>',
			shouldPass: true,
		},
		{
			name: 'should pass for italic text without underline',
			html: '<em>Italic text</em>',
			shouldPass: true,
		},
		{
			name: 'should pass for text with bottom border (not text-decoration)',
			html: '<span style="border-bottom: 1px solid black;">Text with border</span>',
			shouldPass: true,
		},

		// FAILING CASES - These should trigger violations
		{
			name: 'should fail for div with underline text decoration',
			html: '<div style="text-decoration: underline;">This should fail</div>',
			shouldPass: false,
		},
		{
			name: 'should fail for span with underline text decoration',
			html: '<span style="text-decoration: underline;">Underlined span</span>',
			shouldPass: false,
		},
		{
			name: 'should fail for paragraph with underline text decoration',
			html: '<p style="text-decoration: underline;">Underlined paragraph</p>',
			shouldPass: false,
		},
		{
			name: 'should fail for u tag outside of links',
			html: '<u>This is underlined text</u>',
			shouldPass: false,
		},
		{
			name: 'should fail for u tag inside non-link element',
			html: '<div><u>Underlined text in div</u></div>',
			shouldPass: false,
		},
		{
			name: 'should fail for nested u tag in paragraph',
			html: '<p>Some text with <u>underlined part</u> here</p>',
			shouldPass: false,
		},
		{
			name: 'should fail for element with text-decoration-line: underline',
			html: '<span style="text-decoration-line: underline;">CSS3 underline</span>',
			shouldPass: false,
		},
		{
			name: 'should fail for element with multiple text decorations including underline',
			html: '<div style="text-decoration: underline overline;">Multiple decorations</div>',
			shouldPass: false,
		},
		{
			name: 'should fail for heading with underline',
			html: '<h2 style="text-decoration: underline;">Underlined Heading</h2>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with underlined text',
			html: '<button style="text-decoration: underline;">Underlined Button</button>',
			shouldPass: false,
		},
		{
			name: 'should fail for list item with underlined text',
			html: '<ul><li style="text-decoration: underline;">Underlined list item</li></ul>',
			shouldPass: false,
		},
		{
			name: 'should fail for table cell with underlined text',
			html: '<table><tr><td style="text-decoration: underline;">Underlined cell</td></tr></table>',
			shouldPass: false,
		},
		{
			name: 'should fail for deeply nested u tag outside of links',
			html: '<div><section><article><u>Deeply nested underlined text</u></article></section></div>',
			shouldPass: false,
		},
		{
			name: 'should fail for element with computed underline from CSS class',
			html: '<style>.underlined { text-decoration: underline; }</style><span class="underlined">CSS class underline</span>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		it( testCase.name, async () => {
			// Set up the DOM
			document.body.innerHTML = testCase.html;

			// Run axe with only the underlined_text rule
			const results = await axe.run( document, {
				runOnly: [ 'underlined_text' ],
			} );

			if ( testCase.shouldPass ) {
				// Should pass - no violations
				expect( results.violations.length ).toBe( 0 );
			} else {
				// Should fail - should have violations
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'underlined_text' );
			}
		} );
	} );

	// Additional edge case tests
	describe( 'Complex scenarios', () => {
		it( 'should handle mixed content with links and non-links', async () => {
			document.body.innerHTML = `
				<div>
					<p>Normal text here</p>
					<a href="#" style="text-decoration: underline;">This link is fine</a>
					<span style="text-decoration: underline;">This span should fail</span>
					<a href="#"><u>This u in link is fine</u></a>
					<u>This u outside link should fail</u>
				</div>
			`;

			const results = await axe.run( document, {
				runOnly: [ 'underlined_text' ],
			} );

			// Should find 1 violation with 2 nodes: the underlined span and the u tag outside link
			expect( results.violations.length ).toBe( 1 );
			expect( results.violations[ 0 ].nodes.length ).toBe( 2 );
		} );

		it( 'should not affect link elements themselves', async () => {
			document.body.innerHTML = `
				<div>
					<a href="#" style="text-decoration: underline;">Link with underline</a>
					<a href="#" role="link">Link with role</a>
				</div>
			`;

			const results = await axe.run( document, {
				runOnly: [ 'underlined_text' ],
			} );

			// Should pass - links can have underlines
			expect( results.violations.length ).toBe( 0 );
		} );

		it( 'should handle dynamically styled elements', async () => {
			document.body.innerHTML = '<div id="test">Test content</div>';

			// Add underline via JavaScript
			const element = document.getElementById( 'test' );
			element.style.textDecoration = 'underline';

			const results = await axe.run( document, {
				runOnly: [ 'underlined_text' ],
			} );

			// Should fail - dynamically added underline
			expect( results.violations.length ).toBeGreaterThan( 0 );
		} );
	} );
} );
