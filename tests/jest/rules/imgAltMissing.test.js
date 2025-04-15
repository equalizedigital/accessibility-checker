import axe from 'axe-core';

// Use async/await for the test setup
beforeAll( async () => {
	// Dynamically import the modules
	const imgAltMissingRuleModule = await import( '../../../src/pageScanner/rules/img-alt-missing.js' );
	const imgAltMissingCheckModule = await import( '../../../src/pageScanner/checks/img-alt-missing-check.js' );

	const imgAltMissingRule = imgAltMissingRuleModule.default;
	const imgAltMissingCheck = imgAltMissingCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ imgAltMissingRule ],
		checks: [ imgAltMissingCheck ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Image Alt Missing Validation', () => {
	const testCases = [
		// Failing cases - missing alt attribute
		{
			name: 'should fail for img without alt attribute',
			html: '<img src="test.jpg">',
			shouldPass: false,
		},
		{
			name: 'should fail for input type="image" without alt attribute',
			html: '<input type="image" src="button.jpg">',
			shouldPass: false,
		},
		// Passing cases
		{
			name: 'should pass for img with alt attribute not set (empty alt checks for this)',
			html: '<img src="test.jpg" alt>',
			shouldPass: true,
		},
		{
			name: 'should pass for img with empty alt text (decorative)',
			html: '<img src="test.jpg" alt="">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with meaningful alt text',
			html: '<img src="test.jpg" alt="A golden retriever catching a frisbee">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with role="presentation"',
			html: '<img src="test.jpg" role="presentation">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with aria-hidden="true"',
			html: '<img src="test.jpg" aria-hidden="true">',
			shouldPass: true,
		},
		{
			name: 'should pass for input type="image" with alt text',
			html: '<input type="image" src="button.jpg" alt="Submit">',
			shouldPass: true,
		},

		// Edge cases - images inside captions or with context
		{
			name: 'should pass for img inside figure with figcaption',
			html: '<figure><img src="test.jpg"><figcaption>A descriptive caption</figcaption></figure>',
			shouldPass: true,
		},
		{
			name: 'should pass for img inside div with wp-caption class',
			html: '<div class="wp-caption"><img src="test.jpg"><p>A descriptive caption</p></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for img inside anchor with aria-label',
			html: '<a href="#" aria-label="Link description"><img src="test.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for img inside anchor with title',
			html: '<a href="#" title="Link description"><img src="test.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for img inside anchor with text',
			html: '<a href="#">Link description <img src="test.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for img inside a link with only whitespace as text',
			html: '<a href="#">  <img src="test.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for img inside a link with only whitespace as aria-label',
			html: '<a href="#" aria-label="  ">  <img src="test.jpg"></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for img inside a link with only whitespace as title',
			html: '<a href="#" title="  ">  <img src="test.jpg"></a>',
			shouldPass: true,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'img_alt_missing' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
