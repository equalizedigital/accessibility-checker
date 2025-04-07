import axe from 'axe-core';

// Use async/await for the test setup
beforeAll( async () => {
	// Dynamically import the modules
	const ariaHiddenRuleModule = await import( '../../../src/pageScanner/rules/aria-hidden-validation.js' );
	const ariaHiddenCheckModule = await import( '../../../src/pageScanner/checks/aria-hidden-valid-usage.js' );

	const ariaHiddenRule = ariaHiddenRuleModule.default;
	const ariaHiddenCheck = ariaHiddenCheckModule.default;

	// Configure axe with the imported rules
	axe.configure( {
		rules: [ ariaHiddenRule ],
		checks: [ ariaHiddenCheck ],
	} );
} );

// Reset the document between tests
beforeEach( () => {
	document.body.innerHTML = '';
} );

describe( 'Aria Hidden Validation', () => {
	const testCases = [
		// Passing cases
		{
			name: 'should pass for button with aria-label',
			html: '<button type="button" aria-label="Open menu"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with screen reader text',
			html: '<button type="button"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg><span class="sr-only">Open menu</span></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with visible text',
			html: '<button type="button"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg>Menu</button>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with aria-label',
			html: '<a href="/about" aria-label="About Us"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with screen reader text',
			html: '<a href="/about"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg><span class="sr-only">About Us</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with visible text',
			html: '<a href="/about"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><rect x="4" y="7.5" width="16" height="1.5"></rect></svg>About Us</a>',
			shouldPass: true,
		},
		{
			name: 'should pass for image with presentation role',
			html: '<img src="image.jpg" alt="" aria-hidden="true" role="presentation">',
			shouldPass: true,
		},
		{
			name: 'should pass for wp-block-spacer class',
			html: '<div class="wp-block-spacer" aria-hidden="true"></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for element with role="presentation"',
			html: '<div role="presentation" aria-hidden="true">Content</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for element with multiple roles including presentation',
			html: '<div role="presentation img" aria-hidden="true">Content</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with aria-labelledby',
			html: '<span id="label-text">Menu</span><button aria-labelledby="label-text"><svg aria-hidden="true"></svg></button>',
			shouldPass: true,
		},
		{
			name: 'should pass with screen-reader-text class',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="screen-reader-text">About</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass with sr-only class',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="sr-only">About</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass with visually-hidden class',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="visually-hidden">About</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass with partial class name match',
			html: '<a href="/about"><svg aria-hidden="true"></svg><span class="my-sr-only-text">About</span></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for display:none',
			html: '<div style="display:none;" aria-hidden="true">Hidden content</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for visibility:hidden',
			html: '<div style="visibility:hidden;" aria-hidden="true"></div>',
			shouldPass: true,
		},
		{
			name: 'should pass for link with visible text and display:none svg',
			html: '<a href="/about">About Us<svg style="display:none;" aria-hidden="true"></svg></a>',
			shouldPass: true,
		},
		{
			name: 'should pass for button with aria-label and mixed hidden elements',
			html: '<button aria-label="Menu"><span style="display:none;" aria-hidden="true">Icon</span><span style="visibility:hidden;" aria-hidden="true">Text</span></button>',
			shouldPass: true,
		},
		{
			name: 'should pass for element with both display:none and visibility:hidden',
			html: '<div style="display:none; visibility:hidden;" aria-hidden="true">Super hidden</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for element with aria-hidden="false"',
			html: '<div aria-hidden="false">Visible content</div>',
			shouldPass: true,
		},
		{
			name: 'should pass for decorative image with aria-hidden',
			html: '<img src="decorative.jpg" alt="" role="presentation" aria-hidden="true">',
			shouldPass: true,
		},

		// Failing cases
		{
			name: 'should fail for link with only hidden SVG',
			html: '<a href="https://twitter.com/heyamberhinds" class="wp-block-social-link-anchor"><svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13.982 10.622 L20.54 3h-1.554"></path></svg></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for button with only hidden SVG',
			html: '<button type="button" class="components-button"><svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12.158,12.786L9.46,20.625c0.806"></path></svg></button>',
			shouldPass: false,
		},
		{
			name: 'should fail for div with important content',
			html: '<div aria-hidden="true">Important content</div>',
			shouldPass: false,
		},
		{
			name: 'should fail for link with empty aria-label',
			html: '<a href="/about" aria-label=""><svg aria-hidden="true"></svg></a>',
			shouldPass: false,
		},
		{
			name: 'should fail for nested aria-hidden elements',
			html: '<div aria-hidden="true"><button aria-hidden="true"><svg></svg></button></div>',
			shouldPass: false,
		},
		{
			name: 'should fail for aria-hidden on semantic elements',
			html: '<h2 aria-hidden="true">Important heading</h2>',
			shouldPass: false,
		},
		{
			name: 'should fail for aria-hidden on form controls',
			html: '<input type="text" aria-hidden="true">',
			shouldPass: false,
		},
		{
			name: 'should handle empty elements properly',
			html: '<div aria-hidden="true"></div>',
			shouldPass: false,
		},
		{
			name: 'should fail for element with whitespace text only',
			html: '<button><svg aria-hidden="true"></svg>   </button>',
			shouldPass: false,
		},
	];

	testCases.forEach( ( testCase ) => {
		test( testCase.name, async () => {
			// Clear any previous styles
			const existingStyles = document.head.querySelectorAll( 'style[data-test-styles]' );
			existingStyles.forEach( ( el ) => el.remove() );

			// Add CSS if provided
			if ( testCase.css ) {
				const styleEl = document.createElement( 'style' );
				styleEl.setAttribute( 'data-test-styles', 'true' );
				styleEl.textContent = testCase.css;
				document.head.appendChild( styleEl );
			}

			document.body.innerHTML = testCase.html;

			const results = await axe.run( document.body, {
				runOnly: [ 'aria_hidden_validation' ],
			} );

			if ( testCase.shouldPass ) {
				expect( results.violations.length ).toBe( 0 );
			} else {
				expect( results.violations.length ).toBeGreaterThan( 0 );
			}
		} );
	} );
} );
