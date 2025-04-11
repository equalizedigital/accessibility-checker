import axe from 'axe-core';

// Use async/await for the test setup
beforeAll( async () => {
	// Dynamically import the modules
	const imgAltLongRuleModule = await import( '../../../src/pageScanner/rules/img-alt-long.js' );
	const imgAltLongCheckModule = await import( '../../../src/pageScanner/checks/img-alt-long-check.js' );

	const imgAltLongRule = imgAltLongRuleModule.default;
	const imgAltLongCheck = imgAltLongCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ imgAltLongRule ],
		checks: [ imgAltLongCheck ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Image Alt Long Validation', () => {
	const testCases = [
		// Failing cases - alt text too long (over 300 characters)
		{
			name: 'should fail for img with alt text exceeding 300 characters',
			html: `<img src="test.jpg" alt="${ 'x'.repeat( 301 ) }">`,
			shouldPass: false,
		},
		{
			name: 'should fail for img with alt text exactly 301 characters',
			html: `<img src="test.jpg" alt="${ 'a'.repeat( 301 ) }">`,
			shouldPass: false,
		},
		{
			name: 'should fail for img with very long alt text',
			html: '<img src="test.jpg" alt="This is an extremely long alternative text that goes into excessive detail about the image contents. It describes every single aspect of the image including colors, shapes, positions, backgrounds, foregrounds, implied meanings, possible interpretations, historical context, artistic influences, technical aspects of the photography or illustration technique used, the emotional response it might evoke in viewers, comparisons to similar images, and much more unnecessary information that makes this alt text far too verbose for practical use by screen readers or other assistive technologies. The alt text should be concise and focused on the essential information conveyed by the image.">',
			shouldPass: false,
		},

		// Passing cases
		{
			name: 'should pass for img with alt text exactly at max length (300)',
			html: `<img src="test.jpg" alt="${ 'b'.repeat( 300 ) }">`,
			shouldPass: true,
		},
		{
			name: 'should pass for img with short alt text',
			html: '<img src="test.jpg" alt="A simple description">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with empty alt text',
			html: '<img src="decorative.jpg" alt="">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with no alt attribute',
			html: '<img src="test.jpg">',
			shouldPass: true, // This check is specifically for long alt text, not missing alt
		},
		{
			name: 'should pass for img with null alt attribute',
			html: '<img src="test.jpg" alt>',
			shouldPass: true,
		},
		{
			name: 'should pass for img with moderate length alt text',
			html: '<img src="test.jpg" alt="This is a photograph showing a group of people at a conference. They are gathered around a table discussing a presentation displayed on a projector screen.">',
			shouldPass: true,
		},
		{
			name: 'should pass for decorative image with role presentation',
			html: '<img src="decorative.jpg" alt="" role="presentation">',
			shouldPass: true,
		},

		// Edge cases
		{
			name: 'should handle special characters in alt text correctly',
			html: '<img src="test.jpg" alt="Special characters: !@#$%^&*()_+-=[]{}|;:\',./<>?">',
			shouldPass: true,
		},
		{
			name: 'should handle HTML entities in alt text correctly',
			html: '<img src="test.jpg" alt="HTML entities: &lt;div&gt; &amp; &quot;quotes&quot;">',
			shouldPass: true,
		},
		{
			name: 'should handle Unicode characters in alt text correctly',
			html: '<img src="test.jpg" alt="Unicode: 你好世界 • ñ ç é ü">',
			shouldPass: true,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'img_alt_long' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
