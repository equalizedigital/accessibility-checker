import axe from 'axe-core';

beforeAll( async () => {
	const textSmallRuleModule = await import( '../../../src/pageScanner/rules/text-small.js' );
	const textSizeTooSmallCheckModule = await import( '../../../src/pageScanner/checks/text-size-too-small.js' );
	const textSmallRule = textSmallRuleModule.default;
	const textSizeTooSmallCheck = textSizeTooSmallCheckModule.default;

	axe.configure( {
		rules: [ textSmallRule ],
		checks: [ textSizeTooSmallCheck ],
	} );
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Text Small Validation', () => {
	const testCases = [
		// PASSING CASES
		{
			name: 'should pass for text above the size threshold',
			html: '<p style="font-size: 14px;">Normal sized text</p>',
			shouldPass: true,
		},
		{
			name: 'should pass for text exactly above the threshold',
			html: '<span style="font-size: 11px;">Slightly above threshold</span>',
			shouldPass: true,
		},
		{
			name: 'should pass for element with no text content',
			html: '<p style="font-size: 8px;"></p>',
			shouldPass: true,
		},
		{
			name: 'should pass for container element with no direct text children',
			html: '<div style="font-size: 8px;"><span>Child text</span></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for screen-reader-only text using classic clip pattern',
			html: '<span style="position: absolute; width: 1px; height: 1px; clip: rect(0px, 0px, 0px, 0px); overflow: hidden; font-size: 8px;">Screen reader only</span>',
			shouldPass: true,
		},
		{
			name: 'should pass for screen-reader-only text using clip-path pattern',
			html: '<span style="clip-path: inset(50%); font-size: 8px;">Screen reader only</span>',
			shouldPass: true,
		},
		{
			name: 'should pass for screen-reader-only text nested inside a visible element (Elementor social icon pattern)',
			html: '<a href="#" style="font-size: 8px;"><span style="position: absolute; clip: rect(0px, 0px, 0px, 0px);">Facebook</span><svg aria-hidden="true"></svg></a>',
			shouldPass: true,
		},
		// FAILING CASES
		{
			name: 'should fail for clip without absolute/fixed position (clip has no visual effect)',
			html: '<span style="position: static; clip: rect(0px, 0px, 0px, 0px); font-size: 8px;">Clip but not positioned</span>',
			shouldPass: false,
		},
		{
			name: 'should fail for text at the threshold boundary (10px)',
			html: '<span style="font-size: 10px;">Threshold text</span>',
			shouldPass: false,
		},
		{
			name: 'should fail for text below the threshold',
			html: '<p style="font-size: 8px;">Small text</p>',
			shouldPass: false,
		},
		{
			name: 'should fail for small text in a heading',
			html: '<h2 style="font-size: 9px;">Small heading</h2>',
			shouldPass: false,
		},
		{
			name: 'should fail for small text in a link',
			html: '<a href="#" style="font-size: 7px;">Tiny link</a>',
			shouldPass: false,
		},
		{
			name: 'should fail for small text in a list item',
			html: '<ul><li style="font-size: 8px;">Small list item</li></ul>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		it( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document, {
				runOnly: [ 'text_small' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'text_small' );
			}
		} );
	} );
} );
