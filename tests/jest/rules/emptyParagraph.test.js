import axe from 'axe-core';

beforeAll( async () => {
	// Dynamically import the custom rule
	const emptyParagraphRuleModule = await import( '../../../src/pageScanner/rules/empty-paragraph.js' );
	const emptyParagraphCheckModule = await import( '../../../src/pageScanner/checks/paragraph-not-empty.js' );
	const emptyParagraphRule = emptyParagraphRuleModule.default;
	const emptyParagraphCheck = emptyParagraphCheckModule.default;

	// Configure axe with the custom rule
	axe.configure( {
		rules: [ emptyParagraphRule ],
		checks: [ emptyParagraphCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Empty Paragraph Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for paragraph with text content',
			html: '<p>Some text content</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for paragraph with aria-hidden="true"',
			html: '<p aria-hidden="true"></p>',
			shouldPass: true,
		},
		{
			name: 'should pass for paragraph with aria-live attribute (live region)',
			html: '<p aria-live="polite"></p>',
			shouldPass: true,
		},
		{
			name: 'should pass for paragraph with aria-live and role="status" (live region)',
			html: '<p role="status" aria-live="polite" aria-atomic="true"></p>',
			shouldPass: true,
		},
		{
			name: 'should pass for paragraph with aria-live="assertive"',
			html: '<p aria-live="assertive"></p>',
			shouldPass: true,
		},
		{
			name: 'should pass for paragraph with child elements',
			html: '<p><span>text</span></p>',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for completely empty paragraph',
			html: '<p></p>',
			shouldPass: false,
		},
		{
			name: 'should fail for paragraph with only whitespace',
			html: '<p>   </p>',
			shouldPass: false,
		},
		{
			name: 'should fail for paragraph with aria-live="off" (live region is disabled)',
			html: '<p aria-live="off"></p>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'empty_paragraph_tag' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
