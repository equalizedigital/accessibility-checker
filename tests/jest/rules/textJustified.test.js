import axe from 'axe-core';

const LONG_TEXT = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor.';
const SHORT_TEXT = 'Short text.';

beforeAll( async () => {
	const textJustifiedRuleModule = await import( '../../../src/pageScanner/rules/text-justified.js' );
	const textIsJustifiedCheckModule = await import( '../../../src/pageScanner/checks/text-is-justified.js' );

	axe.configure( {
		rules: [ textJustifiedRuleModule.default ],
		checks: [ textIsJustifiedCheckModule.default ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Text Justified Rule', () => {
	const testCases = [
		// Passing cases — not justified
		{
			name: 'should pass for long paragraph with left alignment',
			html: `<p style="text-align: left;">${ LONG_TEXT }</p>`,
			shouldPass: true,
		},
		{
			name: 'should pass for long paragraph with no alignment style',
			html: `<p>${ LONG_TEXT }</p>`,
			shouldPass: true,
		},
		{
			name: 'should pass for long paragraph with center alignment',
			html: `<p style="text-align: center;">${ LONG_TEXT }</p>`,
			shouldPass: true,
		},
		{
			name: 'should pass for long paragraph with right alignment',
			html: `<p style="text-align: right;">${ LONG_TEXT }</p>`,
			shouldPass: true,
		},
		{
			name: 'should pass for short justified text (under 200 character threshold)',
			html: `<p style="text-align: justify;">${ SHORT_TEXT }</p>`,
			shouldPass: true,
		},

		// Failing cases — long text with justify
		{
			name: 'should fail for long justified text in a heading',
			html: `<h2 style="text-align: justify;">${ LONG_TEXT }</h2>`,
			shouldPass: false,
		},
		{
			name: 'should fail for long paragraph with justified text',
			html: `<p style="text-align: justify;">${ LONG_TEXT }</p>`,
			shouldPass: false,
		},
		{
			name: 'should fail for long span with justified text',
			html: `<span style="text-align: justify;">${ LONG_TEXT }</span>`,
			shouldPass: false,
		},
		{
			name: 'should fail for long div with justified text',
			html: `<div style="text-align: justify;">${ LONG_TEXT }</div>`,
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'text_justified' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'text_justified' );
			}
		} );
	} );
} );
