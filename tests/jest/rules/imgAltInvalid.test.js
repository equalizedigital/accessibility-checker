import axe from 'axe-core';

// Use async/await for the test setup
beforeAll( async () => {
	// Dynamically import the modules
	const imgAltInvalidRuleModule = await import( '../../../src/pageScanner/rules/img-alt-invalid.js' );
	const imgAltInvalidCheckModule = await import( '../../../src/pageScanner/checks/img-alt-invalid-check.js' );

	const imgAltInvalidRule = imgAltInvalidRuleModule.default;
	const imgAltInvalidCheck = imgAltInvalidCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ imgAltInvalidRule ],
		checks: [ imgAltInvalidCheck ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Image Alt Invalid Validation', () => {
	const testCases = [
		// Failing cases - invalid alt text
		{
			name: 'should fail for alt text that starts with invalid keywords',
			html: '<img src="test.jpg" alt="image of a cat">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that starts with "bullet"',
			html: '<img src="test.jpg" alt="bullet point">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that starts with "graphic of"',
			html: '<img src="test.jpg" alt="graphic of a dog">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that ends with "image"',
			html: '<img src="test.jpg" alt="cat image">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that ends with "graphic"',
			html: '<img src="test.jpg" alt="dog graphic">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that contains file extensions',
			html: '<img src="test.jpg" alt="my file.jpg">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that exactly matches a keyword',
			html: '<img src="test.jpg" alt="image">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that contains underscores',
			html: '<img src="test.jpg" alt="my_image_file">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text that contains image-related terms',
			html: '<img src="test.jpg" alt="this has png in the middle">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text containing only numbers',
			html: '<img src="test.jpg" alt="12345">',
			shouldPass: false,
		},
		{
			name: 'should fail for alt text containing only whitespace',
			html: '<img src="test.jpg" alt="   ">',
			shouldPass: false,
		},

		// Passing cases
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
			name: 'should pass for img with alt text containing mixed numbers and text',
			html: '<img src="test.jpg" alt="3 cats playing with toys">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with no alt attribute (handled by different rule)',
			html: '<img src="test.jpg">',
			shouldPass: true, // This check is specifically for invalid alt text, not missing alt
		},

		// Edge cases
		{
			name: 'should handle punctuation in alt text correctly',
			html: '<img src="test.jpg" alt="Children\'s toys: balls, blocks, and dolls">',
			shouldPass: true,
		},
		{
			name: 'should handle non-English characters in alt text correctly',
			html: '<img src="test.jpg" alt="Les étoiles dans le ciel nocturne">',
			shouldPass: true,
		},
		{
			name: 'should treat numbers within context as valid',
			html: '<img src="test.jpg" alt="Room 101 entrance">',
			shouldPass: true,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'img_alt_invalid' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
