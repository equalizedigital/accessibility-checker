import axe from 'axe-core';

// Use async/await for the test setup
beforeAll( async () => {
	// Dynamically import the modules
	const imgAltEmptyRuleModule = await import( '../../../src/pageScanner/rules/img-alt-empty.js' );
	const imgAltEmptyCheckModule = await import( '../../../src/pageScanner/checks/img-alt-empty-check.js' );

	const imgAltEmptyRule = imgAltEmptyRuleModule.default;
	const imgAltEmptyCheck = imgAltEmptyCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ imgAltEmptyRule ],
		checks: [ imgAltEmptyCheck ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Image Alt Empty Validation', () => {
	const testCases = [
		// Failing cases - should be flagged as violations
		{
			name: 'should fail for img with empty alt attribute',
			html: '<img src="test.jpg" alt="">',
			shouldPass: false,
		},
		{
			name: 'should fail for input[type=image] with empty alt attribute',
			html: '<input type="image" src="button.jpg" alt="">',
			shouldPass: false,
		},
		{
			name: 'should fail for img with empty alt attribute in a div',
			html: '<div><img src="test.jpg" alt=""></div>',
			shouldPass: false,
		},
		{
			name: 'should fail for multiple images with empty alt attributes',
			html: '<div><img src="test1.jpg" alt=""><img src="test2.jpg" alt=""></div>',
			shouldPass: false,
		},

		// Passing cases
		{
			name: 'should pass for img with non-empty alt attribute',
			html: '<img src="test.jpg" alt="Description of image">',
			shouldPass: true,
		},
		{
			name: 'should pass for input[type=image] with non-empty alt attribute',
			html: '<input type="image" src="button.jpg" alt="Submit button">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with role="presentation"',
			html: '<img src="decorative.jpg" alt="" role="presentation">',
			shouldPass: true,
		},
		{
			name: 'should pass for img with role="none"',
			html: '<img src="decorative.jpg" alt="" role="none">',
			shouldPass: true,
		},
		{
			name: 'should pass for img without alt attribute',
			html: '<img src="test.jpg">',
			shouldPass: true, // This check is specifically for empty alt attributes, not missing ones
		},
		{
			name: 'should pass for input[type=text] with empty alt',
			html: '<input type="text" alt="">',
			shouldPass: true, // Only input[type=image] should be checked
		},

		// Test cases for images with captions (simulating old php function edac_img_alt_ignore_inside_valid_caption)
		{
			name: 'should pass for img with empty alt inside figure with figcaption',
			html: '<figure><img src="test.jpg" alt=""><figcaption>Image caption</figcaption></figure>',
			shouldPass: true,
		},
		{
			name: 'should pass for img with empty alt inside WordPress caption',
			html: '<div class="wp-caption"><img src="test.jpg" alt=""><p class="wp-caption-text">Caption text</p></div>',
			shouldPass: true,
		},

		// Plugin-specific edge cases (simulating old php function edac_img_alt_ignore_plugin_issues)s
		{
			name: 'should pass for img with class containing "wp-smiley"',
			html: '<img src="smiley.jpg" alt="" class="wp-smiley">',
			shouldPass: true,
		},

		// Tracking pixel (1x1) edge cases
		{
			name: 'should pass for 1x1 tracking pixel with empty alt (gif)',
			html: '<img src="track.gif" alt="" width="1" height="1">',
			shouldPass: true,
		},
		{
			name: 'should pass for 1x1 tracking pixel with empty alt (arbitrary src)',
			html: '<img src="https://example.com/whatever.png" alt="" width="1" height="1">',
			shouldPass: true,
		},
		{
			name: 'should fail for 2x1 image with empty alt (not a tracking pixel)',
			html: '<img src="tiny.png" alt="" width="2" height="1">',
			shouldPass: false,
		},
		{
			name: 'should fail for img with empty alt and no dimension attributes (cannot confirm tracking pixel without dimensions)',
			html: '<img src="maybe-tracker.gif" alt="">',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'img_alt_empty' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
