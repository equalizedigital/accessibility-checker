import axe from 'axe-core';

// Simulate a Danish translation catalog so we can exercise the Unicode
// normalization logic the same way it behaves on a translated site,
// without needing real WordPress locale data loaded in the test env.
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => ( {
		'read more': 'Læs mere', // precomposed diacritic (NFC); æ has no NFD decomposition
		'click here': 'Klik her',
		here: 'Aquí', // í canonically decomposes to i + combining acute (U+0301) under NFD
	}[ text ] ?? text ),
} ) );

beforeAll( async () => {
	const linkAmbiguousTextRuleModule = await import( '../../../src/pageScanner/rules/link-ambiguous-text.js' );
	const hasAmbiguousTextCheckModule = await import( '../../../src/pageScanner/checks/has-ambiguous-text.js' );
	const linkAmbiguousTextRule = linkAmbiguousTextRuleModule.default;
	const hasAmbiguousTextCheck = hasAmbiguousTextCheckModule.default;

	axe.configure( {
		rules: [ linkAmbiguousTextRule ],
		checks: [ hasAmbiguousTextCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Ambiguous Link Text Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for descriptive link text',
			html: '<a href="https://example.com">Annual accessibility report 2026</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for descriptive Danish link text',
			html: '<a href="https://example.com">Se vores prisliste</a>',
			shouldPass: true,
		},

		// Failing cases - ASCII ambiguous phrases (control)
		{
			name: 'should fail for ambiguous English phrase "Download"',
			html: '<a href="https://example.com">Download</a>',
			shouldPass: false,
		},

		// Failing cases - translated phrases with precomposed (NFC) diacritics
		{
			name: 'should fail for translated Danish phrase with a precomposed diacritic ("Læs mere")',
			html: '<a href="https://example.com">Læs mere</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for translated Danish phrase without diacritics ("Klik her")',
			html: '<a href="https://example.com">Klik her</a>',
			shouldPass: false,
		},

		// Failing cases - translated phrase with a decomposed (NFD) combining mark
		{
			name: 'should fail for translated Spanish phrase with a precomposed diacritic ("Aquí")',
			html: '<a href="https://example.com">Aquí</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for translated Spanish phrase with a decomposed combining mark ("Aqui" + U+0301)',
			html: `<a href="https://example.com">${ 'Aquí'.normalize( 'NFD' ) }</a>`,
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
			}
		} );
	} );
} );
