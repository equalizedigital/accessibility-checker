import axe from 'axe-core';

beforeAll( async () => {
	const labelRuleModule = await import( '../../../src/pageScanner/rules/extended/label.js' );
	const imageInputHasAltCheckModule = await import( '../../../src/pageScanner/checks/image-input-has-alt.js' );

	axe.configure( {
		rules: [ labelRuleModule.default ],
		checks: [ imageInputHasAltCheckModule.default ],
	} );
} );

afterAll( () => {
	axe.reset();
} );

beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Label Rule (Extended)', () => {
	const testCases = [
		// Passing cases — properly labeled inputs
		{
			name: 'should pass for input with explicit label via for/id',
			html: '<label for="name">First Name</label><input type="text" id="name">',
			shouldPass: true,
		},
		{
			name: 'should pass for input wrapped in an implicit label',
			html: '<label>Email Address <input type="email"></label>',
			shouldPass: true,
		},
		{
			name: 'should pass for input with aria-label',
			html: '<input type="text" aria-label="Username">',
			shouldPass: true,
		},
		{
			name: 'should pass for input with aria-labelledby',
			html: '<span id="phone-label">Phone Number</span><input type="tel" aria-labelledby="phone-label">',
			shouldPass: true,
		},
		{
			name: 'should pass for input with a non-empty title attribute',
			html: '<input type="text" title="Search the site">',
			shouldPass: true,
		},
		{
			name: 'should pass for a textarea with an explicit label',
			html: '<label for="comments">Comments</label><textarea id="comments"></textarea>',
			shouldPass: true,
		},
		{
			name: 'should pass for an image input with a descriptive alt attribute',
			html: '<input type="image" alt="Submit the form">',
			shouldPass: true,
		},
		{
			name: 'should pass for a checkbox with an explicit label',
			html: '<label for="agree">I agree to the terms</label><input type="checkbox" id="agree">',
			shouldPass: true,
		},
		{
			name: 'should pass for a radio button with an explicit label',
			html: '<label for="opt1">Option 1</label><input type="radio" id="opt1" name="options">',
			shouldPass: true,
		},

		// Passing cases — excluded input types
		{
			name: 'should pass for hidden input (excluded by rule)',
			html: '<input type="hidden" name="token" value="abc">',
			shouldPass: true,
		},
		{
			name: 'should pass for submit button input (excluded by rule)',
			html: '<input type="submit" value="Submit Form">',
			shouldPass: true,
		},
		{
			name: 'should pass for reset button input (excluded by rule)',
			html: '<input type="reset" value="Reset">',
			shouldPass: true,
		},
		{
			name: 'should pass for button type input (excluded by rule)',
			html: '<input type="button" value="Click Me">',
			shouldPass: true,
		},

		// Failing cases — missing or inadequate labels
		{
			name: 'should fail for text input with no label',
			html: '<input type="text">',
			shouldPass: false,
		},
		{
			name: 'should fail for text input with id but no associated label',
			html: '<input type="text" id="email"><p>Email address</p>',
			shouldPass: false,
		},
		{
			name: 'should fail for textarea with no label',
			html: '<textarea rows="4"></textarea>',
			shouldPass: false,
		},
		{
			name: 'should fail for password input with no label',
			html: '<input type="password">',
			shouldPass: false,
		},
		{
			name: 'should fail for email input with no label',
			html: '<input type="email">',
			shouldPass: false,
		},
		{
			name: 'should fail for image input with empty alt attribute',
			html: '<input type="image" alt="">',
			shouldPass: false,
		},
		{
			name: 'should fail for image input with no alt attribute',
			html: '<input type="image">',
			shouldPass: false,
		},
		{
			name: 'should fail for image input with whitespace-only alt attribute',
			html: '<input type="image" alt="   ">',
			shouldPass: false,
		},
		{
			name: 'should fail for radio button with no label',
			html: '<input type="radio" name="choice">',
			shouldPass: false,
		},
		{
			name: 'should fail for checkbox with no label',
			html: '<input type="checkbox">',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'label' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
				expect( results.violations[ 0 ].id ).toBe( 'label' );
			}
		} );
	} );
} );
